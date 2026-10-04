<?php
// ===== QURBA: reset an admin's 2FA (lost phone and no recovery codes) =====
namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ResetAdminTwoFactor extends Command
{
    protected $signature = 'qurba:reset-2fa {email}';
    protected $description = 'Remove two-factor from an account so it must be set up again at next admin sign-in';

    public function handle(): int
    {
        $u = User::where('email', $this->argument('email'))->first();
        if (! $u) { $this->error('No user with that email.'); return self::FAILURE; }
        $u->forceFill(['two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null])->save();
        $this->info("Two-factor reset for {$u->email}. They will set it up again at next admin sign-in.");
        return self::SUCCESS;
    }
}
