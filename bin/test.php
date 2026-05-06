<?php

declare(strict_types=1);

use IotBoardLab\Repository\LabRepository;
use IotBoardLab\Service\AssessmentService;

$bootstrap = require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'bootstrap.php';

$repository = new LabRepository($bootstrap['catalog']);
$service = new AssessmentService($repository);

$assertions = 0;

function assertThat(bool $condition, string $message): void
{
    global $assertions;
    $assertions++;

    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$paper = $repository->paper();
assertThat($paper['doi'] === '10.1007/978-3-030-30523-9_3', 'Paper DOI must match the formal chapter DOI.');
assertThat(str_contains($paper['citation'], 'Assessment and Hardening of IoT Development Boards'), 'Citation must include the paper title.');

$controls = $repository->controls();
$threats = $repository->threats();
$scenarios = $repository->scenarios();
assertThat(count($controls) >= 20, 'Control catalog should contain at least 20 controls.');
assertThat(count($threats) >= 9, 'Threat catalog should contain at least 9 threats.');
assertThat(count($scenarios) === 4, 'Scenario model should contain four SSH scenarios.');

$maximum = array_sum(array_map(static fn (array $control): int => (int) $control['weight'], $controls));
assertThat($maximum === 145, 'Maximum control weight should be 145.');

$allControlIds = array_map(static fn (array $control): string => (string) $control['id'], $controls);
$hardened = $service->assess([
    'controls' => $allControlIds,
    'ssh_mode' => 'hardened-signed-keys',
    'default_image' => false,
    'internet_exposed' => false,
    'same_lan_admin' => false,
]);

assertThat($hardened['score'] === 100, 'All controls should score 100.');
assertThat($hardened['maturity'] === 'Hardened', 'All controls should produce hardened maturity.');
assertThat($hardened['risk_tier'] === 'Low', 'All controls should produce low residual risk.');
assertThat(max(array_column($hardened['threats'], 'residual_score')) <= 35, 'All controls should reduce residual threat scores.');

$weak = $service->assess([
    'controls' => [],
    'ssh_mode' => 'legacy-v1-v2',
    'default_image' => true,
    'internet_exposed' => true,
    'same_lan_admin' => true,
]);

assertThat($weak['score'] === 0, 'No controls should score zero.');
assertThat($weak['maturity'] === 'High Exposure', 'No controls should produce high exposure maturity.');
assertThat($weak['risk_tier'] === 'Critical', 'Legacy SSH with default image exposure should be critical.');
assertThat($weak['threats'][0]['residual_score'] === 100, 'Highest weak-profile residual should be capped at 100.');

$partial = $service->assess([
    'controls' => ['ssh-protocol-2', 'regenerate-host-keys', 'hardware-rng', 'ssh-config-audit', 'mitm-monitoring'],
    'ssh_mode' => 'v2-public-key',
    'default_image' => false,
    'internet_exposed' => false,
    'same_lan_admin' => true,
]);
$partialThreats = [];
foreach ($partial['threats'] as $threat) {
    $partialThreats[$threat['id']] = $threat;
}
assertThat($partial['score'] > 30, 'Partial SSH controls should improve the score above the baseline.');
assertThat($partialThreats['ssh-v1-downgrade']['residual_score'] < 55, 'Protocol controls should reduce SSH downgrade risk.');
assertThat($partialThreats['weak-first-boot-keys']['residual_score'] < 60, 'Host-key controls should reduce weak-key risk.');

$summary = $service->summary();
assertThat($summary['metrics']['controls'] === count($controls), 'Summary must report control count.');
assertThat($summary['metrics']['threats'] === count($threats), 'Summary must report threat count.');
assertThat($summary['metrics']['maximum_score'] === 145, 'Summary must report maximum score.');

$migration = file_get_contents(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR . '001_create_core_tables.sql');
$seed = file_get_contents(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'seeders' . DIRECTORY_SEPARATOR . '001_seed_research_data.sql');
assertThat(is_string($migration) && str_contains($migration, 'CREATE TABLE IF NOT EXISTS assessments'), 'Migration must create assessments table.');
assertThat(is_string($migration) && str_contains($migration, 'CREATE TABLE IF NOT EXISTS audit_events'), 'Migration must create audit events table.');
assertThat(is_string($seed) && str_contains($seed, 'ssh-v1-downgrade'), 'Seed must include SSH downgrade threat.');
assertThat(is_string($seed) && str_contains($seed, 'regenerate-host-keys'), 'Seed must include host-key regeneration control.');

echo 'Tests passed: ' . $assertions . ' assertions.' . PHP_EOL;

