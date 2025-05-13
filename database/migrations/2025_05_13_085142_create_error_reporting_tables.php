<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateErrorReportingTables extends Migration
{
    public function up(): void
    {
        // Lý do lỗi
        Schema::create('error_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Báo lỗi sản phẩm
        Schema::create('product_error_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('reason_id')->constrained('error_reasons')->onDelete('restrict');
            $table->text('message')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'not_a_bug', 'fixed', 'rejected'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_error_reports');
        Schema::dropIfExists('error_reasons');
    }
}
