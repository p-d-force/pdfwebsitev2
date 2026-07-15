<section class="section">
    <div class="container">
        <!-- Breadcrumb -->
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <ol>
                <li><a href="/">Home</a></li>
                <li><a href="/districts/">Districts</a></li>
                <?php if (!empty($school['district_code'])): ?>
                <li><a href="/districts/<?= h(strtolower($school['district_code'])) ?>/"><?= h($school['district_name']) ?></a></li>
                <?php endif; ?>
                <li aria-current="page"><?= h($school['org_name']) ?></li>
            </ol>
        </nav>

        <!-- School Header -->
        <div class="school-hero" data-animate>
            <h1 class="school-hero-name"><?= h($school['org_name']) ?></h1>
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
            <?php if (!empty($school['district_code'])): ?>
            <div class="school-district-link">
                Part of <a href="/districts/<?= h(strtolower($school['district_code'])) ?>/"><?= h($school['district_name']) ?> (<?= h($school['district_code']) ?>)</a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Stat Cards -->
        <?php $latestRestraint = !empty($restraint) ? $restraint[count($restraint) - 1] : null; ?>
        <div class="school-stat-cards" data-animate>
            <div class="stat-card">
                <span class="stat-card-value"><?= $latestRestraint && isset($latestRestraint['enrollment']) ? number_format($latestRestraint['enrollment']) : '—' ?></span>
                <span class="stat-card-label">Enrollment</span>
                <span class="stat-card-year"><?= h($latestRestraint['school_year'] ?? '—') ?></span>
            </div>
            <div class="stat-card">
                <span class="stat-card-value"><?= $latestRestraint && isset($latestRestraint['students_restrained']) ? number_format($latestRestraint['students_restrained']) : '—' ?></span>
                <span class="stat-card-label">Students Restrained</span>
                <span class="stat-card-year"><?= h($latestRestraint['school_year'] ?? '—') ?></span>
            </div>
            <div class="stat-card">
                <span class="stat-card-value"><?= $latestRestraint && isset($latestRestraint['total_restraints']) ? number_format($latestRestraint['total_restraints']) : '—' ?></span>
                <span class="stat-card-label">Total Restraints</span>
                <span class="stat-card-year"><?= h($latestRestraint['school_year'] ?? '—') ?></span>
            </div>
            <div class="stat-card">
                <span class="stat-card-value"><?= $latestRestraint && isset($latestRestraint['total_injuries']) ? number_format($latestRestraint['total_injuries']) : '—' ?></span>
                <span class="stat-card-label">Injuries</span>
                <span class="stat-card-year"><?= h($latestRestraint['school_year'] ?? '—') ?></span>
            </div>
        </div>

        <!-- Restraint Trend Chart -->
        <?php if (!empty($restraint)): ?>
        <div class="school-chart-panel" data-animate>
            <h3>Restraint Trends</h3>
            <div class="school-chart" id="school-restraint-chart-container">
                <canvas id="school-restraint-chart" height="320"></canvas>
            </div>
            <script>
            (function() {
                var container = document.getElementById('school-restraint-chart-container');
                var canvas = document.getElementById('school-restraint-chart');
                if (!canvas || typeof Chart === 'undefined') return;

                var raw = <?= json_encode($restraint) ?>;
                var years = raw.map(function(r) { return r.school_year; });
                var restraints = raw.map(function(r) { return parseInt(r.total_restraints) || 0; });
                var injuries = raw.map(function(r) { return parseInt(r.total_injuries) || 0; });
                var students = raw.map(function(r) { return parseInt(r.students_restrained) || 0; });

                new Chart(canvas, {
                    type: 'bar',
                    data: {
                        labels: years,
                        datasets: [
                            {
                                label: 'Total Restraints',
                                data: restraints,
                                backgroundColor: '#ff5a1f',
                                borderColor: '#ff5a1f',
                                borderRadius: 4,
                            },
                            {
                                label: 'Students Restrained',
                                data: students,
                                backgroundColor: '#f59e0b',
                                borderColor: '#f59e0b',
                                borderRadius: 4,
                            },
                            {
                                label: 'Injuries',
                                data: injuries,
                                backgroundColor: '#ef4444',
                                borderColor: '#ef4444',
                                borderRadius: 4,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                labels: { color: '#a0a0a0', font: { family: 'Inter, sans-serif' } }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(ctx) {
                                        var v = ctx.parsed.y;
                                        return ctx.dataset.label + ': ' + (typeof v === 'number' ? v.toLocaleString() : v);
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                ticks: { color: '#a0a0a0', font: { family: 'Inter, sans-serif' } },
                                grid: { color: 'rgba(255,255,255,0.06)' }
                            },
                            y: {
                                ticks: { color: '#a0a0a0', font: { family: 'Inter, sans-serif' }, callback: function(v) { return v.toLocaleString(); } },
                                grid: { color: 'rgba(255,255,255,0.06)' },
                                beginAtZero: true
                            }
                        }
                    }
                });
            })();
            </script>
        </div>
        <?php endif; ?>

        <!-- District Aggregate Percentages -->
        <?php if ($district_enroll_pct !== null && $district_restraint_pct !== null && $latestRestraint): ?>
        <div class="school-comparison school-aggregate<?= $pct_rating ? ' school-aggregate--' . $pct_rating : '' ?>" data-animate>
            <p>
                This school accounts for
                <strong><?= $district_enroll_pct ?>% of district enrollment</strong>
                (<?= number_format($latestRestraint['enrollment']) ?> of <?= number_format($district_restraint_total > 0 ? round($latestRestraint['enrollment'] / ($district_enroll_pct / 100)) : 0) ?>)
                and
                <strong><?= $district_restraint_pct ?>% of district restraints</strong>
                (<?= number_format($latestRestraint['total_restraints']) ?> of <?= number_format($district_restraint_total) ?>)
                in <?= h($latestRestraint['school_year']) ?>.
            </p>
            <?php if ($pct_rating === 'good'): ?>
            <p class="aggregate-rating rating-good">&#10003; Restraint share is proportional to enrollment share</p>
            <?php elseif ($pct_rating === 'bad'): ?>
            <p class="aggregate-rating rating-bad">&#9888; Restraint share exceeds enrollment share by over 50%</p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- District Comparison (existing) -->
        <?php if ($district_restraint_total !== null && $latestRestraint && !empty($latestRestraint['total_restraints'])): ?>

        <!-- State Comparison & Percentile -->
        <?php if ($state_avg_rate !== null || $percentile_rank !== null): ?>
        <div class="school-comparison school-state-comparison" data-animate>
            <?php if ($school_restraint_rate !== null && $state_avg_rate !== null): ?>
            <p>
                This school's restraint rate is
                <strong><?= number_format($school_restraint_rate, 2) ?> per 100</strong>
                <?php if ($school_restraint_rate > $state_avg_rate): ?>
                — <span style="color:#ef4444">above</span> the state average of <?= number_format($state_avg_rate, 2) ?> per 100.
                <?php elseif ($school_restraint_rate < $state_avg_rate): ?>
                — <span style="color:#22c55e">below</span> the state average of <?= number_format($state_avg_rate, 2) ?> per 100.
                <?php else: ?>
                — equal to the state average of <?= number_format($state_avg_rate, 2) ?> per 100.
                <?php endif; ?>
            </p>
            <?php endif; ?>
            <?php if ($percentile_rank !== null): ?>
            <p>
                This school ranks in the
                <strong><?= number_format($percentile_rank, 1) ?>th percentile</strong>
                among all Massachusetts public schools for restraint rate
                <?php if ($percentile_rank >= 75): ?>
                <span style="color:#ef4444">(higher restraint rate than <?= number_format(100 - $percentile_rank, 1) ?>% of schools)</span>.
                <?php elseif ($percentile_rank >= 50): ?>
                <span style="color:#f59e0b">(higher restraint rate than <?= number_format(100 - $percentile_rank, 1) ?>% of schools)</span>.
                <?php else: ?>
                <span style="color:#22c55e">(lower restraint rate than <?= number_format(100 - $percentile_rank, 1) ?>% of schools)</span>.
                <?php endif; ?>
            </p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Demographics Panel -->
        <?php if (!empty($demographics)): ?>
        <div class="school-demographics" data-animate>
            <h3>District Demographics (<?= h($demographics['school_year'] ?? 'Latest') ?>)</h3>
            <p class="school-demographics-note">Demographic data is reported at the district level for <?= h($school['district_name'] ?? 'this district') ?>.</p>
            <div class="demographics-grid">
                <div class="demographic-stat">
                    <span class="demographic-value"><?= isset($demographics['sped_pct']) ? h($demographics['sped_pct']) : '—' ?>%</span>
                    <span class="demographic-label">Students with Disabilities (SPED)</span>
                </div>
                <div class="demographic-stat">
                    <span class="demographic-value"><?= isset($demographics['el_pct']) ? h($demographics['el_pct']) : '—' ?>%</span>
                    <span class="demographic-label">English Learners</span>
                </div>
                <div class="demographic-stat">
                    <span class="demographic-value"><?= isset($demographics['low_income_pct']) ? h($demographics['low_income_pct']) : '—' ?>%</span>
                    <span class="demographic-label">Low Income</span>
                </div>
                <?php if (isset($demographics['high_needs_pct'])): ?>
                <div class="demographic-stat">
                    <span class="demographic-value"><?= h($demographics['high_needs_pct']) ?>%</span>
                    <span class="demographic-label">High Needs</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Demographic Context: School (District) vs State -->
        <?php if (!empty($demo_context)): ?>
        <div class="school-demographics school-demo-context" data-animate>
            <h3>Demographic Context (<?= h($latest_year ?? 'Latest') ?>)</h3>
            <p class="school-demographics-note">District vs. Massachusetts state averages for key demographics.</p>
            <div class="demo-context-grid">
                <div class="demo-context-bar-group">
                    <span class="demo-context-label">SPED %</span>
                    <div class="demo-context-bars">
                        <div class="demo-bar-wrap">
                            <span class="demo-bar-tag">District</span>
                            <div class="demo-bar-track">
                                <div class="demo-bar-fill demo-bar-district" style="width:<?= min(100, ($demo_context['district_sped'] ?? 0) * 5) ?>%"></div>
                            </div>
                            <span class="demo-bar-val"><?= h($demo_context['district_sped'] ?? '—') ?>%</span>
                        </div>
                        <div class="demo-bar-wrap">
                            <span class="demo-bar-tag">State</span>
                            <div class="demo-bar-track">
                                <div class="demo-bar-fill demo-bar-state" style="width:<?= min(100, ($demo_context['state_sped'] ?? 0) * 5) ?>%"></div>
                            </div>
                            <span class="demo-bar-val"><?= h($demo_context['state_sped'] ?? '—') ?>%</span>
                        </div>
                    </div>
                </div>
                <div class="demo-context-bar-group">
                    <span class="demo-context-label">English Learners %</span>
                    <div class="demo-context-bars">
                        <div class="demo-bar-wrap">
                            <span class="demo-bar-tag">District</span>
                            <div class="demo-bar-track">
                                <div class="demo-bar-fill demo-bar-district" style="width:<?= min(100, ($demo_context['district_el'] ?? 0) * 5) ?>%"></div>
                            </div>
                            <span class="demo-bar-val"><?= h($demo_context['district_el'] ?? '—') ?>%</span>
                        </div>
                        <div class="demo-bar-wrap">
                            <span class="demo-bar-tag">State</span>
                            <div class="demo-bar-track">
                                <div class="demo-bar-fill demo-bar-state" style="width:<?= min(100, ($demo_context['state_el'] ?? 0) * 5) ?>%"></div>
                            </div>
                            <span class="demo-bar-val"><?= h($demo_context['state_el'] ?? '—') ?>%</span>
                        </div>
                    </div>
                </div>
                <div class="demo-context-bar-group">
                    <span class="demo-context-label">Low Income %</span>
                    <div class="demo-context-bars">
                        <div class="demo-bar-wrap">
                            <span class="demo-bar-tag">District</span>
                            <div class="demo-bar-track">
                                <div class="demo-bar-fill demo-bar-district" style="width:<?= min(100, ($demo_context['district_low_income'] ?? 0) * 2) ?>%"></div>
                            </div>
                            <span class="demo-bar-val"><?= h($demo_context['district_low_income'] ?? '—') ?>%</span>
                        </div>
                        <div class="demo-bar-wrap">
                            <span class="demo-bar-tag">State</span>
                            <div class="demo-bar-track">
                                <div class="demo-bar-fill demo-bar-state" style="width:<?= min(100, ($demo_context['state_low_income'] ?? 0) * 2) ?>%"></div>
                            </div>
                            <span class="demo-bar-val"><?= h($demo_context['state_low_income'] ?? '—') ?>%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (empty($restraint) && empty($demographics)): ?>
            <div class="empty-state"><h3>No data available</h3><p>School data is being imported. Check back soon for detailed analytics.</p></div>
        <?php endif; ?>
    </div>
</section>
