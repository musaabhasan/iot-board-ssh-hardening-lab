CREATE TABLE IF NOT EXISTS paper_references (
  id VARCHAR(64) PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  authors VARCHAR(255) NOT NULL,
  publication_year SMALLINT UNSIGNED NOT NULL,
  venue VARCHAR(255) NOT NULL,
  series_title VARCHAR(255) NOT NULL,
  series_volume VARCHAR(32) NOT NULL,
  pages VARCHAR(32) NOT NULL,
  publisher VARCHAR(120) NOT NULL,
  doi VARCHAR(120) NOT NULL,
  doi_url VARCHAR(255) NOT NULL,
  citation TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS threat_catalog (
  id VARCHAR(64) PRIMARY KEY,
  paper_reference_id VARCHAR(64) NOT NULL,
  name VARCHAR(160) NOT NULL,
  category VARCHAR(120) NOT NULL,
  severity ENUM('Low', 'Medium', 'High', 'Critical') NOT NULL,
  paper_signal TEXT NOT NULL,
  business_impact TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_threat_paper
    FOREIGN KEY (paper_reference_id) REFERENCES paper_references(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS control_catalog (
  id VARCHAR(64) PRIMARY KEY,
  name VARCHAR(180) NOT NULL,
  category VARCHAR(120) NOT NULL,
  weight TINYINT UNSIGNED NOT NULL,
  maturity_level ENUM('Foundation', 'Managed', 'Advanced') NOT NULL,
  implementation TEXT NOT NULL,
  evidence TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_control_category (category),
  INDEX idx_control_weight (weight)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS threat_control_map (
  threat_id VARCHAR(64) NOT NULL,
  control_id VARCHAR(64) NOT NULL,
  PRIMARY KEY (threat_id, control_id),
  CONSTRAINT fk_map_threat
    FOREIGN KEY (threat_id) REFERENCES threat_catalog(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_map_control
    FOREIGN KEY (control_id) REFERENCES control_catalog(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS scenario_catalog (
  id VARCHAR(64) PRIMARY KEY,
  paper_reference_id VARCHAR(64) NOT NULL,
  name VARCHAR(180) NOT NULL,
  risk_level ENUM('Low', 'Medium', 'High', 'Critical') NOT NULL,
  description TEXT NOT NULL,
  recommended_action TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_scenario_paper
    FOREIGN KEY (paper_reference_id) REFERENCES paper_references(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS assessments (
  id CHAR(36) PRIMARY KEY,
  device_name VARCHAR(160) NOT NULL,
  board_model VARCHAR(160) NOT NULL,
  os_image VARCHAR(160) NOT NULL,
  openssh_version VARCHAR(80) NOT NULL,
  ssh_mode VARCHAR(80) NOT NULL,
  score TINYINT UNSIGNED NOT NULL,
  maturity VARCHAR(40) NOT NULL,
  risk_tier VARCHAR(40) NOT NULL,
  selected_controls JSON NOT NULL,
  result_payload JSON NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_assessments_created (created_at),
  INDEX idx_assessments_risk (risk_tier),
  CHECK (JSON_VALID(selected_controls)),
  CHECK (JSON_VALID(result_payload))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_events (
  id CHAR(36) PRIMARY KEY,
  event_name VARCHAR(120) NOT NULL,
  payload JSON NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_audit_event_name (event_name),
  INDEX idx_audit_created (created_at),
  CHECK (JSON_VALID(payload))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

