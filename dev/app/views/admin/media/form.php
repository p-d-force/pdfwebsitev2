<div class="admin-header">
    <h1><?= $appearance ? 'Edit Appearance' : 'New Appearance' ?></h1>
    <a href="/admin/media" class="btn btn-ghost btn-sm">← Back</a>
</div>
<form method="POST" class="admin-form">
    <?= csrf_field() ?>
    <div class="form-group"><label class="form-label">Title</label><input type="text" name="title" class="form-input" value="<?= h($appearance['title'] ?? '') ?>"></div>
    <div class="form-row">
        <div class="form-group"><label class="form-label">Venue</label><input type="text" name="venue" class="form-input" value="<?= h($appearance['venue'] ?? '') ?>"></div>
        <div class="form-group"><label class="form-label">Date</label><input type="date" name="appearance_date" class="form-input" value="<?= h($appearance['appearance_date'] ?? '') ?>"></div>
    </div>
    <div class="form-group"><label class="form-label">URL</label><input type="url" name="url" class="form-input" value="<?= h($appearance['url'] ?? '') ?>"></div>
    <div class="form-group"><label class="form-label">Description</label><textarea name="description" class="form-textarea" rows="3"><?= h($appearance['description'] ?? '') ?></textarea></div>
    <div class="admin-form-actions"><button type="submit" class="btn btn-primary">Save</button></div>
</form>
