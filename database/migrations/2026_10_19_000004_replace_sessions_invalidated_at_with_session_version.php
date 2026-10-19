<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Session revocation by version instead of time: a session is valid only while
 * the version it stored at login equals the user's. A timestamp let a session
 * signed in during the same second as a revocation survive it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedInteger('session_version')->default(0);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('sessions_invalidated_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('sessions_invalidated_at')->nullable();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('session_version');
        });
    }
};
