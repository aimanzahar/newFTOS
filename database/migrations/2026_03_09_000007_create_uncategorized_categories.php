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
        // This migration creates "Uncategorized" default category for all food trucks
        // We'll do this via seeding instead since we need to iterate food trucks
        // For now, just document that this needs to be done via a command
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
