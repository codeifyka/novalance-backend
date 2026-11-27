<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('notify_proposals')->default(true);
            $table->boolean('notify_purchases')->default(true);
            $table->string('availability_status')->default('available');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['notify_proposals', 'notify_purchases', 'availability_status']);
        });
    }
};
