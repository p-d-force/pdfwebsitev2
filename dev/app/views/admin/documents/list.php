<div class="admin-header"><h1>Documents</h1></div>
<table class="admin-table">
    <thead><tr><th>Title</th><th>Family</th><th>Source</th><th>Public</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($docs as $d): ?>
    <tr>
        <td><?= h($d['title'] ?: $d['file_name'] ?: 'Untitled') ?></td>
        <td><?= h($d['doc_family'] ?? '—') ?></td>
        <td><?= h($d['source_system'] ?? '—') ?></td>
        <td><?= ($d['is_public'] ?? 0) ? 'Yes' : 'No' ?></td>
        <td><?= h($d['status'] ?? '—') ?></td>
        <td class="actions"><a href="/admin/documents/<?= $d['id'] ?>/edit" class="btn btn-ghost btn-sm">Edit</a></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
