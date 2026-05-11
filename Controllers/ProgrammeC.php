<?php
require_once __DIR__ . '/../config/entrainements.php';

class ProgrammeC {
    private function db() { return config::getConnexion(); }

    public function listByUser(int $uid): array {
        $q = $this->db()->prepare(
            "SELECT p.id AS prog_id, p.added_at, e.*,
                    COALESCE(SUM(s.calories),0) AS total_calories,
                    COUNT(s.id) AS nb_seances
             FROM programme_utilisateur p
             INNER JOIN entrainements e ON e.id = p.entrainement_id
             LEFT JOIN seances_completees s ON s.entrainement_id = e.id AND s.utilisateur_id = p.utilisateur_id
             WHERE p.utilisateur_id=:uid
             GROUP BY p.id ORDER BY p.added_at DESC"
        );
        $q->execute([':uid'=>$uid]);
        return $q->fetchAll();
    }

    public function isInProgramme(int $uid, int $eid): bool {
        $q = $this->db()->prepare("SELECT COUNT(*) FROM programme_utilisateur WHERE utilisateur_id=:uid AND entrainement_id=:eid");
        $q->execute([':uid'=>$uid,':eid'=>$eid]);
        return (int)$q->fetchColumn() > 0;
    }

    public function add(int $uid, int $eid): void {
        if ($this->isInProgramme($uid, $eid)) return;
        $q = $this->db()->prepare("INSERT INTO programme_utilisateur (utilisateur_id,entrainement_id) VALUES(:uid,:eid)");
        $q->execute([':uid'=>$uid,':eid'=>$eid]);
    }

    public function remove(int $uid, int $eid): void {
        $q = $this->db()->prepare("DELETE FROM programme_utilisateur WHERE utilisateur_id=:uid AND entrainement_id=:eid");
        $q->execute([':uid'=>$uid,':eid'=>$eid]);
    }

    public function count(int $uid): int {
        $q = $this->db()->prepare("SELECT COUNT(*) FROM programme_utilisateur WHERE utilisateur_id=:uid");
        $q->execute([':uid'=>$uid]);
        return (int)$q->fetchColumn();
    }
}

