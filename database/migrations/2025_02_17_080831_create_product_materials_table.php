<?php
//
//use Illuminate\Database\Migrations\Migration;
//use Illuminate\Database\Schema\Blueprint;
//use Illuminate\Support\Facades\Schema;
//
//return new class extends Migration {
//    public function up()
//    {
//        Schema::create('product_materials', function (Blueprint $table) {
//            $table->id();
//            $table->unsignedBigInteger('product_id'); // Sửa thành unsignedBigInteger
//            $table->unsignedBigInteger('material_id'); // Sửa thành unsignedBigInteger
//            $table->timestamps();
//
//            // Khai báo foreign keys
//            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
//            $table->foreign('material_id')->references('id')->on('materials')->onDelete('cascade');
//        });
//    }
//
//    public function down()
//    {
//        Schema::dropIfExists('product_materials');
//    }
//};
//
