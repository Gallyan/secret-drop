@extends('layouts.app')

@section('title', __('messages.how_it_works_title'))
@section('description', __('messages.how_it_works_meta_description'))

@push('schema')
<script type="application/ld+json" nonce="@nonce">
{
    "@@context": "https://schema.org",
    "@@type": "BreadcrumbList",
    "itemListElement": [
        {
            "@@type": "ListItem",
            "position": 1,
            "name": "{{ config('app.name') }}",
            "item": "{{ route('home') }}"
        },
        {
            "@@type": "ListItem",
            "position": 2,
            "name": "{{ __('messages.how_it_works_title') }}"
        }
    ]
}
</script>
<script type="application/ld+json" nonce="@nonce">
{
    "@@context": "https://schema.org",
    "@@type": "HowTo",
    "name": "{{ __('messages.how_it_works_title') }}",
    "description": "{{ __('messages.how_it_works_intro') }}",
    "step": [
        {
            "@@type": "HowToStep",
            "position": 1,
            "name": "{{ __('messages.how_step1_title') }}",
            "text": "{{ __('messages.how_step1_desc') }}"
        },
        {
            "@@type": "HowToStep",
            "position": 2,
            "name": "{{ __('messages.how_step2_title') }}",
            "text": "{{ __('messages.how_step2_desc') }}"
        },
        {
            "@@type": "HowToStep",
            "position": 3,
            "name": "{{ __('messages.how_step3_title') }}",
            "text": "{{ __('messages.how_step3_desc') }}"
        },
        {
            "@@type": "HowToStep",
            "position": 4,
            "name": "{{ __('messages.how_step4_title') }}",
            "text": "{{ __('messages.how_step4_desc') }}"
        },
        {
            "@@type": "HowToStep",
            "position": 5,
            "name": "{{ __('messages.how_step5_title') }}",
            "text": "{{ __('messages.how_step5_desc') }}"
        }
    ]
}
</script>
<script type="application/ld+json" nonce="@nonce">
{
    "@@context": "https://schema.org",
    "@@type": "WebPage",
    "name": "{{ __('messages.how_it_works_title') }}",
    "description": "{{ __('messages.how_it_works_meta_description') }}",
    "about": {
        "@@type": "WebApplication",
        "name": "{{ config('app.name') }}"
    },
    "isPartOf": {
        "@@type": "WebSite",
        "name": "{{ config('app.name') }}",
        "url": "{{ url('/') }}"
    }
}
</script>
<script type="application/ld+json" nonce="@nonce">
{
    "@@context": "https://schema.org",
    "@@type": "FAQPage",
    "mainEntity": [
        {
            "@@type": "Question",
            "name": "{{ __('messages.faq_q1') }}",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "{{ __('messages.faq_a1') }}"
            }
        },
        {
            "@@type": "Question",
            "name": "{{ __('messages.faq_q2') }}",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "{{ __('messages.faq_a2') }}"
            }
        },
        {
            "@@type": "Question",
            "name": "{{ __('messages.faq_q3') }}",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "{{ __('messages.faq_a3') }}"
            }
        },
        {
            "@@type": "Question",
            "name": "{{ __('messages.faq_q4') }}",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "{{ __('messages.faq_a4') }}"
            }
        },
        {
            "@@type": "Question",
            "name": "{{ __('messages.faq_q5') }}",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "{{ __('messages.faq_a5') }}"
            }
        },
        {
            "@@type": "Question",
            "name": "{{ __('messages.faq_q6') }}",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "{{ __('messages.faq_a6') }}"
            }
        },
        {
            "@@type": "Question",
            "name": "{{ __('messages.faq_q7') }}",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "{{ __('messages.faq_a7') }}"
            }
        },
        {
            "@@type": "Question",
            "name": "{{ __('messages.faq_q8') }}",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "{{ __('messages.faq_a8') }}"
            }
        },
        {
            "@@type": "Question",
            "name": "{{ __('messages.faq_q9') }}",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "{{ __('messages.faq_a9') }}"
            }
        },
        {
            "@@type": "Question",
            "name": "{{ __('messages.faq_q10') }}",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "{{ __('messages.faq_a10') }}"
            }
        },
        {
            "@@type": "Question",
            "name": "{{ __('messages.faq_q11') }}",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "{{ __('messages.faq_a11') }}"
            }
        }
    ]
}
</script>
@endpush

