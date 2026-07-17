@php
    use App\Enums\CandidateFormQuestionType;
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">

        <title>Candidate intake — {{ config('app.name') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=montserrat:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-enterprise-50">
        <header class="border-b border-enterprise-200 bg-white">
            <div class="mx-auto flex max-w-4xl items-center justify-between px-6 py-4">
                <div>
                    <img
                        src="https://d2xsxph8kpxj0f.cloudfront.net/310519663368468239/Ge2emXXoKVgq4kYU9oXE74/saffhire-logo_fe0fac3a.png"
                        alt="SaffHire"
                        class="h-8 w-auto"
                    />
                    <div class="mt-1 text-xs font-semibold uppercase tracking-widest text-accent">
                        Secure candidate intake
                    </div>
                </div>
                <div class="text-right text-sm text-enterprise-600">
                    Requested by<br>
                    <span class="font-semibold text-enterprise-900">{{ $organization->name }}</span>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-4xl space-y-6 px-6 py-8">
            <div class="panel">
                <div class="panel-body space-y-3">
                    <h1 class="text-2xl font-bold text-enterprise-900">Background screening intake</h1>
                    <p class="text-sm leading-relaxed text-enterprise-600">
                        {{ $organization->name }} has requested information to complete a background screening for
                        <span class="font-medium text-enterprise-900">{{ $reportRequest->subject_name }}</span>
                        through SaffHire DataBridge. Please answer the questions below and authorize the screening.
                    </p>
                    @if ($inviteExpiresAt)
                        <p class="text-sm text-enterprise-500">
                            This secure link expires on {{ $inviteExpiresAt->format('M j, Y g:i A') }}.
                        </p>
                    @endif
                </div>
            </div>

            @if ($errors->any())
                <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    Please correct the highlighted fields and try again.
                </div>
            @endif

            <form method="POST" action="{{ route('candidate.intake.store', $token) }}" class="space-y-6">
                @csrf

                @forelse ($questions as $question)
                    <div class="panel">
                        <div class="panel-header">
                            <h2 class="panel-title">
                                {{ $question->label }}
                                @if ($question->is_required)
                                    <span class="text-red-600">*</span>
                                @endif
                            </h2>
                        </div>
                        <div class="panel-body space-y-3">
                            @if ($question->help_text)
                                <p class="text-sm text-enterprise-600">{{ $question->help_text }}</p>
                            @endif

                            @include('candidate.partials.question-field', ['question' => $question])

                            <x-input-error :messages="$errors->get('answers.'.$question->field_key)" class="mt-2" />
                            @foreach ($errors->getMessages() as $errorKey => $messages)
                                @if (str_starts_with($errorKey, 'answers.'.$question->field_key.'.'))
                                    <x-input-error :messages="$messages" class="mt-2" />
                                @endif
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="panel">
                        <div class="panel-body text-sm text-enterprise-600">
                            No intake questions are configured for this client yet. Please contact the requesting organization.
                        </div>
                    </div>
                @endforelse

                <div class="panel">
                    <div class="panel-header">
                        <h2 class="panel-title">Compliance documents &amp; authorization</h2>
                    </div>
                    <div class="panel-body space-y-5">
                        <p class="text-sm text-enterprise-600">
                            By continuing, you authorize <strong>{{ $organization->name }}</strong>, a client of SaffHire using DataBridge,
                            to obtain and review consumer reports and related background information about you from available data sources
                            for employment screening purposes.
                        </p>

                        @if ($documents->isNotEmpty())
                            <div class="space-y-3">
                                @foreach ($documents as $document)
                                    <label class="flex items-start gap-3 rounded-md border border-enterprise-200 bg-enterprise-50 p-3">
                                        <input
                                            type="checkbox"
                                            name="acknowledged_documents[]"
                                            value="{{ $document->id }}"
                                            class="mt-1 rounded border-enterprise-300 text-brand-600 focus:ring-brand-500"
                                            @checked(collect(old('acknowledged_documents', []))->contains($document->id))
                                            @required($document->require_acknowledgment)
                                        />
                                        <span class="text-sm text-enterprise-700">
                                            I have read and acknowledge
                                            <a href="{{ route('candidate.intake.documents.download', [$token, $document]) }}" class="link-action font-medium" target="_blank" rel="noopener">
                                                {{ $document->name }}
                                            </a>
                                            <span class="text-enterprise-500">({{ $document->document_type->label() }})</span>
                                            @if ($document->description)
                                                <span class="mt-1 block text-enterprise-500">{{ $document->description }}</span>
                                            @endif
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            <x-input-error :messages="$errors->get('acknowledged_documents')" class="mt-2" />
                        @endif

                        <label class="flex items-start gap-3">
                            <input
                                type="checkbox"
                                name="authorization_accepted"
                                value="1"
                                class="mt-1 rounded border-enterprise-300 text-brand-600 focus:ring-brand-500"
                                @checked(old('authorization_accepted'))
                                required
                            />
                            <span class="text-sm text-enterprise-700">
                                I authorize {{ $organization->name }} and its designated consumer reporting agency partners,
                                working through SaffHire DataBridge, to obtain information about my identity, address history,
                                employment history, criminal and civil records, and other background information as permitted by law
                                for the purpose of this screening request. I certify that the information I provided is true and complete.
                            </span>
                        </label>
                        <x-input-error :messages="$errors->get('authorization_accepted')" class="mt-2" />

                        <div class="border-t border-enterprise-200 pt-4">
                            <x-primary-button>Submit intake &amp; authorize screening</x-primary-button>
                        </div>
                    </div>
                </div>
            </form>
        </main>

        <footer class="mx-auto max-w-4xl px-6 pb-10 text-xs text-enterprise-500">
            Information submitted through this form is used only for background screening purposes and is protected under applicable privacy and consumer reporting laws.
        </footer>
    </body>
</html>
