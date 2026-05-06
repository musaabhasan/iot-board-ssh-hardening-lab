# IoT Board SSH Hardening Lab

A PHP 8.x and MySQL 8.0 research portal for assessing SSH exposure on Raspberry Pi and comparable IoT development boards.

The project is based on **"Assessment and Hardening of IoT Development Boards"** by Omar Alfandi, Musaab Hasan, and Zayed Balbahaith. It translates the paper's SSH security findings into a practical assessment engine, control catalog, scenario model, database schema, and web interface for development-board hardening.

## Paper Reference

Alfandi, O., Hasan, M., & Balbahaith, Z. (2019). **Assessment and Hardening of IoT Development Boards**. In M. Di Felice, E. Natalizio, R. Bruno, & A. Kassler (Eds.), *Wired/Wireless Internet Communications, WWIC 2019*, Lecture Notes in Computer Science, vol. 11618, pp. 27-39. Springer, Cham. https://doi.org/10.1007/978-3-030-30523-9_3

Official records:

- Springer: https://link.springer.com/chapter/10.1007/978-3-030-30523-9_3
- Repository record: https://zuscholars.zu.ac.ae/works/582/

## What This Repository Provides

- Weighted SSH hardening assessment for IoT development boards.
- Threat model covering SSH V1 downgrade, weak first-boot host keys, ARP-based man-in-the-middle exposure, outdated OpenSSH, default image attack surface, password administration, unverified public keys, remote service exposure, and privilege escalation.
- Control catalog with implementation and evidence guidance across SSH configuration, cryptographic assurance, network trust, patching, system minimization, access control, and auditability.
- Scenario model for legacy SSH, password-based SSH V2, public-key SSH V2, and hardened signed-key operation.
- MySQL schema and seed data for paper references, threats, controls, mappings, scenarios, assessments, and audit events.
- JSON API for dashboard integration or research extension.
- Security-conscious PHP implementation using security headers, CSRF validation, input normalization, PDO prepared statements, secure cookies, and JSON-safe persistence.
- Local linting, functional tests, HTTP smoke-test compatibility, and database migration validation.

## Quick Start

```bash
cp .env.example .env
docker compose up --build
```

Then open:

- Application: `http://localhost:8085`
- Assessment: `http://localhost:8085/assessment`
- Controls: `http://localhost:8085/controls`
- Paper alignment: `http://localhost:8085/paper`
- Health endpoint: `http://localhost:8085/health`
- JSON summary: `http://localhost:8085/api/summary`

## Local Checks

```bash
php bin/lint.php
php bin/test.php
```

## JSON Assessment API

```bash
curl -X POST http://localhost:8085/api/assess \
  -H "Content-Type: application/json" \
  -d '{
    "device_name": "Lab board 01",
    "board_model": "Raspberry Pi 3 Model B",
    "os_image": "Raspberry Pi OS",
    "openssh_version": "OpenSSH 9.x",
    "ssh_mode": "v2-public-key",
    "default_image": false,
    "internet_exposed": false,
    "same_lan_admin": true,
    "controls": ["ssh-protocol-2", "regenerate-host-keys", "hardware-rng", "key-based-auth"]
  }'
```

## Repository Structure

```text
public/              Web entry point and responsive UI assets
src/                 PHP services, repository, security, and support classes
config/              Paper metadata, threat catalog, controls, and scenarios
database/            MySQL migration and seed scripts
docs/                Architecture, security, testing, and extension notes
bin/                 Lint and functional test scripts
```

## Responsible Use

This repository is designed for defensive assessment, secure configuration, and research translation. It does not provide offensive tooling or instructions to attack third-party systems. Use the assessment outputs to strengthen authorized development-board deployments, lab environments, and internal assurance workflows.

## Production Notes

- Place the application behind HTTPS and a trusted reverse proxy.
- Add organization-specific authentication and role-based access control before operational use.
- Store secrets outside source control and rotate them through an approved secret-management process.
- Restrict database and administration access to trusted networks.
- Forward authentication, assessment, and administrative events to a protected logging platform.
- Treat board identifiers, network paths, SSH fingerprints, and hardening evidence as sensitive operational data.
- Review generated recommendations through security governance before using them as formal compliance evidence.

## License

MIT License. See [LICENSE](LICENSE).

