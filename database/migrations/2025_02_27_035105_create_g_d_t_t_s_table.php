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
            $table->string("ttkv")->nullable();
            $table->string("quan")->nullable();
            $table->string("loai_tu")->nullable();
            $table->string("ma_tu_btsnodeb")->nullable();
            $table->string("ma_nha_tram_chuan")->nullable();
            $table->string("ten_canh_bao")->nullable();
            $table->dateTime("thoi_gian_xuat_hien_canh_bao")->nullable();
            $table->dateTime("thoi_diem_ket_thuc")->nullable();
            $table->string("thoi_gian_ton")->nullable();
            $table->string("nhom_canh_bao")->nullable();
            $table->string("nguyen_nhan")->nullable();
            $table->string("tram_small_cell")->nullable();

            $table->string("tg_ngay")->nullable();
            $table->string("tg_dem")->nullable();
            $table->string("cellh_giam_tru")->nullable();
            $table->string("kh_vtnetctct")->nullable();

            $table->string("packed")->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('thoi_gian_ket_thuc', 'gdtts_thoi_gian_ket_thuc_index');
            $table->index('ttkv', 'gdtts_ttkv_index');
            $table->index('quan', 'gdtts_quan_index');
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
