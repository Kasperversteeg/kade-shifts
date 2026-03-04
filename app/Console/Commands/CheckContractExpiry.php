<?php

namespace App\Console\Commands;

use App\Mail\ContractExpiryNotification;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class CheckContractExpiry extends Command
{
    protected $signature = 'contracts:check-expiry';

    protected $description = 'Check for expiring contracts and notify admins';

    public function handle(): int
    {
        $days = 45;

        $expiringUsers = User::active()
            ->whereNotNull('contract_end_date')
            ->where('contract_end_date', '<=', now()->addDays($days))
            ->where('contract_end_date', '>=', today())
            ->whereNull('contract_expiry_notified_at')
            ->get();

        if ($expiringUsers->isEmpty()) {
            $this->info('No expiring contracts found.');

            return Command::SUCCESS;
        }

        $admins = User::role('admin')->get();

        foreach ($expiringUsers as $user) {
            foreach ($admins as $admin) {
                Mail::to($admin->email)->queue(new ContractExpiryNotification($user));
            }
            $user->update(['contract_expiry_notified_at' => now()]);
        }

        $this->info("Notified about {$expiringUsers->count()} expiring contract(s).");

        return Command::SUCCESS;
    }
}
