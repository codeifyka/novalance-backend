<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            if (!Schema::hasColumn('chats', 'chattable_type')) {
                $table->string('chattable_type')->nullable()->after('client_id');
            }
            if (!Schema::hasColumn('chats', 'chattable_id')) {
                $table->unsignedBigInteger('chattable_id')->nullable()->after('chattable_type');
            }
            if (!Schema::hasColumn('chats', 'tag')) {
                $table->string('tag')->nullable()->after('chattable_id');
            }
        });

        // Backfill existing rows
        DB::table('chats')->whereNotNull('job_post_id')->whereNull('chattable_id')->update([
            'chattable_type' => \App\Models\JobPost::class,
            'chattable_id' => DB::raw('job_post_id'),
            'tag' => 'job_post',
        ]);
        DB::table('chats')->whereNotNull('service_id')->whereNull('chattable_id')->update([
            'chattable_type' => \App\Models\Service::class,
            'chattable_id' => DB::raw('service_id'),
            'tag' => 'service',
        ]);

        // Merge duplicate chats if any exist before adding unique index
        $duplicates = DB::select("SELECT chattable_type, chattable_id, COUNT(*) as count FROM chats WHERE chattable_id IS NOT NULL GROUP BY chattable_type, chattable_id HAVING COUNT(*) > 1");
        foreach ($duplicates as $dup) {
            $chats = DB::table('chats')
                ->where('chattable_type', $dup->chattable_type)
                ->where('chattable_id', $dup->chattable_id)
                ->orderBy('id', 'asc')
                ->get();
            if ($chats->count() > 1) {
                $primaryId = $chats[0]->id;
                for ($i = 1; $i < $chats->count(); $i++) {
                    DB::table('messages')->where('chat_id', $chats[$i]->id)->update(['chat_id' => $primaryId]);
                    DB::table('chats')->where('id', $chats[$i]->id)->delete();
                }
            }
        }

        // Add partial unique index
        try {
            DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS unique_chats_chattable ON chats (chattable_type, chattable_id) WHERE chattable_id IS NOT NULL");
        } catch (\Throwable $e) {
            // Ignore if index already exists
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            DB::statement("DROP INDEX IF EXISTS unique_chats_chattable");
        } catch (\Throwable $e) {}

        Schema::table('chats', function (Blueprint $table) {
            if (Schema::hasColumn('chats', 'tag')) {
                $table->dropColumn('tag');
            }
            if (Schema::hasColumn('chats', 'chattable_id')) {
                $table->dropColumn('chattable_id');
            }
            if (Schema::hasColumn('chats', 'chattable_type')) {
                $table->dropColumn('chattable_type');
            }
        });
    }
};
