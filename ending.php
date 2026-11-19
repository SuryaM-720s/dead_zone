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
<section class="ending ending-<?= e($ending['type']) ?>">
    <p class="eyebrow"><?= e(ucfirst($ending['type'])) ?> ending</p>
    <h1 class="title-type"><?= e($ending['label']) ?></h1>
    <p class="lead"><?= e($ending['text']) ?></p>

    <div class="panel report">
        <h2>Survivor Report</h2>
        <p><?= e($result['report']) ?></p>
    </div>

    <div class="panel">
        <h2>Score</h2>
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

    <div class="cta-row">
        <a class="btn btn-primary" href="create.php">Play again</a>
        <a class="btn btn-ghost" href="leaderboard.php">Leaderboard</a>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
