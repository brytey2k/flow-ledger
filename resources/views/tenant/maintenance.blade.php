<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} &middot; Maintenance</title>
</head>
<body style="font-family: system-ui, sans-serif; display: grid; place-items: center; min-height: 100vh; margin: 0; text-align: center;">
    <main style="max-width: 32rem; padding: 1.5rem;">
        <h1>We'll be right back</h1>
        <p>{{ $message !== '' ? $message : 'This workspace is temporarily down for maintenance.' }}</p>
    </main>
</body>
</html>
