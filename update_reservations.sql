-- Add new columns for time_in and purpose
ALTER TABLE reservations
ADD COLUMN time_in TIME AFTER time_slot,
ADD COLUMN purpose VARCHAR(255) AFTER time_in;

-- Update existing records to convert time_slot to time_in
UPDATE reservations 
SET time_in = SUBSTRING_INDEX(time_slot, '-', 1);

-- Make the new columns required
ALTER TABLE reservations
MODIFY time_in TIME NOT NULL,
MODIFY purpose VARCHAR(255) NOT NULL;

-- Drop the old time_slot column after migration
ALTER TABLE reservations
DROP COLUMN time_slot; 