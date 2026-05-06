# Database

The MySQL schema supports both the seeded research catalog and operational assessment history.

## Tables

| Table | Purpose |
| --- | --- |
| `paper_references` | Formal paper metadata and citation details. |
| `threat_catalog` | Threats derived from the paper's SSH and board-hardening findings. |
| `control_catalog` | Weighted hardening controls with implementation and evidence guidance. |
| `threat_control_map` | Many-to-many mapping between threats and controls. |
| `scenario_catalog` | SSH scenario model from weak to hardened operation. |
| `assessments` | Persisted assessment context, score, selected controls, and JSON result. |
| `audit_events` | Audit trail for assessment creation and future administrative actions. |

## Migration Order

1. `database/migrations/001_create_core_tables.sql`
2. `database/seeders/001_seed_research_data.sql`

Docker Compose mounts both scripts into the MySQL initialization directory in this order.

## Data Handling

Board names, SSH versions, network exposure, host fingerprints, and assessment evidence can reveal operational security posture. Treat production assessment data as sensitive and apply retention, access control, logging, and backup policies accordingly.

