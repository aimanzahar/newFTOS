<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('menus')) return;
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('foodtruck_id');
            $table->string('name');
            $table->string('category');
            $table->decimal('base_price', 8, 2);
            $table->integer('quantity');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};
