-- ══════════════════════════════════════════════════════════════════════════════
-- CLEAN DATABASE - Minimal & AI-Focused
-- ══════════════════════════════════════════════════════════════════════════════
-- Philosophy: AI generates everything, minimal static data
-- ══════════════════════════════════════════════════════════════════════════════

CREATE DATABASE IF NOT EXISTS gestion_fitness CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gestion_fitness;

-- ══════════════════════════════════════════════════════════════════════════════
-- CORE TABLES (Essential only)
-- ══════════════════════════════════════════════════════════════════════════════

-- ── USERS ─────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS utilisateur (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    nom        VARCHAR(100) NOT NULL,
    email      VARCHAR(150) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    poids      DECIMAL(5,2) DEFAULT NULL COMMENT 'Weight in kg for calorie calculations',
    taille     INT          DEFAULT NULL COMMENT 'Height in cm',
    age        INT          DEFAULT NULL,
    sexe       ENUM('H','F') DEFAULT 'H',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB;

-- ── AI GENERATED SESSIONS (Main table - AI creates everything) ────────────────
CREATE TABLE IF NOT EXISTS generated_sessions (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    goal           ENUM('perte_graisse','prise_muscle','endurance') NOT NULL,
    niveau         ENUM('debutant','intermediaire','avance') NOT NULL,
    exercises_json TEXT NOT NULL COMMENT 'AI-generated exercises in JSON',
    total_calories DECIMAL(8,2) NOT NULL DEFAULT 0,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_goal_niveau (goal, niveau),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- ── SAVED WORKOUTS (User's saved AI workouts) ─────────────────────────────────
CREATE TABLE IF NOT EXISTS entrainements (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    nom        VARCHAR(100) NOT NULL,
    description TEXT,
    type_sport VARCHAR(50)  NOT NULL DEFAULT 'AI Generated',
    niveau     ENUM('debutant','intermediaire','avance') NOT NULL,
    met_value  DECIMAL(4,1) NOT NULL DEFAULT 5.0,
    is_custom  TINYINT(1)   NOT NULL DEFAULT 1 COMMENT '1 = AI generated',
    user_id    INT          NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ent_user FOREIGN KEY (user_id) REFERENCES utilisateur(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_custom (is_custom)
) ENGINE=InnoDB;

-- ── WORKOUT STEPS (Tutorial steps for saved workouts) ─────────────────────────
CREATE TABLE IF NOT EXISTS etapes_exercice (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    entrainement_id INT NOT NULL,
    ordre           INT NOT NULL DEFAULT 1,
    titre           VARCHAR(120) NOT NULL,
    description     TEXT NOT NULL,
    duree_secondes  INT DEFAULT 30,
    conseil         VARCHAR(255) DEFAULT NULL,
    CONSTRAINT fk_etape_ent FOREIGN KEY (entrainement_id) REFERENCES entrainements(id) ON DELETE CASCADE,
    INDEX idx_entrainement (entrainement_id)
) ENGINE=InnoDB;

-- ── USER PROGRAM (User's workout collection) ──────────────────────────────────
CREATE TABLE IF NOT EXISTS programme_utilisateur (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id  INT NOT NULL,
    entrainement_id INT NOT NULL,
    added_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_prog_user FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(id) ON DELETE CASCADE,
    CONSTRAINT fk_prog_ent  FOREIGN KEY (entrainement_id) REFERENCES entrainements(id) ON DELETE CASCADE,
    UNIQUE KEY unique_prog (utilisateur_id, entrainement_id),
    INDEX idx_user_prog (utilisateur_id)
) ENGINE=InnoDB;

-- ── COMPLETED SESSIONS (Workout history & tracking) ───────────────────────────
CREATE TABLE IF NOT EXISTS seances_completees (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id  INT NOT NULL,
    entrainement_id INT NOT NULL,
    duree_minutes   INT NOT NULL,
    calories        DECIMAL(8,2) NOT NULL,
    intensite       ENUM('faible','moderee','elevee','maximale') DEFAULT 'moderee',
    fc_moyenne      INT DEFAULT NULL COMMENT 'Average heart rate',
    notes           TEXT,
    completed_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_seance_user FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(id) ON DELETE CASCADE,
    CONSTRAINT fk_seance_ent  FOREIGN KEY (entrainement_id) REFERENCES entrainements(id) ON DELETE CASCADE,
    INDEX idx_user_completed (utilisateur_id, completed_at),
    INDEX idx_entrainement (entrainement_id)
) ENGINE=InnoDB;

-- ══════════════════════════════════════════════════════════════════════════════
-- MINIMAL SEED DATA
-- ══════════════════════════════════════════════════════════════════════════════

-- Test user (password: password)
INSERT INTO utilisateur (nom, email, password, poids, taille, age, sexe) VALUES
('Test User', 'test@fitness.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 75.0, 178, 25, 'H');

-- ══════════════════════════════════════════════════════════════════════════════
-- NOTES
-- ══════════════════════════════════════════════════════════════════════════════
-- 
-- REMOVED TABLES (Not needed - AI generates everything):
-- - exercises (30 rows) → AI generates exercises
-- - training_programs (9 rows) → AI generates programs
-- - training_sessions (27 rows) → AI generates sessions
-- - session_exercises (100+ rows) → AI generates exercises
-- - Static entrainements catalogue (30 rows) → AI generates on demand
--
-- WHAT'S LEFT:
-- - utilisateur: User accounts
-- - generated_sessions: AI generation history
-- - entrainements: User's saved AI workouts
-- - etapes_exercice: Tutorial steps for saved workouts
-- - programme_utilisateur: User's workout collection
-- - seances_completees: Workout completion tracking
--
-- RESULT: 6 tables instead of 11, minimal data, AI-first approach
-- ══════════════════════════════════════════════════════════════════════════════
