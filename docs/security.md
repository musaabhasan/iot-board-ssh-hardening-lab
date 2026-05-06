# Security Notes

The application is intentionally small, but it includes controls expected in a professional PHP deployment.

## Application Controls

- Security headers are set for content type protection, clickjacking protection, referrer handling, permissions policy, and content security policy.
- Form submissions use CSRF tokens.
- Session cookies use `HttpOnly`, `SameSite=Lax`, and secure cookies when HTTPS is detected.
- Input is normalized before scoring and before persistence.
- Database access uses PDO with prepared statements and disabled emulated prepares.
- Assessment payloads are persisted as JSON for traceability when MySQL is configured.
- Database connection failures fall back to read-only catalog behavior instead of exposing diagnostics.

## Operational Controls

- Require authentication and role-based access before storing real board data.
- Keep `.env` files and secrets outside source control.
- Restrict database access to the application network.
- Forward audit events and web-server logs to protected storage.
- Monitor for unexpected assessment creation, repeated API calls, and unusual source addresses.
- Place the application behind HTTPS and a managed reverse proxy.

## Board-Hardening Controls Reflected in the Platform

- Disable SSH V1 negotiation.
- Regenerate first-boot host keys after sufficient entropy is available.
- Use a hardware-backed entropy source where supported.
- Maintain OpenSSH and operating-system patch currency.
- Restrict SSH to approved management networks and hosts.
- Prefer protected public-key authentication over password administration.
- Validate public-key trust through signed keys or a trusted key authority.
- Remove unnecessary packages and remote services from board images.
- Maintain service baselines and periodic exposure scans.

