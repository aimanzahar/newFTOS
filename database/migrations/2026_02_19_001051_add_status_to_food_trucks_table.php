<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * This adds the 'status' column to your food_trucks table.
     */
    public function up(): void
    {
        Schema::table('food_trucks', function (Blueprint $table) {
            // We set the default to 'pending' so every new registration 
            // starts in the approval queue automatically.
            $table->string('status')->default('pending')->after('id');
            
            // Adding an index helps the database search by status faster
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('food_trucks', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};