@extends('layouts.app')

@section('noindex', true)
@section('title', __('messages.superadmin_title'))

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 via-gray-200 to-gray-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900 flex items-center justify-center p-4 transition-colors">
    <div class="w-full max-w-md">
        <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-xl border border-gray-200 dark:border-slate-700/50 rounded-2xl shadow-2xl p-8 transition-colors">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 shadow-lg shadow-amber-500/25 mb-4" aria-hidden="true">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('messages.superadmin_title') }}</h1>
                @if(__('messages.superadmin_description'))
                    <p class="mt-2 text-gray-600 dark:text-slate-400">{{ __('messages.superadmin_description') }}</p>
                @endif
            </div>

            <form action="{{ route('superadmin.requestAccess') }}" method="POST" class="space-y-6" autocomplete="off">
                @csrf

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">
                        {{ __('messages.your_email') }}
                    </label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        required
                        aria-required="true"
                        @if(!session('captcha_required')) autofocus @endif
                        autocomplete="off"
                        value="{{ old('email') }}"
                        placeholder="{{ __('messages.admin_email_placeholder') }}"
                        @error('email') aria-describedby="email-error" aria-invalid="true" @enderror
                        class="w-full px-4 py-3 bg-gray-50 dark:bg-slate-900/50 border border-gray-300 dark:border-slate-600/50 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500/50 transition"
                    >
                    @error('email')
                        <p id="email-error" class="mt-2 text-sm text-red-600 dark:text-red-300" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                @if(session('captcha_required'))
                    <div class="p-4 bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 rounded-xl">
                        <p class="text-sm text-amber-700 dark:text-amber-300 mb-3">
                            {{ __('messages.rate_limit_exceeded') }}
                        </p>
                        <label for="captcha_answer" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">
                            {{ __('messages.captcha_label') }}
                        </label>
                        <div class="flex items-center gap-3">
                            <div class="px-4 py-2 bg-white dark:bg-slate-800 border border-gray-300 dark:border-slate-600 rounded-lg font-mono text-lg text-gray-900 dark:text-white">
                                {{ session('captcha_challenge') }} = ?
                            </div>
                            <input
                                id="captcha_answer"
                                name="captcha_answer"
                                type="number"
                                required
                                aria-required="true"
                                autofocus
                                placeholder="{{ __('messages.captcha_placeholder') }}"
                                @error('captcha') aria-describedby="captcha-error" aria-invalid="true" @enderror
                                class="flex-1 px-4 py-2 bg-gray-50 dark:bg-slate-900/50 border border-gray-300 dark:border-slate-600/50 rounded-lg text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500/50 transition"
                            >
                        </div>
                        <input type="hidden" name="captcha_token" value="{{ session('captcha_token') }}">
                        @error('captcha')
                            <p id="captcha-error" class="mt-2 text-sm text-red-600 dark:text-red-300" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                <button
                    type="submit"
                    class="w-full py-3 px-4 bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-500 hover:to-orange-500 text-white font-medium rounded-xl shadow-lg shadow-amber-500/25 transition-all"
                >
                    {{ __('messages.admin_send_link') }}
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="{{ route('home') }}" class="text-sm text-gray-500 dark:text-slate-400 hover:text-amber-600 dark:hover:text-amber-400 transition">
                    {{ __('messages.admin_back_home') }}
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
