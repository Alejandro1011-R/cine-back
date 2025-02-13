<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTOs;

final readonly class PeliculaData
{
    /**
     * @param array<int> $actorIds
     * @param array<int> $generoIds
     */
    public function __construct(
        public ?string $sinopsis,
        public ?int $anno,
        public ?int $nacionalidad,
        public ?int $duracion,
        public ?string $titulo,
        public ?string $imagen,
        public ?string $trailer,
        public array $actorIds = [],
        public array $generoIds = [],
    ) {}
}
