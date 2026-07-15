<div class="admin-header">
    <h1>Media Appearances</h1>
    <a href="/admin/media/new" class="btn btn-primary btn-sm">New</a>
</div>
<table class="admin-table">
    <thead><tr><th>Title</th><th>Venue</th><th>Date</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($appearances as $a): ?>
    <tr>
        <td><?= h($a['title'] ?? 'Untitled') ?></td>
        <td><?= h($a['venue'] ?? '—') ?></td>
        <td><?= h(format_date($a['appearance_date'])) ?></td>
        <td class="actions"><a href="/admin/media/<?= $a['id'] ?>/edit" class="btn btn-ghost btn-sm">Edit</a></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
