# Dead Zone: Downtown

A text-based survival role-playing game for CSC 4370 Project 2, Topic 05 (Choose Your Path). Players register, log in, select a survivor class, and navigate a branching downtown outbreak. PHP handles choices, state, endings, scoring, and the rule-based Survival Advisor.

## Live game

CODD URL: **[Insert verified live CODD URL]**

## Team

- Suryaprakash Murugavvel — project lead / game development **(confirm role and student ID)**
- Victor Doga — team page, documentation, CSRF form protection **(confirm student ID)**

## Setup

Requires PHP 7.3 or later. Upload the repository to a PHP-enabled CODD directory. Make `data/` writable by the PHP process (`chmod 775 data` where appropriate; ownership must also permit writes). From the repository root, run `php -S localhost:8080` for local testing, then open `http://localhost:8080`. Do not expose the writable data directory publicly; its `.htaccess` restricts web access on Apache.

## How to play

Register an account, log in, choose a survivor class, and follow story choices. Decisions change health, stamina, noise, infection, inventory, and survivors. Loud decisions can attract a horde; infection and other conditions can end a run. The ending shows the outcome and score; the session leaderboard displays recent scores.

## Folder structure

- `index.php` — public landing page
- `team.php` — public team introduction
- `register.php` — account registration
- `login.php` — sign-in
- `logout.php` — sign-out
- `create.php` — character selection and saved-run prompt
- `resume.php` — POST handler for restoring a validated saved run
- `game.php` — story choices and inventory actions
- `ending.php` — ending and Survivor Report
- `leaderboard.php` — session leaderboard
- `includes/config.php` — session setup, escaping, flash messages, and CSRF helpers
- `includes/auth.php` — account storage and authentication
- `includes/story.php` — story nodes and branching choices
- `includes/engine.php` — game rules, scoring, and Survival Advisor
- `includes/save.php` — signed cookie save and restore
- `includes/header.php` — shared navigation and page opening
- `includes/footer.php` — shared page ending
- `css/style.css` — styling and responsive layouts
- `img/connector-500.jpg`, `img/connector-960.jpg`, `img/connector-1280.jpg` — responsive downtown imagery
- `data/.htaccess` — deny direct access to stored user data
- `tests/advisor_test.php` — command-line rule tests
- `.gitignore` — excludes generated/private data

## Five core requirements

1. **Sessions and leaderboard:** `includes/config.php`, `includes/engine.php`, and `leaderboard.php` maintain gameplay state and session scores.
2. **Form processing:** `login.php`, `register.php`, `create.php`, and `game.php` validate PHP form submissions and return feedback.
3. **Registration and login:** `includes/auth.php`, `register.php`, `login.php`, and `logout.php` manage accounts, password hashing, and session access.
4. **PHP game logic:** `includes/engine.php` and `game.php` implement branching, item use, stats, endings, and scores server-side.
5. **Choose Your Path topic:** `includes/story.php`, `create.php`, `game.php`, and `ending.php` implement the branching survival story.

## Survival Advisor

The advisor is a deterministic rules engine, not a trained machine-learning model. It previews likely consequences of choices, explains locked choices (such as missing stamina or items), and produces a Survivor Report describing the run. Its predictions depend on the game rules and current session state, not external information.

**Score:** ending base + 150 per survivor + remaining health (if survived) + 20 per carried item (if survived) + 20 per move under 14 − 2 per infection point. The result is clamped to zero or higher. See the comment above `calculate_score()` in `includes/engine.php`.

Run `php tests/advisor_test.php` from the project root. Output from the supplied repository, tested before these changes:

```text
PASS  Locked choice explains the stamina gap
PASS  Locked choice names the missing item
PASS  Scavenger can open the security office
PASS  Preview shows the Runner perk (−5 instead of −10 Stamina)
PASS  Preview warns a low-Luck hero about a bite
PASS  Preview reassures a high-Luck hero
PASS  Too much noise sends the player into the horde
PASS  Survivor Report names class, ending, moves, decisions and survivors

8 passed, 0 failed
```

**Limits:** Luck does not change during a run, making bite outcomes predictable. An older signed save cookie can be replayed to retry a choice. The session leaderboard is temporary and clears when the session is destroyed on logout.

## Image credit

“Downtown Connector at night” by Scott Ehardt, public domain, via Wikimedia Commons.
