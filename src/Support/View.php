<?php

declare(strict_types=1);

namespace IotBoardLab\Support;

final class View
{
    public static function e(string|int|float|null $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function page(string $title, string $body): string
    {
        $safeTitle = self::e($title);

        return <<<HTML
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{$safeTitle}</title>
  <meta name="description" content="IoT development board SSH hardening lab based on peer-reviewed research.">
  <link rel="stylesheet" href="/assets/app.css">
</head>
<body>
  <header class="topbar">
    <a class="brand" href="/">
      <span class="brand-mark">SSH</span>
      <span>IoT Board Hardening Lab</span>
    </a>
    <nav aria-label="Primary">
      <a href="/">Dashboard</a>
      <a href="/assessment">Assessment</a>
      <a href="/controls">Controls</a>
      <a href="/paper">Paper</a>
      <a href="/api/summary">API</a>
    </nav>
  </header>
  <main class="page-shell">
    {$body}
  </main>
</body>
</html>
HTML;
    }
}

