<div class="admin-header">
    <h1>Cases</h1>
    <a href="/admin/cases/new" class="btn btn-primary btn-sm">New Case</a>
</div>
<table class="admin-table">
    <thead><tr><th>Case #</th><th>Title</th><th>Type</th><th>Status</th><th>Filed</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($cases as $c): ?>
    <tr>
        <td style="font-family:monospace;"><?= h($c['case_number']) ?></td>
        <td><?= h(truncate($c['title'], 60)) ?></td>
        <td><?= h($c['case_type'] ?? '—') ?></td>
        <td><?= h($c['status']) ?></td>
        <td><?= h(format_date($c['filed_date'])) ?></td>
        <td class="actions"><a href="/admin/cases/<?= $c['id'] ?>/edit" class="btn btn-ghost btn-sm">Edit</a></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
