<div class="admin-header">
    <div>
        <h1>Dashboard</h1>
        <p style="color:var(--text-secondary);">Welcome back, <?= h($user['display_name'] ?? $user['username']) ?>.</p>
    </div>
</div>

<div class="admin-stats">
    <div class="admin-stat-card">
        <div class="admin-stat-value"><?= number_format($counts['articles']) ?></div>
        <div class="admin-stat-label">Articles</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-value"><?= number_format($counts['cases']) ?></div>
        <div class="admin-stat-label">Cases</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-value"><?= number_format($counts['organizations']) ?></div>
        <div class="admin-stat-label">Organizations</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-value"><?= number_format($counts['documents']) ?></div>
        <div class="admin-stat-label">Documents</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-value"><?= number_format($counts['submissions']) ?></div>
        <div class="admin-stat-label">Pending Submissions</div>
    </div>
</div>
