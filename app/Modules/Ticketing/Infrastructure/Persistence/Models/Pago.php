<?php

declare(strict_types=1);

namespace App\Modules\Ticketing\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id_pg
 */
class Pago extends Model
{
    use SoftDeletes;

    protected $table = 'pagos';

    protected $primaryKey = 'id_pg';

    protected $fillable = [];

    public function efectivo(): HasOne
    {
        return $this->hasOne(Efectivo::class, 'id_pg', 'id_pg');
    }

    public function punto(): HasOne
    {
        return $this->hasOne(Punto::class, 'id_pg', 'id_pg');
    }

    public function webPayment(): HasOne
    {
        return $this->hasOne(WebPayment::class, 'id_pg', 'id_pg');
    }

    public function compra(): HasOne
    {
        return $this->hasOne(Compra::class, 'id_pg', 'id_pg');
    }
}
