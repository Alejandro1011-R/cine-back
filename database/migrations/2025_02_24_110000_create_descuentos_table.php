<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('descuentos', function (Blueprint $table) {
            $table->id('id_d');
            $table->string('nombre_d', 30)->nullable();
            $table->float('porciento')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('descuentos');
    }
};
