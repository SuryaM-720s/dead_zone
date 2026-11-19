<?php
/**
 * tests/advisor_test.php
 * Test cases for the Survival Advisor (our rule-based "AI") and the game rules
 * it explains. Run from the project folder:
 *
 *     php tests/advisor_test.php
 *
 * Each case builds a hero, runs one advisor rule, and compares the output
 * with what the rule promises. Nothing is written to disk.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$_SESSION = [];
require_once __DIR__ . '/../includes/engine.php';

$passed = 0;
$failed = 0;

function check(string $name, bool $ok, string $detail = ''): void
{
    global $passed, $failed;
    $ok ? $passed++ : $failed++;
    echo ($ok ? 'PASS' : 'FAIL') . "  {$name}" . ($ok || $detail === '' ? '' : "\n      got: {$detail}") . "\n";
}

/** A fresh hero of the given class, with optional stat overrides. */
function hero(string $class, array $overrides = []): array
{
    $def = class_defs()[$class];
    return array_merge([
        'name' => 'Test', 'class' => $class,
        'health' => $def['health'], 'max_health' => $def['health'],
        'stamina' => $def['stamina'], 'luck' => $def['luck'],
        'noise' => 0, 'infected' => false, 'infection' => 0,
        'inventory' => $def['items'], 'survivors' => 0, 'moves' => 0,
    ], $overrides);
}

function texts(array $preview): string
{
    return implode(' | ', array_column($preview, 'text'));
}

$story = story_nodes();

// 1. Locked choice: says which stat is short and by how much
$ledge = $story['start']['choices'][2];                  // requires 40 Stamina
$reasons = unmet_requirements($ledge, hero('scavenger')); // scavenger has 30
check('Locked choice explains the stamina gap',
    $reasons === ['Needs 40 Stamina. You have 30 (10 short).'], implode(' ', $reasons));

// 2. Locked choice: missing item names the item and hints where to find it
$security = $story['library']['choices'][0];             // requires a lockpick
$reasons = unmet_requirements($security, hero('runner'));
check('Locked choice names the missing item',
    count($reasons) === 1 && strpos($reasons[0], 'Needs a Lockpick.') === 0, implode(' ', $reasons));

// 3. Same choice is open for a class that starts with the item
check('Scavenger can open the security office',
    unmet_requirements($security, hero('scavenger')) === []);

// 4. Consequence preview applies class perks (Runner halves stamina costs)
$sprint = $story['courtyard']['choices'][2];             // +2 Noise, -10 Stamina
$preview = texts(consequence_preview($sprint, hero('runner')));
check('Preview shows the Runner perk (−5 instead of −10 Stamina)',
    $preview === '+2 Noise | −5 Stamina', $preview);

// 5. Bite warning depends on the hero's Luck
$closet = $story['lab']['choices'][1];                   // bite_risk 45
$low = texts(consequence_preview($closet, hero('runner')));     // luck 30
$high = texts(consequence_preview($closet, hero('scavenger'))); // luck 50
check('Preview warns a low-Luck hero about a bite',
    strpos($low, 'Bite risk: your Luck (30) is under 45') !== false, $low);
check('Preview reassures a high-Luck hero',
    strpos($high, 'Bite risk: your Luck should protect you') !== false, $high);

// 6. Noise rule: arriving at the parking deck too loud redirects to the horde
$_SESSION = [
    'user' => 'tester', 'hero' => hero('runner', ['noise' => 4]),
    'node' => 'courtyard', 'visited' => ['courtyard'], 'choices_log' => [], 'run_started' => time(),
];
apply_choice(2);                                         // sprint: +2 noise → 6
check('Too much noise sends the player into the horde',
    $_SESSION['node'] === 'deck_horde', $_SESSION['node']);

// 7. Survivor Report is assembled from the session
$_SESSION['choices_log'] = [log_entry('stairwell', 0), log_entry('library', 1)];
$report = survivor_report(hero('medic', ['moves' => 7, 'survivors' => 2]), 'extraction');
check('Survivor Report names class, ending, moves, decisions and survivors',
    strpos($report, 'Test the Medic made it onto the last helicopter after 7 moves.') === 0
    && strpos($report, '"Help him down the stairs" at East Stairwell') !== false
    && strpos($report, '2 other survivors') !== false, $report);

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed === 0 ? 0 : 1);
