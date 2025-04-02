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
        Schema::create('q_l_t_s', function (Blueprint $table) {
            $table->id();
            $table->string('ma_tram')->unique();
            $table->string("ttkv")->nullable();
            $table->string("quan")->nullable();
            $table->string("ma_nhan_vien_thuc_te_ql")->nullable();
            $table->string("user_vt")->nullable();
            $table->string("sdt")->nullable();
            $table->string("ten_nhan_vien_thuc_te_ql")->nullable();
            $table->string("tham_nien_quan_ly_tram")->nullable();
            $table->string("loai_tram1")->nullable();
            $table->string("loai_tram")->nullable();
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
        Schema::dropIfExists('q_l_t_s');
    }
};
