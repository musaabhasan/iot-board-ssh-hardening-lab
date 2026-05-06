# Testing

The repository includes lightweight checks that work without external PHP packages.

## Lint

```bash
php bin/lint.php
```

The lint script scans all PHP files under `src`, `public`, `config`, and `bin`.

## Functional Tests

```bash
php bin/test.php
```

The functional test suite verifies:

- Formal paper metadata and DOI.
- Threat, control, and scenario catalog size.
- Maximum control weight.
- All-controls assessment reaches hardened maturity.
- Empty high-risk profile produces critical residual risk.
- Key partial controls reduce the most important SSH and entropy risks.
- JSON summary includes the expected metrics.
- Database migration and seed files contain the required core tables and catalog records.

## HTTP Smoke Test

Start the PHP development server from the repository root:

```bash
php -S 127.0.0.1:8085 -t public
```

Then check:

- `http://127.0.0.1:8085/health`
- `http://127.0.0.1:8085/`
- `http://127.0.0.1:8085/assessment`
- `http://127.0.0.1:8085/controls`
- `http://127.0.0.1:8085/paper`
- `http://127.0.0.1:8085/api/summary`

