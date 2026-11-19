<?php
/**
 * leaderboard.php
 * Two boards:
 *   1. This session: top 10 runs in $_SESSION['scores'] (required, temporary)
 *   2. All players:  top 10 runs from data/scores.json (longer-term, written by the server)
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/engine.php';
require_login();

$sessionRows = leaderboard(10);
$allTimeRows = all_time_leaderboard(10);
$endings = ending_defs();
$classes = class_defs();

/**
 * Render one leaderboard table. The logged-in player's rows are marked.
 */
function board_table(array $rows, array $endings, array $classes, string $caption): void
{
    ?>
    <div class="table-wrap">
        <table class="leaderboard">
            <caption class="visually-hidden"><?= e($caption) ?></caption>
            <thead>
                <tr>
                    <th scope="col">Rank</th><th scope="col">Player</th><th scope="col">Survivor</th><th scope="col">Ending</th>
                    <th scope="col" class="num">Moves</th><th scope="col" class="num">Saved</th><th scope="col" class="num">Score</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $rank => $row): ?>
                <tr class="<?= strtolower($row['user']) === strtolower($_SESSION['user']) ? 'is-me' : '' ?>">
                    <td class="rank"><?= str_pad((string) ($rank + 1), 2, '0', STR_PAD_LEFT) ?></td>
                    <td><?= e($row['user']) ?></td>
                    <td><?= e($row['hero']) ?><span class="class-note"><?= e($classes[$row['class']]['label']) ?></span></td>
                    <td><span class="badge badge-<?= e($endings[$row['ending']]['type']) ?>"><?= e($endings[$row['ending']]['label']) ?></span></td>
                    <td class="num"><?= (int) $row['moves'] ?></td>
                    <td class="num"><?= (int) $row['survivors'] ?></td>
                    <td class="num score"><?= (int) $row['score'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

$pageTitle = 'Leaderboard';
require __DIR__ . '/includes/header.php';
?>
<section aria-labelledby="board-title">
    <div class="board-head">
        <div>
            <h1 id="board-title">Leaderboard</h1>
            <p class="lead">Ranked by score, then fewest moves. Your runs are marked in orange.</p>
        </div>
        <a class="btn btn-ghost" href="<?= has_active_run() ? 'game.php' : 'create.php' ?>"><?= has_active_run() ? 'Continue your run' : 'Start a run' ?></a>
    </div>

    <div class="board">
        <div class="board-title">
            <h2>This session</h2>
            <p class="muted small">Cleared when you log out.</p>
        </div>
        <?php if (!$sessionRows): ?>
            <div class="panel empty">
                <p>No finished runs this session yet. Finish a run, any ending counts, and it lands here.</p>
            </div>
        <?php else: ?>
            <?php board_table($sessionRows, $endings, $classes, 'Top runs this session'); ?>
        <?php endif; ?>
    </div>

    <div class="board">
        <div class="board-title">
            <h2>All players</h2>
            <p class="muted small">Best runs from everyone on this server.</p>
        </div>
        <?php if (!$allTimeRows): ?>
            <div class="panel empty">
                <p>Nobody has finished a run on this server yet.</p>
            </div>
        <?php else: ?>
            <?php board_table($allTimeRows, $endings, $classes, 'Top runs from all players'); ?>
        <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
