<?php
$pageTitle = isset($title) ? $title . ' - ' . APP_NAME : APP_NAME;
$currentRoute = trim((string)($_GET['route'] ?? ''), '/');
$success = flash('success');
$error = flash('error');
$user = auth();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
</head>
<body>
<nav class="navbar">
    <a class="brand" href="<?= e(url('home')) ?>"><span class="logo">TG</span><span>TravelGuide</span></a>
    <button class="nav-toggle" type="button" data-nav-toggle>Menu</button>
    <div class="nav-menu" data-nav-menu>
        <div class="nav-links">
            <a class="<?= in_array($currentRoute,['','home'],true)?'active':'' ?>" href="<?= e(url('home')) ?>">Home</a>
            <a class="<?= $currentRoute==='explore'?'active':'' ?>" href="<?= e(url('explore')) ?>">Explore</a>
            <a class="<?= $currentRoute==='calculator'?'active':'' ?>" href="<?= e(url('calculator')) ?>">Calculator</a>
            <?php if ($user): ?>
                <a class="<?= $currentRoute==='dashboard' || str_starts_with($currentRoute,'admin/')?'active':'' ?>" href="<?= e(url('dashboard')) ?>">Dashboard</a>
                <a href="<?= e(url('wishlist')) ?>">Wishlist</a>
                <?php if ($user['role']==='scout'): ?><a href="<?= e(url('scout/requests')) ?>">Scout Requests</a><?php endif; ?>
            <?php endif; ?>
        </div>
        <div class="nav-actions">
            <?php if (!$user): ?>
                <a class="btn btn-ghost" href="<?= e(url('login')) ?>">Login</a>
                <a class="btn btn-primary" href="<?= e(url('register')) ?>">Register</a>
            <?php else: ?>
                <a class="profile-chip" href="<?= e(url('profile')) ?>">
                    <span class="avatar-sm"><?= e(strtoupper(substr($user['name'],0,1))) ?></span>
                    <span><?= e($user['name']) ?></span>
                    <small><?= e(ucfirst($user['role'])) ?></small>
                </a>
                <form method="post" action="<?= e(url('logout')) ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-outline-light" type="submit">Logout</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</nav>

<?php if ($success || $error): ?>
<div class="flash-wrap">
    <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
</div>
<?php endif; ?>

<main>
