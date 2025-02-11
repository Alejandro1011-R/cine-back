<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Providers;

use App\Modules\Identity\Infrastructure\Hashing\Sha256PasswordHasher;
use Illuminate\Hashing\BcryptHasher;
use Illuminate\Support\ServiceProvider;

final class IdentityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Sha256PasswordHasher::class, function () {
            return new Sha256PasswordHasher(new BcryptHasher());
        });
    }

    public function boot(): void
    {
        //
    }
}
