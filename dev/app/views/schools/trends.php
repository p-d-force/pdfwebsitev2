<section class="section">
    <div class="container">
        <div class="section-header" data-animate>
            <span class="section-tag">Trends</span>
            <h1 class="section-title">School Trend Analysis</h1>
            <p class="section-subtitle">Track multi-year restraint patterns for individual schools, compared against district and state averages.</p>
        </div>

        <!-- School Selector -->
        <div class="trend-selector" data-animate>
            <form method="GET" action="/schools/trends" class="trend-form">
                <div class="filter-group" style="flex:1; max-width:500px;">
                    <label for="school-select">Select a School</label>
                    <select name="code" id="school-select" class="trend-school-select" onchange="this.form.submit()">
                        <option value="">— Choose a school —</option>
                        <?php foreach ($school_names as $sn): ?>
                        <option value="<?= h(strtolower($sn['org_code'])) ?>" <?= ($selected_code === strtolower($sn['org_code'])) ? 'selected' : '' ?>>
                            <?= h($sn['org_name']) ?> (<?= h($sn['district_name']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <noscript><button type="submit" class="btn btn-primary btn-sm">View Trends</button></noscript>
            </form>
        </div>

        <?php if (!empty($school_data)): ?>
        <!-- School Info -->
        <div class="school-hero" data-animate>
            <h2 class="school-hero-name"><?= h($school['org_name']) ?></h2>
            <div class="school-hero-meta">
                <span class="badge badge-school-type"><?= h($school['org_type']) ?></span>
                <span class="school-hero-code"><?= h($school['org_code']) ?></span>
                <?php if (!empty($school['town'])): ?>
                <span class="school-hero-town"><?= h($school['town']) ?>, MA</span>
                <?php endif; ?>
                <?php if (!empty($school['grade_span'])): ?>
                <span class="school-hero-grade">Grades <?= h($school['grade_span']) ?></span>
                <?php endif; ?>
            </div>
            <div class="school-district-link">
                Part of <a href="/districts/<?= h(strtolower($school['district_code'])) ?>/"><?= h($school['district_name']) ?></a>
                &middot; <a href="/schools/<?= h(strtolower($school['org_code'])) ?>/">View full profile</a>
            </div>
        </div>

        <!-- Restraint Rate Trend -->
        <div class="school-chart-panel trend-chart-panel" data-animate>
            <h3>Restraint Rate Over Time</h3>
            <p class="chart-note">Restraint rate per 100 students. Compare this school (orange) to its district average (blue) and the statewide average (grey dashed).</p>
            <div class="school-chart" id="trend-rate-chart-wrapper">
                <canvas id="trend-rate-chart" height="340"></canvas>
            </div>
            <script>
            (function() {
                var canvas = document.getElementById('trend-rate-chart');
                if (!canvas || typeof Chart === 'undefined') return;
                var raw = <?= json_encode($school_data) ?>;
                var years = raw.map(function(r) { return r.school_year; });
                var schoolRates = raw.map(function(r) { return parseFloat(r.restraint_rate) || 0; });
                var districtRates = raw.map(function(r) { return r.district_restraint_rate !== null ? parseFloat(r.district_restraint_rate) : null; });
                var stateRates = raw.map(function(r) { return r.state_restraint_rate !== null ? parseFloat(r.state_restraint_rate) : null; });

                new Chart(canvas, {
                    type: 'line',
                    data: {
                        labels: years,
                        datasets: [
                            {
                                label: '<?= addslashes($school['org_name']) ?>',
                                data: schoolRates,
                                borderColor: '#ff5a1f',
                                backgroundColor: '#ff5a1f33',
                                borderWidth: 2.5,
                                pointRadius: 4,
                                pointBackgroundColor: '#ff5a1f',
                                tension: 0.3,
                                fill: false,
                            },
                            {
                                label: 'District Average',
                                data: districtRates,
                                borderColor: '#60a5fa',
                                backgroundColor: '#60a5fa33',
                                borderWidth: 2,
                                pointRadius: 3,
                                pointBackgroundColor: '#60a5fa',
                                tension: 0.3,
                                fill: false,
                                borderDash: [],
                            },
                            {
                                label: 'State Average',
                                data: stateRates,
                                borderColor: '#767676',
                                backgroundColor: 'transparent',
                                borderWidth: 2,
                                pointRadius: 2,
                                pointBackgroundColor: '#767676',
                                tension: 0.3,
                                fill: false,
                                borderDash: [6, 4],
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                labels: { color: '#a0a0a0', font: { family: 'Inter', size: 11 }, padding: 16, usePointStyle: true }
                            },
                            tooltip: {
                                backgroundColor: '#1d1d1d',
                                titleColor: '#e0e0e0',
                                bodyColor: '#a0a0a0',
                                borderColor: '#2a2a2a',
                                borderWidth: 1,
                                callbacks: {
                                    label: function(ctx) {
                                        var v = ctx.parsed.y;
                                        return ctx.dataset.label + ': ' + (v !== null && v !== undefined ? v.toFixed(2) + ' per 100' : 'No data');
                                    }
                                }
                            }
                        },
                        scales: {
                            x: { ticks: { color: '#a0a0a0', font: { family: 'Inter', size: 11 } }, grid: { color: 'rgba(42,42,42,0.5)' } },
                            y: {
                                ticks: { color: '#767676', font: { family: 'Inter', size: 11 }, callback: function(v) { return v.toFixed(1); } },
                                grid: { color: 'rgba(42,42,42,0.5)' },
                                beginAtZero: true,
                                title: { display: true, text: 'Restraint Rate (per 100)', color: '#a0a0a0', font: { family: 'Inter', size: 12 } }
                            }
                        },
                        interaction: { intersect: false, mode: 'index' }
                    }
                });
            })();
            </script>
        </div>

        <!-- Enrollment Trend -->
        <div class="school-chart-panel trend-chart-panel" data-animate>
            <h3>Enrollment Over Time</h3>
            <div class="school-chart" id="trend-enrollment-chart-wrapper">
                <canvas id="trend-enrollment-chart" height="280"></canvas>
            </div>
            <script>
            (function() {
                var canvas = document.getElementById('trend-enrollment-chart');
                if (!canvas || typeof Chart === 'undefined') return;
                var raw = <?= json_encode($school_data) ?>;
                var years = raw.map(function(r) { return r.school_year; });
                var enroll = raw.map(function(r) { return parseInt(r.enrollment) || 0; });

                new Chart(canvas, {
                    type: 'bar',
                    data: {
                        labels: years,
                        datasets: [{
                            label: 'Enrollment',
                            data: enroll,
                            backgroundColor: '#22c55e99',
                            borderColor: '#22c55e',
                            borderWidth: 1,
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
                                    label: function(ctx) { return ctx.parsed.y.toLocaleString() + ' students'; }
                                }
                            }
                        },
                        scales: {
                            x: { ticks: { color: '#a0a0a0', font: { family: 'Inter', size: 11 } }, grid: { color: 'rgba(42,42,42,0.5)' } },
                            y: {
                                ticks: { color: '#767676', font: { family: 'Inter', size: 11 }, callback: function(v) { return v.toLocaleString(); } },
                                grid: { color: 'rgba(42,42,42,0.5)' },
                                beginAtZero: true,
                                title: { display: true, text: 'Students', color: '#a0a0a0', font: { family: 'Inter', size: 12 } }
                            }
                        }
                    }
                });
            })();
            </script>
        </div>

        <!-- Restraint Totals Trend -->
        <div class="school-chart-panel trend-chart-panel" data-animate>
            <h3>Total Restraints Over Time</h3>
            <div class="school-chart" id="trend-total-chart-wrapper">
                <canvas id="trend-total-chart" height="280"></canvas>
            </div>
            <script>
            (function() {
                var canvas = document.getElementById('trend-total-chart');
                if (!canvas || typeof Chart === 'undefined') return;
                var raw = <?= json_encode($school_data) ?>;
                var years = raw.map(function(r) { return r.school_year; });
                var restraints = raw.map(function(r) { return parseInt(r.total_restraints) || 0; });
                var injuries = raw.map(function(r) { return parseInt(r.total_injuries) || 0; });

                new Chart(canvas, {
                    type: 'bar',
                    data: {
                        labels: years,
                        datasets: [
                            {
                                label: 'Total Restraints',
                                data: restraints,
                                backgroundColor: '#ff5a1f99',
                                borderColor: '#ff5a1f',
                                borderWidth: 1,
                                borderRadius: 4,
                            },
                            {
                                label: 'Injuries',
                                data: injuries,
                                backgroundColor: '#ef444499',
                                borderColor: '#ef4444',
                                borderWidth: 1,
                                borderRadius: 4,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                labels: { color: '#a0a0a0', font: { family: 'Inter', size: 11 }, padding: 16, usePointStyle: true }
                            },
                            tooltip: {
                                backgroundColor: '#1d1d1d',
                                titleColor: '#e0e0e0',
                                bodyColor: '#a0a0a0',
                                borderColor: '#2a2a2a',
                                borderWidth: 1,
                                callbacks: {
                                    label: function(ctx) { return ctx.dataset.label + ': ' + ctx.parsed.y.toLocaleString(); }
                                }
                            }
                        },
                        scales: {
                            x: { ticks: { color: '#a0a0a0', font: { family: 'Inter', size: 11 } }, grid: { color: 'rgba(42,42,42,0.5)' } },
                            y: {
                                ticks: { color: '#767676', font: { family: 'Inter', size: 11 }, callback: function(v) { return v.toLocaleString(); } },
                                grid: { color: 'rgba(42,42,42,0.5)' },
                                beginAtZero: true
                            }
                        }
                    }
                });
            })();
            </script>
        </div>

        <?php elseif (!empty($selected_code)): ?>
            <div class="empty-state"><h3>School Not Found</h3><p>The requested school could not be found or has no data.</p></div>
        <?php endif; ?>
    </div>
</section>
