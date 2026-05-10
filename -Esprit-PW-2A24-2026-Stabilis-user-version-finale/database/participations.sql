CREATE TABLE IF NOT EXISTS participations (
    id INT(11) NOT NULL AUTO_INCREMENT,
    id_utilisateur INT(11) NOT NULL,
    id_defi INT(11) NOT NULL,
    progression INT(11) DEFAULT 0,
    statut ENUM('in_progress','completed','failed') DEFAULT 'in_progress',
    date_debut DATE DEFAULT (CURRENT_DATE),
    date_fin DATE NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_participation_user_defi (id_utilisateur, id_defi),
    INDEX idx_id_utilisateur (id_utilisateur),
    INDEX idx_id_defi (id_defi),
    CONSTRAINT fk_participations_user
        FOREIGN KEY (id_utilisateur) REFERENCES `user`(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_participations_defi
        FOREIGN KEY (id_defi) REFERENCES defis(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS participation_proofs (
    id INT(11) NOT NULL AUTO_INCREMENT,
    participation_id INT(11) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    review_state ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_participation_id (participation_id),
    INDEX idx_review_state (review_state),
    CONSTRAINT fk_participation_proofs_participation
        FOREIGN KEY (participation_id) REFERENCES participations(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS proof_ai_reviews (
    id INT(11) NOT NULL AUTO_INCREMENT,
    proof_id INT(11) NOT NULL,
    ai_decision ENUM('approved','rejected','uncertain','error') NOT NULL DEFAULT 'uncertain',
    ai_confidence TINYINT UNSIGNED NOT NULL DEFAULT 0,
    ai_progress_increment TINYINT UNSIGNED NOT NULL DEFAULT 0,
    ai_reason TEXT NOT NULL,
    ai_raw_response TEXT NULL,
    ai_reviewed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_proof_ai_review (proof_id),
    INDEX idx_ai_decision (ai_decision),
    CONSTRAINT fk_proof_ai_reviews_proof
        FOREIGN KEY (proof_id) REFERENCES participation_proofs(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
