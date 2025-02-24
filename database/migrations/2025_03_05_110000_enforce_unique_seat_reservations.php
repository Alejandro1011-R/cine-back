<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('butacas_reservadas')) {
            return;
        }

        DB::statement(<<<'SQL'
            DO $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1
                    FROM pg_constraint
                    WHERE conname = 'butacas_reservadas_unique_seat_session'
                ) THEN
                    ALTER TABLE butacas_reservadas
                    ADD CONSTRAINT butacas_reservadas_unique_seat_session
                    UNIQUE (id_p, id_s, fecha, id_b);
                END IF;
            END
            $$;
        SQL);
    }

    public function down(): void
    {
        if (!Schema::hasTable('butacas_reservadas')) {
            return;
        }

        DB::statement('ALTER TABLE butacas_reservadas DROP CONSTRAINT IF EXISTS butacas_reservadas_unique_seat_session');
    }
};
