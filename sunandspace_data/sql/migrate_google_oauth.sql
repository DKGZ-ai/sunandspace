-- Google OAuth — run once on existing databases (phpMyAdmin → sunandspace)
USE sunandspace;

ALTER TABLE users
  ADD COLUMN google_id VARCHAR(255) NULL DEFAULT NULL AFTER password_hash,
  ADD UNIQUE KEY uq_users_google_role (google_id, role);
