<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('foodtruck_id')->constrained('food_trucks')->onDelete('cascade');
            $table->string('name');
            $table->string('color')->default('purple'); // CSS color class name or hex value
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->unique(['foodtruck_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_categories');
    }
};
