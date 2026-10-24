<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Named attachment slots: `collection` is the slot key a file belongs to
 * (HasAttachments::attachmentSlots()). Files from before land in the default
 * slot, `lampiran`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attachments', function (Blueprint $table): void {
            $table->string('collection', 64)->default('lampiran');
            $table->index(['attachable_type', 'attachable_id', 'collection']);
        });
    }

    public function down(): void
    {
        Schema::table('attachments', function (Blueprint $table): void {
            $table->dropIndex(['attachable_type', 'attachable_id', 'collection']);
            $table->dropColumn('collection');
        });
    }
};
