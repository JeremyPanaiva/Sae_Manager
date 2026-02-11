-- Migration: Add session_token to users table
-- Run this SQL against your database to add the session_token column used to enforce
-- a single active session per user.

ALTER TABLE users
  ADD COLUMN session_token VARCHAR(128) DEFAULT NULL;

-- Optional: create an index to speed lookups if needed
CREATE INDEX idx_users_session_token ON users(session_token);

