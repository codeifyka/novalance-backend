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
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE chats ALTER COLUMN job_post_id DROP NOT NULL;');

        Schema::table('chats', function (Blueprint $table) {
            if (!Schema::hasColumn('chats', 'service_id')) {
                $table->unsignedBigInteger('service_id')->nullable()->after('job_post_id');
                $table->foreign('service_id')->references('id')->on('services')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            if (Schema::hasColumn('chats', 'service_id')) {
                $table->dropForeign(['service_id']);
                $table->dropColumn('service_id');
            }
        });

        \Illuminate\Support\Facades\DB::statement('ALTER TABLE chats ALTER COLUMN job_post_id SET NOT NULL;');
    }
};
