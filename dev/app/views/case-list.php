<section class="section">
    <div class="container">
        <div class="article-hero">
            <span class="section-tag">Cases</span>
            <h1 class="section-title">Case Directory</h1>
            <p class="section-subtitle">Active investigations, public records requests, appeals, and state determinations.</p>
        </div>

        <form method="GET" action="/cases/" class="case-filters" data-animate>
            <input type="text" name="q" class="form-input" placeholder="Search cases..." value="<?= h($_GET['q'] ?? '') ?>">
            <select name="type" class="form-select">
                <option value="">All types</option>
                <option value="PRS" <?= ($_GET['type'] ?? '') === 'PRS' ? 'selected' : '' ?>>PRS</option>
                <option value="SPR" <?= ($_GET['type'] ?? '') === 'SPR' ? 'selected' : '' ?>>SPR</option>
                <option value="PRR" <?= ($_GET['type'] ?? '') === 'PRR' ? 'selected' : '' ?>>PRR</option>
                <option value="OCR" <?= ($_GET['type'] ?? '') === 'OCR' ? 'selected' : '' ?>>OCR</option>
                <option value="BSEA" <?= ($_GET['type'] ?? '') === 'BSEA' ? 'selected' : '' ?>>BSEA</option>
            </select>
            <select name="status" class="form-select">
                <option value="">All statuses</option>
                <option value="open" <?= ($_GET['status'] ?? '') === 'open' ? 'selected' : '' ?>>Open</option>
                <option value="active" <?= ($_GET['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="resolved" <?= ($_GET['status'] ?? '') === 'resolved' ? 'selected' : '' ?>>Resolved</option>
            </select>
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        </form>

        <?php if (empty($cases)): ?>
            <div class="empty-state"><h3>No cases found</h3><p>Try adjusting your filters.</p></div>
        <?php else: ?>
            <div class="case-list" data-animate>
                <?php foreach ($cases as $c): ?>
                <article class="case-card">
                    <div class="case-card-header">
                        <span class="case-card-number"><?= h($c['case_number']) ?></span>
                        <?= status_badge($c['status'] ?? 'open') ?>
                    </div>
                    <h3 class="case-card-title"><a href="/cases/<?= h($c['slug'] ?? $c['case_number']) ?>"><?= h($c['title']) ?></a></h3>
                    <div class="case-card-meta">
                        <span><?= h($c['case_type'] ?? '') ?></span>
                        <span>Filed: <?= h(format_date($c['filed_date'])) ?></span>
                    </div>
                    <p class="case-card-summary"><?= h(truncate($c['summary'] ?? '', 200)) ?></p>
                </article>
                <?php endforeach; ?>
            </div>
            <?= pagination_links($pagination, '/cases') ?>
        <?php endif; ?>
    </div>
</section>
