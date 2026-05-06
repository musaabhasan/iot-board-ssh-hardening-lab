<?php

declare(strict_types=1);

use IotBoardLab\Repository\LabRepository;
use IotBoardLab\Security\Csrf;
use IotBoardLab\Security\SecurityHeaders;
use IotBoardLab\Service\AssessmentService;
use IotBoardLab\Support\Database;
use IotBoardLab\Support\Json;
use IotBoardLab\Support\View;

$bootstrap = require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'bootstrap.php';

$repository = new LabRepository($bootstrap['catalog'], Database::connection());
$service = new AssessmentService($repository);
SecurityHeaders::apply();

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($path === '/health') {
    Json::respond([
        'status' => 'ok',
        'service' => 'iot-board-ssh-hardening-lab',
        'paper_doi' => $repository->paper()['doi'],
    ]);
}

if ($path === '/api/summary') {
    Json::respond($service->summary());
}

if ($path === '/api/assess') {
    if ($method !== 'POST') {
        Json::respond(['error' => 'POST required'], 405);
    }

    $payload = json_decode(file_get_contents('php://input') ?: '{}', true);
    if (!is_array($payload) || $payload === []) {
        $payload = $_POST;
    }

    Json::respond($service->assess($payload));
}

if ($path === '/') {
    echo View::page('IoT Board SSH Hardening Lab', renderDashboard($repository, $service));
    exit;
}

if ($path === '/assessment') {
    echo View::page('Assessment | IoT Board SSH Hardening Lab', renderAssessment($repository, $service, $method));
    exit;
}

if ($path === '/controls') {
    echo View::page('Controls | IoT Board SSH Hardening Lab', renderControls($repository));
    exit;
}

if ($path === '/paper') {
    echo View::page('Paper | IoT Board SSH Hardening Lab', renderPaper($repository));
    exit;
}

http_response_code(404);
echo View::page('Not Found | IoT Board SSH Hardening Lab', '<section class="panel hero"><h1>Page not found</h1><p>The requested page is not available.</p></section>');

function renderDashboard(LabRepository $repository, AssessmentService $service): string
{
    $summary = $service->summary();
    $paper = $repository->paper();
    $metrics = $summary['metrics'];
    $threats = array_slice($repository->threats(), 0, 4);
    $scenarios = $repository->scenarios();
    $recent = $summary['recent_assessments'];

    $keywordHtml = '';
    foreach ($paper['keywords'] as $keyword) {
        $keywordHtml .= '<span class="pill">' . View::e($keyword) . '</span>';
    }

    $threatHtml = '';
    foreach ($threats as $threat) {
        $threatHtml .= '<article class="threat-card">'
            . '<span>' . View::e($threat['category']) . '</span>'
            . '<h3>' . View::e($threat['name']) . '</h3>'
            . '<p>' . View::e($threat['business_impact']) . '</p>'
            . '<strong class="risk-tag ' . strtolower((string) $threat['severity']) . '">' . View::e($threat['severity']) . '</strong>'
            . '</article>';
    }

    $scenarioHtml = '';
    foreach ($scenarios as $scenario) {
        $scenarioHtml .= '<article class="scenario-row">'
            . '<div><span>' . View::e($scenario['risk']) . ' risk</span><strong>' . View::e($scenario['name']) . '</strong></div>'
            . '<p>' . View::e($scenario['recommended_action']) . '</p>'
            . '</article>';
    }

    $recentHtml = '<p class="muted">Assessments are stored when a database connection is configured.</p>';
    if ($recent !== []) {
        $recentHtml = '';
        foreach ($recent as $item) {
            $recentHtml .= '<div><strong>' . View::e($item['device_name']) . '</strong><span>' . View::e($item['score']) . '/100 - ' . View::e($item['risk_tier']) . '</span></div>';
        }
    }

    return <<<HTML
<section class="panel hero">
  <div>
    <p class="eyebrow">Research-Based SSH Hardening</p>
    <h1>IoT development board security assessment for SSH exposure, weak keys, and local man-in-the-middle risk.</h1>
    <p class="lead">A PHP/MySQL lab based on the peer-reviewed WWIC 2019 chapter by Omar Alfandi, Musaab Hasan, and Zayed Balbahaith.</p>
    <div class="hero-actions">
      <a class="button-link" href="/assessment">Run Assessment</a>
      <a class="secondary-link" href="/paper">View Paper Alignment</a>
    </div>
  </div>
  <aside class="paper-card">
    <span>Paper Reference</span>
    <strong>{$paper['title']}</strong>
    <p>{$paper['venue']} - LNCS {$paper['volume']} - pp. {$paper['pages']}</p>
    <a href="{$paper['doi_url']}" target="_blank" rel="noreferrer">{$paper['doi']}</a>
  </aside>
</section>

<section class="metric-grid">
  <article><span>Threat catalog</span><strong>{$metrics['threats']}</strong><p>SSH, entropy, network, host, and exposure risks.</p></article>
  <article><span>Control library</span><strong>{$metrics['controls']}</strong><p>Implementation and evidence guidance for board hardening.</p></article>
  <article><span>Risk scenarios</span><strong>{$metrics['scenarios']}</strong><p>From legacy SSH V1/V2 exposure to signed-key operation.</p></article>
  <article><span>Max weight</span><strong>{$metrics['maximum_score']}</strong><p>Weighted controls converted to a 100-point maturity score.</p></article>
</section>

<section class="section-head"><h2>Paper Themes</h2><a href="/controls">Control catalog</a></section>
<div class="keyword-row">{$keywordHtml}</div>

<section class="split-layout">
  <div>
    <div class="section-head compact"><h2>Priority Threats</h2><a href="/assessment">Assess a board</a></div>
    <div class="threat-grid">{$threatHtml}</div>
  </div>
  <aside class="panel side-panel">
    <h2>SSH Scenarios</h2>
    <div class="scenario-stack">{$scenarioHtml}</div>
  </aside>
</section>

<section class="panel recent-panel">
  <h2>Recent Assessments</h2>
  <div class="recent-list">{$recentHtml}</div>
</section>
HTML;
}

