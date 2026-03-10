<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending','accepted','preparing','prepared','ready_for_pickup','delivery','done','rejected') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("UPDATE orders SET status = 'pending' WHERE status = 'rejected'");
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending','accepted','preparing','prepared','ready_for_pickup','delivery','done') NOT NULL DEFAULT 'pending'");
    }
};
