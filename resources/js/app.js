import Chart from 'chart.js/auto';

window.Chart = Chart;
window.revenueCharts = {};

const palette = {
    navy: '#263a61',
    navyLight: '#4f7cc5',
    mint: '#14a578',
    violet: '#6d4aff',
    orange: '#f59e0b',
    rose: '#f43f5e',
    slate: '#94a3b8',
    grid: 'rgba(148, 163, 184, 0.18)',
};

window.revenuePalette = palette;

window.renderRevenueChart = function (canvasId, config) {
    const canvas = document.getElementById(canvasId);
    if (!canvas || !config) return;

    if (window.revenueCharts[canvasId]) {
        window.revenueCharts[canvasId].destroy();
    }

    const defaults = {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: {
                display: config.showLegend ?? false,
                position: 'bottom',
                labels: {
                    usePointStyle: true,
                    boxWidth: 7,
                    boxHeight: 7,
                    padding: 18,
                    color: '#64748b',
                    font: { size: 11, family: 'Instrument Sans' },
                },
            },
            tooltip: {
                backgroundColor: '#111f38',
                padding: 12,
                cornerRadius: 8,
                titleFont: { size: 12, family: 'Instrument Sans' },
                bodyFont: { size: 12, family: 'Instrument Sans' },
                displayColors: true,
                boxWidth: 8,
                boxHeight: 8,
                usePointStyle: true,
            },
        },
    };

    const merged = {
        ...config,
        options: {
            ...defaults,
            ...(config.options ?? {}),
            plugins: {
                ...defaults.plugins,
                ...(config.options?.plugins ?? {}),
                legend: {
                    ...defaults.plugins.legend,
                    ...(config.options?.plugins?.legend ?? {}),
                },
            },
            scales: config.type === 'doughnut' || config.type === 'pie' ? undefined : {
                x: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: { color: '#94a3b8', font: { size: 11, family: 'Instrument Sans' } },
                    ...(config.options?.scales?.x ?? {}),
                },
                y: {
                    grid: { color: palette.grid, drawTicks: false },
                    border: { display: false },
                    ticks: {
                        color: '#94a3b8',
                        padding: 8,
                        font: { size: 11, family: 'Instrument Sans' },
                        callback: (value) => {
                            if (Math.abs(value) >= 1000000) return '₦' + (value / 1000000).toFixed(1) + 'M';
                            if (Math.abs(value) >= 1000) return '₦' + (value / 1000).toFixed(0) + 'K';
                            return '₦' + value;
                        },
                    },
                    ...(config.options?.scales?.y ?? {}),
                },
            },
        },
    };

    window.revenueCharts[canvasId] = new Chart(canvas, merged);
};

window.copyToClipboard = function (text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text);
    }
};

document.addEventListener('livewire:init', () => {
    Livewire.on('notify', (event) => {
        const detail = Array.isArray(event) ? event[0] : event;
        window.dispatchEvent(new CustomEvent('toast', { detail }));
    });
});
