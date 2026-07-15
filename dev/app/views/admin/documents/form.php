<div class="admin-header"><h1>Edit Document</h1><a href="/admin/documents" class="btn btn-ghost btn-sm">← Back</a></div>
<form method="POST" class="admin-form">
    <?= csrf_field() ?>
    <div class="form-group"><label class="form-label">Title</label><input type="text" name="title" class="form-input" value="<?= h($doc['title'] ?? '') ?>"></div>
    <div class="form-row">
        <div class="form-group"><label class="form-label">Doc Family</label><input type="text" name="doc_family" class="form-input" value="<?= h($doc['doc_family'] ?? '') ?>"></div>
        <div class="form-group"><label class="form-label">Source System</label><input type="text" name="source_system" class="form-input" value="<?= h($doc['source_system'] ?? '') ?>"></div>
    </div>
    <div class="form-group"><label class="form-label">Description</label><textarea name="description" class="form-textarea" rows="3"><?= h($doc['description'] ?? '') ?></textarea></div>
    <div class="form-checkbox"><input type="checkbox" name="is_public" value="1" <?= ($doc['is_public'] ?? 0) ? 'checked' : '' ?>> Public</div>
    <div class="admin-form-actions"><button type="submit" class="btn btn-primary">Save</button></div>
</form>
