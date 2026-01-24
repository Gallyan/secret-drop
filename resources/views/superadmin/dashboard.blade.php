@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 via-gray-100 to-gray-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900 p-4 md:p-8 transition-colors">
    <div class="max-w-7xl mx-auto">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ __('messages.superadmin_dashboard_title') }}</h1>
                <p class="mt-1 text-gray-600 dark:text-slate-400">{{ __('messages.superadmin_dashboard_subtitle') }}</p>
            </div>
            <div class="flex items-center gap-4">
                <form method="GET" class="flex items-center gap-2">
                    <select name="period" onchange="this.form.submit()" class="px-4 py-2 bg-white dark:bg-slate-800 border border-gray-300 dark:border-slate-600 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-amber-500/50">
                        <option value="7d" {{ $period === '7d' ? 'selected' : '' }}>{{ __('messages.period_7d') }}</option>
                        <option value="30d" {{ $period === '30d' ? 'selected' : '' }}>{{ __('messages.period_30d') }}</option>
                        <option value="90d" {{ $period === '90d' ? 'selected' : '' }}>{{ __('messages.period_90d') }}</option>
                        <option value="1y" {{ $period === '1y' ? 'selected' : '' }}>{{ __('messages.period_1y') }}</option>
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

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-xl border border-gray-200 dark:border-slate-700/50 rounded-2xl p-6">
                <div class="text-3xl font-bold text-gray-900 dark:text-white">
                    {{ number_format(($allTime['secrets_created_text'] ?? 0) + ($allTime['secrets_created_file'] ?? 0)) }}
                </div>
                <div class="text-sm text-gray-600 dark:text-slate-400 mt-1">{{ __('messages.stat_total_secrets') }}</div>
            </div>
            <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-xl border border-gray-200 dark:border-slate-700/50 rounded-2xl p-6">
                <div class="text-3xl font-bold text-gray-900 dark:text-white">
                    {{ number_format($allTime['secrets_read'] ?? 0) }}
                </div>
                <div class="text-sm text-gray-600 dark:text-slate-400 mt-1">{{ __('messages.stat_total_reads') }}</div>
            </div>
            <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-xl border border-gray-200 dark:border-slate-700/50 rounded-2xl p-6">
                <div class="text-3xl font-bold text-gray-900 dark:text-white">
                    {{ number_format($allTime['secrets_created_file'] ?? 0) }}
                </div>
                <div class="text-sm text-gray-600 dark:text-slate-400 mt-1">{{ __('messages.stat_total_files') }}</div>
            </div>
            <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-xl border border-gray-200 dark:border-slate-700/50 rounded-2xl p-6">
                <div class="text-3xl font-bold text-gray-900 dark:text-white">
                    @php
                        $bytes = $allTime['total_file_size_bytes'] ?? 0;
                        if ($bytes >= 1073741824) {
                            echo number_format($bytes / 1073741824, 1) . ' Go';
                        } elseif ($bytes >= 1048576) {
                            echo number_format($bytes / 1048576, 1) . ' Mo';
                        } elseif ($bytes >= 1024) {
                            echo number_format($bytes / 1024, 1) . ' Ko';
                        } else {
                            echo $bytes . ' o';
                        }
                    @endphp
                </div>
                <div class="text-sm text-gray-600 dark:text-slate-400 mt-1">{{ __('messages.stat_total_volume') }}</div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-xl border border-gray-200 dark:border-slate-700/50 rounded-2xl p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('messages.chart_secrets_created') }}</h2>
                <canvas id="secretsCreatedChart" height="200"></canvas>
            </div>
            <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-xl border border-gray-200 dark:border-slate-700/50 rounded-2xl p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('messages.chart_secrets_read') }}</h2>
                <canvas id="secretsReadChart" height="200"></canvas>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-xl border border-gray-200 dark:border-slate-700/50 rounded-2xl p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('messages.chart_secret_types') }}</h2>
                <canvas id="secretTypesChart" height="200"></canvas>
            </div>
            <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-xl border border-gray-200 dark:border-slate-700/50 rounded-2xl p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('messages.chart_secret_options') }}</h2>
                <canvas id="secretOptionsChart" height="200"></canvas>
            </div>
            <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-xl border border-gray-200 dark:border-slate-700/50 rounded-2xl p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('messages.chart_secret_outcomes') }}</h2>
                <canvas id="secretOutcomesChart" height="200"></canvas>
            </div>
        </div>

        <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-xl border border-gray-200 dark:border-slate-700/50 rounded-2xl p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('messages.chart_admin_activity') }}</h2>
            <canvas id="adminActivityChart" height="150"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script @nonce>
    const isDark = document.documentElement.classList.contains('dark');
    const gridColor = isDark ? 'rgba(148, 163, 184, 0.1)' : 'rgba(0, 0, 0, 0.1)';
    const textColor = isDark ? '#94a3b8' : '#6b7280';

    Chart.defaults.color = textColor;
    Chart.defaults.borderColor = gridColor;

    const stats = @json($stats);
    const totals = @json($stats['totals']);
    const allTime = @json($allTime);

    function generateDateLabels(startDate, days) {
        const labels = [];
        const start = new Date(startDate);
        for (let i = 0; i <= days; i++) {
            const date = new Date(start);
            date.setDate(start.getDate() + i);
            labels.push(date.toISOString().split('T')[0]);
        }
        return labels;
    }

    const dateLabels = generateDateLabels(stats.start_date, stats.days);

    function getDataForMetric(metric) {
        const data = stats.metrics[metric] || {};
        return dateLabels.map(date => data[date] || 0);
    }

    new Chart(document.getElementById('secretsCreatedChart'), {
        type: 'line',
        data: {
            labels: dateLabels,
            datasets: [
                {
                    label: '{{ __('messages.stat_text') }}',
                    data: getDataForMetric('secrets_created_text'),
                    borderColor: '#8b5cf6',
                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
                    fill: true,
                    tension: 0.3
                },
                {
                    label: '{{ __('messages.stat_file') }}',
                    data: getDataForMetric('secrets_created_file'),
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    fill: true,
                    tension: 0.3
                }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } },
            scales: {
                x: { display: true, grid: { display: false } },
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });

    new Chart(document.getElementById('secretsReadChart'), {
        type: 'line',
        data: {
            labels: dateLabels,
            datasets: [{
                label: '{{ __('messages.stat_reads') }}',
                data: getDataForMetric('secrets_read'),
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                x: { display: true, grid: { display: false } },
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });

    new Chart(document.getElementById('secretTypesChart'), {
        type: 'doughnut',
        data: {
            labels: ['{{ __('messages.stat_text') }}', '{{ __('messages.stat_file') }}'],
            datasets: [{
                data: [allTime.secrets_created_text || 0, allTime.secrets_created_file || 0],
                backgroundColor: ['#8b5cf6', '#f59e0b']
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } }
        }
    });

    new Chart(document.getElementById('secretOptionsChart'), {
        type: 'bar',
        data: {
            labels: ['{{ __('messages.stat_passphrase') }}', '{{ __('messages.stat_single_use') }}', '{{ __('messages.stat_max_views') }}'],
            datasets: [{
                data: [
                    allTime.secrets_with_passphrase || 0,
                    allTime.secrets_single_use || 0,
                    allTime.secrets_with_max_views || 0
                ],
                backgroundColor: ['#ec4899', '#06b6d4', '#84cc16']
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });

    new Chart(document.getElementById('secretOutcomesChart'), {
        type: 'doughnut',
        data: {
            labels: [
                '{{ __('messages.stat_read') }}',
                '{{ __('messages.stat_expired_unread') }}',
                '{{ __('messages.stat_revoked') }}',
                '{{ __('messages.stat_max_reached') }}'
            ],
            datasets: [{
                data: [
                    allTime.secrets_read || 0,
                    allTime.secrets_expired_unread || 0,
                    allTime.secrets_revoked || 0,
                    allTime.secrets_max_views_reached || 0
                ],
                backgroundColor: ['#10b981', '#6b7280', '#ef4444', '#f59e0b']
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } }
        }
    });

    new Chart(document.getElementById('adminActivityChart'), {
        type: 'line',
        data: {
            labels: dateLabels,
            datasets: [
                {
                    label: '{{ __('messages.stat_magic_links_requested') }}',
                    data: getDataForMetric('magic_links_requested'),
                    borderColor: '#8b5cf6',
                    tension: 0.3
                },
                {
                    label: '{{ __('messages.stat_magic_links_used') }}',
                    data: getDataForMetric('magic_links_used'),
                    borderColor: '#10b981',
                    tension: 0.3
                },
                {
                    label: '{{ __('messages.stat_secrets_extended') }}',
                    data: getDataForMetric('secrets_extended'),
                    borderColor: '#06b6d4',
                    tension: 0.3
                }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } },
            scales: {
                x: { display: true, grid: { display: false } },
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });
</script>
@endsection
