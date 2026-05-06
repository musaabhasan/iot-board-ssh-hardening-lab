# Extension Guide

## Add a New Control

1. Add the control to `config/paper.php`.
2. Add a matching row to `database/seeders/001_seed_research_data.sql`.
3. Map the control to one or more threats in `threat_control_map`.
4. Run `php bin/test.php`.

## Add Evidence Tracking

Recommended table additions:

- `assessment_evidence`
- `evidence_files`
- `evidence_reviews`

Suggested fields:

- `assessment_id`
- `control_id`
- `evidence_type`
- `evidence_summary`
- `review_status`
- `reviewed_by`
- `reviewed_at`

## Add Authentication

Recommended approach:

- Add an `admin_users` table with password hashing through `password_hash`.
- Require login before showing assessment history.
- Add role checks for catalog maintenance and evidence review.
- Log successful login, failed login, assessment creation, and catalog changes.

## Add Fleet Inventory

Recommended tables:

- `board_assets`
- `board_network_interfaces`
- `board_ssh_fingerprints`
- `board_software_versions`
- `board_scan_results`

These can be connected to the assessment engine by pre-filling board model, operating-system image, OpenSSH version, SSH mode, and exposure flags.

