<section class="section">
    <div class="container">
        <div class="section-header" data-animate>
            <span class="section-tag">County</span>
            <h1 class="section-title"><?= h($county['county_name']) ?> County</h1>
            <p class="section-subtitle">
                <?= (int)($district_count ?? 0) ?> school district<?= ($district_count ?? 0) != 1 ? 's' : '' ?>
                <?php if (!empty($stats['total_enrollment'])): ?>
                 &middot; <?= number_format((int)$stats['total_enrollment']) ?> students
                <?php endif; ?>
                <?php if (!empty($stats['total_restraints'])): ?>
                 &middot; <?= number_format((int)$stats['total_restraints']) ?> restraints
                <?php endif; ?>
            </p>
        </div>

        <!-- Stats cards -->
        <div class="stat-cards" data-animate style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:1rem; margin-bottom:2rem;">
            <div class="stat-card" style="background:var(--color-surface,#1a1a2e); border:1px solid var(--color-border,#2a2a3e); border-radius:8px; padding:1.25rem;">
                <div style="color:var(--color-text-secondary,#888); font-size:0.8rem; text-transform:uppercase; letter-spacing:0.05em;">Districts</div>
                <div style="font-size:1.75rem; font-weight:700; color:var(--color-text,#e0e0e0);"><?= (int)($district_count ?? 0) ?></div>
            </div>
            <div class="stat-card" style="background:var(--color-surface,#1a1a2e); border:1px solid var(--color-border,#2a2a3e); border-radius:8px; padding:1.25rem;">
                <div style="color:var(--color-text-secondary,#888); font-size:0.8rem; text-transform:uppercase; letter-spacing:0.05em;">Students</div>
                <div style="font-size:1.75rem; font-weight:700; color:var(--color-text,#e0e0e0);"><?= number_format((int)($stats['total_enrollment'] ?? 0)) ?></div>
            </div>
            <div class="stat-card" style="background:var(--color-surface,#1a1a2e); border:1px solid var(--color-border,#2a2a3e); border-radius:8px; padding:1.25rem;">
                <div style="color:var(--color-text-secondary,#888); font-size:0.8rem; text-transform:uppercase; letter-spacing:0.05em;">Restraints</div>
                <div style="font-size:1.75rem; font-weight:700; color:var(--color-warning,#ff5a1f);"><?= number_format((int)($stats['total_restraints'] ?? 0)) ?></div>
            </div>
            <div class="stat-card" style="background:var(--color-surface,#1a1a2e); border:1px solid var(--color-border,#2a2a3e); border-radius:8px; padding:1.25rem;">
                <div style="color:var(--color-text-secondary,#888); font-size:0.8rem; text-transform:uppercase; letter-spacing:0.05em;">Students Restrained</div>
                <div style="font-size:1.75rem; font-weight:700; color:var(--color-warning,#ff5a1f);"><?= number_format((int)($stats['students_restrained'] ?? 0)) ?></div>
            </div>
            <?php if (!empty($prsStats)): ?>
            <div class="stat-card" style="background:var(--color-surface,#1a1a2e); border:1px solid var(--color-border,#2a2a3e); border-radius:8px; padding:1.25rem;">
                <div style="color:var(--color-text-secondary,#888); font-size:0.8rem; text-transform:uppercase; letter-spacing:0.05em;">PRS Cases</div>
                <div style="font-size:1.75rem; font-weight:700; color:var(--accent,#ff5a1f);"><?= number_format((int)($prsStats['total'] ?? 0)) ?></div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Restraint trend chart -->
        <?php if (!empty($trendChart)): ?>
        <div data-animate style="margin-bottom:2rem;">
            <h2 style="margin-bottom:1rem; color:var(--color-text,#e0e0e0);">Restraint Trend (5-Year)</h2>
            <div style="background:var(--color-surface,#1a1a2e); border:1px solid var(--color-border,#2a2a3e); border-radius:8px; padding:1.5rem;">
                <?= $trendChart ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- All counties comparison -->
        <?php if (!empty($compareChart)): ?>
        <div data-animate style="margin-bottom:2rem;">
            <h2 style="margin-bottom:1rem; color:var(--color-text,#e0e0e0);">County Comparison</h2>
            <div style="background:var(--color-surface,#1a1a2e); border:1px solid var(--color-border,#2a2a3e); border-radius:8px; padding:1.5rem;">
                <?= $compareChart ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- PRS Trend -->
        <?php if (!empty($prsTrendChart)): ?>
        <div data-animate style="margin-bottom:2rem;">
            <h2 style="margin-bottom:1rem; color:var(--color-text,#e0e0e0);">PRS Filing Trend</h2>
            <div style="background:var(--color-surface,#1a1a2e); border:1px solid var(--color-border,#2a2a3e); border-radius:8px; padding:1.5rem;">
                <?= $prsTrendChart ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Town Breakdown -->
        <?php if (!empty($townBreakdown)): ?>
        <div data-animate style="margin-bottom:2rem;">
            <h2 style="margin-bottom:1rem; color:var(--color-text,#e0e0e0);">Towns in <?= h($county['county_name']) ?> County</h2>
            <div style="overflow-x:auto;">
                <table class="data-table">
                    <thead><tr><th>Town</th><th>Enrollment</th><th>Restraints</th><th>Rate/100</th></tr></thead>
                    <tbody>
                        <?php foreach ($townBreakdown as $t): ?>
                        <tr>
                            <td><?= h($t['town']) ?></td>
                            <td><?= number_format((int)$t['enrollment']) ?></td>
                            <td><?= number_format((int)$t['restraints']) ?></td>
                            <td><span style="color:<?= $t['rate'] > 5 ? 'var(--danger)' : ($t['rate'] > 2 ? 'var(--warning)' : 'var(--success)') ?>"><?= $t['rate'] ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- District list -->
        <div data-animate>
            <h2 style="margin-bottom:1rem; color:var(--color-text,#e0e0e0);">School Districts in <?= h($county['county_name']) ?> County</h2>
            <?php if (empty($districts)): ?>
                <div class="empty-state"><p>No districts mapped to this county yet.</p></div>
            <?php else: ?>
                <div class="district-list" data-animate>
                    <?php foreach ($districts as $d): ?>
                    <a href="/districts/<?= h(strtolower($d['org_code'] ?? '')) ?>" class="district-card">
                        <div class="district-card-name"><?= h($d['org_name']) ?></div>
                        <div class="district-card-meta">
                            <span><?= h($d['town'] ?? '') ?></span>
                            <span><?= h($d['org_code'] ?? '') ?></span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
