<?php
/**
 * index.php
 * Landing page: full-bleed hero over a night photograph of downtown,
 * then a short explanation of the three rules that decide a run.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/engine.php';

$locationCount = count(array_filter(story_nodes(), function ($node) {
    return !isset($node['ending']);
}));

$pageTitle = 'Home';
$mainClass = 'page-bleed';
$photoCredit = 'Photo: <a href="https://commons.wikimedia.org/wiki/File:Downtown_connector_nighttime.jpg">Scott Ehardt</a>, public domain';
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <img class="hero-bg"
         src="img/connector-1280.jpg"
         srcset="img/connector-500.jpg 500w, img/connector-960.jpg 960w, img/connector-1280.jpg 1280w"
         sizes="100vw" width="1280" height="962"
         alt="An empty downtown highway at night, taillights streaking toward the skyline"
         fetchpriority="high" decoding="async">
    <div class="wrap">
        <ul class="hero-facts enter" aria-label="Game facts">
            <li>Text survival game</li>
            <li><?= $locationCount ?> locations</li>
            <li>4 endings</li>
        </ul>
        <h1 class="enter enter-2">Dead Zone: Downtown</h1>
        <p class="lead enter enter-3">
            The outbreak starts during your afternoon lecture. The last evacuation flight leaves the
            stadium lot at dawn, two miles south. Every sound you make, every bite and every
            supply you carry decides whether you get there.
        </p>
        <div class="btn-row enter enter-4">
            <?php if (!is_logged_in()): ?>
                <a class="btn btn-primary" href="register.php">Create an account</a>
                <a class="btn btn-ghost" href="login.php">Log in</a>
            <?php elseif (has_active_run()): ?>
                <a class="btn btn-primary" href="game.php">Continue as <?= e($_SESSION['hero']['name']) ?></a>
                <a class="btn btn-ghost" href="create.php">Start a new run</a>
            <?php else: ?>
                <a class="btn btn-primary" href="create.php">Start a run</a>
                <a class="btn btn-ghost" href="leaderboard.php">View the leaderboard</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="rules" aria-labelledby="rules-title">
    <div class="wrap">
        <h2 id="rules-title">Three rules decide every run</h2>
        <ul class="rules-grid">
            <li class="rule">
                <div class="rule-visual" aria-hidden="true">
                    <div class="segments">
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                            <i class="<?= $i <= 6 ? 'on' : '' ?><?= $i >= 5 && $i <= 6 ? ' loud' : '' ?>"></i>
                        <?php endfor; ?>
                    </div>
                </div>
                <h3>Noise brings the horde</h3>
                <p>Running, smashing glass and fighting all add noise. Reach the parking deck or the stadium too loud and the horde is waiting.</p>
            </li>
            <li class="rule">
                <div class="rule-visual" aria-hidden="true">
                    <div class="readout" style="width: 100%">
                        <span>Infection</span><b>40%</b>
                        <div class="meter"><i class="m-infection" style="width: 40%"></i></div>
                    </div>
                </div>
                <h3>A bite keeps spreading</h3>
                <p>Once bitten, infection climbs with every move you make. Antibiotics stop it. At 100% the run is over.</p>
            </li>
            <li class="rule">
                <div class="rule-visual" aria-hidden="true">
                    <ul class="ending-list">
                        <li class="badge badge-heroic">Extraction</li>
                        <li class="badge badge-secret">The Signal</li>
                        <li class="badge badge-tragic">Turned</li>
                        <li class="badge badge-tragic">Overrun</li>
                    </ul>
                </div>
                <h3>Four ways it ends</h3>
                <p>Reach the helicopter, fall to the infection or the horde, or find the route to the hospital radio room.</p>
            </li>
        </ul>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
