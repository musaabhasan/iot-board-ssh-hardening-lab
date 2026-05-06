# Architecture

The platform is organized as a small PHP/MySQL application that can be extended without changing the assessment model.

## Layers

1. Web interface

   `public/index.php` provides the dashboard, assessment form, control catalog, paper page, health endpoint, and JSON endpoints.

2. Service layer

   `src/Service/AssessmentService.php` normalizes assessment input, calculates weighted maturity, ranks residual threats, and returns prioritized actions.

3. Repository layer

   `src/Repository/LabRepository.php` exposes paper metadata, threats, controls, scenarios, and optional MySQL persistence for assessment history.

4. Configuration catalog

   `config/paper.php` is the source of truth for the paper citation, threat model, control catalog, and SSH scenario model.

5. Persistence layer

   `database/migrations/001_create_core_tables.sql` and `database/seeders/001_seed_research_data.sql` create normalized tables for production data, seed the research catalog, and retain assessment evidence.

## Request Flow

```mermaid
flowchart LR
  A["Visitor or API client"] --> B["public/index.php"]
  B --> C["Security headers and session protection"]
  C --> D["LabRepository"]
  C --> E["AssessmentService"]
  D --> F["config/paper.php"]
  D --> G["MySQL persistence when configured"]
  E --> H["Score, residual threats, recommendations"]
  H --> I["HTML result or JSON response"]
```

## Assessment Flow

```mermaid
flowchart TD
  A["Board context"] --> C["Normalize input"]
  B["Selected controls"] --> C
  C --> D["Calculate weighted maturity"]
  D --> E["Apply threat modifiers"]
  E --> F["Rank residual risk"]
  F --> G["Prioritize missing controls"]
  G --> H["Store assessment and audit event"]
```

## Extension Points

- Add controls in `config/paper.php` and `database/seeders/001_seed_research_data.sql`.
- Add a new board scenario in the `scenarios` array and `scenario_catalog` seed.
- Replace or augment persistence in `LabRepository`.
- Add authentication middleware before route handling in `public/index.php`.
- Add an evidence upload workflow using the existing assessment identifier.

