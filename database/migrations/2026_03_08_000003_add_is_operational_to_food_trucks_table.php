<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('food_trucks', function (Blueprint $table) {
            $table->boolean('is_operational')->default(true)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('food_trucks', function (Blueprint $table) {
            $table->dropColumn('is_operational');
        });
    }
};
