<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_change_audits', function (Blueprint $table): void {
            $table->id();
            $table->char('actor_ci', 11);
            $table->char('target_ci', 11);
            $table->string('old_role', 20)->nullable();
            $table->string('new_role', 20);
            $table->timestamps();

            $table->index(['target_ci']);
            $table->foreign('actor_ci')->references('ci')->on('usuarios');
            $table->foreign('target_ci')->references('ci')->on('usuarios');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_change_audits');
    }
};
