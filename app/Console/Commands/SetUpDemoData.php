<?php

namespace App\Console\Commands;

use App\Models\ServiceType;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\ServiceTypeSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Fills a deployed site (e.g. Laravel Cloud) with the fictional demo data used for evaluation:
 * a second Administrator, Staff, a verified resident, two pending registrations, requests at every
 * status, and tickets. Each demo account gets a random password, shown once, because the site is public.
 */
#[Signature('demo:setup')]
#[Description('Add the fictional demo accounts, requests, and tickets for evaluation (runs once)')]
class SetUpDemoData extends Command
{
    public function handle(): int
    {
        if (DemoDataSeeder::alreadySeeded()) {
            $this->warn('Demo data is already set up; nothing was changed. Reset a demo password from User Management if needed.');

            return self::SUCCESS;
        }

        if (! ServiceType::query()->exists()) {
            $this->callSilently('db:seed', ['--class' => ServiceTypeSeeder::class, '--force' => true]);
        }

        $this->callSilently('db:seed', ['--class' => DemoDataSeeder::class, '--force' => true]);

        $rows = User::whereIn('username', DemoDataSeeder::DEMO_USERNAMES)
            ->orderByRaw("case role when 'administrator' then 0 when 'staff' then 1 else 2 end")
            ->get()
            ->map(function (User $user): array {
                $password = Str::password(12, symbols: false);
                $user->update(['password' => $password]);

                return [$user->username, $password, $user->role->label(), $user->status->label(), $user->name];
            })
            ->all();

        $this->info('Demo data added. Copy these passwords now; they are shown only once.');
        $this->table(['Username', 'Password', 'Role', 'Status', 'Name'], $rows);

        return self::SUCCESS;
    }
}
