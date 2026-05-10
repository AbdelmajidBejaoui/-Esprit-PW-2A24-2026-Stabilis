SET FOREIGN_KEY_CHECKS=0;
DROP DATABASE IF EXISTS `stabilis`;
CREATE DATABASE `stabilis` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `stabilis`;

CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL,
  `preference_alimentaire` varchar(50) NOT NULL,
  `date_inscription` datetime NOT NULL,
  `statut_compte` tinyint(1) NOT NULL,
  `face_image` longblob DEFAULT NULL,
  `face_descriptor` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `user` VALUES (5,'Stabilis Admin','stabilisatyourservice@gmail.com','$2y$10$qqYtYRa2kYcCsoNo9spMkub7M4Usx/DiwR2IHYiTeyydaCyR1XxEC','admin','standard','2026-05-07 23:16:17',1,NULL,NULL);

CREATE TABLE `produits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `prix` decimal(10,2) NOT NULL,
  `promo_prix` decimal(10,2) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `coming_soon` tinyint(1) NOT NULL DEFAULT 0,
  `categorie` varchar(50) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `produits` VALUES
(4,'Whey Protein Isolat',49.99,NULL,'Protéine en poudre 100% isolat, absorption rapide, 25g de protéines par portion',50,0,'Protéines','prod_69de4c5fd2b8d6.76571283.jpg','2026-04-12 20:37:15'),
(8,'Barre Protéiné',2.49,1.00,'Barre haute teneur en protéines (20g), faible en sucre',199,0,'Snacks','prod_69de2c30afd8f9.63904406.webp','2026-04-12 20:37:15'),
(9,'Multivitamines Sport',19.99,NULL,'Multivitamines spécial sportifs, 60 gélules',57,0,'Vitamines','prod_69de2c080f4535.95131291.webp','2026-04-12 20:37:15'),
(10,'Wheyy',34.99,18.00,'Acides gras essentiels pour les articulations et le cœur',30,0,'Acides Aminés','prod_69de2bf8596ec1.18767174.jpg','2026-04-12 20:37:15'),
(27,'Granula Bar',8.99,NULL,'',20,0,'Snacks','prod_69f5ff67ea6f90.75548426.jpg','2026-05-02 13:43:03'),
(30,'Creatine',25.99,23.99,'La créatine Stabilis s impose comme un allié incontournable pour les sportifs cherchant à optimiser leurs capacités lors d efforts brefs et intenses.',1,0,'Acides Aminés','prod_69f65dd7bca651.37221756.webp','2026-05-02 20:25:59'),
(31,'Built Bar',19.99,9.99,'La Built Bar est une barre protéinée conçue pour les athlètes et les personnes actives cherchant une collation nutritive et pratique.',0,0,'Snacks','prod_69f660f6b55980.93692714.webp','2026-05-02 20:39:18'),
(38,'Omega 3',48.00,NULL,'Les Omega 3 de Stabilis constituent un complément essentiel pour accompagner le quotidien des sportifs et des personnes actives.',50,0,'Vitamines','prod_69f9fdfc1f32c3.14019970.webp','2026-05-05 14:26:04');

CREATE TABLE `defis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `type` enum('aliment','entrainement','compensation') NOT NULL,
  `objectif` text NOT NULL,
  `recompense` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `defis` VALUES
(1,'Proteines vegetales','aliment','Remplacer les proteines animales par des proteines vegetales pendant 7 jours','150','2026-05-08 23:25:40'),
(2,'Circuit training','entrainement','Completer un circuit training de 30 minutes, 3 fois par semaine','100','2026-05-08 23:25:40'),
(3,'Zero dechet cuisine','compensation','Reduire les dechets de cuisine de 50% en utilisant compost et conservation','120','2026-05-08 23:25:40'),
(4,'5 fruits et legumes','aliment','Manger 5 fruits et legumes locaux et de saison chaque jour pendant 2 semaines','200','2026-05-08 23:25:40'),
(5,'Marche quotidienne','entrainement','Marcher 30 minutes par jour pendant 1 mois.','80','2026-05-08 23:25:40'),
(6,'Reduire le sucre','aliment','Eliminer les sucres ajoutes de son alimentation pendant 10 jours','180','2026-05-08 23:25:40');

