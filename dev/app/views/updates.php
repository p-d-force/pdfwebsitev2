<section class="section">
    <div class="container">
        <div class="section-header" data-animate>
            <span class="section-tag">Updates</span>
            <h1 class="section-title">Latest Updates</h1>
            <p class="section-subtitle">Case developments, new data, site updates, and advocacy news.</p>
        </div>

        <?php if (empty($updates)): ?>
            <div class="empty-state"><h3>No updates yet</h3><p>Check back for the latest developments.</p></div>
        <?php else: ?>
            <div class="updates-list" data-animate>
                <?php foreach ($updates as $u): ?>
                <div class="update-item">
                    <div class="update-item-head">
                        <span class="update-date"><?= h(format_datetime($u['published_date'])) ?></span>
                        <?php if (!empty($u['update_type'])): ?>
                        <span style="font-size:0.75rem;color:var(--accent);text-transform:uppercase;"><?= h($u['update_type']) ?></span>
                        <?php endif; ?>
                    </div>
                    <h4 class="update-title"><?= h($u['title']) ?></h4>
                    <?php if (!empty($u['excerpt'])): ?>
                    <p class="update-body"><?= h($u['excerpt']) ?></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
