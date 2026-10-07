<?php
declare(strict_types=1);

session_set_cookie_params([
    'httponly' => true,
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax',
]);
session_start();
require_once __DIR__ . '/php_helpers.php';
$page = (string)($_GET['page'] ?? 'home');
$pages = ['home', 'menu', 'offers', 'reservation', 'contact', 'login', 'signup', 'cart', 'checkout', 'track', 'account', 'reviews', 'support', 'admin', 'rider', 'setup-admin'];
if (!in_array($page, $pages, true)) $page = 'home';
$user = current_user();
if ($page === 'reservation' && $user && $user['role'] === 'admin') {
    header('Location: ?page=admin#reservations');
    exit;
}
$title = ucfirst($page) . ' · Foodplan';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#246540">
  <title><?= e($title) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="php-app.css?v=5">
</head>
<body data-page="<?= e($page) ?>" data-api="api.php">
  <header class="site-header">
    <a class="brand" href="?page=home"><span class="brand-mark">f</span>food<span>plan</span></a>
    <button class="nav-toggle" type="button" aria-label="Toggle navigation" aria-expanded="false">☰</button>
    <nav class="main-nav" aria-label="Main navigation">
      <a href="?page=home">Home</a><a href="?page=menu">Menu</a><a href="?page=offers">Special offers</a>
      <?php if ($user && $user['role'] === 'admin'): ?><a href="?page=admin#reservations">Reservations</a><?php elseif ($user && $user['role'] === 'rider'): ?><a href="?page=rider">My deliveries</a><?php else: ?><a href="?page=reservation">Book a table</a><?php endif; ?><a href="?page=contact">Contact</a>
    </nav>
    <a class="delivery-location-link" href="?page=home#delivery-area"><span>Delivery area</span><strong data-delivery-location>Choose an area</strong></a>
    <div class="header-actions">
      <?php if ($user): ?>
        <a class="account-link" href="?page=<?= e($user['role'] === 'admin' ? 'admin' : ($user['role'] === 'rider' ? 'rider' : 'account')) ?>"><?= e($user['name']) ?></a>
      <?php else: ?>
        <a class="account-link" href="?page=login">Sign in</a>
      <?php endif; ?>
      <?php if ($user && $user['role'] === 'rider'): ?>
        <button class="button button-small" type="button" data-logout>Sign out</button>
      <?php else: ?>
        <a class="button button-small" href="?page=<?= $user && $user['role'] === 'admin' ? 'admin' : 'cart' ?>"><?= $user && $user['role'] === 'admin' ? 'Dashboard' : 'My order' ?></a>
      <?php endif; ?>
    </div>
  </header>
  <main class="site-main">
    <?php require __DIR__ . '/pages/' . $page . '.php'; ?>
  </main>
  <footer class="site-footer">
    <div><a class="brand" href="?page=home"><span class="brand-mark">f</span>food<span>plan</span></a><p>Good food, thoughtfully made, delivered fresh across Dhaka.</p></div>
    <div><strong>Explore Foodplan</strong><a href="?page=menu">Menu</a><a href="?page=offers">Special offers</a><?php if ($user && $user['role'] === 'rider'): ?><a href="?page=rider">My deliveries</a><?php elseif (!$user || $user['role'] !== 'admin'): ?><a href="?page=reservation">Book a table</a><?php endif; ?></div>
    <div><strong>Need help?</strong><a href="?page=track">Track your order</a><a href="?page=support">Support</a><a href="?page=contact">Contact</a></div>
    <small>© <?= date('Y') ?> Foodplan · Bangladesh</small>
  </footer>
  <div class="toast" role="status" aria-live="polite" hidden></div>
  <script src="app.js?v=4" defer></script>
</body>
</html>
