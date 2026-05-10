-- Create users table for id_utilisateur references
-- DB: stabilis (from error)

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Test user ID=1
INSERT IGNORE INTO `users` (`id`, `nom`, `email`) VALUES (1, 'Test User', 'test@example.com');

-- Verify
-- SELECT * FROM users LIMIT 5;

