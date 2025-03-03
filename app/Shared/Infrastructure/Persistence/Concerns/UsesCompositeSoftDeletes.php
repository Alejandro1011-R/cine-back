<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

trait UsesCompositeSoftDeletes
{
    use SoftDeletes;

    /**
     * @return list<string>
     */
    abstract protected function compositeKeyNames(): array;

    protected function setKeysForSaveQuery($query): Builder
    {
        foreach ($this->compositeKeyNames() as $keyName) {
            $query->where($keyName, '=', $this->getAttribute($keyName));
        }

        return $query;
    }
}
