<div class="admin-header"><h1>Submissions</h1></div>
<table class="admin-table">
    <thead><tr><th>Title</th><th>Type</th><th>Status</th><th>Submitted</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($submissions as $s): ?>
    <tr>
        <td><?= h(truncate($s['title'], 60)) ?></td>
        <td><?= h($s['submission_type'] ?? '—') ?></td>
        <td><?= status_badge($s['status']) ?></td>
        <td><?= h(format_date($s['submitted_at'])) ?></td>
        <td class="actions"><a href="/admin/submissions/<?= $s['id'] ?>" class="btn btn-ghost btn-sm">Review</a></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
