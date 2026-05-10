-- Test Participation Data - Run in phpMyAdmin (Database: stabilis → SQL tab → Paste & Go)
-- This inserts sample data matching form + challenge → proves back-office display works

-- 1. Clean any test data
DELETE FROM participations WHERE id_utilisateur = 1;

-- 2. Insert test row (matches frontend form: user=1, challenge=1, default values)
INSERT INTO participations (id_utilisateur, id_defi
