<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE proposals DROP CONSTRAINT IF EXISTS proposals_status_check;");
        DB::statement("ALTER TABLE proposals ADD CONSTRAINT proposals_status_check CHECK (status IN ('pending', 'active', 'done', 'disputed', 'failed'));");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE proposals DROP CONSTRAINT IF EXISTS proposals_status_check;");
        DB::statement("ALTER TABLE proposals ADD CONSTRAINT proposals_status_check CHECK (status IN ('pending', 'active', 'done'));");
    }
};
