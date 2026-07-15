<section class="section">
    <div class="container">
        <div class="case-detail">
            <header class="case-header">
                <div class="case-number"><?= h($case['case_number']) ?></div>
                <h1 class="case-title"><?= h($case['title']) ?></h1>
                <div class="case-meta-grid">
                    <div class="case-meta-item">
                        <span class="case-meta-label">Type</span>
                        <span class="case-meta-value"><?= h($case['case_type'] ?? '—') ?></span>
                    </div>
                    <div class="case-meta-item">
                        <span class="case-meta-label">Status</span>
                        <span class="case-meta-value"><?= status_badge($case['status'] ?? 'open') ?></span>
                    </div>
                    <div class="case-meta-item">
                        <span class="case-meta-label">Filed</span>
                        <span class="case-meta-value"><?= h(format_date($case['filed_date']) ?: '—') ?></span>
                    </div>
                    <?php if (!empty($case['resolved_date'])): ?>
                    <div class="case-meta-item">
                        <span class="case-meta-label">Resolved</span>
                        <span class="case-meta-value"><?= h(format_date($case['resolved_date'])) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($case['court'])): ?>
                    <div class="case-meta-item">
                        <span class="case-meta-label">Venue</span>
                        <span class="case-meta-value"><?= h($case['court']) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($case['plaintiff'])): ?>
                    <div class="case-meta-item">
                        <span class="case-meta-label">Plaintiff</span>
                        <span class="case-meta-value"><?= h($case['plaintiff']) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($case['defendant'])): ?>
                    <div class="case-meta-item">
                        <span class="case-meta-label">Defendant</span>
                        <span class="case-meta-value"><?= h($case['defendant']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </header>

            <?php if (!empty($case['summary'])): ?>
            <div style="background:var(--bg-elevated);border:1px solid var(--border);border-radius:var(--radius-md);padding:1.25rem;margin-bottom:2rem;">
                <p style="font-size:1.1rem;color:var(--text-secondary);"><?= h($case['summary']) ?></p>
            </div>
            <?php endif; ?>

            <?php if (!empty($case['body'])): ?>
            <div class="case-body">
                <?= sanitize($case['body']) ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($case['ruling'])): ?>
            <div style="margin-top:2rem;padding:1.25rem;background:rgba(255,90,31,0.05);border:1px solid rgba(255,90,31,0.2);border-radius:var(--radius-md);">
                <h3>Ruling / Outcome</h3>
                <p><?= sanitize($case['ruling']) ?></p>
            </div>
            <?php endif; ?>

            <?php if (!empty($documents)): ?>
            <div class="case-documents">
                <h3>Documents</h3>
                <?php foreach ($documents as $d): ?>
                <a href="/documents/<?= $d['id'] ?>" class="doc-link"><?= h($d['title'] ?: $d['file_name']) ?></a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>
