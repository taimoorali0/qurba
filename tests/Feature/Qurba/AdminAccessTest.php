<?php
// ===== QURBA: admin panel access + mandatory 2FA =====
use App\Models\User;

it('blocks users without an admin role', function () {
    $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
});

it('sends a new admin to 2FA setup first', function () {
    $u = User::factory()->create();
    $u->forceFill(['role' => 'super_admin'])->save();
    $this->actingAs($u)->get('/admin')->assertRedirect(route('admin.2fa.setup'));
});

it('asks a configured admin for the 2FA code each session', function () {
    $u = User::factory()->create();
    $u->forceFill(['role' => 'support', 'two_factor_confirmed_at' => now()])->save();
    $this->actingAs($u)->get('/admin')->assertRedirect(route('admin.2fa.challenge'));
});
