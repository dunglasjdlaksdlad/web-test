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
        Schema::create('districts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name1')->nullable();
            $table->string('name2')->nullable();
            $table->unsignedBigInteger('area_id');
            $table->string('area_name');
            $table->foreign('area_id')->references('id')->on('areas')->onDelete('cascade');
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['name', 'area_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('districts');
    }
};
