<section class="section">
    <div class="container">
        <div class="section-header" data-animate>
            <span class="section-tag">Equity</span>
            <h1 class="section-title">School Equity Analysis</h1>
            <p class="section-subtitle">Analyzing restraint and discipline disparities across Massachusetts schools by SPED concentration, income, and school type in <?= h($latest_year) ?>.</p>
        </div>

        <!-- State Averages Reference -->
        <?php if (!empty($state_avg)): ?>
        <div class="equity-state-ref" data-animate>
            <div class="equity-state-grid">
                <div class="stat-card">
                    <span class="stat-card-value"><?= number_format($state_avg['avg_restraint_rate'], 2) ?></span>
                    <span class="stat-card-label">Avg Restraint Rate (per 100)</span>
                </div>
                <div class="stat-card">
                    <span class="stat-card-value"><?= number_format($state_avg['avg_sped_pct'], 1) ?>%</span>
                    <span class="stat-card-label">Avg District SPED %</span>
                </div>
                <div class="stat-card">
                    <span class="stat-card-value"><?= number_format($state_avg['avg_low_income_pct'], 1) ?>%</span>
                    <span class="stat-card-label">Avg Low-Income %</span>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- SPED Restraint Disparity Scatter -->
        <div class="school-chart-panel equity-chart-panel" data-animate>
            <h3>SPED Concentration vs Restraint Rate</h3>
            <p class="chart-note">Each point = one school. X = parent district SPED %, Y = school restraint rate per 100 students. Higher SPED districts tend to have different restraint profiles.</p>
            <div class="school-chart" id="equity-sped-chart-wrapper">
                <canvas id="equity-sped-chart" height="380"></canvas>
            </div>
            <script>
            (function() {
                var canvas = document.getElementById('equity-sped-chart');
                if (!canvas || typeof Chart === 'undefined') return;
                var raw = <?= json_encode($sped_disparity) ?>;
                var points = raw.map(function(r) {
                    return { x: parseFloat(r.district_sped_pct) || 0, y: parseFloat(r.restraint_rate) || 0, name: r.org_name, district: r.district_name };
                });

                new Chart(canvas, {
                    type: 'scatter',
                    data: {
                        datasets: [{
                            label: 'Schools',
                            data: points,
                            backgroundColor: '#ff5a1f66',
                            borderColor: '#ff5a1f',
                            borderWidth: 1,
                            pointRadius: 4,
                            pointHoverRadius: 7,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#1d1d1d',
                                titleColor: '#e0e0e0',
                                bodyColor: '#a0a0a0',
                                borderColor: '#2a2a2a',
                                borderWidth: 1,
                                callbacks: {
                                    label: function(ctx) {
                                        var p = ctx.raw;
                                        return (p.name || '') + ': SPED ' + p.x.toFixed(1) + '%, Rate ' + p.y.toFixed(2);
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                title: { display: true, text: 'District SPED %', color: '#a0a0a0', font: { family: 'Inter', size: 12 } },
                                ticks: { color: '#767676', font: { family: 'Inter', size: 11 }, callback: function(v) { return v + '%'; } },
                                grid: { color: 'rgba(42,42,42,0.5)' }
                            },
                            y: {
                                title: { display: true, text: 'Restraint Rate (per 100)', color: '#a0a0a0', font: { family: 'Inter', size: 12 } },
                                ticks: { color: '#767676', font: { family: 'Inter', size: 11 } },
                                grid: { color: 'rgba(42,42,42,0.5)' },
                                beginAtZero: true
                            }
                        }
                    }
                });
            })();
            </script>
        </div>

        <!-- Low-Income Discipline Disparity -->
        <div class="school-chart-panel equity-chart-panel" data-animate>
            <h3>Low-Income Concentration vs Discipline Rate</h3>
            <p class="chart-note">District-level: X = low-income %, Y = discipline rate %. Higher poverty districts may show different discipline patterns.</p>
            <div class="school-chart" id="equity-income-chart-wrapper">
                <canvas id="equity-income-chart" height="380"></canvas>
            </div>
            <script>
            (function() {
                var canvas = document.getElementById('equity-income-chart');
                if (!canvas || typeof Chart === 'undefined') return;
                var raw = <?= json_encode($disc_disparity) ?>;
                var points = raw.map(function(r) {
                    return { x: parseFloat(r.low_income_pct) || 0, y: parseFloat(r.discipline_rate) || 0, name: r.org_name };
                });

                new Chart(canvas, {
                    type: 'scatter',
                    data: {
                        datasets: [{
                            label: 'Districts',
                            data: points,
                            backgroundColor: '#60a5fa66',
                            borderColor: '#60a5fa',
                            borderWidth: 1,
                            pointRadius: 4,
                            pointHoverRadius: 7,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#1d1d1d',
                                titleColor: '#e0e0e0',
                                bodyColor: '#a0a0a0',
                                borderColor: '#2a2a2a',
                                borderWidth: 1,
                                callbacks: {
                                    label: function(ctx) {
                                        var p = ctx.raw;
                                        return (p.name || '') + ': Low-income ' + p.x.toFixed(1) + '%, Discipline ' + p.y.toFixed(2) + '%';
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                title: { display: true, text: 'Low-Income %', color: '#a0a0a0', font: { family: 'Inter', size: 12 } },
                                ticks: { color: '#767676', font: { family: 'Inter', size: 11 }, callback: function(v) { return v + '%'; } },
                                grid: { color: 'rgba(42,42,42,0.5)' }
                            },
                            y: {
                                title: { display: true, text: 'Discipline Rate %', color: '#a0a0a0', font: { family: 'Inter', size: 12 } },
                                ticks: { color: '#767676', font: { family: 'Inter', size: 11 }, callback: function(v) { return v + '%'; } },
                                grid: { color: 'rgba(42,42,42,0.5)' },
                                beginAtZero: true
                            }
                        }
                    }
                });
            })();
            </script>
        </div>

        <!-- Title I vs Non-Title I -->
        <?php if (!empty($title1_comparison)): ?>
        <div class="school-chart-panel equity-chart-panel" data-animate>
            <h3>Title I vs Non-Title I: Restraint Rate Comparison</h3>
            <div class="school-chart" id="equity-title1-chart-wrapper">
                <canvas id="equity-title1-chart" height="280"></canvas>
            </div>
            <script>
            (function() {
                var canvas = document.getElementById('equity-title1-chart');
                if (!canvas || typeof Chart === 'undefined') return;
                var raw = <?= json_encode($title1_comparison) ?>;
                new Chart(canvas, {
                    type: 'bar',
                    data: {
                        labels: raw.map(function(r) { return r.category; }),
                        datasets: [{
                            label: 'Avg Restraint Rate (per 100)',
                            data: raw.map(function(r) { return parseFloat(r.avg_restraint_rate) || 0; }),
                            backgroundColor: ['#ff5a1f', '#60a5fa'],
                            borderRadius: 4,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#1d1d1d',
                                titleColor: '#e0e0e0',
                                bodyColor: '#a0a0a0',
                                borderColor: '#2a2a2a',
                                borderWidth: 1,
                                callbacks: {
                                    label: function(ctx) {
                                        return ctx.parsed.y.toFixed(2) + ' per 100 (' + (raw[ctx.dataIndex].school_count || 0) + ' schools)';
                                    }
                                }
                            }
                        },
                        scales: {
                            x: { ticks: { color: '#a0a0a0', font: { family: 'Inter', size: 11 } }, grid: { color: 'rgba(42,42,42,0.5)' } },
                            y: {
                                ticks: { color: '#767676', font: { family: 'Inter', size: 11 } },
                                grid: { color: 'rgba(42,42,42,0.5)' },
                                beginAtZero: true,
                                title: { display: true, text: 'Restraint Rate (per 100)', color: '#a0a0a0', font: { family: 'Inter', size: 12 } }
                            }
                        }
                    }
                });
            })();
            </script>
        </div>
        <?php endif; ?>

        <!-- Charter vs Traditional -->
        <?php if (!empty($charter_comparison)): ?>
        <div class="school-chart-panel equity-chart-panel" data-animate>
            <h3>Charter vs Traditional Public: Restraint Rate Comparison</h3>
            <div class="school-chart" id="equity-charter-chart-wrapper">
                <canvas id="equity-charter-chart" height="280"></canvas>
            </div>
            <script>
            (function() {
                var canvas = document.getElementById('equity-charter-chart');
                if (!canvas || typeof Chart === 'undefined') return;
                var raw = <?= json_encode($charter_comparison) ?>;
                new Chart(canvas, {
                    type: 'bar',
                    data: {
                        labels: raw.map(function(r) { return r.category; }),
                        datasets: [{
                            label: 'Avg Restraint Rate (per 100)',
                            data: raw.map(function(r) { return parseFloat(r.avg_restraint_rate) || 0; }),
                            backgroundColor: ['#22c55e', '#ff5a1f'],
                            borderRadius: 4,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#1d1d1d',
                                titleColor: '#e0e0e0',
                                bodyColor: '#a0a0a0',
                                borderColor: '#2a2a2a',
                                borderWidth: 1,
                                callbacks: {
                                    label: function(ctx) {
                                        return ctx.parsed.y.toFixed(2) + ' per 100 (' + (raw[ctx.dataIndex].school_count || 0) + ' schools)';
                                    }
                                }
                            }
                        },
                        scales: {
                            x: { ticks: { color: '#a0a0a0', font: { family: 'Inter', size: 11 } }, grid: { color: 'rgba(42,42,42,0.5)' } },
                            y: {
                                ticks: { color: '#767676', font: { family: 'Inter', size: 11 } },
                                grid: { color: 'rgba(42,42,42,0.5)' },
                                beginAtZero: true,
                                title: { display: true, text: 'Restraint Rate (per 100)', color: '#a0a0a0', font: { family: 'Inter', size: 12 } }
                            }
                        }
                    }
                });
            })();
            </script>
        </div>
        <?php endif; ?>
    </div>
</section>
