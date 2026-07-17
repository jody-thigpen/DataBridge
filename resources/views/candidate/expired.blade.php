<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Intake link expired — {{ config('app.name') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=montserrat:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-enterprise-50">
        <main class="mx-auto flex min-h-screen max-w-xl items-center px-6 py-10">
            <div class="panel w-full">
                <div class="panel-body space-y-4 text-center">
                    <h1 class="text-2xl font-bold text-enterprise-900">This link has expired</h1>
                    <p class="text-sm leading-relaxed text-enterprise-600">
                        Screening intake links are valid for {{ $ttlDays }} days.
                        Please contact <strong>{{ $organization->name }}</strong> and ask them to resend your invitation so you can complete the form.
                    </p>
                </div>
            </div>
        </main>
    </body>
</html>
