<?php
/**
 * ending.php
 * Shows the ending reached, the Survivor Report, and the score breakdown.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/engine.php';
require_login();

if (!isset($_SESSION['result'])) {
    redirect('game.php');
}

$result = $_SESSION['result'];
$ending = ending_defs()[$result['ending']];

$pageTitle = $ending['label'];
require __DIR__ . '/includes/header.php';
?>
<section class="ending ending-<?= e($ending['type']) ?>" style="--chars: <?= strlen($ending['label']) ?>">
    <p class="label ending-type"><?= e(ucfirst($ending['type'])) ?> ending · <?= $result['moves'] ?> moves</p>
    <h1 class="type-on"><?= e($ending['label']) ?></h1>
    <p class="lead"><?= prose($ending['text']) ?></p>

    <div class="ending-grid">
        <div class="panel report">
            <div class="panel-head"><h2>Survivor report</h2></div>
            <p><?= prose($result['report']) ?></p>
        </div>

        <div class="panel">
            <div class="panel-head"><h2>Score</h2></div>
            <table class="breakdown">
                <?php foreach ($result['breakdown'] as $label => $points): ?>
                    <tr>
                        <td><?= e($label) ?></td>
                        <td class="num <?= $points < 0 ? 'neg' : '' ?>"><?= $points > 0 ? '+' : '' ?><?= $points ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="total">
                    <td>Total</td>
                    <td class="num"><?= $result['score'] ?></td>
                </tr>
            </table>
        </div>

        <?php if (!empty($_SESSION['choices_log'])): ?>
            <div class="panel">
                <div class="panel-head">
                    <h2>Your route</h2>
                    <span><?= count($_SESSION['choices_log']) ?> moves</span>
                </div>
                <ol class="path-log">
                    <?php foreach ($_SESSION['choices_log'] as $step): ?>
                        <li class="<?= $step['major'] ? 'is-major' : '' ?>">
                            <span class="path-node"><?= e($step['node']) ?></span>
                            <span class="path-choice"><?= e($step['choice']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </div>
        <?php endif; ?>
    </div>

    <div class="btn-row">
        <a class="btn btn-primary" href="create.php">Start a new run</a>
        <a class="btn btn-ghost" href="leaderboard.php">View the leaderboard</a>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
