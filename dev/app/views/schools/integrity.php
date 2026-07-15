<section class="section">
    <div class="container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <ol>
                <li><a href="/">Home</a></li>
                <li><a href="/schools/">Schools</a></li>
                <li aria-current="page">Data Integrity</li>
            </ol>
        </nav>

        <h1 style="font-size:2rem;font-weight:800;color:var(--text-primary);margin-bottom:0.25rem;">Data Integrity Check</h1>
        <p style="color:var(--text-muted);margin-bottom:2rem;">
            Last checked: <?= h($checked_at) ?> &middot;
            <?php if ($total_issues === 0): ?>
                <span style="color:var(--green)">All checks passed</span>
            <?php else: ?>
                <span style="color:var(--accent)"><?= number_format($total_issues) ?> issues found across <?= count($issues) ?> categories</span>
            <?php endif; ?>
        </p>

        <?php if (empty($issues)): ?>
        <div class="integrity-all-clear">
            <div class="integrity-check-icon">&#10003;</div>
            <h3>No data quality issues detected</h3>
            <p>All active schools have parent districts, enrollment data, and no orphan records.</p>
        </div>
        <?php else: ?>
        <div class="integrity-summary">
            <?php foreach ($issues as $issue): ?>
            <div class="integrity-card integrity-<?= h($issue['severity']) ?>">
                <div class="integrity-card-header">
                    <span class="integrity-severity-badge severity-<?= h($issue['severity']) ?>"><?= h(strtoupper($issue['severity'])) ?></span>
                    <h3><?= h($issue['label']) ?></h3>
                    <span class="integrity-count"><?= number_format($issue['count']) ?> rows</span>
                </div>
                <?php if (!empty($issue['rows'])): ?>
                <div class="integrity-table-wrap">
                    <table class="integrity-table">
                        <thead>
                            <tr>
                                <?php foreach (array_keys($issue['rows'][0]) as $col): ?>
                                <th><?= h(str_replace('_', ' ', $col)) ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($issue['rows'] as $row): ?>
                            <tr>
                                <?php foreach ($row as $val): ?>
                                <td><?= h($val ?? 'NULL') ?></td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>
