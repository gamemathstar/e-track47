<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Idempotently ensure Passport signing keys exist. Safe to call from
 * composer post-install hooks and deploy scripts — exits 0 whether the keys
 * were already present or newly generated.
 *
 * Why this exists: the stock `passport:keys` command errors out with exit
 * code 1 when the keys already exist (it expects you to pass `--force` to
 * overwrite). That's the right default for an interactive operator, but it
 * breaks automated deploys, where re-running is expected.
 */
class EnsurePassportKeysCommand extends Command
{
    protected $signature = 'pdcu:ensure-passport-keys';

    protected $description = 'Generate Passport signing keys if missing — idempotent.';

    public function handle(): int
    {
        $keyPath = storage_path('oauth-private.key');

        if (file_exists($keyPath) && filesize($keyPath) > 500) {
            $this->info('Passport keys already present at '.$keyPath.' — skipping.');

            return self::SUCCESS;
        }

        $this->warn('Passport keys missing or unreadable — generating fresh ones.');

        return $this->call('passport:keys');
    }
}
