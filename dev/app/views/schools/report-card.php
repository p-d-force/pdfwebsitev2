<section class="section report-card">
<div class="container">
    <div class="report-card-header" style="border-bottom:2px solid var(--accent);padding-bottom:1rem;margin-bottom:2rem;">
        <h1 style="font-size:1.5rem;margin:0;"><?= h($school['org_name']) ?></h1>
        <p style="color:var(--text-secondary);margin:0.25rem 0 0;">
            <?= h($school['org_code']) ?> &middot; <?= h($school['town'] ?? '') ?> &middot; <?= h($school['grade_span'] ?? 'All grades') ?>
            <?php if (!empty($school['district_name'])): ?>
            &middot; <strong><?= h($school['district_name']) ?></strong>
            <?php endif; ?>
        </p>
    </div>

    <div class="report-stats" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:1rem;margin-bottom:2rem;">
        <div style="background:var(--bg-elevated);padding:1rem;border-radius:8px;text-align:center;">
            <div style="font-size:1.5rem;font-weight:700;color:var(--accent);"><?= number_format($latest_restraint['enrollment'] ?? 0) ?></div>
            <div style="font-size:0.75rem;color:var(--text-secondary);">Enrollment</div>
        </div>
        <div style="background:var(--bg-elevated);padding:1rem;border-radius:8px;text-align:center;">
            <div style="font-size:1.5rem;font-weight:700;color:var(--accent);"><?= number_format($latest_restraint['total_restraints'] ?? 0) ?></div>
            <div style="font-size:0.75rem;color:var(--text-secondary);">Total Restraints</div>
        </div>
        <div style="background:var(--bg-elevated);padding:1rem;border-radius:8px;text-align:center;">
            <div style="font-size:1.5rem;font-weight:700;color:var(--accent);"><?= $school_restraint_rate ?? '—' ?></div>
            <div style="font-size:0.75rem;color:var(--text-secondary);">Rate per 100</div>
        </div>
        <div style="background:var(--bg-elevated);padding:1rem;border-radius:8px;text-align:center;">
            <div style="font-size:1.5rem;font-weight:700;color:var(--accent);"><?= number_format($latest_restraint['total_injuries'] ?? 0) ?></div>
            <div style="font-size:0.75rem;color:var(--text-secondary);">Injuries</div>
        </div>
        <?php if ($state_avg_rate): ?>
        <div style="background:var(--bg-elevated);padding:1rem;border-radius:8px;text-align:center;">
            <div style="font-size:1.5rem;font-weight:700;color:var(--text-secondary);"><?= $state_avg_rate ?></div>
            <div style="font-size:0.75rem;color:var(--text-secondary);">State Avg Rate</div>
        </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($demographics)): ?>
    <div style="margin-bottom:2rem;">
        <h2 style="font-size:1.1rem;margin-bottom:0.5rem;">Demographics (District, <?= h($demographics['school_year'] ?? '') ?>)</h2>
        <div style="display:flex;gap:1.5rem;flex-wrap:wrap;">
            <div><strong>SPED:</strong> <?= h($demographics['sped_pct'] ?? '—') ?>%</div>
            <div><strong>English Learner:</strong> <?= h($demographics['el_pct'] ?? '—') ?>%</div>
            <div><strong>Low Income:</strong> <?= h($demographics['low_income_pct'] ?? '—') ?>%</div>
            <div><strong>High Needs:</strong> <?= h($demographics['high_needs_pct'] ?? '—') ?>%</div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($district_restraint_pct) || !empty($district_enroll_pct)): ?>
    <div style="margin-bottom:2rem;">
        <h2 style="font-size:1.1rem;margin-bottom:0.5rem;">District Comparison</h2>
        <p style="color:var(--text-secondary);">
            This school accounts for <strong><?= $district_enroll_pct ?>%</strong> of district enrollment 
            and <strong><?= $district_restraint_pct ?>%</strong> of district restraints.
        </p>
    </div>
    <?php endif; ?>

    <?php if (!empty($insights)): ?>
    <div style="margin-bottom:2rem;">
        <h2 style="font-size:1.1rem;margin-bottom:0.5rem;">Key Insights</h2>
        <ul style="color:var(--text-secondary);">
            <?php foreach ($insights as $insight): ?>
            <li><?= h($insight) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <p style="color:var(--text-muted);font-size:0.75rem;text-align:center;margin-top:3rem;">
        Generated <?= date('F j, Y') ?> &middot; Parent Data Force
    </p>
</div>
</section>
