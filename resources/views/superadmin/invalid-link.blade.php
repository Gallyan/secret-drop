@extends('layouts.app')

@section('noindex', true)
@section('title', __('messages.admin_invalid_link_title'))

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 via-gray-100 to-gray-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900 flex items-center justify-center p-4 transition-colors">
    <div class="w-full max-w-md">
        <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-xl border border-gray-200 dark:border-slate-700/50 rounded-2xl shadow-2xl p-8 transition-colors text-center">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-red-100 dark:bg-red-500/10 mb-4" aria-hidden="true">
                <svg class="w-7 h-7 text-red-600 dark:text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>

            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">{{ __('messages.admin_invalid_link_title') }}</h1>
            <p class="text-gray-600 dark:text-slate-400 mb-6">{{ __('messages.admin_invalid_link_description') }}</p>

            <a href="{{ route('superadmin.index') }}" class="inline-flex items-center justify-center w-full py-3 px-4 bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-500 hover:to-orange-500 text-white font-medium rounded-xl shadow-lg shadow-amber-500/25 transition-all">
                {{ __('messages.btn_retry') }}
            </a>

            <div class="mt-6">
                <a href="/" class="text-sm text-gray-500 dark:text-slate-400 hover:text-amber-600 dark:hover:text-amber-400 transition">
                    {{ __('messages.admin_back_home') }}
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
