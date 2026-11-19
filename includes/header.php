<?php
/**
 * header.php
 * Opening HTML + navigation shared by every page.
 * Set $pageTitle before including this file.
 */
$pageTitle = $pageTitle ?? APP_NAME;
$currentPage = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Special+Elite&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<header class="site-header">
    <a class="brand" href="index.php">DEAD<span>ZONE</span></a>
    <nav class="site-nav" aria-label="Main">
        <?php if (is_logged_in()): ?>
            <a href="game.php" class="<?= $currentPage === 'game.php' ? 'active' : '' ?>">Play</a>
            <a href="leaderboard.php" class="<?= $currentPage === 'leaderboard.php' ? 'active' : '' ?>">Leaderboard</a>
            <span class="nav-user"><?= e($_SESSION['user']) ?></span>
            <a href="logout.php" class="nav-logout">Log out</a>
        <?php else: ?>
            <a href="login.php" class="<?= $currentPage === 'login.php' ? 'active' : '' ?>">Log in</a>
            <a href="register.php" class="<?= $currentPage === 'register.php' ? 'active' : '' ?>">Register</a>
        <?php endif; ?>
    </nav>
</header>
<main class="page">
<?php foreach (take_flashes() as $flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div>
<?php endforeach; ?>
