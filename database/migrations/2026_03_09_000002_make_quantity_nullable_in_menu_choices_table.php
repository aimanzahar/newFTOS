<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_choices', function (Blueprint $table) {
            if (DB::getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE menu_choices MODIFY quantity INT NULL');
            } else {
                $table->integer('quantity')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('menu_choices', function (Blueprint $table) {
            if (DB::getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE menu_choices MODIFY quantity INT NOT NULL DEFAULT 0');
            } else {
                $table->integer('quantity')->nullable(false)->default(0)->change();
            }
        });
    }
};
