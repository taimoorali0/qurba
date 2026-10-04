<?php
// ===== QURBA: admin TOTP setup + challenge + recovery codes =====
namespace App\Http\Controllers;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class AdminTwoFactorController extends Controller
{
    private function guard(Request $r)
    {
        abort_unless($r->user()?->role, 403);
    }

    public function setup(Request $r)
    {
        $this->guard($r);
        if ($r->user()->two_factor_confirmed_at) return redirect()->route('admin.2fa.challenge');
        $g = new Google2FA();
        $secret = $r->session()->get('admin_2fa_pending') ?? $g->generateSecretKey(32);
        $r->session()->put('admin_2fa_pending', $secret);
        $url = $g->getQRCodeUrl('Qurba Admin', $r->user()->email, $secret);
        $svg = (new Writer(new ImageRenderer(new RendererStyle(220, 1), new SvgImageBackEnd())))->writeString($url);
        return view('admin2fa.setup', ['svg' => $svg, 'secret' => trim(chunk_split($secret, 4, ' '))]);
    }

    public function confirm(Request $r)
    {
        $this->guard($r);
        $r->validate(['code' => 'required|digits:6']);
        $secret = $r->session()->get('admin_2fa_pending');
        if (! $secret || ! (new Google2FA())->verifyKey($secret, $r->input('code'), 1)) {
            return back()->withErrors(['code' => 'That code is not correct. Check the time on your phone and try again.']);
        }
        $codes = collect(range(1, 8))->map(fn () => Str::upper(Str::random(5) . '-' . Str::random(5)))->all();
        $r->user()->forceFill([
            'two_factor_secret' => Crypt::encryptString($secret),
            'two_factor_recovery_codes' => Crypt::encryptString(json_encode($codes)),
            'two_factor_confirmed_at' => now(),
        ])->save();
        $r->session()->forget('admin_2fa_pending');
        $r->session()->put('admin_2fa_passed', true);
        $r->session()->regenerate();
        return view('admin2fa.codes', ['codes' => $codes]);
    }

    public function challenge(Request $r)
    {
        $this->guard($r);
        if (! $r->user()->two_factor_confirmed_at) return redirect()->route('admin.2fa.setup');
        return view('admin2fa.challenge');
    }

    public function verify(Request $r)
    {
        $this->guard($r);
        $r->validate(['code' => 'required|string|max:20']);
        $u = $r->user();
        $input = strtoupper(trim($r->input('code')));
        $ok = false;
        if (preg_match('/^\d{6}$/', $input)) {
            $ok = (new Google2FA())->verifyKey(Crypt::decryptString($u->two_factor_secret), $input, 1);
        } else {
            $codes = json_decode(Crypt::decryptString($u->two_factor_recovery_codes), true) ?: [];
            if (in_array($input, $codes, true)) {           // each recovery code works once
                $ok = true;
                $u->forceFill(['two_factor_recovery_codes' => Crypt::encryptString(json_encode(array_values(array_diff($codes, [$input]))))])->save();
            }
        }
        if (! $ok) return back()->withErrors(['code' => 'That code is not correct.']);
        $r->session()->put('admin_2fa_passed', true);
        $r->session()->regenerate();
        return redirect()->intended('/admin');
    }
}
