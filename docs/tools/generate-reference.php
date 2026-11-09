<?php

/** Generate documentation from routing, migration source and setting defaults; never query business data. */
declare(strict_types=1);

$root = dirname(__DIR__, 2);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$date = $argv[1] ?? date('Y-m-d');
if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    throw new InvalidArgumentException('Optional argument must be a YYYY-MM-DD documentation date.');
}

function cell(mixed $value): string
{
    return str_replace(["\r", "\n", '|', '`'], ['', '<br>', '&#124;', '&#96;'], (string) $value);
}

function saveReference(string $file, array $lines): void
{
    $result = file_put_contents(dirname(__DIR__).'/'.$file, implode("\n", $lines)."\n");
    if ($result === false) {
        throw new RuntimeException('Could not write '.$file);
    }
    echo $file, ': ', $result, " bytes\n";
}

$groups = ['Storefront and checkout' => [], 'Authentication' => [], 'Customer account' => [], 'Administration' => [], 'Framework endpoints' => []];
$missing = [];
foreach (app('router')->getRoutes() as $route) {
    $uri = $route->uri();
    $action = $route->getActionName();
    $group = str_starts_with($uri, 'admin') ? 'Administration'
        : (str_contains($action, '\\Auth\\') ? 'Authentication'
        : (str_starts_with($uri, 'account') || str_starts_with($uri, 'wishlist') ? 'Customer account'
        : ($action === 'Closure' || str_starts_with($action, 'Illuminate\\') ? 'Framework endpoints' : 'Storefront and checkout')));
    $status = 'Registered';
    if (str_contains($action, '@')) {
        [$class, $method] = explode('@', $action, 2);
        if (! method_exists($class, $method)) {
            $status = '**Missing controller method**';
            $missing[] = '/'.$uri;
        }
    }
    $groups[$group][] = '| `'.cell(implode('|', $route->methods())).'` | `'.cell('/'.ltrim($uri, '/')).'` | '.cell($route->getName() ?: '(unnamed)').' | `'.cell(str_replace('App\\Http\\Controllers\\', '', $action)).'` | '.cell(implode(', ', $route->gatherMiddleware()) ?: '(none)').' | '.$status.' |';
}
$lines = ['# HTTP route reference', '', '[Documentation home](DOCUMENTATION.md)', '', 'Generated from the registered Laravel route collection on '.$date.'.', '',
    'These are session-based web routes, not a separately versioned public REST API. Mutating browser forms normally require a CSRF token. PATCH, PUT and DELETE forms use Laravel method spoofing. The Razorpay webhook is the explicit CSRF exception and verifies its signature in the controller.', '',
    'The middleware column shows route aliases. The `web` group also includes active-account checks; policies and controller-level order/session ownership checks are additional to this column. A registered route is not proof that its full behavior has been tested. Parameters such as `{product}` use the binding configured in source; storefront products/categories use slugs and storefront orders use order numbers.', '',
    'Total routes: **'.array_sum(array_map('count', $groups)).'**. Missing controller actions: **'.count($missing).'**.', '',
    'Regenerate from the project root: `php docs/tools/generate-reference.php` (requires installed Composer dependencies; writes documentation only).', ''];
foreach ($groups as $name => $rows) {
    $lines[] = '## '.$name;
    $lines[] = '';
    $lines[] = '| Method | URL | Route name | Controller action | Middleware | Implementation |';
    $lines[] = '|---|---|---|---|---|---|';
    array_push($lines, ...$rows);
    $lines[] = '';
}
saveReference('ROUTES_REFERENCE.md', $lines);

