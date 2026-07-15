<section class="section">
    <div class="container">
        <div class="section-header" data-animate>
            <span class="section-tag">Media</span>
            <h1 class="section-title">Appearances &amp; Media</h1>
            <p class="section-subtitle">Public comments, school committee testimony, press coverage, and advocacy appearances.</p>
        </div>

        <?php if (empty($appearances)): ?>
            <div class="empty-state"><h3>No appearances recorded</h3><p>Check back for updates.</p></div>
        <?php else: ?>
            <div class="case-list" data-animate>
                <?php foreach ($appearances as $a): ?>
                <article class="case-card" style="border-left-color:var(--accent-glow);">
                    <div class="case-card-header">
                        <span class="update-date"><?= h(format_date($a['appearance_date'])) ?></span>
                        <?php if (!empty($a['venue'])): ?>
                        <span style="font-size:0.8rem;color:var(--text-muted);"><?= h($a['venue']) ?></span>
                        <?php endif; ?>
                    </div>
                    <h3 class="case-card-title"><?= h($a['title'] ?? 'Untitled Appearance') ?></h3>
                    <?php if (!empty($a['description'])): ?>
                    <p class="case-card-summary"><?= h($a['description']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($a['url'])): ?>
                    <a href="<?= h($a['url']) ?>" class="resource-link" target="_blank" rel="noopener">View Recording / Article →</a>
                    <?php endif; ?>
                </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
