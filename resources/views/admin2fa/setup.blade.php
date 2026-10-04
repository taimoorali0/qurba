@extends('admin2fa.layout')
@section('body')
<h1>Set up two-factor sign-in</h1>
<p>Admin access requires an authenticator app (Google Authenticator, Microsoft Authenticator, Authy or 1Password). Scan this code, then enter the 6-digit code it shows.</p>
<div style="display:grid;place-items:center;margin:16px 0">{!! $svg !!}</div>
<p style="font-size:.85rem">Can't scan? Enter this key manually:<br><code>{{ $secret }}</code></p>
<form method="post" action="{{ route('admin.2fa.confirm') }}">@csrf
 <input name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required autofocus placeholder="000000">
 @error('code')<div class="err">{{ $message }}</div>@enderror
 <button>Turn on two-factor</button>
</form>
@endsection
