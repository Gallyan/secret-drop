@extends('layouts.app')

@section('noindex', true)
@section('title', __('messages.admin_invalid_link_title'))

@section('content')
<div class="flex-1 flex items-center justify-center p-4 transition-colors">
    <div class="w-full max-w-md">
        <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-xl border border-gray-200 dark:border-slate-700/50 rounded-2xl shadow-2xl p-8 transition-colors text-center">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-amber-100 dark:bg-amber-500/10 mb-4" aria-hidden="true">
                <svg class="w-7 h-7 text-amber-600 dark:text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>

            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">{{ __('messages.admin_invalid_link_title') }}</h1>
            <p class="text-gray-600 dark:text-slate-400 mb-6">{{ __('messages.admin_invalid_link_description') }}</p>

            <a href="{{ route('admin.index') }}" class="inline-flex items-center justify-center w-full py-3 px-4 bg-linear-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white font-medium rounded-xl shadow-lg shadow-violet-500/25 transition-all">
                {{ __('messages.btn_request_new_link') }}
            </a>

            <div class="mt-6">
                <a href="{{ route('home') }}" class="text-sm text-gray-500 dark:text-slate-400 hover:text-violet-600 dark:hover:text-violet-400 transition">
                    {{ __('messages.admin_back_home') }}
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
