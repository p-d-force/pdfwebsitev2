<section class="section">
    <div class="container">
        <div class="section-header" data-animate>
            <span class="section-tag">PRS Analytics</span>
            <h2 class="section-title">PRS Complaint Analytics</h2>
            <p class="section-subtitle">Aggregate metrics across all Problem Resolution System complaints filed in Massachusetts.</p>
        </div>

        <!-- Stat Cards -->
        <div class="card-grid" data-animate>
            <div class="stat-card">
                <span class="stat-label">Total Cases</span>
                <span class="stat-value"><?= number_format($totalCases) ?></span>
            </div>
            <div class="stat-card">
                <span class="stat-label">Open Cases</span>
                <span class="stat-value"><?= number_format($openCases) ?></span>
            </div>
            <div class="stat-card">
                <span class="stat-label">Avg. Resolution</span>
                <span class="stat-value"><?= h($avgResolution) ?> days</span>
            </div>
            <div class="stat-card">
                <span class="stat-label">Deadline Compliance</span>
                <span class="stat-value"><?= h($deadlineCompliance) ?>%</span>
            </div>
        </div>

        <!-- Charts: Filing Trends & Resolution Analytics -->
        <div class="chart-grid" data-animate>
            <div class="chart-card">
                <h3 class="chart-title">Category Breakdown</h3>
                <?php
                $c0a = new App\Components\Chart('prs-cat-breakdown', 'doughnut');
                $catLabels = array_column($categoryData, 'allegation_category');
                $catValues = array_map('intval', array_column($categoryData, 'cnt'));
                $c0a->setLabels($catLabels);
                $c0a->addDataset('Cases', $catValues, ['palette' => 'default']);
                $c0a->setOption('plugins.legend.position', 'right');
                echo $c0a->render();
                ?>
            </div>

            <div class="chart-card">
                <h3 class="chart-title">Status Distribution</h3>
                <?php
                $c0b = new App\Components\Chart('prs-status-dist', 'bar');
                $sLabels = array_map(function($s) { return ucfirst(str_replace('_', ' ', $s)); }, array_column($statusData, 'current_status'));
                $sValues = array_map('intval', array_column($statusData, 'cnt'));
                $c0b->setLabels($sLabels);
                $statusColors = ['#60a5fa','#22c55e','#f59e0b','#ff5a1f','#a0a0a0','#a78bfa'];
                $c0b->addDataset('Cases', $sValues, ['backgroundColor' => $statusColors]);
                $c0b->setOption('plugins.legend.display', false);
                echo $c0b->render();
                ?>
            </div>

            <div class="chart-card">
                <h3 class="chart-title">Year-over-Year Change</h3>
                <?php
                $c0c = new App\Components\Chart('prs-yoy-change', 'bar');
                $yLabels = array_column($yoyData, 'yr');
                $c0c->setLabels($yLabels);
                $c0c->addDataset('YoY Change %', array_map('floatval', array_column($yoyData, 'change')), [
                    'backgroundColor' => array_map(function($v) { return $v >= 0 ? '#22c55e' : '#ef4444'; }, array_column($yoyData, 'change'))
                ]);
                $c0c->setOption('plugins.legend.display', false);
                echo $c0c->render();
                ?>
            </div>

            <div class="chart-card">
                <h3 class="chart-title">Filing Trend</h3>
                <?php
                $c1 = new App\Components\Chart('prs-filing-trend-new', 'line');
                $c1->setLabels(array_column($filing_trend, 'yr'));
                $c1->addDataset('Cases Filed', array_map('intval', array_column($filing_trend, 'cnt')), [
                    'borderColor' => '#ff5a1f',
                    'backgroundColor' => 'rgba(255,90,31,0.1)',
                    'fill' => true,
                    'unit' => '',
                ]);
                $c1->setOption('plugins.legend.display', false);
                echo $c1->render();
                ?>
            </div>

            <div class="chart-card">
                <h3 class="chart-title">Category Trend</h3>
                <?php
                // Pivot flat rows into per-category datasets
                $catYears = [];
                $catByCat = [];
                foreach ($category_trend as $row) {
                    $yr = $row['yr'];
                    $cat = $row['cat'];
                    if (!in_array($yr, $catYears, true)) $catYears[] = $yr;
                    $catByCat[$cat][$yr] = (int)$row['cnt'];
                }
                sort($catYears);
                $c2 = new App\Components\Chart('prs-cat-trend', 'line');
                $c2->setLabels($catYears);
                $palette = ['#ff5a1f','#60a5fa','#22c55e','#f59e0b','#a78bfa','#f472b6','#34d399','#fbbf24'];
                $pi = 0;
                foreach ($catByCat as $cat => $byYear) {
                    $values = array_map(function($y) use ($byYear) { return $byYear[$y] ?? 0; }, $catYears);
                    $c2->addDataset($cat, $values, [
                        'borderColor' => $palette[$pi % count($palette)],
                        'backgroundColor' => 'transparent',
                        'unit' => '',
                    ]);
                    $pi++;
                }
                $c2->setOption('plugins.legend.position', 'bottom');
                echo $c2->render();
                ?>
            </div>

            <div class="chart-card">
                <h3 class="chart-title">Resolution Time</h3>
                <?php
                $c3 = new App\Components\Chart('prs-res-time-new', 'bar');
                $c3->setLabels(array_column($resolution_time, 'bucket'));
                $c3->addDataset('Cases', array_map('intval', array_column($resolution_time, 'cnt')), ['palette' => 'warm']);
                $c3->setOption('plugins.legend.display', false);
                echo $c3->render();
                ?>
            </div>

            <div class="chart-card">
                <h3 class="chart-title">Substantiation Rate</h3>
                <?php
                $c4 = new App\Components\Chart('prs-subst-rate', 'line');
                $c4->setLabels(array_column($substantiation_rate, 'yr'));
                $c4->addDataset('Rate %', array_map('floatval', array_column($substantiation_rate, 'rate')), [
                    'borderColor' => '#22c55e',
                    'backgroundColor' => 'rgba(34,197,94,0.1)',
                    'fill' => true,
                    'unit' => '%',
                ]);
                $c4->setOption('plugins.legend.display', false);
                $c4->setOption('scales.y.min', 0);
                echo $c4->render();
                ?>
            </div>

            <div class="chart-card">
                <h3 class="chart-title">Top Districts by Case Volume</h3>
                <?php
                $c5 = new App\Components\Chart('prs-district-vol-new', 'bar');
                $dLabels = array_map(function($n) { return strlen($n) > 18 ? substr($n, 0, 17) . "\xe2\x80\xa6" : $n; }, array_column($district_volume, 'org_name'));
                $c5->setLabels($dLabels);
                $c5->addDataset('Cases', array_map('intval', array_column($district_volume, 'cnt')), ['palette' => 'cool']);
                $c5->setOption('indexAxis', 'y');
                $c5->setOption('plugins.legend.display', false);
                echo $c5->render();
                ?>
            </div>
        </div>

        <p class="back-link" data-animate><a href="/prs">&larr; Back to PRS Case List</a></p>
    </div>
</section>
