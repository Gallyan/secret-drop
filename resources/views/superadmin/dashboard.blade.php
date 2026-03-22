@extends('layouts.app')

@section('noindex', true)
@section('title', __('messages.superadmin_dashboard_title'))

@section('content')
<div class="min-h-screen bg-linear-to-br from-gray-50 via-gray-200 to-gray-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900 px-4 pt-0 pb-16 md:p-8 md:pb-16 transition-colors">
    <div class="max-w-7xl mx-auto">
        <div class="sm:mt-12 sticky top-0 z-40 -mx-4 md:-mx-8 px-4 md:px-8 py-3 sm:py-4 mb-4 bg-gray-50/95 dark:bg-slate-900/95 backdrop-blur-sm">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 sm:gap-4">
                <div>
                    <h1 class="text-xl sm:text-3xl font-bold text-gray-900 dark:text-white">{{ __('messages.superadmin_dashboard_title') }}</h1>
                    <p class="mt-0.5 sm:mt-1 text-sm sm:text-base text-gray-600 dark:text-slate-400">{{ __('messages.superadmin_dashboard_subtitle') }}</p>
                </div>
                <div class="flex items-center gap-4">
                    <form method="GET" class="flex items-center gap-2" x-data>
                        <select name="period" x-on:change="$el.form.submit()" aria-label="{{ __('messages.a11y_period_selector') }}" class="px-4 py-2 bg-white dark:bg-slate-800 border border-gray-300 dark:border-slate-600 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-amber-500/50">
                            <option value="7d" {{ $period === '7d' ? 'selected' : '' }}>{{ __('messages.period_7d') }}</option>
                            <option value="30d" {{ $period === '30d' ? 'selected' : '' }}>{{ __('messages.period_30d') }}</option>
                            <option value="90d" {{ $period === '90d' ? 'selected' : '' }}>{{ __('messages.period_90d') }}</option>
                            <option value="1y" {{ $period === '1y' ? 'selected' : '' }}>{{ __('messages.period_1y') }}</option>
                            <option value="all" {{ $period === 'all' ? 'selected' : '' }}>{{ __('messages.period_all') }}</option>
                        </select>
                    </form>
                    <form action="{{ route('superadmin.logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="px-4 py-2 text-gray-600 dark:text-slate-400 hover:text-red-600 dark:hover:text-red-400 transition">
                            {{ __('messages.admin_logout') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>

        @php $totals = $stats['totals']; @endphp
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-4 mb-8">
            <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-xl border border-gray-200 dark:border-slate-700/50 rounded-2xl p-6">
                <div class="text-3xl font-bold text-gray-900 dark:text-white">
                    {{ number_format(($totals['secrets_created_text'] ?? 0) + ($totals['secrets_created_file'] ?? 0)) }}
                </div>
                <div class="text-sm text-gray-600 dark:text-slate-400 mt-1">{{ __('messages.stat_secrets_created') }}</div>
            </div>
            <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-xl border border-gray-200 dark:border-slate-700/50 rounded-2xl p-6">
                <div class="text-3xl font-bold text-gray-900 dark:text-white">
                    {{ number_format($totals['secrets_read'] ?? 0) }}
                </div>
                <div class="text-sm text-gray-600 dark:text-slate-400 mt-1">{{ __('messages.stat_secrets_read') }}</div>
            </div>
            <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-xl border border-gray-200 dark:border-slate-700/50 rounded-2xl p-6">
                <div class="text-3xl font-bold text-gray-900 dark:text-white">
                    @php
                        if ($avgFirstReadDelay === null) {
                            echo '-';
                        } elseif ($avgFirstReadDelay < 60) {
                            echo number_format($avgFirstReadDelay, 0) . 's';
                        } elseif ($avgFirstReadDelay < 3600) {
                            echo number_format($avgFirstReadDelay / 60, 1) . 'm';
                        } elseif ($avgFirstReadDelay < 86400) {
                            echo number_format($avgFirstReadDelay / 3600, 1) . 'h';
                        } else {
                            echo number_format($avgFirstReadDelay / 86400, 1) . 'j';
                        }
                    @endphp
                </div>
                <div class="text-sm text-gray-600 dark:text-slate-400 mt-1">{{ __('messages.stat_avg_first_read') }}</div>
            </div>
            <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-xl border border-gray-200 dark:border-slate-700/50 rounded-2xl p-6">
                <div class="text-3xl font-bold text-gray-900 dark:text-white">
                    {{ number_format($totals['secrets_created_file'] ?? 0) }}
                </div>
                <div class="text-sm text-gray-600 dark:text-slate-400 mt-1">{{ __('messages.stat_files_shared') }}</div>
            </div>
            <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-xl border border-gray-200 dark:border-slate-700/50 rounded-2xl p-6">
                <div class="text-3xl font-bold text-gray-900 dark:text-white">
                    @php
                        $bytes = $totals['total_file_size_bytes'] ?? 0;
                        if ($bytes >= 1073741824) {
                            echo number_format($bytes / 1073741824, 1) . ' ' . __('messages.unit_gigabytes');
                        } elseif ($bytes >= 1048576) {
                            echo number_format($bytes / 1048576, 1) . ' ' . __('messages.unit_megabytes');
                        } elseif ($bytes >= 1024) {
                            echo number_format($bytes / 1024, 1) . ' ' . __('messages.unit_kilobytes');
                        } else {
                            echo $bytes . ' ' . __('messages.unit_bytes');
                        }
                    @endphp
                </div>
                <div class="text-sm text-gray-600 dark:text-slate-400 mt-1">{{ __('messages.stat_volume') }}</div>
            </div>
            <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-xl border border-gray-200 dark:border-slate-700/50 rounded-2xl p-6">
                <div class="text-3xl font-bold text-gray-900 dark:text-white">
                    @php
                        $bytes = $currentDiskUsage;
                        if ($bytes >= 1073741824) {
                            echo number_format($bytes / 1073741824, 1) . ' ' . __('messages.unit_gigabytes');
                        } elseif ($bytes >= 1048576) {
                            echo number_format($bytes / 1048576, 1) . ' ' . __('messages.unit_megabytes');
                        } elseif ($bytes >= 1024) {
                            echo number_format($bytes / 1024, 1) . ' ' . __('messages.unit_kilobytes');
                        } else {
                            echo $bytes . ' ' . __('messages.unit_bytes');
                        }
                    @endphp
                </div>
                <div class="text-sm text-gray-600 dark:text-slate-400 mt-1">{{ __('messages.stat_current_disk_usage') }}</div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-xl border border-gray-200 dark:border-slate-700/50 rounded-2xl p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('messages.chart_secrets_created') }}</h2>
                <canvas id="secretsCreatedChart" height="200" role="img" aria-label="{{ __('messages.chart_secrets_created') }}"></canvas>
            </div>
            <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-xl border border-gray-200 dark:border-slate-700/50 rounded-2xl p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('messages.chart_secrets_read') }}</h2>
                <canvas id="secretsReadChart" height="200" role="img" aria-label="{{ __('messages.chart_secrets_read') }}"></canvas>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-xl border border-gray-200 dark:border-slate-700/50 rounded-2xl p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('messages.chart_secret_types') }}</h2>
                <canvas id="secretTypesChart" height="200" role="img" aria-label="{{ __('messages.chart_secret_types') }}"></canvas>
            </div>
            <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-xl border border-gray-200 dark:border-slate-700/50 rounded-2xl p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('messages.chart_secret_options') }}</h2>
                <canvas id="secretOptionsChart" height="200" role="img" aria-label="{{ __('messages.chart_secret_options') }}"></canvas>
            </div>
            <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-xl border border-gray-200 dark:border-slate-700/50 rounded-2xl p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('messages.chart_secret_outcomes') }}</h2>
                <canvas id="secretOutcomesChart" height="200" role="img" aria-label="{{ __('messages.chart_secret_outcomes') }}"></canvas>
            </div>
        </div>

        <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-xl border border-gray-200 dark:border-slate-700/50 rounded-2xl p-6 mb-8">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('messages.chart_admin_activity') }}</h2>
            <canvas id="adminActivityChart" height="150" role="img" aria-label="{{ __('messages.chart_admin_activity') }}"></canvas>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-xl border border-gray-200 dark:border-slate-700/50 rounded-2xl p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('messages.chart_heatmap_created') }}</h2>
                <div id="heatmapCreated" class="heatmap-container"></div>
            </div>
            <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-xl border border-gray-200 dark:border-slate-700/50 rounded-2xl p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('messages.chart_heatmap_read') }}</h2>
                <div id="heatmapRead" class="heatmap-container"></div>
            </div>
        </div>

        {{-- Pageviews section --}}
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mt-10 mb-6">{{ __('messages.stat_pageviews_title') }}</h2>

        {{-- Pageview KPIs --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
            <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-xl border border-gray-200 dark:border-slate-700/50 rounded-2xl p-6">
                <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($pageviews['total_human']) }}</div>
                <div class="text-sm text-gray-600 dark:text-slate-400 mt-1">{{ __('messages.stat_visitors') }}</div>
            </div>
            <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-xl border border-gray-200 dark:border-slate-700/50 rounded-2xl p-6">
                <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($pageviews['total_bot']) }}</div>
                <div class="text-sm text-gray-600 dark:text-slate-400 mt-1">{{ __('messages.stat_bots') }}</div>
            </div>
            <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-xl border border-gray-200 dark:border-slate-700/50 rounded-2xl p-6">
                <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ count($pageviews['by_country']) }}</div>
                <div class="text-sm text-gray-600 dark:text-slate-400 mt-1">{{ __('messages.stat_countries') }}</div>
            </div>
            <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-xl border border-gray-200 dark:border-slate-700/50 rounded-2xl p-6">
                @php
                    $totalViews = $pageviews['total_human'];
                    $pvDates = array_keys($pageviews['daily']);
                    $createdInPeriod = 0;
                    $metricsData = $stats['metrics'] ?? [];
                    foreach ($pvDates as $date) {
                        $createdInPeriod += ($metricsData['secrets_created_text'][$date] ?? 0)
                            + ($metricsData['secrets_created_file'][$date] ?? 0);
                    }
                    $conversionRate = $totalViews > 0 ? ($createdInPeriod / $totalViews) * 100 : 0;
                @endphp
                <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ $totalViews > 0 ? number_format($conversionRate, 1) . '%' : '-' }}</div>
                <div class="text-sm text-gray-600 dark:text-slate-400 mt-1">{{ __('messages.stat_conversion') }}</div>
            </div>
        </div>

        {{-- Daily visits chart --}}
        <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-xl border border-gray-200 dark:border-slate-700/50 rounded-2xl p-6 mb-8">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('messages.stat_pageviews_title') }}</h3>
            <canvas id="pageviewsChart" height="80" role="img" aria-label="{{ __('messages.stat_pageviews_title') }}"></canvas>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            {{-- By page --}}
            <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-xl border border-gray-200 dark:border-slate-700/50 rounded-2xl p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('messages.stat_by_page') }}</h3>
                <div class="space-y-2">
                    @foreach($pageviews['by_page'] as $page => $counts)
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-700 dark:text-slate-300 font-mono">{{ $page }}</span>
                            <div class="flex items-center gap-3">
                                <span class="text-gray-900 dark:text-white font-medium">{{ number_format($counts['human']) }}</span>
                                <span class="text-gray-400 dark:text-slate-500 text-xs">{{ number_format($counts['bot']) }} bot</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- By country (top 15) --}}
            <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-xl border border-gray-200 dark:border-slate-700/50 rounded-2xl p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('messages.stat_by_country') }}</h3>
                <div class="space-y-2">
                    @foreach(array_slice($pageviews['by_country'], 0, 15, true) as $country => $count)
                        @php
                            $pct = $pageviews['total_human'] > 0 ? ($count / $pageviews['total_human']) * 100 : 0;
                        @endphp
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-700 dark:text-slate-300 font-medium">{{ $country }}</span>
                            <div class="flex items-center gap-2">
                                <div class="w-20 h-1.5 bg-gray-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                    <div class="h-full bg-violet-500 rounded-full" style="width: {{ min($pct, 100) }}%"></div>
                                </div>
                                <span class="text-gray-900 dark:text-white font-medium w-10 text-right">{{ number_format($count) }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- By hour --}}
            <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-xl border border-gray-200 dark:border-slate-700/50 rounded-2xl p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('messages.stat_by_hour') }}</h3>
                @php
                    $maxHour = max(1, max($pageviews['by_hour']));
                    $barMaxPx = 96;
                @endphp
                <div class="flex items-end gap-0.5" style="height: {{ $barMaxPx }}px">
                    @foreach($pageviews['by_hour'] as $hour => $count)
                        @php $heightPx = max(2, (int) (($count / $maxHour) * $barMaxPx)); @endphp
                        <div
                            class="flex-1 bg-violet-500/80 dark:bg-violet-400/80 rounded-t-sm transition-colors hover:bg-violet-600 dark:hover:bg-violet-300"
                            style="height: {{ $heightPx }}px"
                            title="{{ $hour }}h: {{ $count }}"
                        ></div>
                    @endforeach
                </div>
                <div class="flex gap-0.5 mt-1">
                    @foreach($pageviews['by_hour'] as $hour => $count)
                        <div class="flex-1 text-center text-[8px] text-gray-400 dark:text-slate-500">
                            @if($hour % 6 === 0){{ $hour }}@endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<script nonce="@nonce">
    window.superAdminData = {
        stats: @json($stats),
        heatmapCreated: @json($heatmapCreated),
        heatmapRead: @json($heatmapRead),
        pageviewsDaily: @json($pageviews['daily']),
        translations: {
            stat_text: '{{ __('messages.stat_text') }}',
            stat_file: '{{ __('messages.stat_file') }}',
            stat_reads: '{{ __('messages.stat_reads') }}',
            stat_passphrase: '{{ __('messages.stat_passphrase') }}',
            stat_max_views: '{{ __('messages.stat_max_views') }}',
            stat_split_mode: '{{ __('messages.stat_split_mode') }}',
            stat_read: '{{ __('messages.stat_read') }}',
            stat_expired_unread: '{{ __('messages.stat_expired_unread') }}',
            stat_revoked: '{{ __('messages.stat_revoked') }}',
            stat_max_reached: '{{ __('messages.stat_max_reached') }}',
            stat_magic_links_requested: '{{ __('messages.stat_magic_links_requested') }}',
            stat_magic_links_used: '{{ __('messages.stat_magic_links_used') }}',
            stat_secrets_extended: '{{ __('messages.stat_secrets_extended') }}',
            days: [
                '{{ __('messages.day_sunday') }}',
                '{{ __('messages.day_monday') }}',
                '{{ __('messages.day_tuesday') }}',
                '{{ __('messages.day_wednesday') }}',
                '{{ __('messages.day_thursday') }}',
                '{{ __('messages.day_friday') }}',
                '{{ __('messages.day_saturday') }}'
            ],
            stat_visitors: '{{ __('messages.stat_visitors') }}',
            stat_bots: '{{ __('messages.stat_bots') }}'
        }
    };
</script>
@vite('resources/js/superadmin-dashboard.js')
@endsection
