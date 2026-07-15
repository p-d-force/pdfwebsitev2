<div class="admin-header">
    <h1>Articles</h1>
    <a href="/admin/articles/new" class="btn btn-primary btn-sm">New Article</a>
</div>
<table class="admin-table">
    <thead><tr><th>Title</th><th>Type</th><th>Featured</th><th>Status</th><th>Date</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($articles as $a): ?>
    <tr>
        <td><?= h($a['title']) ?></td>
        <td><?= h($a['article_type'] ?? '—') ?></td>
        <td><?= $a['is_featured'] ? 'Yes' : '—' ?></td>
        <td><?= $a['is_active'] ? 'Active' : 'Inactive' ?></td>
        <td><?= h(format_date($a['published_date'])) ?></td>
        <td class="actions"><a href="/admin/articles/<?= $a['id'] ?>/edit" class="btn btn-ghost btn-sm">Edit</a></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
