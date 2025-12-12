-- Migration: Add deactivation columns to users table
-- Run this SQL in your database

ALTER TABLE `users` 
ADD COLUMN `deactivated_at` timestamp NULL DEFAULT NULL AFTER `updated_at`,
ADD COLUMN `deactivated_until` timestamp NULL DEFAULT NULL AFTER `deactivated_at`;

-- Add index for better query performance
ALTER TABLE `users` 
ADD INDEX `idx_deactivated_until` (`deactivated_until`);

