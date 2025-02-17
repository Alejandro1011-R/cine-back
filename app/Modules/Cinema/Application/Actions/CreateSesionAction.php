<?php

declare(strict_types=1);

namespace App\Modules\Cinema\Application\Actions;

use App\Modules\Catalog\Infrastructure\Persistence\Models\Pelicula;
use App\Modules\Cinema\Application\DTOs\CreateSesionData;
use App\Modules\Cinema\Domain\Contracts\SesionScheduleConflictChecker;
use App\Modules\Cinema\Infrastructure\Persistence\Models\Sala;
use App\Modules\Cinema\Infrastructure\Persistence\Models\Sesion;

final class CreateSesionAction
{
    public function __construct(
        private readonly SesionScheduleConflictChecker $conflictChecker,
    ) {}

    /**
     * @return array{success: bool, sesion?: Sesion, error?: string, code?: int}
     */
    public function handle(CreateSesionData $data): array
    {
        $pelicula = Pelicula::find($data->idP);
        if ($pelicula === null || $pelicula->duracion === null) {
            return ['success' => false, 'error' => 'Película no encontrada o sin duración.', 'code' => 400];
        }

        if ($this->conflictChecker->hasOverlap($data->fecha, $pelicula->duracion, $data->idS)) {
            return ['success' => false, 'error' => 'Conflicto de horario con otra sesión.', 'code' => 409];
        }

        $sala = Sala::find($data->idS);
        if ($sala === null) {
            return ['success' => false, 'error' => 'Sala no encontrada.', 'code' => 400];
        }

        $sesion = new Sesion();
        $sesion->id_p = $data->idP;
        $sesion->id_s = $data->idS;
        $sesion->fecha = $data->fecha;
        $sesion->save();

        $sesion->load(['pelicula', 'sala']);

        return ['success' => true, 'sesion' => $sesion];
    }
}
