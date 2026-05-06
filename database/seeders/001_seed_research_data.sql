INSERT INTO paper_references (
  id,
  title,
  authors,
  publication_year,
  venue,
  series_title,
  series_volume,
  pages,
  publisher,
  doi,
  doi_url,
  citation
) VALUES (
  'alfandi-hasan-balbahaith-2019',
  'Assessment and Hardening of IoT Development Boards',
  'Omar Alfandi; Musaab Hasan; Zayed Balbahaith',
  2019,
  'Wired/Wireless Internet Communications, WWIC 2019',
  'Lecture Notes in Computer Science',
  '11618',
  '27-39',
  'Springer, Cham',
  '10.1007/978-3-030-30523-9_3',
  'https://doi.org/10.1007/978-3-030-30523-9_3',
  'Alfandi, O., Hasan, M., & Balbahaith, Z. (2019). Assessment and Hardening of IoT Development Boards. In M. Di Felice, E. Natalizio, R. Bruno, & A. Kassler (Eds.), Wired/Wireless Internet Communications, WWIC 2019, Lecture Notes in Computer Science, vol. 11618, pp. 27-39. Springer, Cham. https://doi.org/10.1007/978-3-030-30523-9_3'
)
ON DUPLICATE KEY UPDATE title = VALUES(title);

INSERT INTO threat_catalog (id, paper_reference_id, name, category, severity, paper_signal, business_impact) VALUES
('ssh-v1-downgrade', 'alfandi-hasan-balbahaith-2019', 'SSH protocol downgrade', 'Remote access', 'Critical', 'Allowing SSH V1 and V2 together can enable downgrade behavior during a man-in-the-middle scenario.', 'Administrative credentials can be exposed, creating a path to device takeover and lateral movement.'),
('weak-first-boot-keys', 'alfandi-hasan-balbahaith-2019', 'Weak first-boot SSH host keys', 'Cryptographic assurance', 'Critical', 'Predictable key generation can occur when cloned operating-system images boot on similar board hardware with limited entropy.', 'A device may appear encrypted while still being vulnerable to impersonation or session interception.'),
('arp-mitm', 'alfandi-hasan-balbahaith-2019', 'ARP poisoning and local man-in-the-middle', 'Network trust', 'High', 'The experiment uses ARP poisoning to place an intermediary host between the SSH client and the board.', 'Local network attackers can observe, manipulate, or downgrade administrative sessions when controls are weak.'),
('outdated-openssh', 'alfandi-hasan-balbahaith-2019', 'Outdated OpenSSH exposure', 'Patch management', 'High', 'The paper reviews vulnerability categories affecting OpenSSH versions, including denial of service, bypass, information disclosure, and privilege gain.', 'Unpatched SSH services increase the probability of exploit chaining after discovery.'),
('default-image-attack-surface', 'alfandi-hasan-balbahaith-2019', 'Default image attack surface', 'System minimization', 'High', 'Board images may include additional packages and services that are not required for focused IoT deployments.', 'Unnecessary packages and services create avoidable entry points and maintenance obligations.'),
('password-admin', 'alfandi-hasan-balbahaith-2019', 'Password-based administrative access', 'Identity and access', 'High', 'The paper compares password-based access with public-key authentication during SSH V2 scenarios.', 'Weak or reused passwords increase brute-force, phishing, and credential replay risk.'),
('unverified-public-key', 'alfandi-hasan-balbahaith-2019', 'Unverified public key trust', 'Trust validation', 'Medium', 'The paper recommends using a certificate authority or signed public keys to confirm key authenticity.', 'Administrators may trust an attacker-controlled key during onboarding or replacement.'),
('open-remote-services', 'alfandi-hasan-balbahaith-2019', 'Unnecessary remote services', 'Exposure management', 'Medium', 'Remote services and preinstalled tools can become risk multipliers on board images.', 'Attackers can pivot from exposed management services to broader device compromise.'),
('post-compromise-privilege-gain', 'alfandi-hasan-balbahaith-2019', 'Privilege escalation after SSH compromise', 'Host hardening', 'High', 'Even a non-root compromise can lead to privilege escalation when the host is weakly maintained.', 'A limited account can become a full device takeover when local hardening is incomplete.')
ON DUPLICATE KEY UPDATE name = VALUES(name), severity = VALUES(severity);

