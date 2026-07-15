<section class="section">
    <div class="container">
        <div class="article-detail">
            <h1 style="margin-bottom:0.5rem;"><?= h($doc['title'] ?: $doc['file_name'] ?: 'Document') ?></h1>
            <?php if (!empty($doc['description'])): ?>
            <p style="color:var(--text-secondary);margin-bottom:1.5rem;"><?= h($doc['description']) ?></p>
            <?php endif; ?>

            <div class="case-meta-grid" style="margin-bottom:2rem;">
                <?php if (!empty($doc['doc_family'])): ?>
                <div class="case-meta-item"><span class="case-meta-label">Type</span><span class="case-meta-value"><?= h($doc['doc_family']) ?></span></div>
                <?php endif; ?>
                <?php if (!empty($doc['doc_subtype'])): ?>
                <div class="case-meta-item"><span class="case-meta-label">Subtype</span><span class="case-meta-value"><?= h($doc['doc_subtype']) ?></span></div>
                <?php endif; ?>
                <?php if (!empty($doc['file_mime'])): ?>
                <div class="case-meta-item"><span class="case-meta-label">Format</span><span class="case-meta-value"><?= h($doc['file_mime']) ?></span></div>
                <?php endif; ?>
                <?php if (!empty($doc['file_size'])): ?>
                <div class="case-meta-item"><span class="case-meta-label">Size</span><span class="case-meta-value"><?= number_format($doc['file_size'] / 1024, 1) ?> KB</span></div>
                <?php endif; ?>
                <?php if (!empty($doc['source_system'])): ?>
                <div class="case-meta-item"><span class="case-meta-label">Source</span><span class="case-meta-value"><?= h($doc['source_system']) ?></span></div>
                <?php endif; ?>
                <?php if (!empty($doc['document_date'])): ?>
                <div class="case-meta-item"><span class="case-meta-label">Date</span><span class="case-meta-value"><?= h(format_date($doc['document_date'])) ?></span></div>
                <?php endif; ?>
                <?php if (!empty($doc['pages'])): ?>
                <div class="case-meta-item"><span class="case-meta-label">Pages</span><span class="case-meta-value"><?= $doc['pages'] ?></span></div>
                <?php endif; ?>
            </div>

            <?php if (!empty($doc['body_text'])): ?>
            <div class="case-body">
                <?= nl2br(h($doc['body_text'])) ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($doc['file_url'])): ?>
            <div style="margin-top:2rem;">
                <a href="<?= h($doc['file_url']) ?>" class="btn btn-primary" target="_blank" rel="noopener">Download File</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>
