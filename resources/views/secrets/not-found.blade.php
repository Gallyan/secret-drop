@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="bg-slate-800/50 backdrop-blur-xl border border-slate-700/50 rounded-2xl shadow-2xl p-8 text-center">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-red-500/10 mb-6">
                <svg class="w-7 h-7 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>

            <h1 class="text-xl font-bold text-white mb-2">
                Secret introuvable
            </h1>
            <p class="text-slate-400 mb-6">
                Ce secret n'existe pas ou a peut-être été supprimé.
            </p>

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
