<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outbox', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('aggregate_type', 100);
            $table->uuid('aggregate_id');
            $table->string('event_type', 100);
            $table->string('status', 20)->default('PENDING');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('sent_at')->nullable();

            $table->index(['status', 'created_at'], 'outbox_status_created_at_index');
            $table->unique('aggregate_id', 'outbox_aggregate_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbox');
    }
};