CREATE TABLE `participations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_utilisateur` int(11) NOT NULL,
  `id_defi` int(11) NOT NULL,
  `progression` int(11) DEFAULT 0,
  `statut` enum('in_progress','completed','failed') DEFAULT 'in_progress',
  `date_debut` date DEFAULT curdate(),
  `date_fin` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_participation_user_defi` (`id_utilisateur`,`id_defi`),
  KEY `idx_id_utilisateur` (`id_utilisateur`),
  KEY `idx_id_defi` (`id_defi`),
  CONSTRAINT `fk_participations_defi` FOREIGN KEY (`id_defi`) REFERENCES `defis` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_participations_user` FOREIGN KEY (`id_utilisateur`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `participations` VALUES (1,5,1,40,'in_progress','2026-05-09',NULL,'2026-05-08 23:37:06');

CREATE TABLE `participation_proofs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `participation_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `review_state` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_participation_id` (`participation_id`),
  KEY `idx_review_state` (`review_state`),
  CONSTRAINT `fk_participation_proofs_participation` FOREIGN KEY (`participation_id`) REFERENCES `participations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `participation_proofs` VALUES (1,1,'storage/uploads/proofs/proof_1_28ca8d1331d06e1a.jpg','rejected','2026-05-09 01:10:56');

CREATE TABLE `proof_ai_reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `proof_id` int(11) NOT NULL,
  `ai_decision` enum('approved','rejected','uncertain','error') NOT NULL DEFAULT 'uncertain',
  `ai_confidence` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `ai_progress_increment` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `ai_reason` text NOT NULL,
  `ai_raw_response` text DEFAULT NULL,
  `ai_reviewed_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_proof_ai_review` (`proof_id`),
  KEY `idx_ai_decision` (`ai_decision`),
  CONSTRAINT `fk_proof_ai_reviews_proof` FOREIGN KEY (`proof_id`) REFERENCES `participation_proofs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `proof_ai_reviews` VALUES (1,1,'error',0,0,'Analyse IA indisponible.','{"decision":"error","confidence":0,"progress_increment":0,"reason":"Analyse IA indisponible."}','2026-05-09 01:10:59');

CREATE TABLE `commandes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `produit_id` int(11) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `telephone` varchar(30) NOT NULL,
  `adresse` text NOT NULL,
  `code_postal` varchar(20) NOT NULL,
  `ville` varchar(100) NOT NULL,
  `pays` varchar(50) NOT NULL,
  `notes` text DEFAULT NULL,
  `paiement` varchar(50) NOT NULL,
  `statut` varchar(50) NOT NULL DEFAULT 'En attente',
  `quantite` int(11) NOT NULL DEFAULT 1,
  `total` decimal(10,2) NOT NULL,
  `discount_percent` int(11) DEFAULT 0,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `final_total` decimal(10,2) DEFAULT NULL,
  `promo_code` varchar(50) DEFAULT NULL,
  `date_commande` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `commandes_ibfk_1` (`produit_id`),
  CONSTRAINT `commandes_ibfk_1` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `commandes` VALUES
(1,8,'Bejaoui','Abdelmajid','abdelmajidbejaoui351@gmail.com','97533559','Ariana','2083','Cit Ghazelle','France','T','card','Expédiée',1,2.49,0,0.00,NULL,NULL,'2026-04-20 11:47:35'),
(6,8,'Testing','tTesting','bejaoui.abdelmajid@esprit.tn','48566796','RUE IBN rochd 3','2083','Ariana','Tunisie','','card','En attente',1,2.49,0,0.00,NULL,NULL,'2026-04-21 11:37:32'),
(7,8,'Testing','sas','bejaoui.abdelmajid@esprit.tn','48566796','RUE IBN rochd 3','2083','Ariana','Tunisie','HELLO','card','En attente',1,2.49,0,0.00,NULL,NULL,'2026-04-21 13:27:43'),
(8,9,'Testing','sas','bejaoui.abdelmajid@esprit.tn','48566796','RUE IBN rochd 3','2083','Ariana','Tunisie','HELLO','card','En attente',1,19.99,0,0.00,NULL,NULL,'2026-04-21 13:27:43'),
(9,4,'Bejaoui','Abdelmajid','bejaoui.abdelmajid@esprit.tn','46546544','RUE IBN rochd 3','2083','Ariana','Tunisie','t','card','Validée',1,49.99,0,0.00,NULL,NULL,'2026-04-21 13:34:45'),
(10,9,'Bejaoui','Abdelmajid','bejaoui.abdelmajid@esprit.tn','46546544','RUE IBN rochd 3','2083','Ariana','Tunisie','t','card','Validée',1,19.99,0,0.00,NULL,NULL,'2026-04-21 13:34:45'),
(12,8,'Bejaoui','Abdelmajid','abdelmajidbejaoui777@gmail.com','21054023','RUE IBN rochd 3','2083','Ariana','Tunisie','','card','En attente',1,2.49,0,0.00,NULL,NULL,'2026-05-02 12:04:23'),
(50,31,'Bejaoui','Abdelmajid','abdelmajidbejaoui351@gmail.com','32321321','RUE IBN rochd 3','2083','Ariana','Tunisie','Paiement Stripe confirme: cs_test_a1SmIyljDgSM9dXwVcnNFPFCM56bFZNChF3Q1HG74AlDgfemJ9Bg46PUk7','card','En attente',7,69.93,0,0.00,69.93,NULL,'2026-05-05 13:22:14'),
(51,31,'Bejaoui','Abdelmajid','abdelmajidbejaoui351@gmail.com','32321321','RUE IBN rochd 3','2083','Ariana','Tunisie','Paiement Stripe confirme: cs_test_a1350B9khPNvTNwOTStqDjIHMAfoaQeSWVvT3yIxlSxbxrpmFbG3OXns8w','card','En attente',1,9.99,0,0.00,9.99,NULL,'2026-05-05 13:57:17'),
(52,9,'Bejaoui','Abdelmajid','abdelmajidbejaoui351@gmail.com','32321321','RUE IBN rochd 3','2083','Ariana','Tunisie','Paiement Stripe confirme: cs_test_a1CAqxPFrEJflKbhhCxvfh5w33X6OwgLNhOeqYKMWT3wsd6gwf5MSl4rDl','card','En attente',1,19.99,0,0.00,19.99,NULL,'2026-05-05 14:23:39'),
(53,8,'Bejaoui','Abdelmajid','abdelmajidbejaoui351@gmail.com','32321321','RUE IBN rochd 3','2083','Ariana','Tunisie','Paiement Stripe confirme: cs_test_a145yryiy18UNlDcG1SxReMrMQkaHt9RcDs1vpqASUyFwDYlESS143ulJo','card','En attente',1,1.00,0,0.00,1.00,NULL,'2026-05-08 19:22:51');

CREATE TABLE `packs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(120) NOT NULL,
  `description` text DEFAULT NULL,
  `prix` decimal(10,2) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `pack_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pack_id` int(11) NOT NULL,
  `produit_id` int(11) NOT NULL,
  `quantite` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `pack_id` (`pack_id`),
  CONSTRAINT `pack_items_ibfk_1` FOREIGN KEY (`pack_id`) REFERENCES `packs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(128) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `token` (`token`),
  CONSTRAINT `fk_passwordresets_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `email_verifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(128) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `token` (`token`),
  CONSTRAINT `fk_emailver_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `two_factor_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `code` varchar(16) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `code` (`code`),
  CONSTRAINT `fk_twofactor_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
INSERT INTO `two_factor_codes` VALUES (5,5,'607147','2026-05-09 02:59:59','2026-05-09 01:54:59');

CREATE TABLE `promo_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `discount` int(11) DEFAULT 15,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `usage_limit` int(11) NOT NULL DEFAULT 1,
  `times_used` int(11) NOT NULL DEFAULT 0,
  `expires_at` datetime NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `used` tinyint(1) DEFAULT 0,
  `used_date` datetime DEFAULT NULL,
  `used_order_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  UNIQUE KEY `idx_code_email_product` (`code`,`customer_email`,`product_id`),
  KEY `idx_code` (`code`),
  KEY `idx_email` (`customer_email`),
  KEY `idx_product` (`product_id`),
  KEY `idx_expires` (`expires_at`),
  KEY `idx_used` (`used`),
  CONSTRAINT `promo_codes_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `produits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `promo_codes` VALUES
(2,'TEST-b4867',4,'bejaoui.abdelmajid@esprit.tn',20,1,1,0,'2026-05-05 15:34:38','2026-05-02 14:34:38',1,'2026-05-02 14:34:38',12345),
(3,'GRANUL-E5151',27,'abdelmajidbejaoui351@gmail.com',25,1,1,0,'2026-05-05 15:43:04','2026-05-02 14:43:04',0,NULL,NULL),
(4,'GRANUL-BD821',27,'abdelmajidbejaoui777@gmail.com',25,1,1,0,'2026-05-05 15:43:08','2026-05-02 14:43:08',0,NULL,NULL),
(5,'GRANUL-1A22B',27,'abdelmajidbejaoui789@gmail.com',25,1,1,0,'2026-05-05 15:43:11','2026-05-02 14:43:11',1,'2026-05-02 15:03:34',17),
(6,'GRANUL-87EF8',27,'bejaoui.abdelmajid@esprit.tn',15,1,1,0,'2026-05-05 15:43:14','2026-05-02 14:43:14',0,NULL,NULL),
(7,'TEST-5c379',4,'test@test.com',20,1,1,0,'2026-05-05 15:47:33','2026-05-02 14:47:33',0,NULL,NULL),
(10,'CREATI-14F5F',30,'abdelmajidbejaoui789@gmail.com',20,1,1,0,'2026-05-05 22:26:02','2026-05-02 21:26:02',0,NULL,NULL),
(11,'BUILT -59115',31,'abdelmajidbejaoui351@gmail.com',25,1,1,0,'2026-05-05 22:39:20','2026-05-02 21:39:20',0,NULL,NULL),
(12,'BUILT -B1501',31,'abdelmajidbejaoui777@gmail.com',25,1,1,0,'2026-05-05 22:39:32','2026-05-02 21:39:32',0,NULL,NULL),
(13,'BUILT -6005F',31,'abdelmajidbejaoui789@gmail.com',15,1,1,0,'2026-05-05 22:39:40','2026-05-02 21:39:40',1,'2026-05-02 22:00:18',24),
(14,'BUILT -387A5',31,'bejaoui.abdelmajid@esprit.tn',15,1,1,0,'2026-05-05 22:39:45','2026-05-02 21:39:45',0,NULL,NULL),
(17,'STABILIS-15-23OOP',NULL,NULL,15,1,0,1,'2026-05-10 13:17:26','2026-05-03 12:17:26',0,'2026-05-03 12:18:35',27),
(18,'STABILIS-15-8JZ2Y',NULL,NULL,15,1,1,1,'2026-05-10 14:54:36','2026-05-03 13:54:36',1,'2026-05-03 13:55:05',33),
(22,'STABILIS-15-7AQQF',NULL,NULL,15,1,10,1,'2026-06-04 01:22:20','2026-05-05 00:22:20',0,'2026-05-05 00:22:57',42),
(28,'OMEGA -1A56D',38,'abdelmajidbejaoui351@gmail.com',20,1,1,0,'2026-05-08 16:26:07','2026-05-05 15:26:07',0,NULL,NULL),
(29,'OMEGA -7E9DC',38,'bejaoui.abdelmajid@esprit.tn',15,1,1,0,'2026-05-08 16:26:16','2026-05-05 15:26:16',0,NULL,NULL),
(31,'STABILIS-15-FMJXJ',NULL,NULL,15,1,20,0,'2026-05-12 16:29:12','2026-05-05 15:29:12',0,NULL,NULL);

CREATE TABLE `promo_code_usages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `promo_code_id` int(11) NOT NULL,
  `customer_email` varchar(255) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `used_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
INSERT INTO `promo_code_usages` VALUES (1,18,'abdelmajidbejaoui456@gmail.com',33,'2026-05-03 13:55:05'),(2,22,'abdelmajidbejaoui351@gmail.com',42,'2026-05-05 00:22:57'),(3,23,'abdelmajidbejaoui351@gmail.com',48,'2026-05-05 01:06:50');

CREATE TABLE `site_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titre` varchar(160) NOT NULL,
  `message` text NOT NULL,
  `code_promo` varchar(80) DEFAULT NULL,
  `lien` varchar(255) DEFAULT NULL,
  `bg_color` varchar(20) NOT NULL DEFAULT '#F9F3E6',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
INSERT INTO `site_events` VALUES (4,'CODE PROMO','STABILIS-15-FMJXJ','STABILIS-15-FMJXJ','index.php','#FEEAE6',1,'2026-05-05 15:29:38');

CREATE TABLE `wishlist_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `produit_id` int(11) NOT NULL,
  `nom` varchar(120) NOT NULL,
  `email` varchar(255) NOT NULL,
  `notified` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `notified_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_product_email` (`produit_id`,`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
INSERT INTO `wishlist_notifications` VALUES (1,32,'Abdelmajid','abdelmajidbejaoui351@gmail.com',0,'2026-05-04 11:51:00',NULL),(2,31,'Abdelmajid','abdelmajidbejaoui351@gmail.com',1,'2026-05-04 12:32:55','2026-05-04 12:33:12');

SET FOREIGN_KEY_CHECKS=1;
