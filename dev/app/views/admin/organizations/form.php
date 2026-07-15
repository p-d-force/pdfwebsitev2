<div class="admin-header"><h1>Edit Organization</h1><a href="/admin/organizations" class="btn btn-ghost btn-sm">← Back</a></div>
<form method="POST" class="admin-form">
    <?= csrf_field() ?>
    <div class="form-row">
        <div class="form-group"><label class="form-label">Org Code</label><input type="text" name="org_code" class="form-input" value="<?= h($org['org_code'] ?? '') ?>"></div>
        <div class="form-group"><label class="form-label">Org Type</label><input type="text" name="org_type" class="form-input" value="<?= h($org['org_type'] ?? '') ?>"></div>
    </div>
    <div class="form-group"><label class="form-label">Name</label><input type="text" name="org_name" class="form-input" value="<?= h($org['org_name'] ?? '') ?>"></div>
    <div class="form-row">
        <div class="form-group"><label class="form-label">Town</label><input type="text" name="town" class="form-input" value="<?= h($org['town'] ?? '') ?>"></div>
        <div class="form-group"><label class="form-label">Grade Span</label><input type="text" name="grade_span" class="form-input" value="<?= h($org['grade_span'] ?? '') ?>"></div>
    </div>
    <div class="form-checkbox"><input type="checkbox" name="is_active" value="1" <?= ($org['is_active'] ?? 1) ? 'checked' : '' ?>> Active</div>
    <div class="admin-form-actions"><button type="submit" class="btn btn-primary">Save</button></div>
</form>
