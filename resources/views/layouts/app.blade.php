<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">

        <title>{{ config('app.name', 'DataBridge') }} — Saffhire</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=montserrat:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans">
        <div x-data="{ sidebarOpen: false }" class="app-shell">
            <div
                x-show="sidebarOpen"
                x-transition.opacity
                @click="sidebarOpen = false"
                class="app-sidebar-backdrop"
                x-cloak
            ></div>

            @include('layouts.partials.sidebar')

            <div class="app-main">
                @include('layouts.partials.topbar')

                @if ($isImpersonating ?? false)
                    <div class="impersonation-banner">
                        <div class="mx-auto flex max-w-[90rem] flex-wrap items-center justify-between gap-2 px-4 py-2 sm:px-6 lg:px-8">
                            <span>
                                Support session: viewing as <strong>{{ auth()->user()->name }}</strong>
                                @if ($impersonator)
                                    (initiated by {{ $impersonator->name }})
                                @endif
                            </span>
                            <form method="POST" action="{{ route('platform.impersonation.destroy') }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-secondary !py-1.5 text-xs">
                                    End support session
                                </button>
                            </form>
                        </div>
                    </div>
                @endif

                @isset($header)
                    <header class="border-b border-enterprise-200 bg-white">
                        <div class="page-container !pb-4 !pt-4">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <main class="page-container">
                    @if (session('status'))
                        <div class="alert-success">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert-error">
                            {{ session('error') }}
                        </div>
                    @endif

                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
