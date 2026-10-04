<?php
// ===== QURBA: give an existing account an admin role =====
namespace App\Console\Commands;

use App\Models\User;
use App\Support\AdminRoles;
use Illuminate\Console\Command;

class MakeAdmin extends Command
{
    protected $signature = 'qurba:make-admin {email} {--role=super_admin : super_admin | content_admin | religious_reviewer | support | none}';
    protected $description = 'Set the admin role of an existing user (register in the app first)';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();
        if (! $user) { $this->error('No user with that email. Register in the app first.'); return self::FAILURE; }
        $role = $this->option('role');
        if ($role !== 'none' && ! array_key_exists($role, AdminRoles::LABELS)) { $this->error('Unknown role.'); return self::FAILURE; }
        $user->forceFill(['role' => $role === 'none' ? null : $role])->save();
        $this->info("{$user->email} is now: " . ($role === 'none' ? 'normal user' : AdminRoles::LABELS[$role]));
        return self::SUCCESS;
    }
}
