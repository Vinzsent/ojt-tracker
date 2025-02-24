-- Add status column to users table if it doesn't exist
ALTER TABLE users ADD COLUMN IF NOT EXISTS status VARCHAR(20) DEFAULT 'active';

-- Update existing teacher accounts to have pending status
UPDATE users SET status = 'pending' WHERE role = 'Teacher' AND status IS NULL;
