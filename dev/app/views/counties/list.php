<section class="section">
    <div class="container">
        <div class="section-header" data-animate>
            <span class="section-tag">Counties</span>
            <h1 class="section-title">Massachusetts Counties</h1>
            <p class="section-subtitle">Browse school district data by county. Select a county to view all districts, restraint statistics, and trends.</p>
        </div>

        <?php if (empty($counties)): ?>
            <div class="empty-state"><h3>No counties found</h3><p>County data is being populated. Check back soon.</p></div>
        <?php else: ?>
            <div class="district-list" style="grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));" data-animate>
                <?php foreach ($counties as $c): ?>
                <a href="/counties/<?= h($c['slug']) ?>" class="district-card">
                    <div class="district-card-name"><?= h($c['county_name']) ?> County</div>
                    <div class="district-card-meta">
                        <span><?= (int)($c['district_count'] ?? 0) ?> district<?= ($c['district_count'] ?? 0) != 1 ? 's' : '' ?></span>
                        <?php if (!empty($c['total_enrollment'])): ?>
                        <span><?= number_format((int)($c['total_enrollment'] ?? 0)) ?> students</span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($c['total_restraints'])): ?>
                    <div class="district-card-meta" style="color: var(--color-warning, #ff5a1f);">
                        <span><?= number_format((int)$c['total_restraints']) ?> restraints</span>
                    </div>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
