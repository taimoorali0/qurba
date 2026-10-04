<?php
// ===== QURBA: admin role names + who may do what =====
namespace App\Support;

class AdminRoles
{
    public const SUPER = 'super_admin';
    public const CONTENT = 'content_admin';
    public const REVIEWER = 'religious_reviewer';
    public const SUPPORT = 'support';

    public const LABELS = [
        self::SUPER => 'Super Admin',
        self::CONTENT => 'Content Administrator',
        self::REVIEWER => 'Religious Content Reviewer',
        self::SUPPORT => 'Support',
    ];

    public static function is(?string ...$roles): bool
    {
        $role = auth()->user()?->role;
        return $role !== null && ($role === self::SUPER || in_array($role, $roles, true));
    }
}
