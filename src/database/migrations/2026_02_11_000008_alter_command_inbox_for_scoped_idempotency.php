<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('command_inbox', function (Blueprint $table) {
            if (!Schema::hasColumn('command_inbox', 'scope_key')) {
                $table->string('scope_key', 191)->default('global')->after('type');
            }
            if (!Schema::hasColumn('command_inbox', 'payload_hash')) {
                $table->string('payload_hash', 64)->after('scope_key');
            }
            if (!Schema::hasColumn('command_inbox', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('processed_at');
            }
        });

        Schema::table('command_inbox', function (Blueprint $table) {
            $table->dropUnique(['idempotency_key']);
            $table->unique(['idempotency_key', 'type', 'scope_key'], 'command_inbox_idem_type_scope_unique');
            $table->index('expires_at', 'command_inbox_expires_at_index');
            $table->index(['type', 'scope_key'], 'command_inbox_type_scope_key_index');
        });
    }

    public function down(): void
    {
        Schema::table('command_inbox', function (Blueprint $table) {
            $table->dropUnique('command_inbox_idem_type_scope_unique');
            $table->dropIndex('command_inbox_expires_at_index');
            $table->dropIndex('command_inbox_type_scope_key_index');
            $table->unique('idempotency_key');
            $table->dropColumn(['scope_key', 'payload_hash', 'expires_at']);
        });
    }
};

