<div class="admin-header">
    <h1><?= $update ? 'Edit Update' : 'New Update' ?></h1>
    <a href="/admin/updates" class="btn btn-ghost btn-sm">← Back</a>
</div>
<form method="POST" class="admin-form">
    <?= csrf_field() ?>
    <div class="form-group"><label class="form-label">Title</label><input type="text" name="title" class="form-input" value="<?= h($update['title'] ?? '') ?>" required></div>
    <div class="form-row">
        <div class="form-group"><label class="form-label">Type</label><select name="update_type" class="form-select"><option value="case">Case</option><option value="data">Data</option><option value="site">Site</option><option value="advocacy">Advocacy</option></select></div>
    </div>
    <div class="form-group"><label class="form-label">Excerpt</label><textarea name="excerpt" class="form-textarea" rows="2"><?= h($update['excerpt'] ?? '') ?></textarea></div>
    <div class="admin-form-actions"><button type="submit" class="btn btn-primary">Save</button></div>
</form>