function renderAssessment(LabRepository $repository, AssessmentService $service, string $method): string
{
    $result = null;
    $notice = '';
    if ($method === 'POST') {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            $notice = '<div class="notice">The assessment could not be submitted because the session token expired.</div>';
        } else {
            $result = $service->assess($_POST);
        }
    }

    $controlsByCategory = [];
    foreach ($repository->controls() as $control) {
        $controlsByCategory[(string) $control['category']][] = $control;
    }

    $controlsHtml = '';
    foreach ($controlsByCategory as $category => $controls) {
        $controlsHtml .= '<fieldset class="control-set"><legend>' . View::e($category) . '</legend><div class="control-grid">';
        foreach ($controls as $control) {
            $id = View::e($control['id']);
            $checked = $result && in_array($control['id'], $result['selected_controls'], true) ? ' checked' : '';
            $controlsHtml .= <<<HTML
<label class="control-item">
  <input type="checkbox" name="controls[]" value="{$id}"{$checked}>
  <span><strong>{$control['name']}</strong><small>{$control['implementation']}</small></span>
</label>
HTML;
        }
        $controlsHtml .= '</div></fieldset>';
    }

    $resultHtml = '';
    if ($result !== null) {
        $recommendationHtml = '';
        foreach ($result['recommendations'] as $recommendation) {
            $recommendationHtml .= '<li><strong>' . View::e($recommendation['name']) . '</strong><span>' . View::e($recommendation['implementation']) . '</span></li>';
        }

        $threatHtml = '';
        foreach (array_slice($result['threats'], 0, 6) as $threat) {
            $threatHtml .= '<tr>'
                . '<td>' . View::e($threat['name']) . '</td>'
                . '<td>' . View::e($threat['severity']) . '</td>'
                . '<td>' . View::e($threat['residual_score']) . '</td>'
                . '<td>' . View::e($threat['residual_tier']) . '</td>'
                . '</tr>';
        }

        $resultHtml = <<<HTML
<section class="panel result-panel">
  <div class="result-score">
    <span>Assessment score</span>
    <strong>{$result['score']}</strong>
    <p>{$result['maturity']} maturity - {$result['risk_tier']} residual risk</p>
  </div>
  <div>
    <h2>Priority Actions</h2>
    <ol class="recommendation-list">{$recommendationHtml}</ol>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Threat</th><th>Severity</th><th>Residual</th><th>Tier</th></tr></thead>
      <tbody>{$threatHtml}</tbody>
    </table>
  </div>
</section>
HTML;
    }

    $csrf = Csrf::token();

    return <<<HTML
