<?php

namespace App\Console\Commands;

use App\Services\RecurringPayableGenerator;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('payables:generate')]
#[Description('Generate recurring payables for the next three months')]
class GenerateUpcomingPayables extends Command
{
    public function handle(RecurringPayableGenerator $generator): int
    {
        $created = $generator->generateUpcoming();

        $this->components->info("Generated {$created} upcoming payable(s).");

        return self::SUCCESS;
    }
}
