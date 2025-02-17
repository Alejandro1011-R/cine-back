<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('butacas', function (Blueprint $table) {
            $table->id('id_b');
            $table->unsignedBigInteger('id_s');
            $table->timestamps();

            $table->foreign('id_s')
                ->references('id_s')
                ->on('salas')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('butacas');
    }
};
