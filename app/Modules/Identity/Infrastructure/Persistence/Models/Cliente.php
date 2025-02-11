<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Persistence\Models;

use App\Modules\Ticketing\Infrastructure\Persistence\Models\Compra;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $ci
 * @property string|null $correo
 * @property bool $confiabilidad
 */
class Cliente extends Model
{
    use SoftDeletes;

    protected $table = 'clientes';

    protected $primaryKey = 'ci';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'ci',
        'correo',
        'confiabilidad',
    ];

    protected $casts = [
        'confiabilidad' => 'boolean',
    ];

    public function usuario(): HasOne
    {
        return $this->hasOne(Usuario::class, 'ci', 'ci');
    }

    public function compras(): HasMany
    {
        return $this->hasMany(Compra::class, 'ci', 'ci');
    }
}
