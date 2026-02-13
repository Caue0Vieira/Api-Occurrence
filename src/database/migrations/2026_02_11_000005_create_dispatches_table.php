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
        Schema::create('dispatches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('occurrence_id');
            $table->string('resource_code', 50);
            $table->string('status_code', 50);
            $table->timestamps();

            // Foreign Keys
            $table->foreign('occurrence_id')
                ->references('id')
                ->on('occurrences')
                ->onDelete('cascade');

            $table->foreign('status_code')
                ->references('code')
                ->on('dispatch_status')
                ->onDelete('restrict');

            // Indexes
            $table->index('occurrence_id');
            $table->index('status_code');
            $table->index('resource_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dispatches');
    }
};

