<?php

namespace App\Console\Commands;

use App\Services\PaymentService;
use Illuminate\Console\Command;

class ExpireUnpaidOrders extends Command
{
    protected $signature = 'orders:expire-unpaid {--minutes= : Override the expiry window}';

    protected $description = 'Cancel online-payment orders that were never paid and release their reserved stock.';

    public function handle(PaymentService $payments): int
    {
        $minutes = $this->option('minutes') !== null ? (int) $this->option('minutes') : null;
        $count = $payments->expireUnpaidOrders($minutes);

        $this->info("Expired $count unpaid order(s).");

        return self::SUCCESS;
    }
}
