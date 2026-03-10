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
            $table->decimal('base_price', 8, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('menus')->whereNull('base_price')->update(['base_price' => 0]);

        Schema::table('menus', function (Blueprint $table) {
            $table->decimal('base_price', 8, 2)->default(0)->change();
        });
    }
};
