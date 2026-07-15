<section class="section">
    <div class="container">
        <div class="section-header" data-animate>
            <h2 class="section-title">Search Results</h2>
            <p class="section-subtitle">
                Results for "<strong><?= h($query) ?></strong>" —
                <?= $total ?> result<?= $total !== 1 ? 's' : '' ?> found
            </p>
        </div>

        <?php if (empty($results)): ?>
            <div class="empty-state"><h3>No results found</h3><p>Try different search terms or browse our <a href="/articles/">articles</a> and <a href="/cases/">cases</a>.</p></div>
        <?php else: ?>
            <div class="case-list" data-animate>
                <?php foreach ($results as $r): ?>
                <article class="case-card" style="border-left-color:<?= $r['type'] === 'article' ? 'var(--accent-glow)' : 'var(--accent)' ?>;">
                    <div class="case-card-header">
                        <span style="font-size:0.75rem;text-transform:uppercase;color:var(--text-muted);"><?= $r['type'] === 'article' ? 'Article' : 'Case' ?></span>
                        <?php if ($r['type'] === 'case'): ?>
                        <span class="case-card-number"><?= h($r['case_number'] ?? '') ?></span>
                        <?php endif; ?>
                    </div>
                    <h3 class="case-card-title">
                        <a href="/<?= $r['type'] === 'article' ? 'articles' : 'cases' ?>/<?= h($r['slug'] ?? $r['case_number'] ?? '') ?>">
                            <?= h($r['title']) ?>
                        </a>
                    </h3>
                    <p class="case-card-summary"><?= h(truncate($r['excerpt'] ?? $r['summary'] ?? '', 200)) ?></p>
                </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
