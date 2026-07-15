<div class="admin-header"><h1>Organizations</h1></div>
<table class="admin-table">
    <thead><tr><th>Code</th><th>Name</th><th>Type</th><th>Town</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($orgs as $o): ?>
    <tr>
        <td style="font-family:monospace;"><?= h($o['org_code']) ?></td>
        <td><?= h($o['org_name']) ?></td>
        <td><?= h($o['org_type']) ?></td>
        <td><?= h($o['town'] ?? '—') ?></td>
        <td class="actions"><a href="/admin/organizations/<?= $o['id'] ?>/edit" class="btn btn-ghost btn-sm">Edit</a></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
