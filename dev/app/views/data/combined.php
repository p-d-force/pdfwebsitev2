<section class="section">
<div class="container">
    <div class="section-header" data-animate>
        <span class="section-tag">Cross-Reference</span>
        <h2 class="section-title">Combined Data: PRS + DESE</h2>
        <p class="section-subtitle">Side-by-side PRS filings and DESE-reported metrics for Massachusetts districts.</p>
    </div>

    <form method="get" class="filter-bar" data-animate>
        <div class="form-group" style="min-width:300px;">
            <label class="form-label">Select District</label>
            <select name="district" onchange="this.form.submit()" class="form-select">
                <option value="">— Choose a district —</option>
                <?php foreach ($districts as $d): ?>
                <option value="<?= h($d['org_code']) ?>" <?= ($districtCode === $d['org_code']) ? 'selected' : '' ?>><?= h($d['org_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>

    <?php if ($selectedDistrict): ?>
    <div class="card-grid" data-animate>
        <div class="stat-card">
            <span class="stat-label">District</span>
            <span class="stat-value" style="font-size:1.2rem;"><?= h($selectedDistrict['org_name']) ?></span>
        </div>
        <div class="stat-card">
            <span class="stat-label">Total PRS Cases</span>
            <span class="stat-value"><?= number_format($prsTotal) ?></span>
        </div>
    </div>

    <?php if ($chartHtml): ?>
    <div class="chart-card" data-animate>
        <h3>PRS Cases vs. Restraints Over Time</h3>
        <?= $chartHtml ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($mergedByYear)): ?>
    <div style="overflow-x:auto;" data-animate>
        <table class="data-table">
            <thead>
                <tr><th>Year</th><th>PRS Cases</th><th>Restraints</th><th>Enrollment</th><th>Rate/1K</th></tr>
            </thead>
            <tbody>
                <?php foreach ($mergedByYear as $r): ?>
                <tr>
                    <td><strong><?= h($r['year']) ?></strong></td>
                    <td><?= number_format($r['prs_count']) ?></td>
                    <td><?= number_format($r['restraints']) ?></td>
                    <td><?= number_format($r['enrollment']) ?></td>
                    <td><?= $r['enrollment'] > 0 ? number_format($r['restraints'] * 1000 / $r['enrollment'], 1) : '—' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if ($deseEnrollment): ?>
    <div class="chart-card" data-animate>
        <h3>Demographics (<?= h($deseEnrollment['school_year']) ?>)</h3>
        <div style="display:flex;gap:1.5rem;flex-wrap:wrap;">
            <div><strong>SPED:</strong> <?= h($deseEnrollment['sped_pct']) ?>%</div>
            <div><strong>English Learner:</strong> <?= h($deseEnrollment['el_pct']) ?>%</div>
            <div><strong>Low Income:</strong> <?= h($deseEnrollment['low_income_pct']) ?>%</div>
            <div><strong>High Needs:</strong> <?= h($deseEnrollment['high_needs_pct']) ?>%</div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($deseSped): ?>
    <div class="chart-card" data-animate>
        <h3>SPED Outcomes (<?= h($deseSped['school_year']) ?>)</h3>
        <div style="display:flex;gap:1.5rem;flex-wrap:wrap;">
            <div><strong>Grad Rate:</strong> <?= h($deseSped['sped_grad_rate']) ?>%</div>
            <div><strong>Dropout Rate:</strong> <?= h($deseSped['sped_dropout_rate']) ?>%</div>
            <div><strong>Full Inclusion:</strong> <?= h($deseSped['lre_full_incl_pct']) ?>%</div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($prsCases)): ?>
    <div class="chart-card" data-animate>
        <h3>Recent PRS Cases</h3>
        <table class="data-table">
            <thead><tr><th>PRS #</th><th>Title</th><th>Status</th><th>Filed</th><th>Resolution</th></tr></thead>
            <tbody>
                <?php foreach ($prsCases as $c): ?>
                <tr>
                    <td><a href="/prs/<?= h($c['prs_number']) ?>"><?= h($c['prs_number']) ?></a></td>
                    <td><?= h(truncate($c['case_title'], 40)) ?></td>
                    <td><?= prsStatusBadge($c['current_status']) ?></td>
                    <td><?= h(format_date($c['filing_date'])) ?></td>
                    <td><?= h($c['resolution_type'] ?? '—') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <div class="empty-state" data-animate>
        <h3>Select a district to view combined data</h3>
        <p>Choose a district from the dropdown above to see PRS cases alongside DESE-reported restraint, discipline, attendance, and demographic data.</p>
    </div>
    <?php endif; ?>
</div>
</section>
