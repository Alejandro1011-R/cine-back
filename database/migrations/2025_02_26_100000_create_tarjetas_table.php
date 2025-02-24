<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tarjetas', function (Blueprint $table) {
            $table->string('codigo_t', 18)->primary();
            $table->char('ci', 11)->nullable();
            $table->timestamps();

            $table->foreign('ci')
                ->references('ci')
                ->on('clientes')
                ->cascadeOnDelete();
        });

        // Add FK from web_payments to tarjetas
        Schema::table('web_payments', function (Blueprint $table) {
            $table->foreign('codigo_t')
                ->references('codigo_t')
                ->on('tarjetas')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('web_payments', function (Blueprint $table) {
            $table->dropForeign(['codigo_t']);
        });
        Schema::dropIfExists('tarjetas');
    }
};
