@extends('layouts.app')

@section('noindex', true)
@section('title', __('messages.admin_link_sent_title'))

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 via-gray-100 to-gray-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900 flex items-center justify-center p-4 transition-colors">
    <div class="w-full max-w-md">
        <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-xl border border-gray-200 dark:border-slate-700/50 rounded-2xl shadow-2xl p-8 transition-colors text-center">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-emerald-100 dark:bg-emerald-500/10 mb-4" aria-hidden="true">
                <svg class="w-7 h-7 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
            </div>

            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">{{ __('messages.admin_link_sent_title') }}</h1>
            <p class="text-gray-600 dark:text-slate-400 mb-6">{{ __('messages.admin_link_sent_description') }}</p>

            <div class="p-4 bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 rounded-xl">
                <p class="text-sm text-amber-700 dark:text-amber-400">{{ __('messages.admin_link_sent_warning') }}</p>
            </div>

            <div class="mt-6">
                <a href="/" class="text-sm text-gray-500 dark:text-slate-400 hover:text-violet-600 dark:hover:text-violet-400 transition">
                    {{ __('messages.admin_back_home') }}
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
