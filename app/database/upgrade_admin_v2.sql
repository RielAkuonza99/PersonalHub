-- Idempotent upgrade for direct project/journey uploads and public traffic.
SET @db_name = DATABASE();

SET @sql = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='projects' AND COLUMN_NAME='image_crop'), 'SELECT 1', "ALTER TABLE projects ADD COLUMN image_crop VARCHAR(40) NOT NULL DEFAULT 'center'"); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='projects' AND COLUMN_NAME='video_start'), 'SELECT 1', 'ALTER TABLE projects ADD COLUMN video_start DECIMAL(10,2) NOT NULL DEFAULT 0'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='projects' AND COLUMN_NAME='video_end'), 'SELECT 1', 'ALTER TABLE projects ADD COLUMN video_end DECIMAL(10,2) NOT NULL DEFAULT 0'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='projects' AND COLUMN_NAME='video_crop'), 'SELECT 1', "ALTER TABLE projects ADD COLUMN video_crop VARCHAR(40) NOT NULL DEFAULT 'contain'"); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='journey' AND COLUMN_NAME='slug'), 'SELECT 1', "ALTER TABLE journey ADD COLUMN slug VARCHAR(200) NOT NULL DEFAULT ''"); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='journey' AND COLUMN_NAME='body_html'), 'SELECT 1', 'ALTER TABLE journey ADD COLUMN body_html MEDIUMTEXT NULL'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='journey' AND COLUMN_NAME='cover_url'), 'SELECT 1', "ALTER TABLE journey ADD COLUMN cover_url VARCHAR(1000) NOT NULL DEFAULT ''"); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='journey' AND COLUMN_NAME='document_url'), 'SELECT 1', "ALTER TABLE journey ADD COLUMN document_url VARCHAR(1000) NOT NULL DEFAULT ''"); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='journey' AND COLUMN_NAME='link_url'), 'SELECT 1', "ALTER TABLE journey ADD COLUMN link_url VARCHAR(1000) NOT NULL DEFAULT ''"); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE journey SET slug=CONCAT(year,'-',id) WHERE slug='' OR slug IS NULL;
SET @sql = IF(EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='journey' AND INDEX_NAME='uq_journey_slug'), 'SELECT 1', 'ALTER TABLE journey ADD UNIQUE INDEX uq_journey_slug (slug)'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS site_visits (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  path VARCHAR(120) NOT NULL,
  ip_hash CHAR(64) NOT NULL,
  user_agent VARCHAR(300) NOT NULL DEFAULT '',
  visited_at DATETIME NOT NULL,
  KEY idx_visit_time (visited_at),
  KEY idx_visit_path (path, visited_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
