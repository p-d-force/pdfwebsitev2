/**
 * Parent Data Force — app.js
 * Minimal vanilla JS: nav toggle, scroll animations, chart initialization.
 * No framework, no build step.
 */
(function () {
    'use strict';

    // ── Scroll-triggered animations ──
    function animateOnScroll() {
        var els = document.querySelectorAll('[data-animate]:not(.visible)');
        var windowHeight = window.innerHeight;
        for (var i = 0; i < els.length; i++) {
            var rect = els[i].getBoundingClientRect();
            if (rect.top < windowHeight - 80) {
                els[i].classList.add('visible');
            }
        }
    }

    // ── Nav: close mobile menu on link click ──
    function initNav() {
        var links = document.querySelectorAll('.nav-links .nav-link');
        var toggle = document.querySelector('.nav-toggle');
        var menu = document.querySelector('.nav-links');
        for (var i = 0; i < links.length; i++) {
            links[i].addEventListener('click', function () {
                if (toggle && toggle.classList.contains('open')) {
                    toggle.classList.remove('open');
                    menu.classList.remove('open');
                }
            });
        }
    }

    // ── Init ──
    document.addEventListener('DOMContentLoaded', function () {
        animateOnScroll();
        initNav();
    });
    window.addEventListener('scroll', animateOnScroll, { passive: true });
})();


// ── Chart.js Plugins (only when Chart.js is loaded) ──
if (typeof Chart !== 'undefined') {
    Chart.register({
        id: 'crosshair',
        afterDraw: function (chart) {
            if (!chart.options.crosshair) return;
            var ctx = chart.ctx;
            var active = chart.getActiveElements();
            if (!active || !active.length) return;
            var x = active[0].element.x;
            var topY = chart.scales.y.top;
            var bottomY = chart.scales.y.bottom;
            ctx.save();
            ctx.beginPath();
            ctx.moveTo(x, topY);
            ctx.lineTo(x, bottomY);
            ctx.lineWidth = 1;
            ctx.strokeStyle = 'rgba(255,90,31,0.5)';
            ctx.setLineDash([4, 4]);
            ctx.stroke();
            ctx.restore();
        }
    });

    Chart.register({
        id: 'filterClick',
        afterEvent: function (chart, evt) {
            if (evt.event.type !== 'click') return;
            var active = chart.getActiveElements();
            if (!active || !active.length) return;
            var el = active[0];
            var dataset = chart.data.datasets[el.datasetIndex];
            var label = chart.data.labels[el.index];
            var value = dataset.data[el.index];
            chart.canvas.dispatchEvent(new CustomEvent('chart-filter', {
                detail: { label: label, value: value, dataset: dataset.label },
                bubbles: true
            }));
        }
    });
}

// ── Sparkline initializer ──
function initSparklines() {
    var canvases = document.querySelectorAll('canvas[data-sparkline]');
    for (var i = 0; i < canvases.length; i++) {
        var c = canvases[i];
        var raw = c.getAttribute('data-values');
        if (!raw) continue;
        try {
            var values = JSON.parse(raw);
        } catch (e) { continue; }
        new Chart(c, {
            type: 'line',
            data: {
                labels: values.map(function (_, i) { return i; }),
                datasets: [{
                    data: values,
                    borderColor: c.getAttribute('data-color') || '#ff5a1f',
                    borderWidth: 1,
                    pointRadius: 0,
                    pointHoverRadius: 0,
                    fill: false,
                    tension: 0.1
                }]
            },
            options: {
                responsive: false,
                maintainAspectRatio: false,
                animation: { duration: 0 },
                plugins: { legend: { display: false }, tooltip: { enabled: false } },
                scales: {
                    x: { display: false, grid: { display: false } },
                    y: { display: false, grid: { display: false } }
                }
            }
        });
    }
}

// Auto-init sparklines on DOM ready

// ── Lazy-load below-fold charts via IntersectionObserver ──
(function() {
    if (!('IntersectionObserver' in window)) return;
    var observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (!entry.isIntersecting) return;
            var el = entry.target;
            var canvas = el.querySelector('canvas');
            if (!canvas || canvas.dataset.lazy !== 'true') return;
            canvas.dataset.lazy = 'loading';
            var src = canvas.dataset.src;
            if (src) {
                var img = new Image();
                img.onload = function() { canvas.getContext('2d').drawImage(img, 0, 0); };
                img.src = src;
            }
            observer.unobserve(el);
        });
    }, { rootMargin: '200px' });

    document.querySelectorAll('[data-lazy-chart]').forEach(function(el) {
        observer.observe(el);
    });
})();
document.addEventListener('DOMContentLoaded', function () {
    initSparklines();
});
