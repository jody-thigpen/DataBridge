<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">

        <title>Sign in — {{ config('app.name') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=montserrat:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="flex min-h-screen">
            <div class="hidden w-[42%] flex-col justify-between bg-saffhire-navy p-10 text-white lg:flex">
                <div>
                    <img
                        src="https://d2xsxph8kpxj0f.cloudfront.net/310519663368468239/Ge2emXXoKVgq4kYU9oXE74/saffhire-logo_fe0fac3a.png"
                        alt="SaffHire"
                        class="h-10 w-auto"
                    />
                    <div class="mt-3 text-xs font-semibold uppercase tracking-widest text-accent">
                        Background screening platform
                    </div>
                </div>

                <div class="max-w-md">
                    <h1 class="text-3xl font-bold leading-tight tracking-tight">
                        The information you need to hire fast, secure and safe
                    </h1>
                    <p class="mt-4 text-sm leading-relaxed text-white/75">
                        Securely manage screening requests, client organizations, and compliance workflows from DataBridge.
                    </p>
                </div>

                <p class="text-xs text-white/50">
                    Authorized users only. Activity may be monitored for security and compliance purposes.
                </p>
            </div>

            <div class="flex flex-1 items-center justify-center bg-enterprise-50 px-6 py-10">
                <div class="w-full max-w-md">
                    <div class="mb-8 lg:hidden">
                        <img
                            src="https://d2xsxph8kpxj0f.cloudfront.net/310519663368468239/Ge2emXXoKVgq4kYU9oXE74/saffhire-logo_fe0fac3a.png"
                            alt="SaffHire"
                            class="h-9 w-auto"
                        />
                        <div class="mt-2 text-xs font-semibold uppercase tracking-widest text-accent">
                            DataBridge
                        </div>
                    </div>

                    <div class="panel">
                        <div class="panel-body">
                            {{ $slot }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
