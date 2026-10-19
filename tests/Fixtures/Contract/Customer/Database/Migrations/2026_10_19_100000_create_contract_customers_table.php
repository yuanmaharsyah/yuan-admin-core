<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_customers', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->index();
            $table->string('alamat')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contract_customer_types', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_customer_types');
        Schema::dropIfExists('contract_customers');
    }
};
