<?php

declare(strict_types=1);

namespace App\Modules\Ticketing\Infrastructure\Persistence\Models;

use App\Modules\Cinema\Infrastructure\Persistence\Models\Butaca;
use App\Modules\Identity\Infrastructure\Persistence\Models\Cliente;
use App\Shared\Infrastructure\Persistence\Concerns\UsesCompositeSoftDeletes;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id_p
 * @property int $id_s
 * @property string $fecha
 * @property string $ci
 * @property int $id_pg
 * @property string|null $tipo
 * @property string|null $fecha_de_compra
 * @property string|null $medio_ad
 */
class Compra extends Model
{
    use UsesCompositeSoftDeletes;

    protected $table = 'compras';

    // Composite primary key
    public $incrementing = false;

    protected $primaryKey = null;

    protected $fillable = [
        'id_p',
        'id_s',
        'fecha',
        'ci',
        'id_pg',
        'tipo',
        'fecha_de_compra',
        'medio_ad',
    ];

    protected $casts = [
        'id_p' => 'integer',
        'id_s' => 'integer',
        'id_pg' => 'integer',
        'fecha' => 'datetime',
        'fecha_de_compra' => 'datetime',
    ];

    public function getKeyName(): string
    {
        return 'id_p';
    }

    protected function compositeKeyNames(): array
    {
        return ['id_p', 'id_s', 'fecha', 'ci'];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'ci', 'ci');
    }

    public function pago(): BelongsTo
    {
        return $this->belongsTo(Pago::class, 'id_pg', 'id_pg');
    }

    /**
     * Custom accessor for butacas (composite FK pivot — not supported by BelongsToMany).
     *
     * @return Collection<int, Butaca>
     */
    public function getButacasAttribute(): Collection
    {
        $butacaIds = DB::table('butacas_reservadas')
            ->where('id_p', $this->id_p)
            ->where('id_s', $this->id_s)
            ->where('fecha', $this->fecha)
            ->where('ci', $this->ci)
            ->pluck('id_b');

        return Butaca::whereIn('id_b', $butacaIds)->get();
    }

    /**
     * Custom accessor for descuentos (composite FK pivot).
     *
     * @return Collection<int, Descuento>
     */
    public function getDescuentosAttribute(): Collection
    {
        $descuentoIds = DB::table('descontados')
            ->where('id_p', $this->id_p)
            ->where('id_s', $this->id_s)
            ->where('fecha', $this->fecha)
            ->where('ci', $this->ci)
            ->pluck('id_d');

        return Descuento::whereIn('id_d', $descuentoIds)->get();
    }
}
