<section class="section">
    <div class="container">
        <div class="article-hero">
            <span class="section-tag">Articles</span>
            <h1 class="section-title">Research, Analysis &amp; Advocacy</h1>
            <p class="section-subtitle">Data-driven reporting on special education, public records, and systemic accountability across Massachusetts.</p>
        </div>

        <?php if (empty($articles)): ?>
            <div class="empty-state"><h3>No articles yet</h3><p>Check back soon for new content.</p></div>
        <?php else: ?>
            <div class="articles-grid" data-animate>
                <?php foreach ($articles as $a): ?>
                <article class="article-card">
                    <div class="article-card-body">
                        <div class="article-card-meta">
                            <span class="article-category"><?= h($a['article_type'] ?? 'Article') ?></span>
                            <span class="article-date"><?= h(format_date($a['published_date'])) ?></span>
                        </div>
                        <h3 class="article-card-title"><a href="/articles/<?= h($a['slug']) ?>"><?= h($a['title']) ?></a></h3>
                        <p class="article-card-excerpt"><?= h(truncate($a['excerpt'] ?? '', 200)) ?></p>
                        <div class="article-card-footer">
                            <span class="article-read-time"><?= read_time($a['excerpt'] ?? '') ?> min read</span>
                            <a href="/articles/<?= h($a['slug']) ?>" class="resource-link">Read Article</a>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
            <?= pagination_links($pagination, '/articles') ?>
        <?php endif; ?>
    </div>
</section>
