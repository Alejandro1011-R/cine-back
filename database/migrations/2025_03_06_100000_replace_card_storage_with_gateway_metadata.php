<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('web_payments', function (Blueprint $table): void {
            $table->string('gateway_reference', 64)->nullable();
            $table->string('gateway_status', 32)->nullable();
            $table->string('card_brand', 32)->nullable();
            $table->string('card_last_four', 4)->nullable();
        });

        Schema::table('web_payments', function (Blueprint $table): void {
            $table->dropForeign(['codigo_t']);
        });

        DB::table('web_payments')->update(['codigo_t' => null]);

        if (Schema::hasTable('tarjetas')) {
            DB::table('tarjetas')->delete();
        }
    }

    public function down(): void
    {
        Schema::table('web_payments', function (Blueprint $table): void {
            $table->dropColumn([
                'gateway_reference',
                'gateway_status',
                'card_brand',
                'card_last_four',
            ]);
        });

        Schema::table('web_payments', function (Blueprint $table): void {
            $table->foreign('codigo_t')
                ->references('codigo_t')
                ->on('tarjetas')
                ->nullOnDelete();
        });
    }
};
