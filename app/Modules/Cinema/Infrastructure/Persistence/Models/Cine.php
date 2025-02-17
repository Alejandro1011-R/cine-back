<?php

declare(strict_types=1);

namespace App\Modules\Cinema\Infrastructure\Persistence\Models;

use App\Modules\Identity\Infrastructure\Persistence\Models\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id_c
 * @property string $nombre
 * @property string|null $direccion
 */
class Cine extends Model
{
    use SoftDeletes;

    protected $table = 'cines';
    protected $primaryKey = 'id_c';

    protected $fillable = ['nombre', 'direccion'];

    public function salas(): HasMany
    {
        return $this->hasMany(Sala::class, 'id_c', 'id_c');
    }

    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(Usuario::class, 'cine_usuario', 'id_c', 'ci', 'id_c', 'ci')
            ->withTimestamps();
    }
}
