<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Persistence\Models;

use App\Modules\Cinema\Infrastructure\Persistence\Models\Cine;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property string $ci
 * @property string|null $nombre_s
 * @property string|null $apellidos
 * @property int $puntos
 * @property string|null $codigo
 * @property string|null $contrasena
 * @property string $rol
 */
class Usuario extends Authenticatable
{
    use HasApiTokens;
    use SoftDeletes;

    protected $table = 'usuarios';

    protected $primaryKey = 'ci';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'ci',
        'id_c',
        'nombre_s',
        'apellidos',
        'puntos',
        'codigo',
        'contrasena',
        'rol',
    ];

    protected $hidden = [
        'contrasena',
    ];

    protected $casts = [
        'id_c' => 'integer',
        'puntos' => 'integer',
    ];

    public function cine(): BelongsTo
    {
        return $this->belongsTo(Cine::class, 'id_c', 'id_c');
    }

    public function cines(): BelongsToMany
    {
        return $this->belongsToMany(Cine::class, 'cine_usuario', 'ci', 'id_c', 'ci', 'id_c')
            ->withTimestamps();
    }

    public function belongsToCine(int $cineId): bool
    {
        if ($this->relationLoaded('cines')) {
            return $this->cines->contains('id_c', $cineId);
        }

        return $this->cines()
            ->where('cines.id_c', $cineId)
            ->exists();
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'ci', 'ci');
    }
}
