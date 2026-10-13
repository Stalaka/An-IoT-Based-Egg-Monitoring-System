<?php
// Top of every page. Expects auth.php to be loaded and $pageTitle to be set.
$u = current_user();
$flash = flash();
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle ?? 'Egg Monitor') ?> - Egg Storage Monitor</title>
<link rel="stylesheet" href="assets/style.css">
<?= $headExtra ?? '' ?>
</head>
<body>
<header class="topbar">
  <div class="brand">Egg Storage Monitor</div>
  <?php if ($u): ?>
  <nav>
    <a href="dashboard.php">Dashboard</a>
    <?php if (in_array($u['role'], ['admin', 'supplier'], true)): ?><a href="stock.php">Stock</a><?php endif; ?>
    <?php if (in_array($u['role'], ['admin', 'vendor'], true)): ?><a href="sales.php">Sales</a><?php endif; ?>
    <?php if ($u['role'] === 'admin'): ?><a href="users.php">Users</a><a href="simulate.php">Simulator</a><?php endif; ?>
    <span class="who"><?= e($u['full_name']) ?> (<?= e($u['role']) ?>)</span>
    <a href="logout.php">Logout</a>
  </nav>
  <?php endif; ?>
</header>
<main class="container">
<?php if ($flash): ?><div class="flash <?= e($flash[1]) ?>"><?= e($flash[0]) ?></div><?php endif; ?>
