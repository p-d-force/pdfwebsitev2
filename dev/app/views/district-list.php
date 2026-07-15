<section class="section">
    <div class="container">
        <div class="section-header" data-animate>
            <span class="section-tag">Districts</span>
            <h1 class="section-title">Massachusetts School Districts</h1>
            <p class="section-subtitle">Browse districts tracked by Parent Data Force. View restraint data, enrollment, cases, and advocacy history.</p>
        </div>

        <?php if (empty($districts)): ?>
            <div class="empty-state"><h3>No districts found</h3><p>District data is being imported. Check back soon.</p></div>
        <?php else: ?>
            <div class="district-list" data-animate>
                <?php foreach ($districts as $d): ?>
                <a href="/districts/<?= h(strtolower($d['org_code'])) ?>/" class="district-card">
                    <div class="district-card-header">
                        <div class="district-card-name"><?= h($d['org_name']) ?></div>
                        <?php if (!empty($d['badges'])): ?>
                        <div class="district-card-badges">
                            <?php foreach ($d['badges'] as $badge): ?>
                            <span class="badge <?= h($badge['class']) ?>"><?= h($badge['label']) ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="district-card-meta">
                        <span><?= h($d['town'] ?? '') ?></span>
                        <span><?= h($d['org_code']) ?></span>
                        <?php if (!empty($d['prs_count'])): ?>
                        <span><?= $d['prs_count'] ?> PRS case<?= $d['prs_count'] != 1 ? 's' : '' ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($d['sparkline'])): ?>
                    <div class="district-card-sparkline">
                        <canvas class="sparkline-canvas" data-sparkline data-values="<?= h(json_encode($d['sparkline'])) ?>"></canvas>
                    </div>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
            <?= pagination_links($pagination, '/districts') ?>
        <?php endif; ?>
    </div>
</section>
