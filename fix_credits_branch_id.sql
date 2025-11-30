-- Make branch_id nullable in credits table
ALTER TABLE credits MODIFY COLUMN branch_id BIGINT UNSIGNED NULL;

-- Update any existing credits with NULL branch_id to use a default branch
UPDATE credits 
SET branch_id = (SELECT id FROM branches WHERE is_active = 1 LIMIT 1) 
WHERE branch_id IS NULL;