<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add delivery_days to services table
        Schema::table('services', function (Blueprint $table) {
            if (!Schema::hasColumn('services', 'delivery_days')) {
                $table->integer('delivery_days')->default(3)->nullable()->after('price_id');
            }
        });

        // 2. Add timestamps & tracking to purchases table
        Schema::table('purchases', function (Blueprint $table) {
            if (!Schema::hasColumn('purchases', 'started_at')) {
                $table->timestamp('started_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('purchases', 'ends_at')) {
                $table->timestamp('ends_at')->nullable()->after('started_at');
            }
            if (!Schema::hasColumn('purchases', 'delivered_at')) {
                $table->timestamp('delivered_at')->nullable()->after('ends_at');
            }
            if (!Schema::hasColumn('purchases', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('delivered_at');
            }
        });

        // 3. Update disputes table to support purchase_id and nullable proposal_id
        Schema::table('disputes', function (Blueprint $table) {
            if (!Schema::hasColumn('disputes', 'purchase_id')) {
                $table->foreignId('purchase_id')->nullable()->unique()->constrained('purchases')->onDelete('cascade')->after('proposal_id');
            }
        });

        // Make proposal_id nullable
        try {
            DB::statement('ALTER TABLE disputes ALTER COLUMN proposal_id DROP NOT NULL');
        } catch (\Throwable $e) {
            // Ignore if already nullable or sqlite
        }
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (Schema::hasColumn('services', 'delivery_days')) {
                $table->dropColumn('delivery_days');
            }
        });

        Schema::table('purchases', function (Blueprint $table) {
            $cols = ['started_at', 'ends_at', 'delivered_at', 'completed_at'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('purchases', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('disputes', function (Blueprint $table) {
            if (Schema::hasColumn('disputes', 'purchase_id')) {
                $table->dropForeign(['purchase_id']);
                $table->dropColumn('purchase_id');
            }
        });
    }
};
