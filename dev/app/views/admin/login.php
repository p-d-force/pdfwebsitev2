<div class="admin-login">
    <h1><?= SITE_NAME ?></h1>
    <p class="login-subtitle">Admin Sign In</p>

    <?php if ($error): ?>
    <div class="alert alert-error"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="/admin/login">
        <?= csrf_field() ?>
        <div class="form-group">
            <label class="form-label" for="username">Username or Email</label>
            <input type="text" name="username" id="username" class="form-input" required autofocus>
        </div>
        <div class="form-group">
            <label class="form-label" for="password">Password</label>
            <input type="password" name="password" id="password" class="form-input" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;">Sign In</button>
    </form>
</div>
