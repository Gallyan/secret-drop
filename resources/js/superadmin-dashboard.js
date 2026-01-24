import Chart from 'chart.js/auto';

function initDashboard() {
    const data = window.superAdminData;
    if (!data) {
        console.error('superAdminData not found');
        return;
    }

    const { stats, allTime, heatmapCreated, heatmapRead, translations } = data;

    const isDark = document.documentElement.classList.contains('dark');
    const gridColor = isDark ? 'rgba(148, 163, 184, 0.1)' : 'rgba(0, 0, 0, 0.1)';
    const textColor = isDark ? '#94a3b8' : '#6b7280';

    Chart.defaults.color = textColor;
    Chart.defaults.borderColor = gridColor;

    function generateDateLabels(startDate, endDate) {
        const labels = [];
        const start = new Date(startDate);
        const end = new Date(endDate);
        const current = new Date(start);

        while (current <= end) {
            labels.push(current.toISOString().split('T')[0]);
            current.setDate(current.getDate() + 1);
        }
        return labels;
    }

    const dateLabels = generateDateLabels(stats.start_date, stats.end_date);

    function getDataForMetric(metric) {
        const metricsData = stats.metrics[metric] || {};
        return dateLabels.map(date => metricsData[date] || 0);
    }

    // Secrets Created Chart
    new Chart(document.getElementById('secretsCreatedChart'), {
        type: 'line',
        data: {
            labels: dateLabels,
            datasets: [
                {
                    label: translations.stat_text,
                    data: getDataForMetric('secrets_created_text'),
                    borderColor: '#8b5cf6',
                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
                    fill: true,
                    tension: 0.3
                },
                {
                    label: translations.stat_file,
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

    // Secrets Read Chart
    new Chart(document.getElementById('secretsReadChart'), {
        type: 'line',
        data: {
            labels: dateLabels,
            datasets: [{
                label: translations.stat_reads,
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

    // Secret Types Chart
    new Chart(document.getElementById('secretTypesChart'), {
        type: 'doughnut',
        data: {
            labels: [translations.stat_text, translations.stat_file],
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

    // Secret Options Chart
    new Chart(document.getElementById('secretOptionsChart'), {
        type: 'bar',
        data: {
            labels: [
                translations.stat_passphrase,
                translations.stat_single_use,
                translations.stat_max_views,
                translations.stat_split_mode
            ],
            datasets: [{
                data: [
                    allTime.secrets_with_passphrase || 0,
                    allTime.secrets_single_use || 0,
                    allTime.secrets_with_max_views || 0,
                    allTime.secrets_split_mode || 0
                ],
                backgroundColor: ['#ec4899', '#06b6d4', '#84cc16', '#f97316']
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

    // Secret Outcomes Chart
    new Chart(document.getElementById('secretOutcomesChart'), {
        type: 'doughnut',
        data: {
            labels: [
                translations.stat_read,
                translations.stat_expired_unread,
                translations.stat_revoked,
                translations.stat_max_reached
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

    // Admin Activity Chart
    new Chart(document.getElementById('adminActivityChart'), {
        type: 'line',
        data: {
            labels: dateLabels,
            datasets: [
                {
                    label: translations.stat_magic_links_requested,
                    data: getDataForMetric('magic_links_requested'),
                    borderColor: '#8b5cf6',
                    tension: 0.3
                },
                {
                    label: translations.stat_magic_links_used,
                    data: getDataForMetric('magic_links_used'),
                    borderColor: '#10b981',
                    tension: 0.3
                },
                {
                    label: translations.stat_secrets_extended,
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

    // Heatmaps (hours as rows, days as columns)
    function renderHeatmap(containerId, heatmapData, color) {
        const container = document.getElementById(containerId);
        if (!container) {
            return;
        }

        const days = translations.days || ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];

        let maxValue = 0;
        for (let day = 0; day < 7; day++) {
            for (let hour = 0; hour < 24; hour++) {
                const val = heatmapData[day]?.[hour] || 0;
                if (val > maxValue) {
                    maxValue = val;
                }
            }
        }

        function getIntensityClass(val, max, colorName) {
            if (val === 0) {
                return 'heatmap-empty';
            }
            const intensity = max > 0 ? val / max : 0;
            const level = Math.min(6, Math.max(1, Math.ceil(intensity * 6)));
            return `heatmap-${colorName}-${level}`;
        }

        let html = '<div class="overflow-x-auto"><table class="w-full border-collapse text-xs">';

        // Header row with day names
        html += '<tr><td class="w-8"></td>';
        for (let day = 0; day < 7; day++) {
            html += `<td class="text-center text-gray-600 dark:text-slate-400 pb-2 font-medium">${days[day]}</td>`;
        }
        html += '</tr>';

        // Rows for each hour
        for (let hour = 0; hour < 24; hour++) {
            html += `<tr><td class="text-right pr-2 text-gray-500 dark:text-slate-500 text-[10px]">${hour}h</td>`;
            for (let day = 0; day < 7; day++) {
                const val = heatmapData[day]?.[hour] || 0;
                const intensityClass = getIntensityClass(val, maxValue, color);
                const title = `${days[day]} ${hour}h: ${val}`;

                html += `<td class="p-0.5">`;
                html += `<div class="h-5 rounded-sm flex items-center justify-center text-[10px] ${intensityClass}" title="${title}">`;
                html += val > 0 ? val : '';
                html += `</div></td>`;
            }
            html += '</tr>';
        }

        html += '</table></div>';
        container.innerHTML = html;
    }

    if (heatmapCreated) {
        renderHeatmap('heatmapCreated', heatmapCreated, 'violet');
    }

    if (heatmapRead) {
        renderHeatmap('heatmapRead', heatmapRead, 'green');
    }
}

// Run when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDashboard);
} else {
    initDashboard();
}
