<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE menus MODIFY quantity INT NULL');
    }

    public function down(): void
    {
        DB::statement('UPDATE menus SET quantity = 0 WHERE quantity IS NULL');
        DB::statement('ALTER TABLE menus MODIFY quantity INT NOT NULL');
    }
};
