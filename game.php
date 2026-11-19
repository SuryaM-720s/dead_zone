<?php
/**
 * game.php
 * The main game screen. Handles two POST actions, then redirects
 * (Post/Redirect/Get) so refreshing the page never repeats a move:
 *   action=choose&choice=N  → take choice N at the current node
 *   action=use&item=KEY     → use a consumable item
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/engine.php';
require_login();

if (isset($_SESSION['result'])) {
    redirect('ending.php');
}
if (!has_active_run()) {
    redirect('create.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = filter_input(INPUT_POST, 'action', FILTER_UNSAFE_RAW);

    if ($action === 'choose') {
        $choice = filter_input(INPUT_POST, 'choice', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        if ($choice === false || $choice === null || !apply_choice($choice)) {
            flash('error', "You can't take that path right now.");
        }
    } elseif ($action === 'use') {
        $item = (string) filter_input(INPUT_POST, 'item', FILTER_UNSAFE_RAW);
        if (!use_item($item)) {
            flash('error', "You can't use that.");
        }
    }

    redirect(isset($_SESSION['result']) ? 'ending.php' : 'game.php');
}

$hero  = $_SESSION['hero'];
$node  = current_node();
$event = $_SESSION['last_event'];
$_SESSION['last_event'] = null; // narrator message shows once
$classLabel = class_defs()[$hero['class']]['label'];
$items = item_defs();

/** Width/colour helper for the stat bars. */
function bar(int $value, int $max): int
{
    return $max > 0 ? (int) round($value / $max * 100) : 0;
}

$pageTitle = $node['title'];
require __DIR__ . '/includes/header.php';
?>
<div class="game-layout">
    <section class="story">
        <p class="eyebrow">Move <?= $hero['moves'] + 1 ?> · <?= e(ucwords(str_replace('_', ' ', $node['area']))) ?></p>
        <h1 class="title-type"><?= e($node['title']) ?></h1>

        <?php if ($event): ?>
            <div class="narrator">
                <?php if ($event['text'] !== ''): ?><p><?= e($event['text']) ?></p><?php endif; ?>
                <?php foreach ($event['events'] as $line): ?>
                    <p class="narrator-event"><?= e($line) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="story-text">
            <?php foreach ($node['text'] as $i => $paragraph): ?>
                <p style="--delay: <?= $i * 0.35 ?>s"><?= e($paragraph) ?></p>
            <?php endforeach; ?>
        </div>

        <form method="post" action="game.php" class="choices">
            <input type="hidden" name="action" value="choose">
            <?php foreach ($node['choices'] as $i => $choice):
                $locked  = unmet_requirements($choice, $hero);
                $preview = consequence_preview($choice, $hero);
            ?>
                <div class="choice <?= $locked ? 'is-locked' : '' ?>">
                    <button type="submit" name="choice" value="<?= $i ?>" <?= $locked ? 'disabled' : '' ?>>
                        <?= e($choice['text']) ?>
                    </button>
                    <?php if ($locked): ?>
                        <p class="lock-reason"><?= e(implode(' ', $locked)) ?></p>
                    <?php elseif ($preview): ?>
                        <ul class="preview" aria-label="Advisor preview">
                            <?php foreach ($preview as $part): ?>
                                <li class="tone-<?= e($part['tone']) ?>"><?= e($part['text']) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </form>
    </section>

    <aside class="sidebar">
        <div class="panel">
            <h2><?= e($hero['name']) ?> <span class="tag"><?= e($classLabel) ?></span></h2>
            <div class="stat">
                <span>Health</span><span><?= $hero['health'] ?>/<?= $hero['max_health'] ?></span>
                <div class="meter"><i class="m-health" style="width: <?= bar($hero['health'], $hero['max_health']) ?>%"></i></div>
            </div>
            <div class="stat">
                <span>Stamina</span><span><?= $hero['stamina'] ?></span>
                <div class="meter"><i class="m-stamina" style="width: <?= min(100, $hero['stamina']) ?>%"></i></div>
            </div>
            <div class="stat">
                <span>Luck</span><span><?= $hero['luck'] ?></span>
                <div class="meter"><i class="m-luck" style="width: <?= min(100, $hero['luck']) ?>%"></i></div>
            </div>
            <div class="stat">
                <span>Noise</span><span><?= $hero['noise'] ?>/<?= MAX_NOISE ?></span>
                <div class="meter"><i class="m-noise" style="width: <?= bar($hero['noise'], MAX_NOISE) ?>%"></i></div>
            </div>
            <?php if ($hero['infected']): ?>
                <div class="stat stat-infection">
                    <span>Infection</span><span><?= $hero['infection'] ?>%</span>
                    <div class="meter"><i class="m-infection" style="width: <?= $hero['infection'] ?>%"></i></div>
                </div>
            <?php endif; ?>
            <p class="muted small">Survivors with you: <?= $hero['survivors'] ?></p>
        </div>

        <div class="panel">
            <h2>Inventory</h2>
            <?php if (!$hero['inventory']): ?>
                <p class="muted small">Empty.</p>
            <?php else: ?>
                <ul class="inventory">
                    <?php foreach (array_count_values($hero['inventory']) as $key => $count): ?>
                        <li>
                            <span><?= e(item_label($key)) ?><?= $count > 1 ? ' ×' . $count : '' ?></span>
                            <?php if ($items[$key]['usable']): ?>
                                <form method="post" action="game.php">
                                    <input type="hidden" name="action" value="use">
                                    <input type="hidden" name="item" value="<?= e($key) ?>">
                                    <button type="submit" class="btn-mini">Use</button>
                                </form>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="panel">
            <h2>Path Tracker</h2>
            <?php if (!$_SESSION['choices_log']): ?>
                <p class="muted small">Your route will appear here as you move.</p>
            <?php else: ?>
                <ol class="path-log">
                    <?php foreach ($_SESSION['choices_log'] as $step): ?>
                        <li class="<?= $step['major'] ? 'is-major' : '' ?>">
                            <span class="path-node"><?= e($step['node']) ?></span>
                            <span class="path-choice"><?= e($step['choice']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </div>
    </aside>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
