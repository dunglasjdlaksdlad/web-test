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
        Schema::create('1_bo_n_n_g_d_t_t_s', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string("dau_vao")->nullable();
            $table->string("muc_1")->nullable();
            $table->string("giam_tru_muc_kv")->nullable();
            $table->string("giam_tru_muc_tinh")->nullable();
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
        Schema::dropIfExists('1_bo_n_n_g_d_t_t_s');
    }
};
