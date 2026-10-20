<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demo_items', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('nik')->nullable();
            $table->string('no_hp')->nullable();
            $table->string('secret')->nullable();
            $table->string('pin')->nullable();
            $table->string('status')->nullable()->index();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_items');
    }
};
