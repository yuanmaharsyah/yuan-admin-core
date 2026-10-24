<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The day of each row as a virtual column with a covering (created_on, event)
 * index: the audit summary counts per event and day over a 366-day range
 * from the index alone, in index order (spec §7, AuditSummary). Nothing is
 * written: rows stay append-only.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_log', function (Blueprint $table): void {
            $table->date('created_on')->nullable()->virtualAs('DATE(created_at)');
            $table->index(['created_on', 'event']);
        });
    }

    public function down(): void
    {
        Schema::table('activity_log', function (Blueprint $table): void {
            $table->dropIndex(['created_on', 'event']);
            $table->dropColumn('created_on');
        });
    }
};
