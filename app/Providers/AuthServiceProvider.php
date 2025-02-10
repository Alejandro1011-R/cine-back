<?php

declare(strict_types=1);

namespace App\Providers;

use App\Modules\Cinema\Http\Policies\CinePolicy;
use App\Modules\Cinema\Http\Policies\ButacaPolicy;
use App\Modules\Cinema\Http\Policies\SalaPolicy;
use App\Modules\Cinema\Http\Policies\SesionPolicy;
use App\Modules\Cinema\Infrastructure\Persistence\Models\Butaca;
use App\Modules\Cinema\Infrastructure\Persistence\Models\Cine;
use App\Modules\Cinema\Infrastructure\Persistence\Models\Sala;
use App\Modules\Cinema\Infrastructure\Persistence\Models\Sesion;
use App\Modules\Identity\Http\Policies\ClientePolicy;
use App\Modules\Identity\Http\Policies\UsuarioPolicy;
use App\Modules\Identity\Infrastructure\Persistence\Models\Cliente;
use App\Modules\Identity\Infrastructure\Persistence\Models\Usuario;
use App\Modules\Ticketing\Http\Policies\CompraPolicy;
use App\Modules\Ticketing\Infrastructure\Persistence\Models\Compra;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Mapeo Modelo → Policy.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Compra::class => CompraPolicy::class,
        Butaca::class => ButacaPolicy::class,
        Cine::class   => CinePolicy::class,
        Cliente::class => ClientePolicy::class,
        Sala::class => SalaPolicy::class,
        Sesion::class => SesionPolicy::class,
        Usuario::class => UsuarioPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