$tables = [];
foreach (glob($root.'/database/migrations/*.php') as $migration) {
    $source = file_get_contents($migration);
    $up = explode('public function down', $source, 2)[0];
    preg_match_all("/Schema::(create|table)\('([^']+)', function \(Blueprint \\\$table\) \{(.*?)\n        \}\);/s", $up, $blocks, PREG_SET_ORDER);
    foreach ($blocks as $block) {
        $name = $block[2];
        $tables[$name] ??= ['sources' => [], 'columns' => [], 'indexes' => []];
        $tables[$name]['sources'][] = basename($migration);
        preg_match_all('/\$table->([^;]+);/', $block[3], $declarations);
        foreach ($declarations[1] as $declaration) {
            $declaration = trim(preg_replace('/\s+/', ' ', $declaration));
            preg_match('/^(\w+)\(/', $declaration, $methodMatch);
            $method = $methodMatch[1] ?? '';
            if (in_array($method, ['index', 'unique', 'primary', 'foreign', 'fullText'], true)) {
                $tables[$name]['indexes'][] = '`'.cell($declaration).'`';
                continue;
            }
            preg_match("/^\w+\('([^']+)'/", $declaration, $columnMatch);
            $column = $columnMatch[1] ?? match ($method) {
                'id' => 'id', 'timestamps' => 'created_at, updated_at',
                'softDeletes' => 'deleted_at', 'rememberToken' => 'remember_token',
                default => throw new RuntimeException('Unrecognized schema declaration: '.$declaration),
            };
            $tables[$name]['columns'][] = '| `'.cell($column).'` | `'.cell($declaration).'` |';
        }
    }
}
if (count($tables) !== 36) {
    throw new RuntimeException('Expected the documented 36 tables; review migration changes before regenerating.');
}
$lines = ['# Database reference', '', '[Documentation home](DOCUMENTATION.md)', '',
    'Generated from migration source on '.$date.'. This reference includes **36 explicitly declared tables: 29 application tables and 7 framework support tables**. Laravel also creates its `migrations` bookkeeping table. This describes the source schema, not the contents or migration status of an installed database.', '',
    'Migrations are authoritative. The optional handwritten SQL file predates the Google-login migration and must not replace the current migration workflow. Definitions below use Laravel Blueprint notation so precision, limits, nullability, defaults, indexes and foreign-key deletion rules remain explicit.', '',
    '`id()` declares an auto-incrementing primary key; `timestamps()` adds nullable creation/update timestamps; `softDeletes()` adds a deletion timestamp; `rememberToken()` adds a nullable remember-me token. A column is required unless `nullable()` is present. `constrained()` infers the referenced table when no table name is supplied. Composite indexes are listed below each table; inline indexes appear in the column declaration.', '',
    'Currency amounts use decimal database columns. Checkout arithmetic uses integer paise through `Money`; reporting aggregates use database sums. Price and stock belong to product variants. Order items and addresses are snapshots. JSON columns store product specifications, payment metadata and webhook payloads; they are not card-storage fields.', '',
    'See the handbook for domain relationships, order/payment states, inventory rules and seed behavior. Regenerate with `php docs/tools/generate-reference.php`.', '', '## Tables', ''];
foreach ($tables as $name => $table) {
    $lines[] = '- ['.$name.'](#'.str_replace('_', '-', $name).')';
}
foreach ($tables as $name => $table) {
    $lines[] = '';
    // Explicit anchors avoid renderer-dependent treatment of underscores.
    $lines[] = '<a id="'.str_replace('_', '-', $name).'"></a>';
    $lines[] = '## '.$name;
    $lines[] = '';
    $links = array_map(fn ($source) => '['.$source.'](../database/migrations/'.$source.')', array_unique($table['sources']));
    $lines[] = 'Source: '.implode(', ', $links).'.';
    $lines[] = '';
    $lines[] = '| Column(s) | Schema declaration |';
    $lines[] = '|---|---|';
    array_push($lines, ...$table['columns']);
    if ($table['indexes']) {
        $lines[] = '';
        $lines[] = 'Additional indexes/constraints: '.implode('; ', $table['indexes']).'.';
    }
}
saveReference('DATABASE_REFERENCE.md', $lines);

$lines = ['# Runtime settings reference', '', '[Documentation home](DOCUMENTATION.md)', '',
    'Generated from `config/arsh.php` defaults on '.$date.'. These are declared defaults, not values from the installed settings table. Administrators edit these under **Admin > Settings**. Google/Razorpay secrets, database credentials and mail credentials belong in environment configuration and are intentionally absent from this list.', '',
    'Types describe how `Setting` casts values. Boolean defaults use `0`/`1`; `(empty)` means an empty string. Homepage switches control sections when their required content exists. Currency formatting is configurable, but checkout/payment currency is INR; changing the symbol does not implement currency conversion.', '',
    'The settings form writes all boolean switches on submission; unchecked/omitted switches become false. Keep the complete form when extending the UI. Regenerate with `php docs/tools/generate-reference.php`.', '',
    '| Key | Group | Type | Default |', '|---|---|---|---|'];
foreach (config('arsh.settings') as $key => [$group, $type, $default]) {
    $lines[] = '| `'.cell($key).'` | '.cell($group).' | '.cell($type).' | '.cell($default === '' ? '(empty)' : $default).' |';
}
saveReference('SETTINGS_REFERENCE.md', $lines);
