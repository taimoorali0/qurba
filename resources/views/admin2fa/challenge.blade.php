@extends('admin2fa.layout')
@section('body')
<h1>Two-factor check</h1>
<p>Enter the 6-digit code from your authenticator app, or one of your recovery codes.</p>
<form method="post" action="{{ route('admin.2fa.verify') }}">@csrf
 <input name="code" autocomplete="one-time-code" maxlength="20" required autofocus placeholder="000000">
 @error('code')<div class="err">{{ $message }}</div>@enderror
 <button>Continue</button>
</form>
@endsection
