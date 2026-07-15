<h1 class="admin-heading">PRS Data Quality</h1>

<a href="/admin/prs" class="btn btn-outline" style="margin-bottom:1rem;">&larr; Back to PRS Cases</a>

<?php if (empty($issues)): ?>
<div class="alert alert-success">
    <strong>All clear!</strong> No data quality issues found in PRS tables.
</div>
<?php else: ?>
<div class="quality-summary" style="margin:1rem 0;">
    <strong><?= count($issues) ?> issue type(s) found</strong>
    &mdash; <?= number_format(array_sum(array_column($issues, 'count'))) ?> total records affected
</div>

<table class="admin-table">
    <thead>
        <tr>
            <th>Issue Type</th>
            <th>Description</th>
            <th>Count</th>
            <th>Severity</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($issues as $issue): ?>
        <tr>
            <td><strong><?= h($issue['label']) ?></strong></td>
            <td><?= h($issue['description']) ?></td>
            <td><?= number_format($issue['count']) ?></td>
            <td>
                <span class="status-badge status-<?= h($issue['severity']) ?>"><?= h(ucfirst($issue['severity'])) ?></span>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<div class="quality-actions" style="margin-top:2rem;">
    <h3>Resolution Guidance</h3>
    <ul>
        <li><strong>Orphan Events/Findings:</strong> These reference prs_case_ids that no longer exist. Delete them from the database.</li>
        <li><strong>Missing Deadlines:</strong> Cases with findings_issued_date but no statutory_deadline. Set statutory_deadline = findings_issued_date + 30 days as a default.</li>
        <li><strong>Date Inconsistencies:</strong> Review and correct the dates on these cases.</li>
        <li><strong>Overdue Cases:</strong> Cases past their statutory deadline. Prioritize review.</li>
        <li><strong>No Events:</strong> Cases with no timeline at all. Add at minimum a "filed" event.</li>
    </ul>
</div>
<?php endif; ?>
