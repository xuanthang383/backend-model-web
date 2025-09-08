<?php

namespace App\Jobs;

use App\Models\AppConfig;
use App\Models\Category;
use App\Models\Color;
use App\Models\File;
use App\Models\Material;
use App\Models\Platform;
use App\Models\Product;
use App\Models\ProductCrawl;
use App\Models\ProductFiles;
use App\Models\Render;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Log;
use Throwable;

/**
 * Class SyncProductCrawlJob
 *
 * Mô tả:
 * Job này chịu trách nhiệm đồng bộ dữ liệu từ bảng `product_crawl` sang bảng `product`.
 * Các bản ghi chưa đồng bộ (is_sync = false) sẽ được lấy ra, chuyển đổi thành bản ghi Product,
 * sau đó đánh dấu đã đồng bộ để tránh xử lý lại.
 * Quá trình này được thực hiện theo batch (mỗi lần tối đa 100 bản ghi) để tránh quá tải hệ thống.
 *
 * Các bước để tạo cron job và chạy nó:
 * 1. Tạo job bằng lệnh: php artisan make:job SyncProductCrawlJob
 * 2. Viết logic đồng bộ trong file app/Jobs/SyncProductCrawlJob.php
 * 3. Đăng ký job trong app/Console/Kernel.php bằng: $schedule->job(new \App\Jobs\SyncProductCrawlJob)->hourly();
 * 4. Thiết lập cron job trên server bằng lệnh: crontab -e
 * 5. Thêm dòng: * * * * * php /path-to-your-project/artisan schedule:run >> /dev/null 2>&1
 *    (thay /path-to-your-project/ bằng đường dẫn thực tế)
 * 6. Kiểm tra log hoặc chạy thủ công: php artisan schedule:run
 *
 * Nếu dùng Windows, sử dụng Task Scheduler để chạy lệnh schedule:run mỗi phút.
 */
class SyncProductCrawlJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // tuỳ bạn chỉnh:
    public int $timeout = 120;               // tránh kẹt worker quá lâu
    public int $tries = 3;                 // retry khi tạm lỗi
    private const BATCH = 100;

    /**
     * Entry point.
     */
    public function handle(): void
    {
        Log::info('[SyncProductCrawl] Start');

        ProductCrawl::where('is_sync', 0)
            ->orderBy('id')
            ->chunkById(self::BATCH, function (EloquentCollection $records) {
                // Tải trước bảng tham chiếu → map[name_lower] = id
                $refMaps = $this->buildReferenceMaps();

                // cache AppConfig theo config_key trong batch để giảm query
                $appConfigCache = [];

                foreach ($records as $record) {
                    Log::info('[SyncProductCrawl] Record id=' . $record->id);

                    // bọc mỗi record trong transaction (atomic)
                    DB::beginTransaction();
                    try {
                        $errors = [];

                        // 1) Lấy AppConfig
                        $configKey = trim((string)$record->app_config);
                        if ($configKey === '') {
                            $errors[] = 'Thiếu app_config trên record.';
                            $this->saveNote($record, $errors);
                            DB::commit(); // ghi chú rồi bỏ qua
                            continue;
                        }

                        $appConfig = $appConfigCache[$configKey] ??= AppConfig::where('config_key', $configKey)->first();
                        if (!$appConfig) {
                            $errors[] = "Không tìm thấy AppConfig với config_key={$configKey}.";
                            $this->saveNote($record, $errors);
                            DB::commit();
                            continue;
                        }

                        $cfg = $this->decodeJson($appConfig->config_value);
                        if ($cfg === null) {
                            $errors[] = 'AppConfig.config_value không phải JSON hợp lệ.';
                            $this->saveNote($record, $errors);
                            DB::commit();
                            continue;
                        }

                        // 2) Ánh xạ Category/Platform/Render (1-1)
                        $categoryId = $this->mapSingle($cfg, 'category', (string)$record->category, $refMaps['categories'], $errors, 'Category');
                        $platformId = $this->mapSingle($cfg, 'platform', (string)$record->platform, $refMaps['platforms'], $errors, 'Platform');
                        // chú ý: cột của bạn là "renders" trên record → dùng đúng
                        $renderId = $this->mapSingle($cfg, 'render', (string)$record->renders, $refMaps['renders'], $errors, 'Render');

                        // 3) Ánh xạ Material/Color (1-n)
                        $materialIds = $this->mapMulti($cfg, 'material', $this->decodeJson($record->materials) ?: [], $refMaps['materials'], $errors, 'Material');
                        $colorIds = $this->mapMulti($cfg, 'color', $this->decodeJson($record->colors) ?: [], $refMaps['colors'], $errors, 'Color');

                        // 4) Tag: upsert theo tên thô, sau đó lấy id
                        $tagNames = $this->decodeJson($record->tags) ?: [];
                        $tagIds = $this->upsertAndResolveTags($tagNames);

                        if (!empty($errors)) {
                            $this->saveNote($record, $errors);
                            DB::commit();
                            continue;
                        }

                        // 5) Tạo Product
                        $product = $this->createProductFromCrawl(
                            $record,
                            $categoryId,
                            $platformId,
                            $renderId
                        );

                        // 6) Gắn quan hệ N-N
                        if (!empty($materialIds)) $product->materials()->syncWithoutDetaching($materialIds);
                        if (!empty($colorIds)) $product->colors()->syncWithoutDetaching($colorIds);
                        if (!empty($tagIds)) $product->tags()->syncWithoutDetaching($tagIds);

                        // 7) Ảnh / File
                        $this->attachImagesAndModel(
                            $product->id,
                            $this->decodeJson($record->images) ?: [],
                            $record->url
                        );

                        // 8) Đánh dấu sync
                        $record->is_sync = 1;
                        $record->note = null;
                        $record->save();

                        DB::commit();
                        Log::info("[SyncProductCrawl] Done record id={$record->id}");
                    } catch (Throwable $e) {
                        DB::rollBack();
                        Log::error("[SyncProductCrawl] Error id={$record->id}: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

                        // ghi chú lỗi vào record (không throw để tiếp tục các record khác)
                        try {
                            $record->note = 'Lỗi khi đồng bộ: ' . $e->getMessage();
                            $record->save();
                        } catch (Throwable $inner) {
                            Log::error("[SyncProductCrawl] Failed to save note id={$record->id}: " . $inner->getMessage());
                        }
                    }
                }
            });

        Log::info('[SyncProductCrawl] Finish');
    }

    /* =========================
     * Helpers
     * ========================= */

    private function buildReferenceMaps(): array
    {
        // Chuyển thành mốc tra cứu theo tên (lowercase + trim)
        $nameToId = fn($rows) => collect($rows)
            ->mapWithKeys(fn($r) => [$this->normalize((string)$r->name) => $r->id])
            ->all();

        return [
            'categories' => $nameToId(Category::select(['id', 'name'])->whereNotNull('parent_id')->get()),
            'platforms' => $nameToId(Platform::select(['id', 'name'])->get()),
            'renders' => $nameToId(Render::select(['id', 'name'])->get()),
            'materials' => $nameToId(Material::select(['id', 'name'])->get()),
            'colors' => $nameToId(Color::select(['id', 'name'])->get()),
        ];
    }

    private function decodeJson(?string $json): ?array
    {
        if ($json === null || $json === '') return null;
        try {
            $data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
            return is_array($data) ? $data : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function normalize(string $v): string
    {
        return trim(mb_strtolower($v));
    }

    /**
     * Tìm id mục tiêu dựa trên config.mapping: [{src: [...], des: "TargetName"}]
     */
    private function mapSingle(array $cfg, string $key, string $sourceValue, array $refMap, array &$errors, string $label): ?int
    {
        if ($sourceValue === '') {
            $errors[] = "[AppConfig - {$label}] dữ liệu nguồn rỗng.";
            return null;
        }
        $section = Arr::get($cfg, "{$key}.mapping");
        if (!is_array($section) || empty($section)) {
            $errors[] = "[AppConfig - {$label}] thiếu cấu hình mapping.";
            return null;
        }

        $sourceNorm = $this->normalize($sourceValue);
        foreach ($section as $rule) {
            $src = array_map([$this, 'normalize'], (array)($rule['src'] ?? []));
            $des = (string)($rule['des'] ?? '');
            if ($des === '' || empty($src)) continue;

            if (in_array($sourceNorm, $src, true)) {
                $id = $refMap[$this->normalize($des)] ?? null;
                if ($id) return $id;
            }
        }

        $errors[] = "[AppConfig - {$label}] không tìm thấy mapping phù hợp cho: {$sourceValue}";
        return null;
    }

    /**
     * Trả về danh sách id cho multi-value (material/color)
     */
    private function mapMulti(array $cfg, string $key, array $sources, array $refMap, array &$errors, string $label): array
    {
        $ids = [];
        if (empty($sources)) {
            $errors[] = "[AppConfig - {$label}] danh sách nguồn rỗng.";
            return $ids;
        }

        $section = Arr::get($cfg, "{$key}.mapping");
        if (!is_array($section) || empty($section)) {
            $errors[] = "[AppConfig - {$label}] thiếu cấu hình mapping.";
            return $ids;
        }

        // chuẩn hóa sources
        $sources = array_unique(array_filter(array_map([$this, 'normalize'], $sources)));
        if (empty($sources)) {
            $errors[] = "[AppConfig - {$label}] danh sách nguồn sau chuẩn hoá rỗng.";
            return $ids;
        }

        // build reverse index: value_norm → des_name_norm
        $valueToDes = [];
        foreach ($section as $rule) {
            $des = (string)($rule['des'] ?? '');
            $src = (array)($rule['src'] ?? []);
            if ($des === '' || empty($src)) continue;

            $desNorm = $this->normalize($des);
            foreach ($src as $s) {
                $valueToDes[$this->normalize((string)$s)] = $desNorm;
            }
        }

        foreach ($sources as $s) {
            $desNorm = $valueToDes[$s] ?? null;
            if ($desNorm && isset($refMap[$desNorm])) {
                $ids[] = $refMap[$desNorm];
            }
        }

        if (empty($ids)) {
            $errors[] = "[AppConfig - {$label}] không tìm thấy mapping phù hợp cho danh sách nguồn.";
        }

        return array_values(array_unique($ids));
    }

    private function upsertAndResolveTags(array $tagNames): array
    {
        $tagNames = array_values(array_unique(array_filter(array_map('trim', $tagNames))));
        if (empty($tagNames)) return [];

        $rows = array_map(fn($name) => ['name' => $name], $tagNames);
        Tag::upsert($rows, ['name'], ['name']);

        return Tag::whereIn('name', $tagNames)->pluck('id')->all();
    }

    private function createProductFromCrawl(ProductCrawl $record, ?int $categoryId, ?int $platformId, ?int $renderId): Product
    {
        // admin user
        $admin = User::where('email', config('app.admin_email'))->first();
        if (!$admin) {
            throw new \RuntimeException('Không tìm thấy admin theo app.admin_email.');
        }

        $product = new Product();
        $product->name = $record->title ?: $record->url;
        $product->description = $record->description;
        $product->status = Product::STATUS_APPROVED;
        $product->category_id = $categoryId;
        $product->platform_id = $platformId;
        $product->render_id = $renderId;
        $product->user_id = $admin->id;
        $product->public = true;
        $product->is_crawl = true;
        $product->save();

        return $product;
    }

    private function attachImagesAndModel(int $productId, array $images, ?string $modelUrl): void
    {
        // --- xử lý images ---
        if (!empty($images)) {
            // Thumbnail
            $thumbnail = array_shift($images);
            if ($thumbnail) {
                $file = File::firstOrCreate(
                    ['file_path' => $thumbnail],
                    ['file_name' => basename($thumbnail) ?: $thumbnail, 'is_model_link' => false]
                );

                ProductFiles::updateOrCreate(
                    ['product_id' => $productId, 'file_id' => $file->id],
                    ['is_thumbnail' => true, 'is_model' => false]
                );
            }

            // Các ảnh còn lại
            if (!empty($images)) {
                $now = now();
                $rows = [];
                foreach ($images as $img) {
                    if (!$img) continue;
                    $rows[] = [
                        'file_name' => basename($img) ?: $img,
                        'file_path' => $img,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'is_model_link' => false,
                    ];
                }
                if (!empty($rows)) {
                    File::upsert($rows, ['file_path'], ['file_name', 'updated_at', 'is_model_link']);
                }

                $files = File::whereIn('file_path', $images)->get(['id', 'file_path']);
                if ($files->isNotEmpty()) {
                    $pfRows = [];
                    foreach ($files as $f) {
                        $pfRows[] = [
                            'product_id'   => $productId,
                            'file_id'      => $f->id,
                            'is_thumbnail' => false,
                            'is_model'     => false,
                            'created_at'   => $now,
                            'updated_at'   => $now,
                        ];
                    }
                    ProductFiles::upsert(
                        $pfRows,
                        ['product_id', 'file_id'],
                        ['is_thumbnail', 'is_model', 'updated_at']
                    );
                }
            }
        }

        // --- xử lý model link từ url ---
        if ($modelUrl) {
            $file = File::updateOrCreate(
                ['file_path' => $modelUrl],
                ['file_name' => basename($modelUrl) ?: $modelUrl, 'is_model_link' => true]
            );

            ProductFiles::updateOrCreate(
                ['product_id' => $productId, 'file_id' => $file->id],
                ['is_thumbnail' => false, 'is_model' => true]
            );
        }
    }

    private function saveNote(ProductCrawl $record, array $errors): void
    {
        $record->note = implode(' | ', array_values(array_unique($errors)));
        $record->save();
    }
}
