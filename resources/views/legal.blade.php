@extends('layouts.app')

@section('noindex', true)
@section('title', __('messages.legal_title'))

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 via-gray-100 to-gray-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900 py-12 px-4 transition-colors">
    <div class="max-w-3xl mx-auto">
        <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-xl border border-gray-200 dark:border-slate-700/50 rounded-2xl shadow-2xl p-8 lg:p-12 transition-colors">
            {{-- Header --}}
            <div class="flex items-center gap-4 mb-8">
                <a href="{{ route('home') }}" class="p-2 -ms-2 text-gray-400 hover:text-gray-600 dark:text-slate-500 dark:hover:text-slate-300 transition-colors" aria-label="{{ __('messages.a11y_back') }}">
                    <svg class="w-5 h-5 rtl:rotate-180" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight transition-colors">
                    {{ __('messages.legal_title') }}
                </h1>
            </div>

            <div class="prose prose-gray dark:prose-invert max-w-none">
                {{-- Editor --}}
                <section class="mb-8">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
                        {{ __('messages.legal_editor_title') }}
                    </h2>
                    <p class="text-gray-600 dark:text-slate-400">
                        {{ __('messages.legal_editor_text', ['name' => config('legal.editor_name', 'Secret Drop')]) }}
                    </p>
                </section>

                {{-- Hosting --}}
                <section class="mb-8">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
                        {{ __('messages.legal_hosting_title') }}
                    </h2>
                    <p class="text-gray-600 dark:text-slate-400 mb-2">
                        {{ __('messages.legal_hosting_text') }}
                    </p>
                    <div class="text-gray-600 dark:text-slate-400 space-y-1 rtl:text-right" dir="ltr">
                        <p>{{ config('legal.hosting.name') }}</p>
                        <p>{{ config('legal.hosting.address') }}</p>
                        <p>{{ __('messages.legal_hosting_phone') }} {{ config('legal.hosting.phone') }}</p>
                    </div>
                </section>

                {{-- Data Protection --}}
                <section class="mb-8">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
                        {{ __('messages.legal_data_title') }}
                    </h2>
                    <p class="text-gray-600 dark:text-slate-400 mb-4">
                        {{ __('messages.legal_data_text') }}
                    </p>

                    <div class="grid md:grid-cols-2 gap-6">
                        <div class="p-4 bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 rounded-xl">
                            <h3 class="font-medium text-emerald-800 dark:text-emerald-400 mb-2">
                                {{ __('messages.legal_data_stored') }}
                            </h3>
                            <ul class="text-sm text-emerald-700 dark:text-emerald-300 space-y-1">
                                <li class="flex items-start gap-2">
                                    <svg class="w-4 h-4 mt-0.5 shrink-0" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    {{ __('messages.legal_data_item_ciphertext') }}
                                </li>
                                <li class="flex items-start gap-2">
                                    <svg class="w-4 h-4 mt-0.5 shrink-0" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    {{ __('messages.legal_data_item_metadata') }}
                                </li>
                                <li class="flex items-start gap-2">
                                    <svg class="w-4 h-4 mt-0.5 shrink-0" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    {{ __('messages.legal_data_item_email') }}
                                </li>
                            </ul>
                        </div>

                        <div class="p-4 bg-violet-50 dark:bg-violet-500/10 border border-violet-200 dark:border-violet-500/20 rounded-xl">
                            <h3 class="font-medium text-violet-800 dark:text-violet-400 mb-2">
                                {{ __('messages.legal_data_not_stored') }}
                            </h3>
                            <ul class="text-sm text-violet-700 dark:text-violet-300 space-y-1">
                                <li class="flex items-start gap-2">
                                    <svg class="w-4 h-4 mt-0.5 shrink-0" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                    {{ __('messages.legal_data_not_item_plaintext') }}
                                </li>
                                <li class="flex items-start gap-2">
                                    <svg class="w-4 h-4 mt-0.5 shrink-0" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                    {{ __('messages.legal_data_not_item_key') }}
                                </li>
                            </ul>
                        </div>
                    </div>
                </section>

                {{-- Cookies --}}
                <section class="mb-8">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
                        {{ __('messages.legal_cookies_title') }}
                    </h2>
                    <p class="text-gray-600 dark:text-slate-400 mb-2">
                        {{ __('messages.legal_cookies_text') }}
                    </p>
                    <p class="text-gray-600 dark:text-slate-400">
                        {{ __('messages.legal_cookies_cnil') }}
                    </p>
                </section>

                {{-- Contact --}}
                <section>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
                        {{ __('messages.legal_contact_title') }}
                    </h2>
                    @php
                        $email = config('legal.contact_email', config('mail.from.address'));
                        [$emailUser, $emailDomain] = explode('@', $email);
                    @endphp
                    <p class="text-gray-600 dark:text-slate-400">
                        {{ __('messages.legal_contact_prefix') }} <a href="{{ route('contact.email') }}" class="protected-email text-violet-600 dark:text-violet-400 hover:underline" dir="ltr" data-user="{{ $emailUser }}" data-domain="{{ $emailDomain }}">[e-mail]</a>.
                    </p>
                </section>
            </div>
        </div>
    </div>
</div>
@endsection
