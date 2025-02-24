<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class ClearExpiredSeatHoldsCommand extends Command
{
    protected $signature = 'seat-holds:clear-expired';

    protected $description = 'Delete expired temporary seat holds.';

    public function handle(): int
    {
        $deleted = DB::table('seat_holds')
            ->where('expires_at', '<=', now())
            ->delete();

        $this->info("Expired seat holds deleted: {$deleted}");

        return self::SUCCESS;
    }
}
