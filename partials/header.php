<?php
// Top of every page. Expects auth.php loaded and $pageTitle set.
// Optional: $pageSub (one line under the title), $layout ('public' | 'auth' | 'app'), $headExtra.
require_once __DIR__ . '/../ui.php';
$u       = current_user();
$flash   = flash();
$layout  = $layout ?? ($u ? 'app' : 'auth');
$current = basename($_SERVER['SCRIPT_NAME'] ?? '');

$nav = [];
if ($u) {
    $nav[] = ['dashboard.php', 'Dashboard', 'dashboard'];
    if (in_array($u['role'], ['admin', 'supplier'], true)) $nav[] = ['stock.php', 'Stock', 'stock'];
    if (in_array($u['role'], ['admin', 'vendor'], true))   $nav[] = ['sales.php', 'Sales', 'sales'];
    if ($u['role'] === 'admin') {
        $nav[] = ['users.php', 'Users', 'users'];
        $nav[] = ['simulate.php', 'Simulator', 'sim'];
    }
}
$favicon = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 120'%3E%3Cpath fill='%23F2A81D' d='M50 4C27 4 8 44 8 76c0 24 18 40 42 40s42-16 42-40C92 44 73 4 50 4z'/%3E%3C/svg%3E";
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($pageTitle ?? 'Egg Storage Monitor') ?> - Egg Storage Monitor</title>
<link rel="icon" href="<?= $favicon ?>">
<link rel="preload" href="assets/fonts/PublicSans-Variable.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="assets/style.css">
<?= $headExtra ?? '' ?>
</head>
<body class="layout-<?= h($layout) ?>">
<a class="skip" href="#content">Skip to content</a>

<?php if ($layout === 'public'): ?>
<header class="site-nav">
  <div class="wrap">
    <a class="brand" href="index.php"><?= egg_mark(24) ?><span>Egg Storage Monitor</span></a>
    <nav class="site-links" aria-label="Sections">
      <a href="#problem">The problem</a>
      <a href="#how">How it works</a>
      <a href="#inside">What is inside</a>
    </nav>
    <div class="nav-actions">
      <a class="btn ghost" href="login.php">Log in</a>
      <a class="btn" href="register.php">Create account</a>
    </div>
  </div>
</header>
<main id="content">

<?php elseif ($layout === 'auth'): ?>
<div class="auth-shell">
  <aside class="auth-side on-ink">
    <a class="brand" href="index.php"><?= egg_mark(26) ?><span>Egg Storage Monitor</span></a>
    <div class="auth-pitch">
      <?= egg_mark(84, 'is-ok glow') ?>
      <h2>Know the store room is safe.</h2>
      <p>Temperature, humidity and air quality, checked all day, next to your sales and stock records.</p>
    </div>
    <p class="auth-foot">Final-year project prototype</p>
  </aside>
  <main class="auth-main" id="content">
    <div class="auth-card">
    <?php if ($flash): ?><div class="flash <?= h($flash[1]) ?>" role="status"><?= h($flash[0]) ?></div><?php endif; ?>

<?php else: ?>
<div class="app">
  <aside class="side on-ink">
    <a class="brand" href="dashboard.php"><?= egg_mark(24) ?><span>Egg Storage Monitor</span></a>
    <nav class="side-nav" aria-label="Main">
      <?php foreach ($nav as [$href, $label, $ico]): ?>
        <a href="<?= $href ?>" <?= $current === $href ? 'aria-current="page"' : '' ?>><?= icon($ico) ?><span><?= h($label) ?></span></a>
      <?php endforeach; ?>
    </nav>
    <div class="side-user">
      <div class="avatar" aria-hidden="true"><?= h(strtoupper(mb_substr($u['full_name'], 0, 1))) ?></div>
      <div class="who"><strong><?= h($u['full_name']) ?></strong><small><?= h(ucfirst($u['role'])) ?></small></div>
      <a class="logout" href="logout.php" title="Log out"><?= icon('logout') ?><span>Log out</span></a>
    </div>
  </aside>
  <div class="app-main">
    <header class="page-head">
      <h1><?= h($pageTitle ?? '') ?></h1>
      <?php if (!empty($pageSub)): ?><p><?= h($pageSub) ?></p><?php endif; ?>
    </header>
    <main class="content" id="content">
    <?php if ($flash): ?><div class="flash <?= h($flash[1]) ?>" role="status"><?= h($flash[0]) ?></div><?php endif; ?>
<?php endif; ?>
