<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->string('type')->comment('promotion|birthday|custom');
            $table->string('sender_id')->default('SMSlenzDEMO');
            $table->json('recipients');          // array of phone numbers
            $table->text('message');
            $table->integer('total_count')->default(0);
            $table->integer('success_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->json('api_response')->nullable();
            $table->string('status')->default('sent'); // sent|failed|partial
            $table->string('campaign_name')->nullable();
            $table->foreignId('sent_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index('type');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};
