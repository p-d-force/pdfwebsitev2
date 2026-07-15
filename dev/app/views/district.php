<section class="section">
    <div class="container">
        <div class="district-hero">
            <h1 class="district-hero-name"><?= h($district['org_name']) ?></h1>
            <span class="district-hero-code"><?= h($district['org_code']) ?> &middot; <?= h($district['org_type']) ?></span>
            <?php if (!empty($district['town'])): ?>
            <span class="district-hero-code"> &middot; <?= h($district['town']) ?>, MA</span>
            <?php endif; ?>
            <?php if (!empty($district['grade_span'])): ?>
            <span class="district-hero-code"> &middot; Grades <?= h($district['grade_span']) ?></span>
            <?php endif; ?>
        </div>

        <div class="district-hero-stats">
            <div class="hero-stat">Town: <?= h($district['town'] ?? '—') ?></div>
            <div class="hero-stat">County: <?php if (!empty($district['county_name'])): ?><a href="/counties/<?= h($district['county_slug'] ?? '') ?>" style="color:var(--accent-glow);"><?= h($district['county_name']) ?></a><?php else: ?>—<?php endif; ?></div>
            <div class="hero-stat">Schools: <?= number_format($school_count) ?></div>
            <div class="hero-stat">Enrollment: <?= number_format($total_enrollment) ?></div>
        </div>

        <div class="district-dashboard">
            <!-- Cases -->
            <?php if (!empty($cases)): ?>
            <div class="dashboard-panel">
                <h3>Cases &amp; Advocacy</h3>
                <?php foreach ($cases as $c): ?>
                <div class="dashboard-stat">
                    <a href="/cases/<?= h($c['slug'] ?? $c['case_number']) ?>" style="color:var(--text-primary);font-size:0.9rem;"><?= h($c['title']) ?></a>
                    <?= status_badge($c['status'] ?? 'open') ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Restraint -->
            <?php if (!empty($restraintData)): ?>
            <div class="dashboard-panel">
                <h3>Restraint Data</h3>
                <?php foreach ($restraintData as $r): ?>
                <div class="dashboard-stat">
                    <span class="dashboard-stat-label"><?= h($r['school_year']) ?></span>
                    <span class="dashboard-stat-value"><?= number_format($r['total_restraints'] ?? 0) ?> restraints</span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Demographics -->
            <?php if (!empty($enrollmentData)): ?>
            <div class="dashboard-panel">
                <h3>Demographics (<?= h($enrollmentData['school_year'] ?? 'Latest') ?>)</h3>
                <div class="dashboard-stat"><span class="dashboard-stat-label">SPED</span><span class="dashboard-stat-value"><?= h($enrollmentData['sped_pct'] ?? '—') ?>%</span></div>
                <div class="dashboard-stat"><span class="dashboard-stat-label">English Learner</span><span class="dashboard-stat-value"><?= h($enrollmentData['el_pct'] ?? '—') ?>%</span></div>
                <div class="dashboard-stat"><span class="dashboard-stat-label">Low Income</span><span class="dashboard-stat-value"><?= h($enrollmentData['low_income_pct'] ?? '—') ?>%</span></div>
                <div class="dashboard-stat"><span class="dashboard-stat-label">High Needs</span><span class="dashboard-stat-value"><?= h($enrollmentData['high_needs_pct'] ?? '—') ?>%</span></div>
            <?php endif; ?>
        </div>

        <?php if (!empty($spedData)): ?>
            <div class="dashboard-panel">
                <h3>Special Education Outcomes (<?= h($spedData['school_year'] ?? 'Latest') ?>)</h3>
                <div class="dashboard-stat"><span class="dashboard-stat-label">Graduation Rate</span><span class="dashboard-stat-value"><?= h($spedData['sped_grad_rate'] ?? '—') ?>%</span></div>
                <div class="dashboard-stat"><span class="dashboard-stat-label">Dropout Rate</span><span class="dashboard-stat-value"><?= h($spedData['sped_dropout_rate'] ?? '—') ?>%</span></div>
                <div class="dashboard-stat"><span class="dashboard-stat-label">Full Inclusion (LRE A)</span><span class="dashboard-stat-value"><?= h($spedData['lre_full_incl_pct'] ?? '—') ?>%</span></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($trendChart)): ?>
            <div class="dashboard-panel dashboard-panel--wide">
                <h3>Restraint Trend</h3>
                <?= $trendChart ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($spedChart)): ?>
            <div class="dashboard-panel dashboard-panel--wide">
                <h3>SPED Outcomes vs. State Average</h3>
                <?= $spedChart ?>
            </div>
        <?php endif; ?>


        <?php if (!empty($similarDistricts)): ?>
        <div class="dashboard-panel" data-animate>
            <h3>Similar Districts</h3>
            <p style="color:var(--text-secondary);font-size:0.85rem;margin-bottom:0.75rem;">Districts with comparable enrollment size</p>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:0.75rem;">
                <?php foreach ($similarDistricts as $sd): ?>
                <a href="/districts/<?= h(strtolower($sd['org_code'])) ?>" style="text-decoration:none;background:var(--bg-elevated);border:1px solid var(--border);border-radius:8px;padding:0.75rem;display:block;transition:border-color 0.2s;" onmouseover="this.style.borderColor='var(--accent)'" onmouseout="this.style.borderColor='var(--border)'">
                    <div style="font-weight:600;color:var(--text-primary);"><?= h($sd['org_name']) ?></div>
                    <div style="font-size:0.8rem;color:var(--text-secondary);"><?= h($sd['town'] ?? '') ?> &middot; <?= h($sd['org_code']) ?></div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php if (empty($cases) && empty($restraintData) && empty($enrollmentData) && empty($spedData)): ?>
            <div class="empty-state"><h3>No data available</h3><p>District data is being imported. Check back soon for detailed analytics.</p></div>
        <?php endif; ?>
    </div>
</section>
