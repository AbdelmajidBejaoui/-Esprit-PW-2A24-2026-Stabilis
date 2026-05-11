<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Model/Utilisateur.php';

class UtilisateurC {
    private function db() { return config::getConnexion(); }

    public function getById(int $id): ?Utilisateur {
        $q = $this->db()->prepare("SELECT * FROM utilisateur WHERE id=:id");
        $q->execute([':id'=>$id]);
        $r = $q->fetch();
        if (!$r) return null;
        return new Utilisateur($r['id'],$r['nom'],$r['email'],$r['password'],$r['poids'],$r['taille'],$r['age'],$r['sexe'],$r['created_at']);
    }

    public function getByEmail(string $email): ?Utilisateur {
        $q = $this->db()->prepare("SELECT * FROM utilisateur WHERE email=:e");
        $q->execute([':e'=>$email]);
        $r = $q->fetch();
        if (!$r) return null;
        return new Utilisateur($r['id'],$r['nom'],$r['email'],$r['password'],$r['poids'],$r['taille'],$r['age'],$r['sexe'],$r['created_at']);
    }

    public function emailExists(string $email, int $excludeId=0): bool {
        $q = $this->db()->prepare("SELECT COUNT(*) FROM utilisateur WHERE email=:e AND id!=:id");
        $q->execute([':e'=>$email,':id'=>$excludeId]);
        return (int)$q->fetchColumn() > 0;
    }

    public function insert(Utilisateur $u): void {
        $q = $this->db()->prepare("INSERT INTO utilisateur (nom,email,password,poids,taille,age,sexe) VALUES(:n,:e,:p,:po,:t,:a,:s)");
        $q->execute([':n'=>$u->getNom(),':e'=>$u->getEmail(),':p'=>$u->getPassword(),
                     ':po'=>$u->getPoids(),':t'=>$u->getTaille(),':a'=>$u->getAge(),':s'=>$u->getSexe()]);
    }

    public function update(Utilisateur $u): void {
        $q = $this->db()->prepare("UPDATE utilisateur SET nom=:n,email=:e,poids=:po,taille=:t,age=:a,sexe=:s WHERE id=:id");
        $q->execute([':n'=>$u->getNom(),':e'=>$u->getEmail(),':po'=>$u->getPoids(),
                     ':t'=>$u->getTaille(),':a'=>$u->getAge(),':s'=>$u->getSexe(),':id'=>$u->getId()]);
    }

    public function listAll(): array {
        return $this->db()->query("SELECT * FROM utilisateur ORDER BY id DESC")->fetchAll();
    }

    public function delete(int $id): void {
        $this->db()->prepare("DELETE FROM utilisateur WHERE id=:id")->execute([':id'=>$id]);
    }

    public function count(): int {
        return (int)$this->db()->query("SELECT COUNT(*) FROM utilisateur")->fetchColumn();
    }

    // ── Validation ────────────────────────────────────────────────────────────
    public function validateRegister(array $p): array {
        $errors = [];
        if (empty(trim($p['nom'] ?? '')))                        $errors[] = 'Le nom est obligatoire.';
        if (strlen(trim($p['nom'] ?? '')) > 100)                 $errors[] = 'Nom max 100 caractères.';
        if (empty(trim($p['email'] ?? '')))                      $errors[] = 'L\'email est obligatoire.';
        if (!filter_var($p['email']??'', FILTER_VALIDATE_EMAIL)) $errors[] = 'Email invalide.';
        if ($this->emailExists(trim($p['email']??'')))            $errors[] = 'Cet email est déjà utilisé.';
        if (empty($p['password']))                                $errors[] = 'Mot de passe obligatoire.';
        if (strlen($p['password']??'') < 6)                      $errors[] = 'Mot de passe min 6 caractères.';
        return $errors;
    }

    public function validateProfile(array $p, int $userId): array {
        $errors = [];
        if (empty(trim($p['nom'] ?? '')))                        $errors[] = 'Le nom est obligatoire.';
        if (empty(trim($p['email'] ?? '')))                      $errors[] = 'L\'email est obligatoire.';
        if (!filter_var($p['email']??'', FILTER_VALIDATE_EMAIL)) $errors[] = 'Email invalide.';
        if ($this->emailExists(trim($p['email']??''), $userId))   $errors[] = 'Email déjà utilisé par un autre compte.';
        if (!empty($p['poids']) && (!is_numeric($p['poids']) || (float)$p['poids']<30 || (float)$p['poids']>300))
                                                                  $errors[] = 'Poids invalide (30–300 kg).';
        if (!empty($p['taille']) && (!is_numeric($p['taille']) || (int)$p['taille']<100 || (int)$p['taille']>250))
                                                                  $errors[] = 'Taille invalide (100–250 cm).';
        if (!empty($p['age']) && (!is_numeric($p['age']) || (int)$p['age']<10 || (int)$p['age']>100))
                                                                  $errors[] = 'Âge invalide (10–100 ans).';
        return $errors;
    }

    public function validateLogin(array $p): array {
        $errors = [];
        if (empty($p['email']))    $errors[] = 'Email obligatoire.';
        if (empty($p['password'])) $errors[] = 'Mot de passe obligatoire.';
        return $errors;
    }
}
