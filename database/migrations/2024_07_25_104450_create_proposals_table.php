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
        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            $table->text('cover_letter');
            $table->unsignedBigInteger('freelancer_id');
            $table->unsignedBigInteger('job_post_id');
            $table->enum('status', ['pending', 'active', 'done'])->default('pending');
            $table->timestamps();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            
            // Add foreign key constraints
            $table->foreign('freelancer_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('job_post_id')->references('id')->on('job_posts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->id();
            $table->text('cover_letter');
            $table->unsignedBigInteger('freelancer_id');
            $table->unsignedBigInteger('job_post_id');
            $table->enum('status', ['pending', 'active', 'done'])->default('pending');
            $table->timestamps();

            // Add foreign key constraints
            $table->foreign('freelancer_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('job_post_id')->references('id')->on('job_posts')->onDelete('cascade');
        });
    }
};
