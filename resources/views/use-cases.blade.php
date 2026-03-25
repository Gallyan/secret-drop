@extends('layouts.app')

@section('title', __('messages.use_cases_title'))
@section('description', __('messages.use_cases_meta_description'))

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
            "name": "{{ __('messages.use_cases_title') }}"
        }
    ]
}
</script>
<script type="application/ld+json" nonce="@nonce">
{
    "@@context": "https://schema.org",
    "@@type": "WebPage",
    "name": "{{ __('messages.use_cases_title') }}",
    "description": "{{ __('messages.use_cases_meta_description') }}",
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
@endpush

@section('content')
<div class="flex-1 py-12 px-4 pb-8 overflow-x-hidden transition-colors">
    <div class="max-w-4xl mx-auto">
        <x-card class="p-8 lg:p-12">
            <x-page-header :title="__('messages.use_cases_title')" />

            {{-- Introduction --}}
            <p class="text-gray-600 dark:text-slate-400 mb-10 text-lg">
                {{ __('messages.use_cases_intro') }}
            </p>

            {{-- Use cases grid --}}
            <div class="space-y-6 mb-12">
                {{-- Use case 1: Password sharing --}}
                <div class="p-6 bg-linear-to-br from-violet-50 to-indigo-50 dark:from-violet-500/10 dark:to-indigo-500/10 border border-violet-200 dark:border-violet-500/20 rounded-xl">
                    <div class="flex items-start gap-4">
                        <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-violet-100 dark:bg-violet-500/20 shrink-0" aria-hidden="true">
                            <x-icon.key class="w-6 h-6 text-violet-600 dark:text-violet-400" />
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ __('messages.usecase1_title') }}</h2>
                            <p class="text-gray-600 dark:text-slate-400 mb-3">{{ __('messages.usecase1_desc') }}</p>
                            <p class="text-sm text-violet-700 dark:text-violet-300 italic">{{ __('messages.usecase1_example') }}</p>
                        </div>
                    </div>
                </div>

                {{-- Use case 2: Confidential documents --}}
                <div class="p-6 bg-linear-to-br from-emerald-50 to-teal-50 dark:from-emerald-500/10 dark:to-teal-500/10 border border-emerald-200 dark:border-emerald-500/20 rounded-xl">
                    <div class="flex items-start gap-4">
                        <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-emerald-100 dark:bg-emerald-500/20 shrink-0" aria-hidden="true">
                            <x-icon.document class="w-6 h-6 text-emerald-600 dark:text-emerald-300" />
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ __('messages.usecase2_title') }}</h2>
                            <p class="text-gray-600 dark:text-slate-400 mb-3">{{ __('messages.usecase2_desc') }}</p>
                            <p class="text-sm text-emerald-700 dark:text-emerald-300 italic">{{ __('messages.usecase2_example') }}</p>
                        </div>
                    </div>
                </div>

                {{-- Use case 3: API keys --}}
                <div class="p-6 bg-linear-to-br from-amber-50 to-orange-50 dark:from-amber-500/10 dark:to-orange-500/10 border border-amber-200 dark:border-amber-500/20 rounded-xl">
                    <div class="flex items-start gap-4">
                        <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-amber-100 dark:bg-amber-500/20 shrink-0" aria-hidden="true">
                            <x-icon.code class="w-6 h-6 text-amber-600 dark:text-amber-300" />
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ __('messages.usecase3_title') }}</h2>
                            <p class="text-gray-600 dark:text-slate-400 mb-3">{{ __('messages.usecase3_desc') }}</p>
                            <p class="text-sm text-amber-700 dark:text-amber-300 italic">{{ __('messages.usecase3_example') }}</p>
                        </div>
                    </div>
                </div>

                {{-- Use case 4: SSH keys --}}
                <div class="p-6 bg-linear-to-br from-indigo-50 to-purple-50 dark:from-indigo-500/10 dark:to-purple-500/10 border border-indigo-200 dark:border-indigo-500/20 rounded-xl">
                    <div class="flex items-start gap-4">
                        <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-indigo-100 dark:bg-indigo-500/20 shrink-0" aria-hidden="true">
                            <x-icon.terminal class="w-6 h-6 text-indigo-600 dark:text-indigo-300" />
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ __('messages.usecase4_title') }}</h2>
                            <p class="text-gray-600 dark:text-slate-400 mb-3">{{ __('messages.usecase4_desc') }}</p>
                            <p class="text-sm text-indigo-700 dark:text-indigo-300 italic">{{ __('messages.usecase4_example') }}</p>
                        </div>
                    </div>
                </div>

                {{-- Use case 5: WiFi passwords --}}
                <div class="p-6 bg-linear-to-br from-cyan-50 to-sky-50 dark:from-cyan-500/10 dark:to-sky-500/10 border border-cyan-200 dark:border-cyan-500/20 rounded-xl">
                    <div class="flex items-start gap-4">
                        <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-cyan-100 dark:bg-cyan-500/20 shrink-0" aria-hidden="true">
                            <x-icon.wifi class="w-6 h-6 text-cyan-600 dark:text-cyan-400" />
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ __('messages.usecase5_title') }}</h2>
                            <p class="text-gray-600 dark:text-slate-400 mb-3">{{ __('messages.usecase5_desc') }}</p>
                            <p class="text-sm text-cyan-700 dark:text-cyan-300 italic">{{ __('messages.usecase5_example') }}</p>
                        </div>
                    </div>
                </div>

                {{-- Use case 6: Personal info --}}
                <div class="p-6 bg-linear-to-br from-rose-50 to-pink-50 dark:from-rose-500/10 dark:to-pink-500/10 border border-rose-200 dark:border-rose-500/20 rounded-xl">
                    <div class="flex items-start gap-4">
                        <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-rose-100 dark:bg-rose-500/20 shrink-0" aria-hidden="true">
                            <x-icon.user class="w-6 h-6 text-rose-600 dark:text-rose-400" />
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ __('messages.usecase6_title') }}</h2>
                            <p class="text-gray-600 dark:text-slate-400 mb-3">{{ __('messages.usecase6_desc') }}</p>
                            <p class="text-sm text-rose-700 dark:text-rose-300 italic">{{ __('messages.usecase6_example') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Best practices --}}
            <div class="mb-12 p-6 bg-gray-50 dark:bg-slate-700/30 rounded-xl border border-gray-200 dark:border-slate-600/50">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('messages.use_cases_tips_title') }}</h2>
                <ul class="space-y-3">
                    <li class="flex items-start gap-3 text-gray-600 dark:text-slate-400">
                        <x-icon.check-circle-outline class="w-5 h-5 text-violet-500 dark:text-violet-400 mt-0.5 shrink-0" />
                        <span>{{ __('messages.use_cases_tip1') }}</span>
                    </li>
                    <li class="flex items-start gap-3 text-gray-600 dark:text-slate-400">
                        <x-icon.check-circle-outline class="w-5 h-5 text-violet-500 dark:text-violet-400 mt-0.5 shrink-0" />
                        <span>{{ __('messages.use_cases_tip2') }}</span>
                    </li>
                    <li class="flex items-start gap-3 text-gray-600 dark:text-slate-400">
                        <x-icon.check-circle-outline class="w-5 h-5 text-violet-500 dark:text-violet-400 mt-0.5 shrink-0" />
                        <span>{{ __('messages.use_cases_tip3') }}</span>
                    </li>
                </ul>
            </div>

            {{-- CTA --}}
            <div class="text-center">
                <x-btn-primary :href="route('home')">
                    {{ __('messages.use_cases_cta') }}
                </x-btn-primary>
                <p class="mt-4 text-sm text-gray-600 dark:text-slate-400">
                    <a href="{{ localized_route('how-it-works') }}" class="hover:text-violet-600 dark:hover:text-violet-400 transition-colors">
                        {{ __('messages.use_cases_see_how') }}
                    </a>
                </p>
            </div>
        </x-card>
    </div>
</div>
@endsection