<section class="panel form-panel">
  <p class="eyebrow">Board Assessment</p>
  <h1>Score a development board against SSH hardening controls.</h1>
  {$notice}
  <form method="post" action="/assessment">
    <input type="hidden" name="_csrf" value="{$csrf}">
    <div class="form-grid">
      <label>Device name<input name="device_name" placeholder="Training lab board 01"></label>
      <label>Board model<input name="board_model" placeholder="Raspberry Pi 3 Model B"></label>
      <label>OS image<input name="os_image" placeholder="Raspberry Pi OS / Raspbian image"></label>
      <label>OpenSSH version<input name="openssh_version" placeholder="OpenSSH 9.x"></label>
      <label>SSH mode
        <select name="ssh_mode">
          <option value="unknown">Unknown</option>
          <option value="legacy-v1-v2">Legacy V1 and V2 enabled</option>
          <option value="v2-password">V2 with password access</option>
          <option value="v2-public-key">V2 with public-key access</option>
          <option value="hardened-signed-keys">Hardened with signed keys</option>
        </select>
      </label>
    </div>
    <div class="toggle-row">
      <label><input type="checkbox" name="default_image" value="1"> Fresh or cloned default image</label>
      <label><input type="checkbox" name="internet_exposed" value="1"> SSH reachable beyond the management network</label>
      <label><input type="checkbox" name="same_lan_admin" value="1" checked> Administration occurs on a shared local network</label>
    </div>
    {$controlsHtml}
    <button type="submit">Calculate Risk</button>
  </form>
</section>
{$resultHtml}
HTML;
}

function renderControls(LabRepository $repository): string
{
    $controlsByCategory = [];
    foreach ($repository->controls() as $control) {
        $controlsByCategory[(string) $control['category']][] = $control;
    }

    $html = '<section class="panel paper-detail"><p class="eyebrow">Control Library</p><h1>SSH hardening controls for development-board fleets.</h1></section>';
    foreach ($controlsByCategory as $category => $controls) {
        $html .= '<section class="section-head"><h2>' . View::e($category) . '</h2><span>' . count($controls) . ' controls</span></section><div class="control-catalog">';
        foreach ($controls as $control) {
            $html .= '<article class="control-card">'
                . '<span>' . View::e($control['maturity']) . ' - weight ' . View::e($control['weight']) . '</span>'
                . '<h3>' . View::e($control['name']) . '</h3>'
                . '<p>' . View::e($control['implementation']) . '</p>'
                . '<small>Evidence: ' . View::e($control['evidence']) . '</small>'
                . '</article>';
        }
        $html .= '</div>';
    }

    return $html;
}

function renderPaper(LabRepository $repository): string
{
    $paper = $repository->paper();
    $keywordHtml = '';
    foreach ($paper['keywords'] as $keyword) {
        $keywordHtml .= '<span class="pill">' . View::e($keyword) . '</span>';
    }

    $threatHtml = '';
    foreach ($repository->threats() as $threat) {
        $threatHtml .= '<article class="threat-card">'
            . '<span>' . View::e($threat['category']) . '</span>'
            . '<h3>' . View::e($threat['name']) . '</h3>'
            . '<p>' . View::e($threat['paper_signal']) . '</p>'
            . '</article>';
    }

    $scenarioHtml = '';
    foreach ($repository->scenarios() as $scenario) {
        $scenarioHtml .= '<article class="scenario-row">'
            . '<div><span>' . View::e($scenario['risk']) . ' risk</span><strong>' . View::e($scenario['name']) . '</strong></div>'
            . '<p>' . View::e($scenario['description']) . '</p>'
            . '</article>';
    }

    return <<<HTML
<section class="panel paper-detail">
  <p class="eyebrow">Research Alignment</p>
  <h1>{$paper['title']}</h1>
  <p class="lead">{$paper['summary']}</p>
  <div class="paper-citation">
    <span>Formal citation</span>
    <p>{$paper['citation']}</p>
  </div>
  <div class="hero-actions">
    <a class="button-link" href="{$paper['doi_url']}" target="_blank" rel="noreferrer">DOI</a>
    <a class="secondary-link" href="{$paper['springer_url']}" target="_blank" rel="noreferrer">Springer</a>
    <a class="secondary-link" href="{$paper['repository_url']}" target="_blank" rel="noreferrer">Repository Record</a>
  </div>
</section>

<section class="section-head"><h2>Keywords</h2></section>
<div class="keyword-row">{$keywordHtml}</div>

<section class="section-head"><h2>Mapped Threats</h2></section>
<div class="threat-grid">{$threatHtml}</div>

<section class="section-head"><h2>SSH Scenario Model</h2></section>
<div class="scenario-stack wide">{$scenarioHtml}</div>
HTML;
}
