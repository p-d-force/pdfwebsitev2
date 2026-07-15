<div class="admin-header"><h1>Review Submission</h1><a href="/admin/submissions" class="btn btn-ghost btn-sm">← Back</a></div>
<div style="background:var(--bg-elevated);border:1px solid var(--border);border-radius:var(--radius-md);padding:1.5rem;margin-bottom:1.5rem;">
    <h2 style="margin-bottom:0.5rem;"><?= h($submission['title']) ?></h2>
    <div class="case-meta-grid" style="margin-bottom:1.5rem;">
        <div class="case-meta-item"><span class="case-meta-label">Type</span><span class="case-meta-value"><?= h($submission['submission_type'] ?? '—') ?></span></div>
        <div class="case-meta-item"><span class="case-meta-label">Status</span><span class="case-meta-value"><?= status_badge($submission['status']) ?></span></div>
        <div class="case-meta-item"><span class="case-meta-label">Submitted</span><span class="case-meta-value"><?= h(format_datetime($submission['submitted_at'])) ?></span></div>
        <?php if (!empty($submission['submitter_name'])): ?>
        <div class="case-meta-item"><span class="case-meta-label">From</span><span class="case-meta-value"><?= h($submission['submitter_name']) ?><?= !empty($submission['submitter_email']) ? ' (' . h($submission['submitter_email']) . ')' : '' ?></span></div>
        <?php endif; ?>
    </div>
    <div style="white-space:pre-wrap;color:var(--text-secondary);"><?= h($submission['body'] ?? '') ?></div>
</div>
<form method="POST" style="display:flex;gap:0.75rem;">
    <?= csrf_field() ?>
    <button type="submit" name="action" value="accept" class="btn btn-primary btn-sm">Accept</button>
    <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm">Reject</button>
</form>
