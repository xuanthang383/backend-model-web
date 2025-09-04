<?php

namespace App\Jobs;

use App\Models\ProductCrawl;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
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

    /**
     * Execute the job.
     *
     * @return void
     * @throws Throwable
     */
    public function handle(): void
    {
        // Lấy các bản ghi chưa đồng bộ
        $records = ProductCrawl::where('is_sync', false)->limit(100)->get();

        foreach ($records as $record) {
            DB::beginTransaction();
            try {
//                // TODO: Map dữ liệu từ ProductCrawl sang Product
//                // Ví dụ đơn giản, cần chỉnh sửa cho phù hợp với logic thực tế
//                $product = new Product();
//                $product->name = $record->title ?? $record->url;
//                $product->description = $record->description;
//                // ... map các trường khác ...
//                $product->status = Product::STATUS_DRAFT;
//                $product->public = true;
//                $product->user_id = 1; // Hoặc lấy user phù hợp
//                $product->category_id = 1; // Map từ $record->category nếu có
//                $product->save();

                // Đánh dấu đã đồng bộ
                $record->is_sync = true;
                $record->save();

                DB::commit();
            } catch (Throwable $e) {
                DB::rollBack();
                // Có thể log lỗi hoặc cập nhật trường lỗi nếu cần
            }
        }
    }
}
