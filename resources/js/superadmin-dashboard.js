import Chart from 'chart.js/auto';

window.initSuperAdminDashboard = function(stats, allTime) {
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
        const data = stats.metrics[metric] || {};
        return dateLabels.map(date => data[date] || 0);
    }

    // Secrets Created Chart
    new Chart(document.getElementById('secretsCreatedChart'), {
        type: 'line',
        data: {
            labels: dateLabels,
            datasets: [
                {
                    label: window.translations.stat_text,
                    data: getDataForMetric('secrets_created_text'),
                    borderColor: '#8b5cf6',
                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
                    fill: true,
                    tension: 0.3
                },
                {
                    label: window.translations.stat_file,
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
                label: window.translations.stat_reads,
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
            labels: [window.translations.stat_text, window.translations.stat_file],
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
            labels: [window.translations.stat_passphrase, window.translations.stat_single_use, window.translations.stat_max_views],
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

    // Secret Outcomes Chart
    new Chart(document.getElementById('secretOutcomesChart'), {
        type: 'doughnut',
        data: {
            labels: [
                window.translations.stat_read,
                window.translations.stat_expired_unread,
                window.translations.stat_revoked,
                window.translations.stat_max_reached
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
                    label: window.translations.stat_magic_links_requested,
                    data: getDataForMetric('magic_links_requested'),
                    borderColor: '#8b5cf6',
                    tension: 0.3
                },
                {
                    label: window.translations.stat_magic_links_used,
                    data: getDataForMetric('magic_links_used'),
                    borderColor: '#10b981',
                    tension: 0.3
                },
                {
                    label: window.translations.stat_secrets_extended,
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
};
