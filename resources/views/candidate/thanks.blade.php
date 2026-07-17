<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Intake submitted — {{ config('app.name') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=montserrat:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-enterprise-50">
        <main class="mx-auto flex min-h-screen max-w-xl items-center px-6 py-10">
            <div class="panel w-full">
                <div class="panel-body space-y-4 text-center">
                    <h1 class="text-2xl font-bold text-enterprise-900">Thank you</h1>
                    <p class="text-sm leading-relaxed text-enterprise-600">
                        Your screening intake for <strong>{{ $organization->name }}</strong> was submitted successfully.
                        You can close this window. The requesting organization and SaffHire will continue the screening process.
                    </p>
                </div>
            </div>
        </main>
    </body>
</html>
