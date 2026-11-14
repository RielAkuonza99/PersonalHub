-- Upgrade for installs created from the older schema (TOTP login, Streamable/MediaFire). Idempotent: safe to re-run.
-- 1) E-mail OTP login  2) TikTok videos  3) remove Streamable/MediaFire from the media library.
SET NAMES utf8mb4;

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

-- Authenticator recovery codes are no longer used. The old totp_* columns on `admins` are left in place (unused, harmless).
DROP TABLE IF EXISTS recovery_codes;

-- Rows from the removed providers stay as plain manual links; the public links themselves keep working.
UPDATE media SET provider='manual' WHERE provider IN ('streamable','mediafire');
ALTER TABLE media MODIFY provider ENUM('imgbb','manual') NOT NULL;
DELETE FROM provider_status WHERE provider IN ('streamable','mediafire');
