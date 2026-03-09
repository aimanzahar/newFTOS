<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE menus MODIFY base_price DECIMAL(8,2) NULL');
    }

    public function down(): void
    {
        DB::statement('UPDATE menus SET base_price = 0 WHERE base_price IS NULL');
        DB::statement('ALTER TABLE menus MODIFY base_price DECIMAL(8,2) NOT NULL');
    }
};
