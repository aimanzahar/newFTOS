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
        Schema::table('users', function (Blueprint $table) {
            // Adding the role column. 
            // Default is 1 (Customer) to prevent errors with existing users.
            // Using tinyInteger for better performance with small numbers.
            $table->tinyInteger('role')->default(1)->after('email')
                  ->comment('1:Customer, 2:FT Admin, 3:FT Worker, 6:Sys Admin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};