@section('content')
<div class="min-h-screen bg-linear-to-br from-gray-50 via-gray-200 to-gray-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900 py-12 px-4 pb-16 overflow-x-hidden transition-colors">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-xl border border-gray-200 dark:border-slate-700/50 rounded-2xl shadow-2xl p-8 lg:p-12 transition-colors">
            {{-- Header --}}
            <div class="flex items-center gap-4 mb-8">
                <a href="{{ route('home') }}" class="p-2 -ms-2 text-gray-500 hover:text-gray-700 dark:text-slate-400 dark:hover:text-slate-300 transition-colors" aria-label="{{ __('messages.a11y_back') }}">
                    <svg class="w-5 h-5 rtl:rotate-180" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight transition-colors">
                    {{ __('messages.how_it_works_title') }}
                </h1>
            </div>

            {{-- Introduction --}}
            <p class="text-gray-600 dark:text-slate-400 mb-10 text-lg">
                {{ __('messages.how_it_works_intro') }}
            </p>

            {{-- Visual Encryption Diagram --}}
            <div class="mb-12">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
                    {{ __('messages.how_it_works_diagram_title') }}
                </h2>

                {{-- Step-by-step flow --}}
                <ol class="space-y-6 list-none">
                    {{-- Step 1: User writes secret --}}
                    <div class="flex items-start gap-4">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full bg-violet-100 dark:bg-violet-500/20 text-violet-600 dark:text-violet-400 font-bold shrink-0">
                            1
                        </div>
                        <div class="flex-1 pt-1">
                            <h3 class="font-medium text-gray-900 dark:text-white mb-1">{{ __('messages.how_step1_title') }}</h3>
                            <p class="text-gray-600 dark:text-slate-400 text-sm">{{ __('messages.how_step1_desc') }}</p>
                        </div>
                        <div class="hidden sm:flex items-center justify-center w-16 h-16 rounded-xl bg-gray-100 dark:bg-slate-700/50 shrink-0" aria-hidden="true">
                            <svg class="w-8 h-8 text-gray-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </div>
                    </div>

                    {{-- Arrow --}}
                    <div class="flex justify-center" aria-hidden="true">
                        <svg class="w-6 h-6 text-gray-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                        </svg>
                    </div>

                    {{-- Step 2: Browser encrypts --}}
                    <div class="flex items-start gap-4">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-300 font-bold shrink-0">
                            2
                        </div>
                        <div class="flex-1 pt-1">
                            <h3 class="font-medium text-gray-900 dark:text-white mb-1">{{ __('messages.how_step2_title') }}</h3>
                            <p class="text-gray-600 dark:text-slate-400 text-sm">{{ __('messages.how_step2_desc') }}</p>
                            <div class="mt-2 p-3 bg-emerald-50 dark:bg-emerald-500/10 rounded-lg border border-emerald-200 dark:border-emerald-500/20">
                                <code class="text-xs text-emerald-700 dark:text-emerald-300 font-mono">AES-256-GCM + WebCrypto API</code>
                            </div>
                        </div>
                        <div class="hidden sm:flex items-center justify-center w-16 h-16 rounded-xl bg-emerald-100 dark:bg-emerald-500/20 shrink-0" aria-hidden="true">
                            <svg class="w-8 h-8 text-emerald-600 dark:text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                    </div>

                    {{-- Arrow --}}
                    <div class="flex justify-center" aria-hidden="true">
                        <svg class="w-6 h-6 text-gray-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                        </svg>
                    </div>

                    {{-- Step 3: Server stores ciphertext --}}
                    <div class="flex items-start gap-4">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-500/20 text-amber-600 dark:text-amber-300 font-bold shrink-0">
                            3
                        </div>
                        <div class="flex-1 pt-1">
                            <h3 class="font-medium text-gray-900 dark:text-white mb-1">{{ __('messages.how_step3_title') }}</h3>
                            <p class="text-gray-600 dark:text-slate-400 text-sm">{{ __('messages.how_step3_desc') }}</p>
                        </div>
                        <div class="hidden sm:flex items-center justify-center w-16 h-16 rounded-xl bg-amber-100 dark:bg-amber-500/20 shrink-0" aria-hidden="true">
                            <svg class="w-8 h-8 text-amber-600 dark:text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                            </svg>
                        </div>
                    </div>

                    {{-- Arrow --}}
                    <div class="flex justify-center" aria-hidden="true">
                        <svg class="w-6 h-6 text-gray-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                        </svg>
                    </div>

                    {{-- Step 4: Key in URL fragment --}}
                    <div class="flex items-start gap-4">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-500/20 text-indigo-600 dark:text-indigo-300 font-bold shrink-0">
                            4
                        </div>
                        <div class="flex-1 pt-1">
                            <h3 class="font-medium text-gray-900 dark:text-white mb-1">{{ __('messages.how_step4_title') }}</h3>
                            <p class="text-gray-600 dark:text-slate-400 text-sm">{{ __('messages.how_step4_desc') }}</p>
                            <div class="mt-2 p-3 bg-indigo-50 dark:bg-indigo-500/10 rounded-lg border border-indigo-200 dark:border-indigo-500/20 overflow-x-auto">
                                <code class="text-xs text-indigo-700 dark:text-indigo-300 font-mono whitespace-nowrap" dir="ltr">https://example.com/s/abc123<span class="text-red-500 dark:text-red-300 font-bold">#secret_key_here</span></code>
                            </div>
                        </div>
                        <div class="hidden sm:flex items-center justify-center w-16 h-16 rounded-xl bg-indigo-100 dark:bg-indigo-500/20 shrink-0" aria-hidden="true">
                            <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                            </svg>
                        </div>
                    </div>

                    {{-- Arrow --}}
                    <div class="flex justify-center" aria-hidden="true">
                        <svg class="w-6 h-6 text-gray-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                        </svg>
                    </div>

                    {{-- Step 5: Recipient decrypts --}}
                    <div class="flex items-start gap-4">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full bg-violet-100 dark:bg-violet-500/20 text-violet-600 dark:text-violet-400 font-bold shrink-0">
                            5
                        </div>
                        <div class="flex-1 pt-1">
                            <h3 class="font-medium text-gray-900 dark:text-white mb-1">{{ __('messages.how_step5_title') }}</h3>
                            <p class="text-gray-600 dark:text-slate-400 text-sm">{{ __('messages.how_step5_desc') }}</p>
                        </div>
                        <div class="hidden sm:flex items-center justify-center w-16 h-16 rounded-xl bg-violet-100 dark:bg-violet-500/20 shrink-0" aria-hidden="true">
                            <svg class="w-8 h-8 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                    </div>
                </ol>
            </div>

            {{-- Key security features --}}
            <div class="mb-12">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
                    {{ __('messages.how_security_title') }}
                </h2>

                <div class="grid md:grid-cols-2 gap-4">
                    <div class="p-4 bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 rounded-xl">
                        <div class="flex items-center gap-3 mb-2">
                            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-300" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <h3 class="font-medium text-emerald-800 dark:text-emerald-300">{{ __('messages.how_feature1_title') }}</h3>
                        </div>
                        <p class="text-sm text-emerald-700 dark:text-emerald-300">{{ __('messages.how_feature1_desc') }}</p>
                    </div>

                    <div class="p-4 bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 rounded-xl">
                        <div class="flex items-center gap-3 mb-2">
                            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-300" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <h3 class="font-medium text-emerald-800 dark:text-emerald-300">{{ __('messages.how_feature2_title') }}</h3>
                        </div>
                        <p class="text-sm text-emerald-700 dark:text-emerald-300">{{ __('messages.how_feature2_desc') }}</p>
                    </div>

                    <div class="p-4 bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 rounded-xl">
                        <div class="flex items-center gap-3 mb-2">
                            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-300" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <h3 class="font-medium text-emerald-800 dark:text-emerald-300">{{ __('messages.how_feature3_title') }}</h3>
                        </div>
                        <p class="text-sm text-emerald-700 dark:text-emerald-300">{{ __('messages.how_feature3_desc') }}</p>
                    </div>

                    <div class="p-4 bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 rounded-xl">
                        <div class="flex items-center gap-3 mb-2">
                            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-300" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <h3 class="font-medium text-emerald-800 dark:text-emerald-300">{{ __('messages.how_feature4_title') }}</h3>
                        </div>
                        <p class="text-sm text-emerald-700 dark:text-emerald-300">{{ __('messages.how_feature4_desc') }}</p>
                    </div>
                </div>
            </div>

            {{-- FAQ --}}
            <div class="mb-12">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
                    {{ __('messages.faq_title') }}
                </h2>

                <dl class="space-y-4">
                    @foreach(range(1, 11) as $i)
                    <div class="p-4 bg-gray-50 dark:bg-slate-700/30 border border-gray-200 dark:border-slate-600/30 rounded-xl">
                        <dt class="font-medium text-gray-900 dark:text-white mb-2">{{ __("messages.faq_q{$i}") }}</dt>
                        <dd class="text-sm text-gray-600 dark:text-slate-400">{{ __("messages.faq_a{$i}") }}</dd>
                    </div>
                    @endforeach
                </dl>
            </div>

            {{-- CTA --}}
            <div class="text-center">
                <a href="{{ route('home') }}" class="inline-flex items-center justify-center py-3 px-6 bg-linear-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white font-medium rounded-xl shadow-lg shadow-violet-500/25 transition-all">
                    {{ __('messages.how_cta') }}
                </a>
                <p class="mt-4 text-sm text-gray-600 dark:text-slate-400">
                    <a href="{{ localized_route('use-cases') }}" class="hover:text-violet-600 dark:hover:text-violet-400 transition-colors">
                        {{ __('messages.how_see_use_cases') }}
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
