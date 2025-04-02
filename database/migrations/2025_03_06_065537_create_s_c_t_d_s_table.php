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
        Schema::create('s_c_t_d_s', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string("ttkv")->nullable();
            $table->string("huyen")->nullable();
            $table->string("ma_su_co")->nullable();
            // $table->dateTime("thoi_diem_bat_dau")->nullable();
            // $table->dateTime("thoi_diem_ket_thuc")->nullable();
            // $table->dateTime("thoi_gian_anh_huong_dich_vuh")->nullable();
            // $table->dateTime("thoi_gian_khac_phuc_loi")->nullable();
            $table->dateTime("ngay_ps")->nullable();
            $table->string("phan_loai")->nullable();
            $table->string("loai_nn_lop_1")->nullable();
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
        Schema::dropIfExists('s_c_t_d_s');
    }
};
