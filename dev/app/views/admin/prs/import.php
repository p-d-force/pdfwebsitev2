<h1 class="admin-heading">Import PRS Cases from Intakes</h1>

<a href="/admin/prs" class="btn btn-outline" style="margin-bottom:1rem;">&larr; Back to PRS Cases</a>

<?php if ($imported > 0): ?>
<div class="alert alert-success">
    <strong>Imported <?= number_format($imported) ?> case(s)</strong> from prs_intakes.
</div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <strong>Errors:</strong>
    <ul>
    <?php foreach ($errors as $err): ?>
        <li><?= h($err) ?></li>
    <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="card" style="padding:1.5rem;">
    <h3>Import Status</h3>
    <p><strong>Remaining intakes to import:</strong> <?= number_format($remaining) ?></p>
    <?php if ($remaining > 0): ?>
    <form method="POST">
        <?= csrf_field() ?>
        <p>This will import all prs_intakes entries that don't already have a matching prs_number in prs_cases.</p>
        <button type="submit" class="btn btn-primary">Import All</button>
    </form>
    <?php else: ?>
    <p class="text-success">All prs_intakes entries have been imported.</p>
    <?php endif; ?>
</div>
