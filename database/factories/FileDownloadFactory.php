<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\FileDownload;
use Illuminate\Database\Eloquent\Factories\Factory;

class FileDownloadFactory extends Factory
{
    protected $model = FileDownload::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),

            'parent_id' => Category::factory(),
        ];
    }
}