INSERT INTO control_catalog (id, name, category, weight, maturity_level, implementation, evidence) VALUES
('ssh-protocol-2', 'Enforce SSH protocol version 2', 'SSH configuration', 12, 'Foundation', 'Validate the effective SSH daemon configuration and ensure SSH V1 cannot be negotiated.', 'Configuration review, service restart record, and scanner output confirming that only SSH V2 is accepted.'),
('regenerate-host-keys', 'Regenerate board host keys before network use', 'Cryptographic assurance', 11, 'Foundation', 'Remove first-boot host keys from cloned images and regenerate keys on the target board after entropy is ready.', 'Provisioning record showing host-key removal, regeneration command, fingerprint capture, and approval.'),
('hardware-rng', 'Enable a hardware-backed entropy source', 'Cryptographic assurance', 10, 'Managed', 'Load the board-supported random number generator module, enable the entropy service, and verify entropy availability before key generation.', 'Boot log, entropy service status, and key generation runbook.'),
('openssh-patching', 'Maintain OpenSSH patch currency', 'Patch management', 9, 'Managed', 'Track OpenSSH package versions, vendor advisories, and approved maintenance windows for board images.', 'Version inventory, update policy, vulnerability review, and remediation record.'),
('signed-keys', 'Use signed keys or trusted key authority', 'Trust validation', 8, 'Advanced', 'Use a trusted authority or signed public keys to validate administrative identities and host trust.', 'Key authority policy, signed-key record, host certificate, or trusted fingerprint register.'),
('key-based-auth', 'Prefer key-based administration', 'Identity and access', 7, 'Managed', 'Use strong administrative key pairs, protect private keys, and disable password authentication where operationally feasible.', 'Authorized key inventory, private-key protection standard, and password-auth exception register.'),
('ssh-config-audit', 'Audit SSH daemon configuration', 'Assurance', 7, 'Foundation', 'Review daemon settings for protocol, authentication, ciphers, root login, logging, and idle timeout requirements.', 'Audited sshd_config snapshot, reviewer sign-off, and exception notes.'),
('mitm-monitoring', 'Monitor for local man-in-the-middle indicators', 'Network trust', 7, 'Advanced', 'Detect ARP anomalies, duplicate addresses, unexpected gateway changes, and new management-path intermediaries.', 'Network monitoring rule, sample alert, and incident triage procedure.'),
('network-segmentation', 'Segment development-board management traffic', 'Network trust', 7, 'Managed', 'Place board administration on a controlled management segment with restricted peer access.', 'Network diagram, access control list, and test connection results.'),
('admin-network', 'Restrict SSH to approved administration sources', 'Exposure management', 7, 'Managed', 'Limit SSH access to known administration hosts, bastions, VPNs, or management subnets.', 'Firewall policy, allowed source list, and verification scan.'),
('package-minimization', 'Minimize default packages', 'System minimization', 6, 'Managed', 'Remove games, desktop utilities, unused interpreters, and packages not required by the target use case.', 'Package baseline, removal log, and approved image manifest.'),
('service-baseline', 'Maintain a service exposure baseline', 'Exposure management', 6, 'Foundation', 'Define expected open ports and running services for each board role and scan against the baseline.', 'Nmap scan result, running-service list, and exception register.'),
('firewall-policy', 'Apply host firewall rules', 'Host hardening', 6, 'Managed', 'Deny unsolicited inbound traffic by default and explicitly allow required management flows.', 'Host firewall configuration and external scan confirmation.'),
('image-hardening', 'Harden and version board images', 'System minimization', 6, 'Advanced', 'Build reusable images with approved packages, key regeneration hooks, logging, and secure defaults.', 'Image build file, checksum, release note, and rollback record.'),
('version-baseline', 'Inventory board OS and OpenSSH versions', 'Asset intelligence', 5, 'Foundation', 'Capture board model, operating-system image, kernel version, OpenSSH version, and management endpoint.', 'Inventory export and change history.'),
('vulnerability-tracking', 'Track vulnerability exposure by version', 'Patch management', 5, 'Managed', 'Map detected versions to advisories, CVEs, and remediation status for each board fleet.', 'Vulnerability register, risk owner, and closure evidence.'),
('key-inventory', 'Maintain host and administrator key inventory', 'Cryptographic assurance', 5, 'Managed', 'Record host fingerprints, administrator keys, rotation dates, revocation status, and authorized devices.', 'Key register and periodic reconciliation report.'),
('password-auth-policy', 'Control password-authentication exceptions', 'Identity and access', 4, 'Managed', 'Require strong approval, expiry, monitoring, and compensating controls for password-based SSH access.', 'Exception ticket, expiry date, and monitoring rule.'),
('least-privilege', 'Apply least privilege for board accounts', 'Host hardening', 4, 'Managed', 'Separate operator, developer, and administrator roles; limit sudo; and remove shared accounts.', 'Account review, sudoers policy, and access recertification.'),
('host-hardening', 'Apply host-level hardening controls', 'Host hardening', 4, 'Managed', 'Disable unused accounts, enforce logging, remove insecure defaults, and validate secure boot-time configuration.', 'Hardening checklist, configuration snapshot, and exception register.'),
('audit-logging', 'Centralize administrative audit logging', 'Assurance', 3, 'Advanced', 'Forward authentication logs, command audit events, and configuration changes to a protected log destination.', 'Log forwarding status, sample event, retention policy, and review record.'),
('change-control', 'Control SSH and image changes', 'Assurance', 3, 'Managed', 'Require review and traceability for SSH policy, key authority, image, and firewall changes.', 'Approved change record and post-change validation.'),
('exposure-review', 'Review exposed interfaces before deployment', 'Exposure management', 3, 'Foundation', 'Run a pre-deployment review for open ports, discovery signals, default credentials, and remote access paths.', 'Pre-deployment checklist and scan evidence.')
ON DUPLICATE KEY UPDATE name = VALUES(name), weight = VALUES(weight);

