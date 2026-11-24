<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
            $table->decimal('balance', 12, 2)->default(0.00);
            $table->timestamps();
        });

        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('wallets')->onDelete('cascade');
            $table->string('type'); // deposit, escrow_lock, escrow_release, escrow_refund, payout
            $table->decimal('amount', 12, 2);
            $table->string('direction'); // credit, debit
            $table->string('source_type')->nullable(); // polymorphic (e.g. App\Models\Proposal, App\Models\PayoutRequest)
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('provider')->nullable(); // stripe, manual, etc.
            $table->json('provider_data')->nullable(); // encapsulated gateway attributes
            $table->string('status')->default('completed'); // pending, completed, failed
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index(['source_type', 'source_id']);
            $table->index(['wallet_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallets');
    }
};
