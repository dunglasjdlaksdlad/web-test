<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('g_d_t_t_s', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string("khu_vuc")->nullable();
            $table->string("quanhuyen")->nullable();
            $table->string("loai_tu")->nullable();
            $table->string("ma_nha_tram_chuan")->nullable();
            $table->string("ten_canh_bao")->nullable();
            $table->dateTime("thoi_gian_xuat_hien_canh_bao")->nullable();
            $table->dateTime("thoi_gian_ket_thuc")->nullable();
            $table->string("thoi_gian_ton")->nullable();
            $table->string("cellh_sau_giam_tru")->nullable();
            $table->string("nn_muc_1")->nullable();
            $table->string("filter_data")->nullable();
            $table->string("packed")->nullable();
            $table->string("status")->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('g_d_t_t_s');
    }
};
