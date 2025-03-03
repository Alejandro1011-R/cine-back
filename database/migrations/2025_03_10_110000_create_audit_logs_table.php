<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->char('actor_ci', 11)->nullable();
            $table->string('action', 80);
            $table->string('auditable_type', 120)->nullable();
            $table->string('auditable_id', 120)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['actor_ci']);
            $table->index(['action']);
            $table->index(['auditable_type', 'auditable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
