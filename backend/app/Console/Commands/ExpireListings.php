<?php

namespace App\Console\Commands;

use App\Models\Listing;
use Illuminate\Console\Command;

class ExpireListings extends Command
{
    protected $signature = 'listings:expire';

    protected $description = 'Marca como expirados los anuncios publicados cuya fecha de caducidad ya ha pasado';

    public function handle(): int
    {
        $caducados = Listing::query()
            ->where('status', 'published')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update(['status' => 'expired']);

        $this->info($caducados === 0
            ? 'No había anuncios que caducar.'
            : "Anuncios caducados: {$caducados}.");

        return self::SUCCESS;
    }
}
