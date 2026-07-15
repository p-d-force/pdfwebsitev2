<div class="admin-header">
    <h1><?= $legal_case ? 'Edit Case' : 'New Case' ?></h1>
    <a href="/admin/cases" class="btn btn-ghost btn-sm">← Back</a>
</div>
<form method="POST" class="admin-form">
    <?= csrf_field() ?>
    <div class="form-row">
        <div class="form-group"><label class="form-label">Case Number</label><input type="text" name="case_number" class="form-input" value="<?= h($legal_case['case_number'] ?? '') ?>" required></div>
        <div class="form-group"><label class="form-label">Slug</label><input type="text" name="slug" class="form-input" value="<?= h($legal_case['slug'] ?? '') ?>"></div>
    </div>
    <div class="form-group"><label class="form-label">Title</label><input type="text" name="title" class="form-input" value="<?= h($legal_case['title'] ?? '') ?>" required></div>
    <div class="form-row">
        <div class="form-group"><label class="form-label">Type</label><select name="case_type" class="form-select"><option value="">—</option><option value="PRS">PRS</option><option value="SPR">SPR</option><option value="PRR">PRR</option><option value="OCR">OCR</option><option value="BSEA">BSEA</option><option value="court">Court</option></select></div>
        <div class="form-group"><label class="form-label">Status</label><select name="status" class="form-select"><option value="open">Open</option><option value="active">Active</option><option value="resolved">Resolved</option><option value="closed">Closed</option></select></div>
    </div>
    <div class="form-group"><label class="form-label">Summary</label><textarea name="summary" class="form-textarea" rows="3"><?= h($legal_case['summary'] ?? '') ?></textarea></div>
    <div class="form-group"><label class="form-label">Body (HTML)</label><textarea name="body" class="form-textarea" rows="12"><?= h($legal_case['body'] ?? '') ?></textarea></div>
    <div class="admin-form-actions">
        <button type="submit" class="btn btn-primary">Save</button>
        <a href="/admin/cases" class="btn btn-ghost">Cancel</a>
    </div>
</form>
