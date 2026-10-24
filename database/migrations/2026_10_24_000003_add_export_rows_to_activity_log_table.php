<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The row count of an `exported` row as a virtual column, in a covering
 * (created_on, event, export_rows) index: the audit summary's per-day and
 * per-event counts and its "Baris Diunduh" sum come from one index range
 * instead of every row's JSON. Nothing is written: rows stay append-only.
 * `SIGNED INTEGER` reads on MySQL and SQLite.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_log', function (Blueprint $table): void {
            $table->bigInteger('export_rows')->nullable()
                ->virtualAs("CASE WHEN event = 'exported' THEN CAST(json_extract(properties, '$.meta.rows') AS SIGNED INTEGER) END");
            $table->index(['created_on', 'event', 'export_rows']);
        });
    }

    public function down(): void
    {
        Schema::table('activity_log', function (Blueprint $table): void {
            $table->dropIndex(['created_on', 'event', 'export_rows']);
            $table->dropColumn('export_rows');
        });
    }
};
