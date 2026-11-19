<?php
/**
 * header.php
 * Opening HTML + navigation shared by every page.
 * Optional before including:
 *   $pageTitle   text for the <title>
 *   $mainClass   'page' (default, padded) or 'page-bleed' (full-width hero pages)
 */
$pageTitle = $pageTitle ?? APP_NAME;
$mainClass = $mainClass ?? 'page';
$currentPage = basename($_SERVER['SCRIPT_NAME']);

/** aria-current for the nav link of the page we're on. */
function nav_current(string $file, string $current): string
{
    return $file === $current ? ' aria-current="page"' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>
    <meta name="description" content="A text survival game built in PHP: get across a downtown outbreak to the last evacuation flight.">
    <meta name="theme-color" content="#18191b">
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Crect width='16' height='16' rx='3' fill='%2318191b'/%3E%3Crect x='4' y='4' width='8' height='8' rx='1' fill='%23ff4a1f'/%3E%3C/svg%3E">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Mona+Sans:wdth,wght@75..125,400..600&family=Geist+Mono:wght@400..500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<a class="skip-link" href="#main">Skip to content</a>
<header class="site-header">
    <div class="wrap">
        <a class="brand" href="index.php" translate="no"><span class="brand-mark" aria-hidden="true"></span><span class="brand-text">Dead Zone</span></a>
        <nav class="site-nav" aria-label="Main">
            <?php if (is_logged_in()): ?>
                <a href="game.php"<?= nav_current('game.php', $currentPage) ?>>Play</a>
                <a href="leaderboard.php"<?= nav_current('leaderboard.php', $currentPage) ?>>Leaderboard</a>
                <a href="team.php"<?= nav_current('team.php', $currentPage) ?>>Team</a>
                <span class="nav-user"><?= e($_SESSION['user']) ?></span>
                <a href="logout.php">Log out</a>
            <?php else: ?>
                <a href="team.php"<?= nav_current('team.php', $currentPage) ?>>Team</a>
                <a href="login.php"<?= nav_current('login.php', $currentPage) ?>>Log in</a>
                <a href="register.php"<?= nav_current('register.php', $currentPage) ?>>Register</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main id="main" class="<?= e($mainClass) ?>">
<?php
// Padded pages get one .wrap around all content (closed in footer.php);
// full-bleed pages place their own .wrap inside each section.
$flashes = take_flashes();
if ($mainClass === 'page'): ?>
<div class="wrap">
<?php elseif ($flashes): ?>
<div class="wrap flash-wrap">
<?php endif; ?>
<?php foreach ($flashes as $flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div>
<?php endforeach; ?>
<?php if ($mainClass !== 'page' && $flashes): ?>
</div>
<?php endif; ?>
