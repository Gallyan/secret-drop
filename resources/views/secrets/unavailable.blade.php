@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 via-gray-100 to-gray-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900 flex items-center justify-center p-4 transition-colors">
    <div class="w-full max-w-md">
        <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-xl border border-gray-200 dark:border-slate-700/50 rounded-2xl shadow-2xl p-8 text-center transition-colors">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-amber-100 dark:bg-amber-500/10 mb-6 transition-colors">
                <svg class="w-7 h-7 text-amber-500 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>

            <h1 class="text-xl font-bold text-gray-900 dark:text-white mb-2 transition-colors">
                Secret indisponible
            </h1>

            @switch($reason)
                @case('expired')
                    <p class="text-gray-600 dark:text-slate-400 mb-6 transition-colors">
                        Ce secret a expiré et n'est plus accessible.
                    </p>
                    @break

                @case('revoked')
                    <p class="text-gray-600 dark:text-slate-400 mb-6 transition-colors">
                        Ce secret a été révoqué par son créateur.
                    </p>
                    @break

                @case('max_views')
                    <p class="text-gray-600 dark:text-slate-400 mb-6 transition-colors">
                        Ce secret a atteint son nombre maximum de lectures et n'est plus accessible.
                    </p>
                    @break

                @default
                    <p class="text-gray-600 dark:text-slate-400 mb-6 transition-colors">
                        Ce secret n'est plus accessible.
                    </p>
            @endswitch

            <a
                href="{{ route('secrets.create') }}"
                class="inline-block py-2.5 px-6 bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white font-medium rounded-xl shadow-lg shadow-violet-500/25 transition-all"
            >
                Créer un nouveau secret
            </a>
        </div>
    </div>
</div>
@endsection
