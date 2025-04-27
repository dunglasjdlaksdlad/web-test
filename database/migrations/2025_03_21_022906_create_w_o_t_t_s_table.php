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
        Schema::create('w_o_t_t_s', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string("ma_cong_viec")->nullable();
            $table->string("ma_tram")->nullable();
            $table->string("trang_thai")->nullable();
            $table->string("ttkv")->nullable();
            $table->string("quan")->nullable();
            $table->dateTime("thoi_diem_bat_dau")->nullable();
            $table->dateTime("thoi_diem_ket_thuc")->nullable();
            $table->dateTime("thoi_diem_cd_dong")->nullable();
            $table->string("nhan_vien_thuc_hien")->nullable();
            $table->string("danh_gia_wo_thuc_hien")->nullable();
            $table->string("time_status")->nullable();
            $table->string("phat")->nullable();
            $table->string("muc_do_uu_tien")->nullable();
            $table->string("packed")->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('thoi_diem_ket_thuc', 'wotts_thoi_diem_ket_thuc_index');
            $table->index('thoi_diem_cd_dong', 'wotts_thoi_diem_cd_dong_index');
            $table->index('ttkv', 'wotts_ttkv_index');
            $table->index('quan', 'wotts_quan_index');
            $table->index('ma_cong_viec', 'wotts_ma_cong_viec_index');

            // $table->foreign('ma_tram')->references('ma_tram')->on('q_l_t_s')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('w_o_t_t_s');
    }
};
