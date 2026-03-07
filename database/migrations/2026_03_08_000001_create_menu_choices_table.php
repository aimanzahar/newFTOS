<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_choices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('menu_option_groups')->onDelete('cascade');
            $table->string('name');
            $table->decimal('price', 8, 2)->default(0);
            $table->integer('quantity')->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_choices');
    }
};
