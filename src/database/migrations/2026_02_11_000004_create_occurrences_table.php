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
        Schema::create('occurrences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('external_id', 100)->unique();
            $table->string('type_code', 50);
            $table->string('status_code', 50);
            $table->text('description');
            $table->timestamp('reported_at');
            $table->timestamps();

            // Foreign Keys
            $table->foreign('type_code')
                ->references('code')
                ->on('occurrence_types')
                ->onDelete('restrict');

            $table->foreign('status_code')
                ->references('code')
                ->on('occurrence_status')
                ->onDelete('restrict');

            // Indexes
            $table->index('status_code');
            $table->index('type_code');
            $table->index('reported_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('occurrences');
    }
};

