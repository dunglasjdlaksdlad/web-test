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
        Schema::create('p_a_k_h_s', function (Blueprint $table) {
            // $table->id();
            // $table->string('uuid')->unique();
            // $table->string("ma_cong_viec")->nullable();
            // $table->string("ttkv")->nullable();
            // $table->string("quan")->nullable();
            // $table->dateTime("thoi_diem_ket_thuc")->nullable();
            // $table->string("wo_qua_han")->nullable();
            // $table->string("loai_wo")->nullable();
            // $table->string("packed")->nullable();
            // $table->softDeletes();
            // $table->timestamps();
                    $table->id();
            $table->string('uuid')->unique();
            $table->string("ma_cong_viec")->nullable();
            $table->string("ma_tram")->nullable();
            $table->string("trang_thai")->nullable();
                  $table->string("he_thong")->nullable();
                         $table->string("nhom_dieu_phoi")->nullable();
            $table->dateTime("thoi_diem_bat_dau")->nullable();
            $table->dateTime("thoi_diem_ket_thuc")->nullable();
            $table->dateTime("thoi_diem_cd_dong")->nullable();
            $table->string("nhan_vien_thuc_hien")->nullable();
            $table->string("danh_gia_wo_thuc_hien")->nullable();
            $table->string("muc_do_uu_tien")->nullable();
            $table->string("packed")->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('p_a_k_h_s');
    }
};
