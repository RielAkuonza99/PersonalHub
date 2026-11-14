-- Portfolio schema. Import via phpMyAdmin into the (empty) InfinityFree database.
-- Engine InnoDB, utf8mb4. Compatible with MySQL 5.7+ / MariaDB 10.x. Safe to re-run (IF NOT EXISTS).
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS admins (
  id TINYINT UNSIGNED NOT NULL PRIMARY KEY,                 -- always 1: single owner account
  username VARCHAR(64) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- One-time login codes e-mailed after the password step. Only an HMAC of the code is stored.
CREATE TABLE IF NOT EXISTS login_otps (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  admin_id TINYINT UNSIGNED NOT NULL,
  code_hash CHAR(64) NOT NULL,
  attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  KEY idx_otp_admin (admin_id, created_at),
  CONSTRAINT fk_otp_admin FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS auth_attempts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  bucket VARCHAR(40) NOT NULL,
  ip_hash CHAR(64) NOT NULL,
  created_at DATETIME NOT NULL,
  KEY idx_attempt (bucket, ip_hash, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS audit_log (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  action VARCHAR(60) NOT NULL,
  detail VARCHAR(500) NOT NULL DEFAULT '',
  ip_hash CHAR(16) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL,
  KEY idx_audit_time (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS site_profile (
  id TINYINT UNSIGNED NOT NULL PRIMARY KEY DEFAULT 1,
  logo VARCHAR(60) NOT NULL DEFAULT '',
  title VARCHAR(120) NOT NULL DEFAULT '',
  name VARCHAR(120) NOT NULL DEFAULT '',
  tagline TEXT NOT NULL,
  photo VARCHAR(1000) NOT NULL DEFAULT '',
  cv_url VARCHAR(1000) NOT NULL DEFAULT '',
  email VARCHAR(190) NOT NULL DEFAULT '',
  about TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- roles (typing effect + Focus areas), interests, goals, bio (label=key, value=text)
CREATE TABLE IF NOT EXISTS profile_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kind ENUM('role','interest','goal','bio') NOT NULL,
  label VARCHAR(190) NOT NULL,
  value VARCHAR(500) NOT NULL DEFAULT '',
  sort_order INT NOT NULL DEFAULT 0,
  status ENUM('draft','published') NOT NULL DEFAULT 'published',
  deleted_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_item (kind, label),
  KEY idx_item_order (kind, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS skill_categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  key_name VARCHAR(60) NOT NULL,
  label VARCHAR(120) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_cat_key (key_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS skills (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  category_id INT UNSIGNED NOT NULL,
  level TINYINT UNSIGNED NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  status ENUM('draft','published') NOT NULL DEFAULT 'published',
  deleted_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_skill_name (name),
  KEY idx_skill_order (sort_order),
  CONSTRAINT fk_skill_cat FOREIGN KEY (category_id) REFERENCES skill_categories(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS projects (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  type ENUM('web','design','app') NOT NULL DEFAULT 'web',   -- media ratio 16:9 / 4:5 / 9:16
  category VARCHAR(60) NOT NULL DEFAULT '',
  summary TEXT NOT NULL,
  detail TEXT NOT NULL,
  tech TEXT NOT NULL,                                        -- JSON array of strings
  image_url VARCHAR(1000) NOT NULL DEFAULT '',
  image_crop VARCHAR(40) NOT NULL DEFAULT 'center',
  video_url VARCHAR(1000) NOT NULL DEFAULT '',
  video_start DECIMAL(10,2) NOT NULL DEFAULT 0,
  video_end DECIMAL(10,2) NOT NULL DEFAULT 0,
  video_crop VARCHAR(40) NOT NULL DEFAULT 'contain',
  github_url VARCHAR(1000) NOT NULL DEFAULT '',
  demo_url VARCHAR(1000) NOT NULL DEFAULT '',
  sort_order INT NOT NULL DEFAULT 0,
  status ENUM('draft','published') NOT NULL DEFAULT 'published',
  deleted_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_project_name (name),
  KEY idx_project_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS journey (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  year VARCHAR(20) NOT NULL,
  title VARCHAR(190) NOT NULL,
  body TEXT NOT NULL,
  body_html MEDIUMTEXT NULL,
  slug VARCHAR(200) NOT NULL DEFAULT '',
  cover_url VARCHAR(1000) NOT NULL DEFAULT '',
  document_url VARCHAR(1000) NOT NULL DEFAULT '',
  link_url VARCHAR(1000) NOT NULL DEFAULT '',
  sort_order INT NOT NULL DEFAULT 0,
  status ENUM('draft','published') NOT NULL DEFAULT 'published',
  deleted_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_journey (year, title),
  UNIQUE KEY uq_journey_slug (slug),
  KEY idx_journey_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS socials (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  provider_id VARCHAR(30) NOT NULL,                          -- icon id: instagram, github, tiktok, x (others: generic icon)
  label VARCHAR(120) NOT NULL,
  url VARCHAR(1000) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  status ENUM('draft','published') NOT NULL DEFAULT 'published',
  deleted_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_social_label (label)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS messages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  body TEXT NOT NULL,
  ip_hash CHAR(64) NOT NULL DEFAULT '',
  status ENUM('new','read','archived') NOT NULL DEFAULT 'new',
  deleted_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_msg_status (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS settings (
  skey VARCHAR(60) NOT NULL PRIMARY KEY,
  svalue TEXT NOT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS media (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  provider ENUM('imgbb','manual') NOT NULL,
  kind ENUM('image','video','document') NOT NULL,
  url VARCHAR(1000) NOT NULL,
  provider_ref VARCHAR(190) NOT NULL DEFAULT '',             -- imgbb id
  delete_url_enc TEXT NULL,                                  -- sensitive: encrypted
  filename VARCHAR(255) NOT NULL DEFAULT '',
  mime_type VARCHAR(100) NOT NULL DEFAULT '',
  size_bytes INT UNSIGNED NOT NULL DEFAULT 0,
  alt_text VARCHAR(255) NOT NULL DEFAULT '',
  deleted_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_media_kind (kind, provider)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Videos posted from the dashboard by pasting a TikTok URL. Playback happens on tiktok.com (Embed Player); only metadata is stored.
CREATE TABLE IF NOT EXISTS tiktok_videos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  video_id VARCHAR(25) NOT NULL,
  url VARCHAR(500) NOT NULL,
  author_name VARCHAR(120) NOT NULL DEFAULT '',
  author_url VARCHAR(300) NOT NULL DEFAULT '',
  title VARCHAR(500) NOT NULL DEFAULT '',
  caption VARCHAR(255) NOT NULL DEFAULT '',
  thumbnail_url VARCHAR(1000) NOT NULL DEFAULT '',
  verified TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  status ENUM('draft','published') NOT NULL DEFAULT 'published',
  deleted_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_tiktok_video (video_id),
  KEY idx_tiktok_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notification_log (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event VARCHAR(40) NOT NULL,
  status ENUM('sent','failed','skipped') NOT NULL,
  detail VARCHAR(300) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS provider_status (
  provider VARCHAR(30) NOT NULL PRIMARY KEY,
  status VARCHAR(20) NOT NULL,
  message VARCHAR(300) NOT NULL DEFAULT '',
  checked_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
