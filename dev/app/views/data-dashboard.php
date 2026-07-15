<section class="section" id="statewide-dashboard">
    <div class="container">
        <div class="section-header" data-animate>
            <span class="section-tag">Dashboard</span>
            <h2 class="section-title">Statewide Data Dashboard</h2>
            <p class="section-subtitle">Key metrics and trends across all Massachusetts public school districts. School year <?= h($latestYear ?? '') ?>.</p>
        </div>

        <!-- ═══ Stat Cards ═══ -->
        <div class="stat-cards" data-animate>
            <div class="stat-card">
                <div class="stat-value"><?= number_format((int)($curStats['total_students'] ?? 0)) ?></div>
                <div class="stat-label">Total Students</div>
                <?= yoy_badge((float)($curStats['total_students'] ?? 0), (float)($prevStats['total_students'] ?? 0)) ?>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= number_format((int)($curStats['total_restraints'] ?? 0)) ?></div>
                <div class="stat-label">Total Restraints</div>
                <?= yoy_badge((float)($curStats['total_restraints'] ?? 0), (float)($prevStats['total_restraints'] ?? 0)) ?>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= number_format((float)($curStats['avg_attendance'] ?? 0), 2) ?>%</div>
                <div class="stat-label">Avg Attendance Rate</div>
                <?= yoy_badge((float)($curStats['avg_attendance'] ?? 0), (float)($prevStats['avg_attendance'] ?? 0)) ?>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= number_format((float)($curStats['avg_sped_grad'] ?? 0), 2) ?>%</div>
                <div class="stat-label">Avg SPED Grad Rate</div>
                <?= yoy_badge((float)($curStats['avg_sped_grad'] ?? 0), (float)($prevStats['avg_sped_grad'] ?? 0)) ?>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= number_format((int)($curStats['total_prs'] ?? 0)) ?></div>
                <div class="stat-label">Total PRS Complaints</div>
                <div class="stat-delta delta-neutral">all-time</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= number_format((int)($curStats['districts_reporting'] ?? 0)) ?></div>
                <div class="stat-label">Districts Reporting</div>
                <div class="stat-delta delta-neutral"><?= h($latestYear) ?></div>
            </div>
        </div>

        <!-- ═══ 2×2 Chart Grid ═══ -->
        <div class="dashboard-grid" data-animate>
            <div class="chart-card">
                <h3>Statewide Restraint Trends</h3>
                <?php
                $chart = new \App\Components\Chart('restraintTrendsChart', 'bar');
                $chart->setLabels(array_column($restraintTrends, 'school_year'));
                $chart->addDataset('Total Restraints',
                    array_map('intval', array_column($restraintTrends, 'restraints')),
                    ['palette' => 'warm', 'backgroundColor' => '#ff5a1f']
                );
                $chart->setOption('scales', [
                    'y' => ['beginAtZero' => true, 'title' => ['display' => true, 'text' => 'Restraints', 'color' => '#a0a0a0']],
                    'x' => ['title' => ['display' => true, 'text' => 'School Year', 'color' => '#a0a0a0']],
                ]);
                echo $chart->render();
                ?>
            </div>
            <div class="chart-card">
                <h3>Discipline Breakdown (Statewide Avg %)</h3>
                <?php
                $chart2 = new \App\Components\Chart('disciplineBreakdownChart', 'bar');
                $chart2->setLabels(array_column($disciplineData, 'school_year'));
                $chart2->addDataset('In-School Suspension',
                    array_map('floatval', array_column($disciplineData, 'in_school')),
                    ['backgroundColor' => '#f59e0b']
                );
                $chart2->addDataset('Out-of-School Suspension',
                    array_map('floatval', array_column($disciplineData, 'out_school')),
                    ['backgroundColor' => '#ef4444']
                );
                $chart2->addDataset('Expulsion',
                    array_map('floatval', array_column($disciplineData, 'expulsion')),
                    ['backgroundColor' => '#8b5cf6']
                );
                $chart2->setOption('scales', [
                    'x' => ['stacked' => true],
                    'y' => ['stacked' => true, 'beginAtZero' => true, 'title' => ['display' => true, 'text' => '%', 'color' => '#a0a0a0']],
                ]);
                $chart2->setOption('plugins', ['legend' => ['position' => 'top']]);
                echo $chart2->render();
                ?>
            </div>
            <div class="chart-card">
                <h3>Enrollment Demographics (<?= h($latestYear) ?>)</h3>
                <?php
                $chart3 = new \App\Components\Chart('demoDoughnutChart', 'doughnut');
                $chart3->setLabels(['SPED', 'English Learner', 'Low Income', 'High Needs']);
                $chart3->addDataset('Students',
                    [
                        (int)($demos['sped'] ?? 0),
                        (int)($demos['el'] ?? 0),
                        (int)($demos['low_income'] ?? 0),
                        (int)($demos['high_needs'] ?? 0),
                    ],
                    ['palette' => 'default']
                );
                $chart3->setOption('plugins', ['legend' => ['position' => 'bottom']]);
                echo $chart3->render();
                ?>
            </div>
            <div class="chart-card">
                <h3>Attendance vs SPED Graduation Rate</h3>
                <?php
                $chart4 = new \App\Components\Chart('attendanceSpedChart', 'line');
                $years4 = array_column($attendanceTrends, 'school_year');
                $chart4->setLabels($years4);
                $chart4->addDataset('Attendance Rate',
                    array_map('floatval', array_column($attendanceTrends, 'attendance')),
                    ['borderColor' => '#22c55e', 'backgroundColor' => 'rgba(34,197,94,0.1)', 'fill' => false, 'tension' => 0.3, 'yAxisID' => 'y']
                );
                $chart4->addDataset('SPED Grad Rate',
                    array_map('floatval', array_column($spedTrends, 'sped_grad')),
                    ['borderColor' => '#ff5a1f', 'backgroundColor' => 'rgba(255,90,31,0.1)', 'fill' => false, 'tension' => 0.3, 'yAxisID' => 'y1']
                );
                $chart4->setOption('scales', [
                    'y'  => ['beginAtZero' => false, 'position' => 'left', 'title' => ['display' => true, 'text' => 'Attendance %', 'color' => '#22c55e']],
                    'y1' => ['beginAtZero' => false, 'position' => 'right', 'title' => ['display' => true, 'text' => 'SPED Grad %', 'color' => '#ff5a1f'], 'grid' => ['drawOnChartArea' => false]],
                ]);
                echo $chart4->render();
                ?>
            </div>
        </div>

        <!-- ═══ Correlation Views ═══ -->
        <div class="section-header" data-animate>
            <h2 class="section-title" style="font-size:1.4rem;">Correlation Views</h2>
            <p class="section-subtitle">District-level patterns in <?= h($latestYear) ?>.</p>
        </div>

        <div class="dashboard-grid" data-animate>
            <div class="chart-card">
                <h3>Restraint vs Discipline Rate</h3>
                <?php
                $scatterData = [];
                foreach ($correlation as $d) {
                    $enr = max((int)($d['enrollment'] ?? 1), 1);
                    $rate = round(((int)($d['total_restraints'] ?? 0) / $enr) * 1000, 1);
                    $discRate = round((float)($d['in_school_susp'] ?? 0) + (float)($d['out_school_susp'] ?? 0), 1);
                    if ($rate > 0 || $discRate > 0) {
                        $scatterData[] = ['x' => $rate, 'y' => $discRate];
                    }
                }
                $scatter = new \App\Components\Chart('restDiscScatter', 'scatter');
                $scatter->addDataset('Districts', $scatterData, [
                    'backgroundColor' => '#ff5a1f',
                    'pointRadius' => 4,
                    'pointHoverRadius' => 6,
                ]);
                $scatter->setOption('scales', [
                    'x' => ['title' => ['display' => true, 'text' => 'Restraints per 1,000 Students', 'color' => '#a0a0a0'], 'beginAtZero' => true],
                    'y' => ['title' => ['display' => true, 'text' => 'Discipline Rate %', 'color' => '#a0a0a0'], 'beginAtZero' => true],
                ]);
                $scatter->setOption('plugins', ['legend' => ['display' => false]]);
                echo $scatter->render();
                ?>
            </div>
            <div class="chart-card">
                <h3>Poverty vs Outcomes</h3>
                <div class="correlation-subgrid">
                    <div class="mini-chart">
                        <?php
                        $povSped = [];
                        foreach ($correlation as $d) {
                            $lp = (float)($d['low_income_pct'] ?? 0);
                            $sg = (float)($d['sped_grad_rate'] ?? 0);
                            if ($lp > 0 && $sg > 0) $povSped[] = ['x' => $lp, 'y' => $sg];
                        }
                        $ps = new \App\Components\Chart('povSpedScatter', 'scatter');
                        $ps->addDataset('Low Income % vs SPED Grad %', $povSped, ['backgroundColor' => '#22c55e', 'pointRadius' => 3]);
                        $ps->setOption('scales', [
                            'x' => ['title' => ['display' => true, 'text' => 'Low Income %', 'color' => '#a0a0a0'], 'beginAtZero' => false],
                            'y' => ['title' => ['display' => true, 'text' => 'SPED Grad %', 'color' => '#a0a0a0'], 'beginAtZero' => false],
                        ]);
                        $ps->setOption('plugins', ['legend' => ['display' => false]]);
                        echo $ps->render();
                        ?>
                    </div>
                    <div class="mini-chart">
                        <?php
                        $povRest = [];
                        foreach ($correlation as $d) {
                            $lp = (float)($d['low_income_pct'] ?? 0);
                            $enr2 = max((int)($d['enrollment'] ?? 1), 1);
                            $rr = round(((int)($d['total_restraints'] ?? 0) / $enr2) * 1000, 1);
                            if ($lp > 0 && $rr > 0) $povRest[] = ['x' => $lp, 'y' => $rr];
                        }
                        $pr = new \App\Components\Chart('povRestScatter', 'scatter');
                        $pr->addDataset('Low Income % vs Restraint Rate', $povRest, ['backgroundColor' => '#f59e0b', 'pointRadius' => 3]);
                        $pr->setOption('scales', [
                            'x' => ['title' => ['display' => true, 'text' => 'Low Income %', 'color' => '#a0a0a0'], 'beginAtZero' => false],
                            'y' => ['title' => ['display' => true, 'text' => 'Restraint/1k', 'color' => '#a0a0a0'], 'beginAtZero' => true],
                        ]);
                        $pr->setOption('plugins', ['legend' => ['display' => false]]);
                        echo $pr->render();
                        ?>
                    </div>
                    <div class="mini-chart">
                        <?php
                        $povDisc = [];
                        foreach ($correlation as $d) {
                            $lp = (float)($d['low_income_pct'] ?? 0);
                            $dr = round((float)($d['in_school_susp'] ?? 0) + (float)($d['out_school_susp'] ?? 0), 1);
                            if ($lp > 0 && $dr > 0) $povDisc[] = ['x' => $lp, 'y' => $dr];
                        }
                        $pd = new \App\Components\Chart('povDiscScatter', 'scatter');
                        $pd->addDataset('Low Income % vs Discipline %', $povDisc, ['backgroundColor' => '#ef4444', 'pointRadius' => 3]);
                        $pd->setOption('scales', [
                            'x' => ['title' => ['display' => true, 'text' => 'Low Income %', 'color' => '#a0a0a0'], 'beginAtZero' => false],
                            'y' => ['title' => ['display' => true, 'text' => 'Discipline %', 'color' => '#a0a0a0'], 'beginAtZero' => true],
                        ]);
                        $pd->setOption('plugins', ['legend' => ['display' => false]]);
                        echo $pd->render();
                        ?>
                    </div>
                    <div class="mini-chart">
                        <?php
                        $povAtt = [];
                        foreach ($correlation as $d) {
                            $lp = (float)($d['low_income_pct'] ?? 0);
                            $att = (float)($d['attendance_rate'] ?? 0);
                            if ($lp > 0 && $att > 0) $povAtt[] = ['x' => $lp, 'y' => $att];
                        }
                        $pa = new \App\Components\Chart('povAttScatter', 'scatter');
                        $pa->addDataset('Low Income % vs Attendance %', $povAtt, ['backgroundColor' => '#60a5fa', 'pointRadius' => 3]);
                        $pa->setOption('scales', [
                            'x' => ['title' => ['display' => true, 'text' => 'Low Income %', 'color' => '#a0a0a0'], 'beginAtZero' => false],
                            'y' => ['title' => ['display' => true, 'text' => 'Attendance %', 'color' => '#a0a0a0'], 'beginAtZero' => false],
                        ]);
                        $pa->setOption('plugins', ['legend' => ['display' => false]]);
                        echo $pa->render();
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ YoY Tracking ═══ -->
        <div class="section-header yoy-section" data-animate>
            <h2 class="section-title" style="font-size:1.4rem;">Year-over-Year Changes</h2>
            <p class="section-subtitle">Restraint and attendance changes from <?= h($prevYear) ?> to <?= h($latestYear) ?>.</p>
        </div>

        <!-- Biggest Movers -->
        <?php if (!empty($mostImproved) || !empty($mostDeclined)): ?>
        <div class="yoy-movers" data-animate>
            <div class="mover-card improved">
                <h4>Most Improved (Restraint Reduction)</h4>
                <?php foreach ($mostImproved as $m):
                    $chg = ((int)($m['cur_restraints'] ?? 0) - (int)($m['prev_restraints'] ?? 0));
                    $pct = ($m['prev_restraints'] ?? 0) > 0 ? round(($chg / $m['prev_restraints']) * 100, 1) : 0;
                ?>
                <div class="mover-district"><?= h($m['org_name']) ?></div>
                <div class="mover-change delta-down">&#9660; <?= abs($chg) ?> restraints (<?= abs($pct) ?>%)</div>
                <?php endforeach; ?>
            </div>
            <div class="mover-card declined">
                <h4>Most Declined (Restraint Increase)</h4>
                <?php foreach ($mostDeclined as $m):
                    $chg = ((int)($m['cur_restraints'] ?? 0) - (int)($m['prev_restraints'] ?? 0));
                    $pct = ($m['prev_restraints'] ?? 0) > 0 ? round(($chg / $m['prev_restraints']) * 100, 1) : 0;
                ?>
                <div class="mover-district"><?= h($m['org_name']) ?></div>
                <div class="mover-change delta-up">&#9650; +<?= $chg ?> restraints (+<?= $pct ?>%)</div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- YoY Table -->
        <?php if (!empty($yoyData)): ?>
        <div class="yoy-table-wrap" data-animate>
            <table>
                <thead>
                    <tr>
                        <th>District</th>
                        <th class="num"><?= h($latestYear) ?> Restraints</th>
                        <th class="num"><?= h($prevYear) ?> Restraints</th>
                        <th class="num">Change</th>
                        <th class="num"><?= h($latestYear) ?> Attendance</th>
                        <th class="num"><?= h($prevYear) ?> Attendance</th>
                        <th class="num">Change</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($yoyData as $row):
                        $rChg = (int)($row['cur_restraints'] ?? 0) - (int)($row['prev_restraints'] ?? 0);
                        $aChg = round((float)($row['cur_attendance'] ?? 0) - (float)($row['prev_attendance'] ?? 0), 1);
                    ?>
                    <tr>
                        <td><a href="/districts/<?= h($row['org_code'] ?? '') ?>"><?= h($row['org_name']) ?></a></td>
                        <td class="num"><?= number_format((int)($row['cur_restraints'] ?? 0)) ?></td>
                        <td class="num"><?= number_format((int)($row['prev_restraints'] ?? 0)) ?></td>
                        <td class="num" style="color:<?= $rChg > 0 ? 'var(--danger)' : ($rChg < 0 ? 'var(--success)' : '') ?>">
                            <?= $rChg > 0 ? '+' : '' ?><?= number_format($rChg) ?>
                        </td>
                        <td class="num"><?= number_format((float)($row['cur_attendance'] ?? 0), 1) ?>%</td>
                        <td class="num"><?= number_format((float)($row['prev_attendance'] ?? 0), 1) ?>%</td>
                        <td class="num" style="color:<?= $aChg > 0 ? 'var(--success)' : ($aChg < 0 ? 'var(--danger)' : '') ?>">
                            <?= $aChg > 0 ? '+' : '' ?><?= number_format($aChg, 1) ?>%
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- ═══ Rankings ═══ -->
        <div class="section-header rankings-section" data-animate>
            <h2 class="section-title" style="font-size:1.4rem;">District Rankings</h2>
            <p class="section-subtitle"><?= h($latestYear) ?> school year. Top and bottom 10.</p>
        </div>

        <div class="rankings-section" data-animate>
            <div class="rankings-tabs">
                <button class="ranking-tab active" onclick="switchRankingTab('restraint', this)">Highest Restraint</button>
                <button class="ranking-tab" onclick="switchRankingTab('attendance', this)">Lowest Attendance</button>
                <button class="ranking-tab" onclick="switchRankingTab('sped', this)">Highest SPED Gap</button>
                <button class="ranking-tab" onclick="switchRankingTab('prs', this)">Most PRS</button>
            </div>

            <!-- Restraint Rankings -->
            <div class="ranking-panel active" id="ranking-restraint">
                <div class="rankings-columns">
                    <div class="ranking-list">
                        <h4>Highest Restraint Incidents</h4>
                        <table>
                            <thead><tr><th>#</th><th>District</th><th class="rank-value">Total</th></tr></thead>
                            <tbody>
                                <?php $ri = 1; foreach ($rankRestraintTop as $r): ?>
                                <tr>
                                    <td class="rank-num"><?= $ri++ ?></td>
                                    <td><a href="/districts/<?= h($r['org_code']) ?>"><?= h($r['org_name']) ?></a></td>
                                    <td class="rank-value"><?= number_format((int)$r['total']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="ranking-list">
                        <h4>Lowest Restraint Incidents</h4>
                        <table>
                            <thead><tr><th>#</th><th>District</th><th class="rank-value">Total</th></tr></thead>
                            <tbody>
                                <?php $ri = 1; foreach ($rankRestraintBottom as $r): ?>
                                <tr>
                                    <td class="rank-num"><?= $ri++ ?></td>
                                    <td><a href="/districts/<?= h($r['org_code']) ?>"><?= h($r['org_name']) ?></a></td>
                                    <td class="rank-value"><?= number_format((int)$r['total']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Attendance Rankings -->
            <div class="ranking-panel" id="ranking-attendance">
                <div class="rankings-columns">
                    <div class="ranking-list">
                        <h4>Lowest Attendance Rate</h4>
                        <table>
                            <thead><tr><th>#</th><th>District</th><th class="rank-value">Rate</th></tr></thead>
                            <tbody>
                                <?php $ri = 1; foreach ($rankAttendanceBottom as $r): ?>
                                <tr>
                                    <td class="rank-num"><?= $ri++ ?></td>
                                    <td><a href="/districts/<?= h($r['org_code']) ?>"><?= h($r['org_name']) ?></a></td>
                                    <td class="rank-value"><?= number_format((float)$r['val'], 1) ?>%</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="ranking-list">
                        <h4>Highest Attendance Rate</h4>
                        <table>
                            <thead><tr><th>#</th><th>District</th><th class="rank-value">Rate</th></tr></thead>
                            <tbody>
                                <?php $ri = 1; foreach ($rankAttendanceTop as $r): ?>
                                <tr>
                                    <td class="rank-num"><?= $ri++ ?></td>
                                    <td><a href="/districts/<?= h($r['org_code']) ?>"><?= h($r['org_name']) ?></a></td>
                                    <td class="rank-value"><?= number_format((float)$r['val'], 1) ?>%</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- SPED Gap Rankings -->
            <div class="ranking-panel" id="ranking-sped">
                <div class="rankings-columns">
                    <div class="ranking-list">
                        <h4>Lowest SPED Graduation Rate</h4>
                        <table>
                            <thead><tr><th>#</th><th>District</th><th class="rank-value">Rate</th></tr></thead>
                            <tbody>
                                <?php $ri = 1; foreach ($rankSpedBottom as $r): ?>
                                <tr>
                                    <td class="rank-num"><?= $ri++ ?></td>
                                    <td><a href="/districts/<?= h($r['org_code']) ?>"><?= h($r['org_name']) ?></a></td>
                                    <td class="rank-value"><?= number_format((float)$r['val'], 1) ?>%</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="ranking-list">
                        <h4>Highest SPED Graduation Rate</h4>
                        <table>
                            <thead><tr><th>#</th><th>District</th><th class="rank-value">Rate</th></tr></thead>
                            <tbody>
                                <?php $ri = 1; foreach ($rankSpedTop as $r): ?>
                                <tr>
                                    <td class="rank-num"><?= $ri++ ?></td>
                                    <td><a href="/districts/<?= h($r['org_code']) ?>"><?= h($r['org_name']) ?></a></td>
                                    <td class="rank-value"><?= number_format((float)$r['val'], 1) ?>%</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- PRS Rankings -->
            <div class="ranking-panel" id="ranking-prs">
                <div class="rankings-columns">
                    <div class="ranking-list" style="grid-column: 1 / -1; max-width: 600px;">
                        <h4>Most PRS Complaints</h4>
                        <table>
                            <thead><tr><th>#</th><th>District</th><th class="rank-value">Complaints</th></tr></thead>
                            <tbody>
                                <?php $ri = 1; foreach ($rankPrs as $r): ?>
                                <tr>
                                    <td class="rank-num"><?= $ri++ ?></td>
                                    <td><a href="/districts/<?= h($r['org_code']) ?>"><?= h($r['org_name']) ?></a></td>
                                    <td class="rank-value"><?= number_format((int)$r['val']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
function switchRankingTab(tabId, btn) {
    document.querySelectorAll('.ranking-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.ranking-panel').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    var panel = document.getElementById('ranking-' + tabId);
    if (panel) panel.classList.add('active');
}
</script>
