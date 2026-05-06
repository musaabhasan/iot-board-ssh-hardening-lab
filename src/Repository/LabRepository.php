<?php

declare(strict_types=1);

namespace IotBoardLab\Repository;

use IotBoardLab\Support\Uuid;
use PDO;
use Throwable;

final class LabRepository
{
    /**
     * @param array<string, mixed> $catalog
     */
    public function __construct(
        private readonly array $catalog,
        private readonly ?PDO $pdo = null
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function paper(): array
    {
        return $this->catalog['paper'];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function threats(): array
    {
        return $this->catalog['threats'];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function controls(): array
    {
        return $this->catalog['controls'];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function scenarios(): array
    {
        return $this->catalog['scenarios'];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function controlsById(): array
    {
        $indexed = [];
        foreach ($this->controls() as $control) {
            $indexed[(string) $control['id']] = $control;
        }

        return $indexed;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function threatsById(): array
    {
        $indexed = [];
        foreach ($this->threats() as $threat) {
            $indexed[(string) $threat['id']] = $threat;
        }

        return $indexed;
    }

    /**
     * @param array<string, mixed> $assessment
     */
    public function saveAssessment(array $assessment): ?string
    {
        if ($this->pdo === null) {
            return null;
        }

        $id = Uuid::v4();

        try {
            $statement = $this->pdo->prepare(
                'INSERT INTO assessments (
                    id,
                    device_name,
                    board_model,
                    os_image,
                    openssh_version,
                    ssh_mode,
                    score,
                    maturity,
                    risk_tier,
                    selected_controls,
                    result_payload,
                    created_at
                ) VALUES (
                    :id,
                    :device_name,
                    :board_model,
                    :os_image,
                    :openssh_version,
                    :ssh_mode,
                    :score,
                    :maturity,
                    :risk_tier,
                    :selected_controls,
                    :result_payload,
                    NOW()
                )'
            );
            $statement->execute([
                'id' => $id,
                'device_name' => (string) ($assessment['context']['device_name'] ?? 'Unnamed board'),
                'board_model' => (string) ($assessment['context']['board_model'] ?? ''),
                'os_image' => (string) ($assessment['context']['os_image'] ?? ''),
                'openssh_version' => (string) ($assessment['context']['openssh_version'] ?? ''),
                'ssh_mode' => (string) ($assessment['context']['ssh_mode'] ?? 'unknown'),
                'score' => (int) $assessment['score'],
                'maturity' => (string) $assessment['maturity'],
                'risk_tier' => (string) $assessment['risk_tier'],
                'selected_controls' => json_encode($assessment['selected_controls'], JSON_THROW_ON_ERROR),
                'result_payload' => json_encode($assessment, JSON_THROW_ON_ERROR),
            ]);

            $this->audit('assessment.created', ['assessment_id' => $id, 'score' => $assessment['score']]);

            return $id;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function recentAssessments(int $limit = 5): array
    {
        if ($this->pdo === null) {
            return [];
        }

        try {
            $statement = $this->pdo->prepare(
                'SELECT id, device_name, board_model, score, maturity, risk_tier, created_at
                 FROM assessments
                 ORDER BY created_at DESC
                 LIMIT :limit'
            );
            $statement->bindValue('limit', max(1, min(25, $limit)), PDO::PARAM_INT);
            $statement->execute();

            return $statement->fetchAll();
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function audit(string $event, array $payload): void
    {
        if ($this->pdo === null) {
            return;
        }

        try {
            $statement = $this->pdo->prepare(
                'INSERT INTO audit_events (id, event_name, payload, created_at)
                 VALUES (:id, :event_name, :payload, NOW())'
            );
            $statement->execute([
                'id' => Uuid::v4(),
                'event_name' => $event,
                'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
            ]);
        } catch (Throwable) {
        }
    }
}

