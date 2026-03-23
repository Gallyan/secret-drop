@props(['id', 'text', 'position' => 'start'])

<div class="relative" x-data="{ showHint: false }">
    <button
        type="button"
        @mouseenter="showHint = true"
        @mouseleave="showHint = false"
        @focus="showHint = true"
        @blur="showHint = false"
        aria-describedby="{{ $id }}"
        class="text-gray-500 dark:text-slate-400 hover:text-violet-500 dark:hover:text-violet-400 transition cursor-pointer"
    >
        <x-icon.info />
        <span class="sr-only">{{ $text }}</span>
    </button>
    <div
        x-show="showHint"
        x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-1"
        id="{{ $id }}"
        role="tooltip"
        class="absolute z-10 bottom-full {{ $position === 'start' ? 'start-0' : 'end-0' }} mb-2 w-72 px-3 py-2 text-xs text-white bg-gray-900 dark:bg-slate-700 rounded-lg shadow-lg"
    >
        {{ $text }}
        <div class="absolute top-full {{ $position === 'start' ? 'start-3' : 'end-3' }} border-4 border-transparent border-t-gray-900 dark:border-t-slate-700"></div>
    </div>
</div>
