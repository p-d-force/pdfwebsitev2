<div class="admin-header">
    <h1>Updates</h1>
    <a href="/admin/updates/new" class="btn btn-primary btn-sm">New Update</a>
</div>
<table class="admin-table">
    <thead><tr><th>Title</th><th>Type</th><th>Date</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($updates as $u): ?>
    <tr>
        <td><?= h(truncate($u['title'], 60)) ?></td>
        <td><?= h($u['update_type'] ?? '—') ?></td>
        <td><?= h(format_date($u['published_date'])) ?></td>
        <td class="actions"><a href="/admin/updates/<?= $u['id'] ?>/edit" class="btn btn-ghost btn-sm">Edit</a></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
