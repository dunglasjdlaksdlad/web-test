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
        Schema::create('1_tram_uu_tien_g_d_t_t_s', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string("ma_nha_tram_chuan")->nullable();
            $table->string("ma_bts")->nullable();
            $table->string("cau_hinh")->nullable();
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
        Schema::dropIfExists('1_tram_uu_tien_g_d_t_t_s');
    }
};
