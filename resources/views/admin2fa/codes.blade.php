@extends('admin2fa.layout')
@section('body')
<h1>Save your recovery codes</h1>
<p>If you lose your phone, each code below lets you sign in once. Store them somewhere safe. They will not be shown again.</p>
<div class="codes">@foreach ($codes as $c)<code>{{ $c }}</code>@endforeach</div>
<a href="/admin"><button type="button">I saved them, open admin</button></a>
@endsection
