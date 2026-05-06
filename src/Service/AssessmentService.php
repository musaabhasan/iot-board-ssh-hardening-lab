<?php

declare(strict_types=1);

namespace IotBoardLab\Service;

use IotBoardLab\Repository\LabRepository;

final class AssessmentService
{
    public function __construct(private readonly LabRepository $repository)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        $controls = $this->repository->controls();
        $threats = $this->repository->threats();
        $categories = [];

        foreach ($controls as $control) {
            $category = (string) $control['category'];
            $categories[$category] = ($categories[$category] ?? 0) + 1;
        }

        return [
            'paper' => $this->repository->paper(),
            'metrics' => [
                'threats' => count($threats),
                'controls' => count($controls),
                'scenarios' => count($this->repository->scenarios()),
                'control_categories' => count($categories),
                'maximum_score' => $this->maximumScore(),
            ],
            'control_categories' => $categories,
            'scenarios' => $this->repository->scenarios(),
            'recent_assessments' => $this->repository->recentAssessments(),
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function assess(array $input): array
    {
        $selectedControls = $this->normalizeSelectedControls($input['controls'] ?? []);
        $controlsById = $this->repository->controlsById();
        $selected = array_values(array_intersect($selectedControls, array_keys($controlsById)));
        $earned = 0;

        foreach ($selected as $controlId) {
            $earned += (int) $controlsById[$controlId]['weight'];
        }

        $score = (int) round(($earned / $this->maximumScore()) * 100);
        $context = $this->context($input);
        $threats = $this->residualThreats($selected, $score, $context);
        $riskTier = $this->riskTier($threats);
        $maturity = $this->maturity($score, $riskTier);
        $recommendations = $this->recommendations($selected, $threats);

        $result = [
            'score' => $score,
            'maximum_score' => 100,
            'earned_weight' => $earned,
            'available_weight' => $this->maximumScore(),
            'maturity' => $maturity,
            'risk_tier' => $riskTier,
            'selected_controls' => $selected,
            'context' => $context,
            'threats' => $threats,
            'recommendations' => $recommendations,
            'next_actions' => $this->nextActions($score, $riskTier, $recommendations),
        ];

        $assessmentId = $this->repository->saveAssessment($result);
        if ($assessmentId !== null) {
            $result['assessment_id'] = $assessmentId;
        }

        return $result;
    }

    private function maximumScore(): int
    {
        return array_sum(array_map(
            static fn (array $control): int => (int) $control['weight'],
            $this->repository->controls()
        ));
    }

    /**
     * @param mixed $controls
     * @return array<int, string>
     */
    private function normalizeSelectedControls(mixed $controls): array
    {
        if (is_string($controls)) {
            $controls = array_filter(array_map('trim', explode(',', $controls)));
        }

        if (!is_array($controls)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            static fn (mixed $control): string => preg_replace('/[^a-z0-9-]/', '', strtolower((string) $control)) ?? '',
            $controls
        ))));
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function context(array $input): array
    {
        return [
            'device_name' => $this->cleanText($input['device_name'] ?? 'Development board'),
            'board_model' => $this->cleanText($input['board_model'] ?? 'Raspberry Pi or compatible development board'),
            'os_image' => $this->cleanText($input['os_image'] ?? 'Board Linux image'),
            'openssh_version' => $this->cleanText($input['openssh_version'] ?? 'Unknown'),
            'ssh_mode' => $this->cleanText($input['ssh_mode'] ?? 'unknown'),
            'default_image' => $this->bool($input['default_image'] ?? false),
            'internet_exposed' => $this->bool($input['internet_exposed'] ?? false),
            'same_lan_admin' => $this->bool($input['same_lan_admin'] ?? true),
        ];
    }

    private function cleanText(mixed $value): string
    {
        $value = trim((string) $value);
        $value = preg_replace('/\s+/', ' ', $value) ?? '';

        return substr($value, 0, 120);
    }

    private function bool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * @param array<int, string> $selected
     * @param array<string, mixed> $context
     * @return array<int, array<string, mixed>>
     */
    private function residualThreats(array $selected, int $score, array $context): array
    {
        $severity = [
            'Critical' => 100,
            'High' => 82,
            'Medium' => 58,
            'Low' => 35,
        ];

        $residuals = [];
        foreach ($this->repository->threats() as $threat) {
            $recommended = array_map('strval', $threat['recommended_controls']);
            $covered = count(array_intersect($recommended, $selected));
            $coverageReduction = min(66, $covered * 22);
            $generalReduction = min(20, (int) round($score * 0.2));
            $residual = ($severity[(string) $threat['severity']] ?? 60) - $coverageReduction - $generalReduction;

            $id = (string) $threat['id'];
            if ($id === 'ssh-v1-downgrade' && $context['ssh_mode'] === 'legacy-v1-v2') {
                $residual += 28;
            }
            if ($id === 'weak-first-boot-keys' && $context['default_image'] === true) {
                $residual += 22;
            }
            if ($id === 'arp-mitm' && $context['same_lan_admin'] === true) {
                $residual += 10;
            }
            if (in_array($id, ['open-remote-services', 'outdated-openssh', 'password-admin'], true) && $context['internet_exposed'] === true) {
                $residual += 12;
            }
            if ($id === 'password-admin' && $context['ssh_mode'] === 'v2-password') {
                $residual += 14;
            }

            $residual = max(5, min(100, $residual));
            $residuals[] = [
                'id' => $id,
                'name' => (string) $threat['name'],
                'category' => (string) $threat['category'],
                'severity' => (string) $threat['severity'],
                'residual_score' => $residual,
                'residual_tier' => $this->tierFromScore($residual),
                'covered_controls' => $covered,
                'required_controls' => count($recommended),
                'paper_signal' => (string) $threat['paper_signal'],
                'business_impact' => (string) $threat['business_impact'],
                'recommended_controls' => $recommended,
            ];
        }

        usort(
            $residuals,
            static fn (array $left, array $right): int => $right['residual_score'] <=> $left['residual_score']
        );

        return $residuals;
    }

    /**
     * @param array<int, array<string, mixed>> $threats
     */
    private function riskTier(array $threats): string
    {
        $highest = (int) max(array_column($threats, 'residual_score'));
        return $this->tierFromScore($highest);
    }

    private function tierFromScore(int $score): string
    {
        return match (true) {
            $score >= 85 => 'Critical',
            $score >= 70 => 'High',
            $score >= 45 => 'Medium',
            default => 'Low',
        };
    }

    private function maturity(int $score, string $riskTier): string
    {
        if ($score >= 90 && $riskTier === 'Low') {
            return 'Hardened';
        }
        if ($score >= 70) {
            return 'Managed';
        }
        if ($score >= 45) {
            return 'Developing';
        }

        return 'High Exposure';
    }

    /**
     * @param array<int, string> $selected
     * @param array<int, array<string, mixed>> $threats
     * @return array<int, array<string, mixed>>
     */
    private function recommendations(array $selected, array $threats): array
    {
        $controlsById = $this->repository->controlsById();
        $ranked = [];

        foreach (array_slice($threats, 0, 5) as $threat) {
            foreach ($threat['recommended_controls'] as $controlId) {
                if (in_array($controlId, $selected, true) || !isset($controlsById[$controlId])) {
                    continue;
                }

                $ranked[$controlId] = ($ranked[$controlId] ?? 0) + (int) $threat['residual_score'];
            }
        }

        foreach ($controlsById as $controlId => $control) {
            if (!in_array($controlId, $selected, true)) {
                $ranked[$controlId] = ($ranked[$controlId] ?? 0) + (int) $control['weight'];
            }
        }

        arsort($ranked);
        $recommendations = [];

        foreach (array_slice(array_keys($ranked), 0, 7) as $controlId) {
            $control = $controlsById[$controlId];
            $recommendations[] = [
                'id' => $controlId,
                'name' => (string) $control['name'],
                'category' => (string) $control['category'],
                'weight' => (int) $control['weight'],
                'implementation' => (string) $control['implementation'],
                'evidence' => (string) $control['evidence'],
            ];
        }

        return $recommendations;
    }

    /**
     * @param array<int, array<string, mixed>> $recommendations
     * @return array<int, string>
     */
    private function nextActions(int $score, string $riskTier, array $recommendations): array
    {
        if ($score >= 90 && $riskTier === 'Low') {
            return [
                'Maintain the patch cadence and repeat the evidence review at each image release.',
                'Run periodic SSH exposure scans and reconcile key inventory against deployed boards.',
                'Use the lab output as assurance evidence for board onboarding and change control.',
            ];
        }

        $actions = [
            'Close the highest residual SSH and key-management exposure before field deployment.',
            'Capture before-and-after scan evidence for the management path.',
        ];

        foreach (array_slice($recommendations, 0, 3) as $recommendation) {
            $actions[] = 'Implement: ' . $recommendation['name'] . '.';
        }

        return $actions;
    }
}