INSERT INTO threat_control_map (threat_id, control_id) VALUES
('ssh-v1-downgrade', 'ssh-protocol-2'),
('ssh-v1-downgrade', 'ssh-config-audit'),
('ssh-v1-downgrade', 'mitm-monitoring'),
('weak-first-boot-keys', 'regenerate-host-keys'),
('weak-first-boot-keys', 'hardware-rng'),
('weak-first-boot-keys', 'key-inventory'),
('arp-mitm', 'mitm-monitoring'),
('arp-mitm', 'network-segmentation'),
('arp-mitm', 'admin-network'),
('outdated-openssh', 'openssh-patching'),
('outdated-openssh', 'version-baseline'),
('outdated-openssh', 'vulnerability-tracking'),
('default-image-attack-surface', 'package-minimization'),
('default-image-attack-surface', 'service-baseline'),
('default-image-attack-surface', 'image-hardening'),
('password-admin', 'key-based-auth'),
('password-admin', 'password-auth-policy'),
('password-admin', 'admin-network'),
('unverified-public-key', 'signed-keys'),
('unverified-public-key', 'key-inventory'),
('unverified-public-key', 'change-control'),
('open-remote-services', 'service-baseline'),
('open-remote-services', 'firewall-policy'),
('open-remote-services', 'exposure-review'),
('post-compromise-privilege-gain', 'least-privilege'),
('post-compromise-privilege-gain', 'host-hardening'),
('post-compromise-privilege-gain', 'audit-logging')
ON DUPLICATE KEY UPDATE threat_id = VALUES(threat_id);

INSERT INTO scenario_catalog (id, paper_reference_id, name, risk_level, description, recommended_action) VALUES
('legacy-v1-v2', 'alfandi-hasan-balbahaith-2019', 'Legacy SSH V1 and V2 enabled', 'Critical', 'The board accepts both protocol generations, creating downgrade exposure during hostile local-network conditions.', 'Disable SSH V1 immediately and validate the effective daemon configuration through scanning.'),
('v2-password', 'alfandi-hasan-balbahaith-2019', 'SSH V2 with password administration', 'High', 'Protocol downgrade is reduced, but credential guessing and password reuse remain material concerns.', 'Move toward protected key-based administration and restrict source networks.'),
('v2-public-key', 'alfandi-hasan-balbahaith-2019', 'SSH V2 with public-key administration', 'Medium', 'Authentication strength improves, but weak first-boot host keys and unverified public-key trust can still undermine assurance.', 'Regenerate host keys with sufficient entropy and maintain key inventory.'),
('hardened-signed-keys', 'alfandi-hasan-balbahaith-2019', 'Hardened SSH with regenerated and signed keys', 'Low', 'The board uses SSH V2, regenerated host keys, trusted public-key validation, controlled exposure, and monitored administration paths.', 'Maintain patch cadence, continuous evidence, and periodic key review.')
ON DUPLICATE KEY UPDATE name = VALUES(name), risk_level = VALUES(risk_level);

