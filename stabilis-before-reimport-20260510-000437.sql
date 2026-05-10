-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: stabilis
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `stabilis`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `stabilis` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `stabilis`;

--
-- Table structure for table `aliments`
--

DROP TABLE IF EXISTS `aliments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `aliments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `calories` int(11) DEFAULT 0,
  `proteines` decimal(5,2) DEFAULT 0.00,
  `glucides` decimal(5,2) DEFAULT 0.00,
  `lipides` decimal(5,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `aliments`
--

LOCK TABLES `aliments` WRITE;
/*!40000 ALTER TABLE `aliments` DISABLE KEYS */;
INSERT INTO `aliments` VALUES (1,'Poulet','Blanc de poulet grillé',165,31.00,0.00,3.60,'2026-04-14 14:06:19','2026-04-14 14:06:19'),(2,'Riz','Riz blanc cuit',130,2.70,28.00,0.30,'2026-04-14 14:06:19','2026-04-14 14:06:19'),(3,'Brocoli','Brocoli vapeur',55,3.70,11.20,0.60,'2026-04-14 14:06:19','2026-04-14 14:06:19');
/*!40000 ALTER TABLE `aliments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `defis`
--

DROP TABLE IF EXISTS `defis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `defis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) NOT NULL,
  `type` varchar(50) NOT NULL,
  `objectif` text NOT NULL,
  `recompense` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `defis`
--

LOCK TABLES `defis` WRITE;
/*!40000 ALTER TABLE `defis` DISABLE KEYS */;
INSERT INTO `defis` VALUES (1,'Proteines vegetales','aliment','5 repas vegetariens par semaine','100 points'),(2,'Reduction des dechets','compensation','Zero dechet alimentaire','150 points'),(3,'Sport durable','entrainement','30 minutes dexercice','80 points'),(4,'Repas Anti-Gaspillage','aliment','Documenter 5 repas consécutifs où vous avez terminé tout ce qui se trouvait dans votre assiette, en partageant une photo sur la plateforme.','150 points'),(5,'Zéro Déchet au Café','aliment','Utiliser votre propre tasse réutilisable pour 10 achats de boissons au café du campus, en demandant à ce qu\'elle soit remplie sans gobelet jetable.','120 points'),(6,'Panier local de saison','aliment','Composer un panier avec au moins trois produits locaux et de saison, puis envoyer une preuve visuelle claire.','120 points'),(7,'Marche active responsable','entrainement','Realiser une marche active de 30 minutes et envoyer une preuve de l activite.','90 points');
/*!40000 ALTER TABLE `defis` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `depenses_energetiques`
--

DROP TABLE IF EXISTS `depenses_energetiques`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `depenses_energetiques` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entrainement_id` int(11) NOT NULL,
  `calories_brulees` decimal(8,2) NOT NULL,
  `frequence_cardiaque_moy` int(11) DEFAULT NULL,
  `intensite` enum('faible','moderee','elevee','maximale') NOT NULL DEFAULT 'moderee',
  `remarques` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_entrainement` (`entrainement_id`),
  CONSTRAINT `fk_entrainement` FOREIGN KEY (`entrainement_id`) REFERENCES `entrainements` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `depenses_energetiques`
--

LOCK TABLES `depenses_energetiques` WRITE;
/*!40000 ALTER TABLE `depenses_energetiques` DISABLE KEYS */;
INSERT INTO `depenses_energetiques` VALUES (1,1,320.50,135,'moderee','Bonne séance, météo agréable','2026-04-14 14:05:09'),(2,1,310.00,130,'faible','Légèrement fatigué','2026-04-14 14:05:09'),(3,2,450.75,150,'elevee','Progression notable sur le développé couché','2026-04-14 14:05:09'),(4,3,150.25,95,'faible','Très relaxant','2026-04-14 14:05:09'),(5,4,520.00,175,'maximale','Effort maximal, excellente séance','2026-04-14 14:05:09'),(6,4,510.50,170,'maximale','Second essai HIIT','2026-04-14 14:05:09'),(7,5,400.00,145,'elevee','Spinning de 50 min très efficace','2026-04-14 14:05:09');
/*!40000 ALTER TABLE `depenses_energetiques` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_verifications`
--

DROP TABLE IF EXISTS `email_verifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `email_verifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `user_id` (`user_id`),
  KEY `idx_token` (`token`),
  CONSTRAINT `email_verifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_verifications`
--

LOCK TABLES `email_verifications` WRITE;
/*!40000 ALTER TABLE `email_verifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `email_verifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `entrainements`
--

DROP TABLE IF EXISTS `entrainements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `entrainements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `duree` int(11) NOT NULL COMMENT 'durée en minutes',
  `type_sport` varchar(50) NOT NULL,
  `niveau` enum('debutant','intermediaire','avance') NOT NULL DEFAULT 'debutant',
  `date_entrainement` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `entrainements`
--

LOCK TABLES `entrainements` WRITE;
/*!40000 ALTER TABLE `entrainements` DISABLE KEYS */;
INSERT INTO `entrainements` VALUES (1,'Course matinale','Jogging léger en plein air',45,'Course à pied','debutant','2026-04-01','2026-04-14 14:05:09'),(2,'Musculation haut du corps','Exercices pour pectoraux, épaules et bras',60,'Musculation','intermediaire','2026-04-03','2026-04-14 14:05:09'),(3,'Yoga relaxation','Séance de yoga pour débutants',30,'Yoga','debutant','2026-04-05','2026-04-14 14:05:09'),(4,'HIIT intensif','Intervalle haute intensité 20/40',35,'HIIT','avance','2026-04-07','2026-04-14 14:05:09'),(5,'Vélo spinning','Cours de spinning en salle',50,'Cyclisme','intermediaire','2026-04-09','2026-04-14 14:05:09');
/*!40000 ALTER TABLE `entrainements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ingredients`
--

DROP TABLE IF EXISTS `ingredients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ingredients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recette_id` int(11) NOT NULL,
  `aliment_id` int(11) NOT NULL,
  `quantite` decimal(5,2) NOT NULL,
  `unite` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `recette_id` (`recette_id`),
  KEY `aliment_id` (`aliment_id`),
  CONSTRAINT `ingredients_ibfk_1` FOREIGN KEY (`recette_id`) REFERENCES `recettes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ingredients_ibfk_2` FOREIGN KEY (`aliment_id`) REFERENCES `aliments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ingredients`
--

LOCK TABLES `ingredients` WRITE;
/*!40000 ALTER TABLE `ingredients` DISABLE KEYS */;
INSERT INTO `ingredients` VALUES (1,1,1,150.00,'g'),(2,1,2,200.00,'g'),(3,1,3,100.00,'g');
/*!40000 ALTER TABLE `ingredients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pack_items`
--

DROP TABLE IF EXISTS `pack_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pack_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pack_id` int(11) NOT NULL,
  `produit_id` int(11) NOT NULL,
  `quantite` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `pack_id` (`pack_id`),
  CONSTRAINT `pack_items_ibfk_1` FOREIGN KEY (`pack_id`) REFERENCES `packs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pack_items`
--

LOCK TABLES `pack_items` WRITE;
/*!40000 ALTER TABLE `pack_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `pack_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `packs`
--

DROP TABLE IF EXISTS `packs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `packs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(120) NOT NULL,
  `description` text DEFAULT NULL,
  `prix` decimal(10,2) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `packs`
--

LOCK TABLES `packs` WRITE;
/*!40000 ALTER TABLE `packs` DISABLE KEYS */;
/*!40000 ALTER TABLE `packs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `participation_proofs`
--

DROP TABLE IF EXISTS `participation_proofs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `participation_proofs`
--

LOCK TABLES `participation_proofs` WRITE;
/*!40000 ALTER TABLE `participation_proofs` DISABLE KEYS */;
INSERT INTO `participation_proofs` VALUES (2,1,'uploads/proofs/proof_1_2f9062e9eb9b52fb.png','pending','2026-05-05 14:22:47'),(3,3,'uploads/proofs/proof_3_9bd823781cb17988.jpg','pending','2026-05-05 14:28:33'),(4,3,'uploads/proofs/proof_3_b1f389ec9587acd3.png','rejected','2026-05-05 14:30:14'),(5,1,'uploads/proofs/proof_1_e02b1833d7d80c21.png','pending','2026-05-05 15:09:11');
/*!40000 ALTER TABLE `participation_proofs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `participations`
--

DROP TABLE IF EXISTS `participations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `participations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_utilisateur` int(11) NOT NULL,
  `id_defi` int(11) NOT NULL,
  `progression` int(11) NOT NULL DEFAULT 0,
  `statut` enum('in_progress','completed','failed') NOT NULL DEFAULT 'in_progress',
  `date_debut` date NOT NULL,
  `date_fin` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_participation_user_defi` (`id_utilisateur`,`id_defi`),
  KEY `idx_id_utilisateur` (`id_utilisateur`),
  KEY `idx_id_defi` (`id_defi`),
  KEY `idx_statut` (`statut`),
  CONSTRAINT `fk_participations_user` FOREIGN KEY (`id_utilisateur`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  CONSTRAINT `participations_ibfk_1` FOREIGN KEY (`id_defi`) REFERENCES `defis` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `participations`
--

LOCK TABLES `participations` WRITE;
/*!40000 ALTER TABLE `participations` DISABLE KEYS */;
INSERT INTO `participations` VALUES (1,2,6,0,'in_progress','2026-05-05',NULL),(2,3,7,100,'completed','2026-05-05','2026-05-05'),(3,5,6,40,'in_progress','2026-05-05',NULL),(4,5,7,0,'in_progress','2026-05-05',NULL);
/*!40000 ALTER TABLE `participations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `user_id` (`user_id`),
  KEY `idx_token` (`token`),
  CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `produits`
--

DROP TABLE IF EXISTS `produits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `produits` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Product unique identifier',
  `nom` varchar(255) NOT NULL COMMENT 'Product name',
  `description` text DEFAULT NULL COMMENT 'Detailed product description',
  `prix` decimal(10,2) NOT NULL COMMENT 'Product price in euros',
  `promo_prix` decimal(10,2) DEFAULT NULL,
  `stock` int(11) DEFAULT 0 COMMENT 'Available quantity in stock',
  `coming_soon` tinyint(1) NOT NULL DEFAULT 0,
  `categorie` varchar(100) DEFAULT NULL COMMENT 'Product category (e.g., Protéines, Vitamines)',
  `image_url` varchar(255) DEFAULT NULL COMMENT 'Filename of product image stored in /dist/img/',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Date product was created',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Date product was last updated',
  PRIMARY KEY (`id`),
  KEY `idx_categorie` (`categorie`),
  KEY `idx_nom` (`nom`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `produits`
--

LOCK TABLES `produits` WRITE;
/*!40000 ALTER TABLE `produits` DISABLE KEYS */;
/*!40000 ALTER TABLE `produits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `proof_ai_reviews`
--

DROP TABLE IF EXISTS `proof_ai_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `proof_ai_reviews`
--

LOCK TABLES `proof_ai_reviews` WRITE;
/*!40000 ALTER TABLE `proof_ai_reviews` DISABLE KEYS */;
INSERT INTO `proof_ai_reviews` VALUES (1,2,'rejected',100,0,'L\'image fournie est un logo et ne représente pas un panier composé de produits locaux et de saison. Il n\'y a aucune preuve visuelle de la composition d\'un panier.','{\"decision\":\"rejected\",\"confidence\":100,\"progress_increment\":0,\"reason\":\"L\'image fournie est un logo et ne représente pas un panier composé de produits locaux et de saison. Il n\'y a aucune preuve visuelle de la composition d\'un panier.\"}','2026-05-05 14:23:47'),(2,3,'rejected',100,0,'L\'image fournie ne montre aucun panier de produits alimentaires, ni de produits locaux ou de saison. Elle semble être une image promotionnelle pour la Playstation 2 avec un personnage de dessin animé.','{\"decision\":\"rejected\",\"confidence\":100,\"progress_increment\":0,\"reason\":\"L\'image fournie ne montre aucun panier de produits alimentaires, ni de produits locaux ou de saison. Elle semble être une image promotionnelle pour la Playstation 2 avec un personnage de dessin animé.\"}','2026-05-05 14:28:45'),(3,4,'uncertain',30,0,'La preuve montre un panier de fruits, mais il n\'est pas possible de déterminer si les fruits sont locaux et de saison. Il faudrait des informations supplémentaires pour valider cet aspect du défi.','{\"decision\":\"uncertain\",\"confidence\":30,\"progress_increment\":0,\"reason\":\"La preuve montre un panier de fruits, mais il n\'est pas possible de déterminer si les fruits sont locaux et de saison. Il faudrait des informations supplémentaires pour valider cet aspect du défi.\"}','2026-05-05 14:30:28'),(4,5,'uncertain',40,0,'La preuve montre un panier de fruits, mais il est impossible de vérifier si les fruits sont locaux et de saison sans informations supplémentaires. Il y a au moins trois types de fruits visibles (raisins, bananes, poire), mais la conditionnalité \'locaux et de saison\' n\'est pas remplie.','{\"decision\":\"uncertain\",\"confidence\":40,\"progress_increment\":0,\"reason\":\"La preuve montre un panier de fruits, mais il est impossible de vérifier si les fruits sont locaux et de saison sans informations supplémentaires. Il y a au moins trois types de fruits visibles (raisins, bananes, poire), mais la conditionnalité \'locaux et de saison\' n\'est pas remplie.\"}','2026-05-05 15:09:34');
/*!40000 ALTER TABLE `proof_ai_reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recettes`
--

DROP TABLE IF EXISTS `recettes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recettes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `instructions` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recettes`
--

LOCK TABLES `recettes` WRITE;
/*!40000 ALTER TABLE `recettes` DISABLE KEYS */;
INSERT INTO `recettes` VALUES (1,'Poulet au riz','Un plat simple avec poulet et riz','Faire cuire le riz. Griller le poulet. Servir ensemble.','2026-04-14 14:06:19','2026-04-14 14:06:19');
/*!40000 ALTER TABLE `recettes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `site_events`
--

DROP TABLE IF EXISTS `site_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site_events`
--

LOCK TABLES `site_events` WRITE;
/*!40000 ALTER TABLE `site_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `site_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `two_factor_codes`
--

DROP TABLE IF EXISTS `two_factor_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `two_factor_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `code` varchar(10) NOT NULL,
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `two_factor_codes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `two_factor_codes`
--

LOCK TABLES `two_factor_codes` WRITE;
/*!40000 ALTER TABLE `two_factor_codes` DISABLE KEYS */;
/*!40000 ALTER TABLE `two_factor_codes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'client',
  `preference_alimentaire` varchar(50) NOT NULL,
  `date_inscription` datetime NOT NULL DEFAULT current_timestamp(),
  `statut_compte` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_email` (`email`),
  KEY `idx_user_role` (`role`),
  KEY `idx_user_statut` (`statut_compte`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
INSERT INTO `user` VALUES (1,'Admin Nutri','admin@nutrismart.tn','$2y$10$wH4r2h9A4L18W7aF2V8W8OQf1Bq2X8Q1Qv0m9G4zK8iY1B6nM8l4m','admin','equilibre','2026-04-01 09:00:00',1),(2,'Ali Ben Salah','ali.bs@nutrismart.tn','$2y$10$wH4r2h9A4L18W7aF2V8W8OQf1Bq2X8Q1Qv0m9G4zK8iY1B6nM8l4m','client','hyperproteine','2026-04-02 10:30:00',1),(3,'Sarra Trabelsi','sarra.tr@nutrismart.tn','$2y$10$wH4r2h9A4L18W7aF2V8W8OQf1Bq2X8Q1Qv0m9G4zK8iY1B6nM8l4m','client','vegetarien','2026-04-03 14:15:00',1),(4,'Youssef K.','youssef.k@nutrismart.tn','$2y$10$wH4r2h9A4L18W7aF2V8W8OQf1Bq2X8Q1Qv0m9G4zK8iY1B6nM8l4m','client','sans_gluten','2026-04-04 08:45:00',0),(5,'Meriem H.','meriem.h@nutrismart.tn','$2y$10$wH4r2h9A4L18W7aF2V8W8OQf1Bq2X8Q1Qv0m9G4zK8iY1B6nM8l4m','client','keto','2026-04-05 17:20:00',1),(6,'Stabilis Admin','stabilisatyourservice@gmail.com','$2y$10$aI8wXCaDUQIzs8xSXvggd.U7MYdEY1yVIFj.Aqi5jlFsVSASHEmpC','admin','','2026-05-10 00:40:07',1);
/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-10  0:04:38
