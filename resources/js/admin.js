import Chart from 'chart.js/auto';
window.Chart = Chart;

function initAdminNavigation() {
    const nav = document.querySelector('.admin-nav');
    if (!nav) return;

    const storageKey = 'arsh.admin.navigation.scroll';
    const savePosition = () => {
        if (!nav.clientHeight) return;
        try { sessionStorage.setItem(storageKey, String(nav.scrollTop)); } catch { /* Storage may be disabled. */ }
    };
    const restorePosition = () => {
        // Alpine hides the mobile drawer until it opens. Restore only once it
        // has dimensions, otherwise the browser clamps its scroll position to 0.
        if (!nav.clientHeight) return;
        try {
            const saved = Number(sessionStorage.getItem(storageKey));
            if (Number.isFinite(saved) && saved >= 0) nav.scrollTop = saved;
        } catch { /* Navigation also works without browser storage. */ }

        const active = nav.querySelector('[aria-current="page"]');
        if (active) {
            const bounds = nav.getBoundingClientRect();
            const item = active.getBoundingClientRect();
            if (item.top < bounds.top) nav.scrollTop -= bounds.top - item.top;
            else if (item.bottom > bounds.bottom) nav.scrollTop += item.bottom - bounds.bottom;
        }
        savePosition();
    };

    // Run after Alpine applies its initial drawer visibility.
    requestAnimationFrame(restorePosition);
    new ResizeObserver(restorePosition).observe(nav);
    nav.addEventListener('scroll', savePosition, { passive: true });
    nav.addEventListener('click', savePosition);
    window.addEventListener('pagehide', savePosition);
    window.addEventListener('pageshow', restorePosition);
}

function initAdminChart() {
    const canvas = document.getElementById('salesChart');
    if (!canvas) return;
    const theme = getComputedStyle(document.body);
    const accent = theme.getPropertyValue('--color-gold').trim() || '#dcb477';
    const muted = theme.getPropertyValue('--color-muted').trim() || '#b2aa9b';
    const reduced = matchMedia('(prefers-reduced-motion: reduce)');
    const currency = new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', maximumFractionDigits: 2 });
    const chart = new Chart(canvas, {
        type: 'line',
        data: {
            labels: JSON.parse(canvas.dataset.labels || '[]'),
            datasets: [
                {
                    label: 'Net revenue',
                    data: JSON.parse(canvas.dataset.revenue || '[]'),
                    borderColor: accent,
                    backgroundColor: context => {
                        const { ctx, chartArea } = context.chart;
                        if (!chartArea) return 'rgba(220,180,119,0.08)';
                        const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                        gradient.addColorStop(0, 'rgba(220,180,119,0.28)');
                        gradient.addColorStop(1, 'rgba(220,180,119,0.01)');
                        return gradient;
                    },
                    fill: true, tension: 0.35, borderWidth: 2, pointRadius: 0, pointHoverRadius: 4, pointHitRadius: 12, yAxisID: 'y',
                },
                {
                    label: 'Revenue orders',
                    data: JSON.parse(canvas.dataset.orders || '[]'),
                    borderColor: '#b9c4aa', borderDash: [4, 5], tension: 0.3, borderWidth: 1.5, pointRadius: 0, pointHoverRadius: 4, pointHitRadius: 12, yAxisID: 'y1',
                },
            ],
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            animation: reduced.matches ? false : { duration: 700 },
            interaction: { mode: 'index', intersect: false },
            font: { family: 'Montserrat, system-ui, sans-serif' },
            plugins: {
                legend: { position: 'bottom', align: 'start', labels: { color: muted, usePointStyle: true, pointStyle: 'circle', boxWidth: 6, boxHeight: 6, padding: 20, font: { family: 'Montserrat, system-ui, sans-serif', size: 9 } } },
                tooltip: { backgroundColor: '#17140f', borderColor: '#8e6d3c', borderWidth: 1, titleColor: '#f1dfc1', bodyColor: '#d7c29e', padding: 12, displayColors: true, callbacks: { label: context => `${context.dataset.label}: ${context.dataset.yAxisID === 'y' ? currency.format(context.parsed.y) : context.parsed.y}` } },
            },
            scales: {
                x: { ticks: { color: muted, maxTicksLimit: 6, maxRotation: 0, font: { size: 9 } }, grid: { display: false }, border: { color: '#50402b' } },
                y: { ticks: { color: muted, maxTicksLimit: 5, font: { size: 9 }, callback: value => currency.format(value) }, grid: { color: '#c3a26712' }, border: { display: false }, beginAtZero: true },
                y1: { position: 'right', ticks: { color: '#b9c4aa', precision: 0, maxTicksLimit: 5, font: { size: 9 } }, grid: { display: false }, border: { display: false }, beginAtZero: true },
            },
        },
    });
    reduced.addEventListener('change', () => { chart.options.animation = reduced.matches ? false : { duration: 700 }; chart.stop(); chart.update('none'); });
}
function initAdmin() {
    initAdminNavigation();
    initAdminChart();
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initAdmin, { once: true });
else initAdmin();
