<div class="admin-header">
    <h1><?= $article ? 'Edit Article' : 'New Article' ?></h1>
    <a href="/admin/articles" class="btn btn-ghost btn-sm">← Back</a>
</div>
<form method="POST" class="admin-form">
    <?= csrf_field() ?>
    <div class="form-group"><label class="form-label">Title</label><input type="text" name="title" class="form-input" value="<?= h($article['title'] ?? '') ?>"></div>
    <div class="form-group"><label class="form-label">Slug</label><input type="text" name="slug" class="form-input" value="<?= h($article['slug'] ?? '') ?>"></div>
    <div class="form-row">
        <div class="form-group"><label class="form-label">Type</label><select name="article_type" class="form-select"><option value="analysis">Analysis</option><option value="guide">Guide</option><option value="report">Report</option><option value="news">News</option></select></div>
        <div class="form-group"><label class="form-label">Published Date</label><input type="date" name="published_date" class="form-input" value="<?= h($article['published_date'] ?? '') ?>"></div>
    </div>
    <div class="form-group"><label class="form-label">Excerpt</label><textarea name="excerpt" class="form-textarea" rows="2"><?= h($article['excerpt'] ?? '') ?></textarea></div>
    <div class="form-group"><label class="form-label">Body (HTML)</label><textarea name="body" class="form-textarea" rows="16"><?= h($article['body'] ?? '') ?></textarea></div>
    <div class="form-row">
        <div class="form-checkbox"><input type="checkbox" name="is_featured" value="1" <?= ($article['is_featured'] ?? 0) ? 'checked' : '' ?>> Featured</div>
        <div class="form-checkbox"><input type="checkbox" name="is_active" value="1" <?= ($article['is_active'] ?? 1) ? 'checked' : '' ?>> Active</div>
    </div>
    <div class="admin-form-actions">
        <button type="submit" class="btn btn-primary">Save</button>
        <a href="/admin/articles" class="btn btn-ghost">Cancel</a>
    </div>
</form>
