<section class="section">
    <div class="container">
        <p class="breadcrumb" data-animate><a href="/prs">PRS Cases</a> &rsaquo; District View</p>

        <div class="section-header" data-animate>
            <span class="section-tag">PRS District View</span>
            <h2 class="section-title"><?= h($district['org_name']) ?></h2>
            <p class="section-subtitle"><?= number_format($total) ?> PRS cases &middot; <?= number_format($openCases) ?> open &middot; <?= number_format($substantiated) ?> substantiated</p>
        </div>

        <p>
            <a href="/districts/<?= h($district['org_code']) ?>" class="btn btn-outline">View District Profile</a>
        </p>

        <!-- Summary Stats -->
        <div class="card-grid" data-animate>
            <div class="stat-card">
                <span class="stat-label">Total PRS Cases</span>
                <span class="stat-value"><?= number_format($total) ?></span>
            </div>
            <div class="stat-card">
                <span class="stat-label">Open Cases</span>
                <span class="stat-value"><?= number_format($openCases) ?></span>
            </div>
            <div class="stat-card">
                <span class="stat-label">Substantiated</span>
                <span class="stat-value"><?= number_format($substantiated) ?></span>
            </div>
        </div>

        <!-- Charts -->
        <div class="chart-grid" data-animate>
            <?php if (!empty($filingTrend)): ?>
            <div class="chart-card">
                <h3 class="chart-title">Filings per Year</h3>
                <?php
                $dc1 = new App\Components\Chart('dist-prs-trend', 'line');
                $dc1->setLabels(array_column($filingTrend, 'yr'));
                $dc1->addDataset('Cases', array_map('intval', array_column($filingTrend, 'cnt')), [
                    'borderColor' => '#ff5a1f', 'backgroundColor' => 'rgba(255,90,31,0.1)', 'fill' => true
                ]);
                $dc1->setOption('plugins.legend.display', false);
                echo $dc1->render();
                ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($categoryBreakdown)): ?>
            <div class="chart-card">
                <h3 class="chart-title">Category Breakdown</h3>
                <?php
                $dc2 = new App\Components\Chart('dist-prs-cats', 'doughnut');
                $dc2->setLabels(array_column($categoryBreakdown, 'allegation_category'));
                $dc2->addDataset('Cases', array_map('intval', array_column($categoryBreakdown, 'cnt')), ['palette' => 'default']);
                $dc2->setOption('plugins.legend.position', 'right');
                echo $dc2->render();
                ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($statusDist)): ?>
            <div class="chart-card">
                <h3 class="chart-title">Status Distribution</h3>
                <?php
                $dc3 = new App\Components\Chart('dist-prs-status', 'bar');
                $dc3->setLabels(array_map(function($s) { return ucfirst(str_replace('_', ' ', $s)); }, array_column($statusDist, 'current_status')));
                $dc3->addDataset('Cases', array_map('intval', array_column($statusDist, 'cnt')), [
                    'backgroundColor' => ['#60a5fa','#22c55e','#f59e0b','#ff5a1f','#a0a0a0','#a78bfa']
                ]);
                $dc3->setOption('plugins.legend.display', false);
                echo $dc3->render();
                ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Case Table -->
        <?php if (empty($cases)): ?>
            <div class="empty-state" data-animate>
                <h3>No PRS cases for this district</h3>
            </div>
        <?php else: ?>
            <div style="overflow-x:auto;" data-animate>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>PRS #</th>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Filed</th>
                            <th>Closed</th>
                            <th>Days Open</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cases as $c): ?>
                        <tr style="cursor:pointer;" onclick="location.href='/prs/<?= h($c['prs_number']) ?>'">
                            <td><strong><?= h($c['prs_number']) ?></strong></td>
                            <td><?= h(truncate($c['case_title'] ?? 'Untitled', 40)) ?></td>
                            <td><?= prsStatusBadge($c['current_status']) ?></td>
                            <td><?= format_date($c['filing_date']) ?></td>
                            <td><?= $c['closure_date'] ? format_date($c['closure_date']) : '—' ?></td>
                            <td><span class="num"><?= $c['total_days_open'] !== null ? number_format((int)$c['total_days_open']) : '—' ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?= pagination_links($pagination, '/prs/district/' . h($district['org_code'])) ?>
        <?php endif; ?>

        <p class="back-link" data-animate><a href="/prs">&larr; Back to PRS Case List</a></p>
    </div>
</section>
