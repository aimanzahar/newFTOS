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
        Schema::create('food_trucks', function (Blueprint $table) {
            $table->id(); 
            $table->string('foodtruck_name'); 
            $table->string('business_license_no');
            $table->text('foodtruck_desc')->nullable();
            
            // This requires the 'users' table to exist first!
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('food_trucks');
    }
};