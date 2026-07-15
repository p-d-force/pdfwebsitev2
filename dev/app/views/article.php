<section class="section">
    <div class="container">
        <div class="article-detail">
            <header class="article-header">
                <div class="article-meta">
                    <span class="article-category"><?= h($article['article_type'] ?? 'Article') ?></span>
                    <span class="article-date"><?= h(format_date($article['published_date'])) ?></span>
                    <span class="article-read-time"><?= read_time($article['body'] ?? '') ?> min read</span>
                </div>
                <h1 class="article-title"><?= h($article['title']) ?></h1>
                <?php if (!empty($article['subtitle'])): ?>
                    <p class="article-subtitle"><?= h($article['subtitle']) ?></p>
                <?php endif; ?>
                <?php if (!empty($article['author'])): ?>
                    <p class="article-author">By <?= h($article['author']) ?></p>
                <?php endif; ?>
            </header>

            <div class="article-body">
                <?= sanitize($article['body'] ?? '') ?>
            </div>
        </div>
    </div>
</section>
