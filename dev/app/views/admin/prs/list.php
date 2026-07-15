<h1 class="admin-heading">PRS Cases</h1>

<div class="admin-toolbar">
    <a href="/admin/prs/new" class="btn btn-primary">+ New PRS Case</a>
    <a href="/admin/prs/import" class="btn btn-outline" style="margin-left:8px;">Import from Intakes</a>
    <a href="/admin/prs/quality" class="btn btn-outline" style="margin-left:8px;">Data Quality</a>
</div>

<?php if (!empty($quality_issues)): ?>
<div class="quality-alerts" style="margin:1rem 0;">
    <?php foreach ($quality_issues as $issue): ?>
    <div class="alert alert-<?= h($issue['severity'] === 'high' ? 'danger' : ($issue['severity'] === 'medium' ? 'warning' : 'info')) ?>">
        <strong><?= h($issue['label']) ?></strong> — <?= number_format($issue['count']) ?> issues
        <p style="margin:0.25rem 0 0;font-size:0.85rem;"><?= h($issue['description']) ?></p>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<table class="admin-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>PRS #</th>
            <th>Title</th>
            <th>District</th>
            <th>Status</th>
            <th>Filed</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($cases)): ?>
        <tr><td colspan="7" style="text-align:center;padding:2rem;">No PRS cases found.</td></tr>
    <?php else: ?>
        <?php foreach ($cases as $c): ?>
        <tr>
            <td><?= (int)$c['id'] ?></td>
            <td style="font-family:monospace;"><?= h($c['prs_number']) ?></td>
            <td><?= h(truncate($c['case_title'] ?? '(Untitled)', 60)) ?></td>
            <td><?= h($c['org_name'] ?? '—') ?></td>
            <td><?= h(ucfirst(str_replace('_', ' ', $c['current_status']))) ?></td>
            <td><?= format_date($c['filing_date']) ?></td>
            <td class="admin-actions">
                <a href="/admin/prs/<?= (int)$c['id'] ?>/edit" class="btn btn-sm btn-outline">Edit</a>
                <form method="POST" action="/admin/prs/<?= (int)$c['id'] ?>/delete" style="display:inline;" onsubmit="return confirm('Delete PRS <?= h($c['prs_number']) ?>? This cannot be undone.');">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>

<?php if (!empty($pagination) && $pagination['total_pages'] > 1): ?>
<div class="admin-pagination">
    <?= pagination_links($pagination, '/admin/prs') ?>
</div>
<?php endif; ?>
