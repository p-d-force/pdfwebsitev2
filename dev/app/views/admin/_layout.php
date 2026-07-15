<?php declare(strict_types=1);
/**
 * Admin layout — wraps every admin page.
 * Sidebar nav + main content area.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= h(($page_title ?? 'Admin') . ' | ' . SITE_NAME . ' Admin') ?></title>
    <link rel="icon" type="image/png" href="<?= h(ASSETS_URL . '/images/logo.png') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/global.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/elements/nav.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/elements/buttons.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/elements/cards.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/elements/forms.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/elements/tables.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/elements/badges.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/pages/admin.css') ?>">
</head>
<body>
    <?php (new App\Components\Nav())->render(); ?>
    <main>
        <div class="admin-layout">
            <aside class="admin-sidebar">
                <h3>Content</h3>
                <ul class="admin-nav">
                    <li><a href="/admin">Dashboard</a></li>
                    <li><a href="/admin/articles">Articles</a></li>
                    <li><a href="/admin/cases">Cases</a></li>
                    <li><a href="/admin/organizations">Organizations</a></li>
                    <li><a href="/admin/prs">PRS Cases</a></li>
                    <li><a href="/admin/documents">Documents</a></li>
                    <li><a href="/admin/submissions">Submissions</a></li>
                    <li><a href="/admin/updates">Updates</a></li>
                    <li><a href="/admin/media">Media</a></li>
                </ul>
                <h3>Account</h3>
                <ul class="admin-nav">
                    <?php if (!empty($user)): ?>
                    <li><a href="/admin/logout">Logout (<?= h($user['display_name'] ?? $user['username']) ?>)</a></li>
                    <?php else: ?>
                    <li><a href="/admin/login">Login</a></li>
                    <?php endif; ?>
                </ul>
            </aside>
            <div class="admin-main">
                <?php require $view; ?>
            </div>
        </div>
    </main>
</body>
</html>
