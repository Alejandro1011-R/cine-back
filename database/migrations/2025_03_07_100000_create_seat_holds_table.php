<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seat_holds', function (Blueprint $table): void {
            $table->id();
            $table->uuid('hold_token');
            $table->unsignedBigInteger('id_p');
            $table->unsignedBigInteger('id_s');
            $table->timestamp('fecha');
            $table->char('ci', 11);
            $table->unsignedBigInteger('id_b');
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->unique(['id_p', 'id_s', 'fecha', 'id_b']);
            $table->index(['hold_token']);
            $table->index(['expires_at']);

            $table->foreign('ci')
                ->references('ci')
                ->on('clientes')
                ->cascadeOnDelete();

            $table->foreign('id_b')
                ->references('id_b')
                ->on('butacas')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seat_holds');
    }
};
