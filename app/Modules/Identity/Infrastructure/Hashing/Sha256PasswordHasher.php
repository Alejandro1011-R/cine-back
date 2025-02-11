<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Hashing;

use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Facades\Hash;

final class Sha256PasswordHasher implements Hasher
{
    public function __construct(
        private readonly Hasher $bcryptHasher,
    ) {}
    public function info($hashedValue): array
    {
        return $this->bcryptHasher->info($hashedValue);
    }
    public function make($value, array $options = []): string
    {
        return $this->bcryptHasher->make($value, $options);
    }
    public function check($value, $hashedValue, array $options = []): bool
    {
        if (empty($hashedValue)) {
            return false;
        }

        if ($this->isBcrypt($hashedValue)) {
            return $this->bcryptHasher->check($value, $hashedValue, $options);
        }

        $sha256Hex = hash('sha256', $value);

        return hash_equals(strtolower($hashedValue), strtolower($sha256Hex));
    }
    public function needsRehash($hashedValue, array $options = []): bool
    {
        if (!$this->isBcrypt($hashedValue)) {
            return true;
        }

        return $this->bcryptHasher->needsRehash($hashedValue, $options);
    }

    private function isBcrypt(string $hashedValue): bool
    {
        return str_starts_with($hashedValue, '$2y$')
            || str_starts_with($hashedValue, '$2a$')
            || str_starts_with($hashedValue, '$2b$');
    }
}
