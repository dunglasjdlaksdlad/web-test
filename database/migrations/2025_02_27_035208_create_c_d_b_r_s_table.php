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
        Schema::create('c_d_b_r_s', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string("ma_su_co")->nullable();
            $table->string("dinh_danh_su_co")->nullable();
            $table->string("khu_vuc")->nullable();
            $table->string("quan")->nullable();
            $table->string("ma_tram")->nullable();
            $table->dateTime("thoi_gian_bat_dau")->nullable();
            $table->dateTime("thoi_gian_ket_thuc")->nullable();
            $table->string("tong_thoi_gian")->nullable();
            $table->dateTime("ngay_ps_sc")->nullable();
            $table->string("filter_data")->nullable();
            $table->string("nn_muc_1")->nullable();
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
        Schema::dropIfExists('c_d_b_r_s');
    }
};
