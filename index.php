<?php
/**
 * index.php
 * Landing page. Logged-in players see a "continue / new run" panel.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/engine.php';

$pageTitle = 'Home';
require __DIR__ . '/includes/header.php';
?>
<section class="hero-banner">
    <p class="eyebrow">CSC 4370 · Choose Your Path</p>
    <h1 class="title-type">Dead Zone: Downtown</h1>
    <p class="lead">
        The outbreak hit during your 2 PM lecture. The evacuation helicopter leaves the stadium at dawn.
        Find your way through campus, the parking deck, the train tunnels and a hospital,
        and remember that every sound you make draws them closer.
    </p>

    <div class="cta-row">
        <?php if (!is_logged_in()): ?>
            <a class="btn btn-primary" href="register.php">Create an account</a>
            <a class="btn btn-ghost" href="login.php">Log in</a>
        <?php elseif (has_active_run()): ?>
            <a class="btn btn-primary" href="game.php">Continue as <?= e($_SESSION['hero']['name']) ?></a>
            <a class="btn btn-ghost" href="create.php">Start a new run</a>
        <?php else: ?>
            <a class="btn btn-primary" href="create.php">Start a run</a>
            <a class="btn btn-ghost" href="leaderboard.php">Leaderboard</a>
        <?php endif; ?>
    </div>
</section>

<section class="feature-grid">
    <article class="feature">
        <h2>Noise draws hordes</h2>
        <p>Running, smashing and fighting raise your Noise. Get too loud and the horde blocks your route.</p>
    </article>
    <article class="feature">
        <h2>Bites spread</h2>
        <p>Get bitten and the infection climbs with every move. Find antibiotics before it reaches 100%.</p>
    </article>
    <article class="feature">
        <h2>Four endings</h2>
        <p>Extraction, Turned, Overrun, and one secret ending for players who find the right route.</p>
    </article>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
