@extends('layouts.app')

@section('title', __('messages.faq_meta_title'))
@section('description', __('messages.faq_meta_description'))

@push('schema')
    @php
        $breadcrumb = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => config_string('app.name'),
                    'item' => route('home'),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => __('messages.faq_title'),
                    'item' => localized_route('faq'),
                ],
            ],
        ];

        $faqPage = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(fn (int $i): array => [
                '@type' => 'Question',
                'name' => __("messages.faq_q{$i}"),
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => strip_tags(__("messages.faq_a{$i}", [
                        'manage_link' => '« '.__('messages.footer_manage').' »',
                        'minutes' => config('secrets.magic_link_ttl'),
                    ])),
                ],
            ], range(1, 12)),
        ];

        $webPage = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => __('messages.faq_title'),
            'description' => __('messages.faq_meta_description'),
            'datePublished' => '2026-03-01',
            'dateModified' => date('Y-m-d', filemtime(resource_path('views/faq.blade.php'))),
            'isPartOf' => [
                '@type' => 'WebSite',
                'name' => config_string('app.name'),
                'url' => url('/'),
            ],
            'speakable' => [
                '@type' => 'SpeakableSpecification',
                'cssSelector' => ['h1', 'dt', 'dd'],
            ],
        ];
    @endphp
<script type="application/ld+json" nonce="@nonce">{!! json_ld($breadcrumb) !!}</script>
<script type="application/ld+json" nonce="@nonce">{!! json_ld($faqPage) !!}</script>
<script type="application/ld+json" nonce="@nonce">{!! json_ld($webPage) !!}</script>
@endpush

@section('content')
<div class="flex-1 py-12 px-4 pb-8 overflow-x-hidden transition-colors">
    <div class="max-w-4xl mx-auto">
        <x-card class="p-8 lg:p-12">
            <x-page-header :title="__('messages.faq_title')" />

            <p class="text-gray-600 dark:text-slate-400 mb-10 text-lg">
                {{ __('messages.faq_intro') }}
            </p>

            <dl class="space-y-4 mb-12">
                @foreach(range(1, 12) as $i)
                <div class="p-4 bg-gray-50 dark:bg-slate-700/30 border border-gray-200 dark:border-slate-600/30 rounded-xl">
                    <dt class="font-medium text-gray-900 dark:text-white mb-2">{{ __("messages.faq_q{$i}") }}</dt>
                    <dd class="text-sm text-gray-600 dark:text-slate-400">{!! __("messages.faq_a{$i}", [
                        'manage_link' => '<a href="' . route('admin.index') . '" class="text-violet-600 dark:text-violet-400 hover:underline">« ' . e(__('messages.footer_manage')) . ' »</a>',
                        'minutes' => config('secrets.magic_link_ttl'),
                    ]) !!}</dd>
                </div>
                @endforeach
            </dl>

            {{-- CTA --}}
            <div class="text-center">
                <x-btn-primary :href="route('home')">
                    {{ __('messages.faq_cta') }}
                </x-btn-primary>
            </div>

        </x-card>
    </div>
</div>
@endsection
