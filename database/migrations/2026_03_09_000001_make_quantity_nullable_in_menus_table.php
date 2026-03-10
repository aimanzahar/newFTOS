<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->integer('quantity')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('menus')->whereNull('quantity')->update(['quantity' => 0]);

        Schema::table('menus', function (Blueprint $table) {
            $table->integer('quantity')->default(0)->change();
        });
    }
};
