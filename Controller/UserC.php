<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Model/User.php';

class UserC
{
    public function countUsers($search = '')
    {
        $sql = 'SELECT COUNT(*) FROM `user`';
        $params = [];
        $search = trim($search);

        if ($search !== '') {
            $sql .= ' WHERE nom LIKE :search
                      OR email LIKE :search
                      OR role LIKE :search
                      OR preference_alimentaire LIKE :search';
            $params['search'] = '%' . $search . '%';
        }

        $db = config::getConnexion();

        try {
            if ($search === '') {
                return (int) $db->query($sql)->fetchColumn();
            }

            $query = $db->prepare($sql);
            $query->execute($params);
            return (int) $query->fetchColumn();
        } catch (Exception $e) {
            die('Error: ' . $e->getMessage());
        }
    }

    public function listUsers($search = '', $limit = null, $offset = 0)
    {
        $sql = 'SELECT * FROM `user`';
        $params = [];
        $search = trim($search);

        if ($search !== '') {
            $sql .= ' WHERE nom LIKE :search
                      OR email LIKE :search
                      OR role LIKE :search
                      OR preference_alimentaire LIKE :search';
            $params['search'] = '%' . $search . '%';
        }

        $sql .= ' ORDER BY id DESC';

        if ($limit !== null) {
            $limit = max(1, (int) $limit);
            $offset = max(0, (int) $offset);
            $sql .= " LIMIT $limit OFFSET $offset";
        }
        
        $db = config::getConnexion();

        try {
            if ($search === '') {
                $list = $db->query($sql);
                return $list->fetchAll();
            }

            $query = $db->prepare($sql);
            $query->execute($params);
            return $query->fetchAll();
        } catch (Exception $e) {
            die('Error: ' . $e->getMessage());
        }
    }

    public function getUserById($id)
    {
        $sql = 'SELECT * FROM `user` WHERE id = :id';
        $db = config::getConnexion();

        try {
            $query = $db->prepare($sql);
            $query->execute(['id' => $id]);
            return $query->fetch();
        } catch (Exception $e) {
            die('Error: ' . $e->getMessage());
        }
    }

    public function getUserByEmail($email)
    {
        $sql = 'SELECT * FROM `user` WHERE email = :email LIMIT 1';
        $db = config::getConnexion();

        try {
            $query = $db->prepare($sql);
            $query->execute(['email' => $email]);
            return $query->fetch();
        } catch (Exception $e) {
            die('Error: ' . $e->getMessage());
        }
    }

    public function emailExists($email, $excludeId = null)
    {
        $sql = 'SELECT COUNT(*) FROM `user` WHERE email = :email';
        $params = ['email' => $email];

        if ($excludeId !== null) {
            $sql .= ' AND id != :exclude_id';
            $params['exclude_id'] = (int) $excludeId;
        }

        $db = config::getConnexion();

        try {
            $query = $db->prepare($sql);
            $query->execute($params);
            return (int) $query->fetchColumn() > 0;
        } catch (Exception $e) {
            die('Error: ' . $e->getMessage());
        }
    }

    public function authenticateUser($email, $password)
    {
        $user = $this->getUserByEmail($email);

        if (!$user) {
            return null;
        }

        if ((int) $user['statut_compte'] !== 1) {
            return null;
        }

        if (!password_verify($password, $user['password'])) {
            return null;
        }

        return $user;
    }

    public function insertUser($user)
    {
        $sql = 'INSERT INTO `user` (nom, email, password, role, preference_alimentaire, date_inscription, statut_compte)
                VALUES (:nom, :email, :password, :role, :preference_alimentaire, :date_inscription, :statut_compte)';
        $db = config::getConnexion();

        try {
            $query = $db->prepare($sql);
            $query->execute([
                'nom' => $user->getNom(),
                'email' => $user->getEmail(),
                'password' => password_hash($user->getPassword(), PASSWORD_DEFAULT),
                'role' => $user->getRole(),
                'preference_alimentaire' => $user->getPreferenceAlimentaire(),
                'date_inscription' => $user->getDateInscription(),
                'statut_compte' => $user->getStatutCompte()
            ]);
        } catch (Exception $e) {
            die('Error: ' . $e->getMessage());
        }
    }

    public function updateUser($user, $id, $updatePassword = false)
    {
        $db = config::getConnexion();

        try {
            if ($updatePassword) {
                $sql = 'UPDATE `user`
                        SET nom = :nom,
                            email = :email,
                            password = :password,
                            role = :role,
                            preference_alimentaire = :preference_alimentaire,
                            date_inscription = :date_inscription,
                            statut_compte = :statut_compte
                        WHERE id = :id';

                $query = $db->prepare($sql);
                $query->execute([
                    'nom' => $user->getNom(),
                    'email' => $user->getEmail(),
                    'password' => password_hash($user->getPassword(), PASSWORD_DEFAULT),
                    'role' => $user->getRole(),
                    'preference_alimentaire' => $user->getPreferenceAlimentaire(),
                    'date_inscription' => $user->getDateInscription(),
                    'statut_compte' => $user->getStatutCompte(),
                    'id' => $id
                ]);
                return;
            }

            $sql = 'UPDATE `user`
                    SET nom = :nom,
                        email = :email,
                        role = :role,
                        preference_alimentaire = :preference_alimentaire,
                        date_inscription = :date_inscription,
                        statut_compte = :statut_compte
                    WHERE id = :id';

            $query = $db->prepare($sql);
            $query->execute([
                'nom' => $user->getNom(),
                'email' => $user->getEmail(),
                'role' => $user->getRole(),
                'preference_alimentaire' => $user->getPreferenceAlimentaire(),
                'date_inscription' => $user->getDateInscription(),
                'statut_compte' => $user->getStatutCompte(),
                'id' => $id
            ]);
        } catch (Exception $e) {
            die('Error: ' . $e->getMessage());
        }
    }

    public function deleteUser($id)
    {
        $sql = 'DELETE FROM `user` WHERE id = :id';
        $db = config::getConnexion();

        try {
            $query = $db->prepare($sql);
            $query->execute(['id' => $id]);
        } catch (Exception $e) {
            die('Error: ' . $e->getMessage());
        }
    }

    public function validateUserData($data, $passwordRequired = true)
    {
        $errors = [];

        $nom = trim($data['nom'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $role = trim($data['role'] ?? 'client');
        $preference = trim($data['preference_alimentaire'] ?? '');
        $dateInscription = trim($data['date_inscription'] ?? '');
        $statutCompte = isset($data['statut_compte']) ? (int) $data['statut_compte'] : 1;

        if ($nom === '' || !preg_match('/^[a-zA-Z\s\-]{2,100}$/', $nom)) {
            $errors[] = 'Nom invalide (lettres, espaces, tirets, min 2 caracteres).';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email invalide.';
        }

        if ($passwordRequired) {
            if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
                $errors[] = 'Mot de passe invalide (min 8 caracteres avec lettres et chiffres).';
            }
        }

        if (!in_array($role, ['admin', 'client'], true)) {
            $errors[] = 'Role invalide (admin ou client).';
        }

        if ($preference === '' || strlen($preference) > 50) {
            $errors[] = 'Preference alimentaire invalide (obligatoire, max 50 caracteres).';
        }

        if ($dateInscription === '') {
            $errors[] = 'Date inscription obligatoire.';
        } else {
            $date = DateTime::createFromFormat('Y-m-d H:i:s', $dateInscription);
            if (!$date || $date->format('Y-m-d H:i:s') !== $dateInscription) {
                $errors[] = 'Date inscription invalide (format: YYYY-MM-DD HH:MM:SS).';
            }
        }

        if (!in_array($statutCompte, [0, 1], true)) {
            $errors[] = 'Statut compte invalide (0 ou 1).';
        }

        return $errors;
    }

    public function validateRegistrationData($data)
    {
        $errors = [];

        $nom = trim($data['nom'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $preference = trim($data['preference_alimentaire'] ?? '');

        if ($nom === '' || !preg_match('/^[a-zA-Z\s\-]{2,100}$/', $nom)) {
            $errors[] = 'Nom invalide (lettres, espaces, tirets, min 2 caracteres).';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email invalide.';
        } elseif ($this->emailExists($email)) {
            $errors[] = 'Cet email est deja utilise.';
        }

        if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
            $errors[] = 'Mot de passe invalide (min 8 caracteres avec lettres et chiffres).';
        }

        if ($preference === '' || strlen($preference) > 50) {
            $errors[] = 'Preference alimentaire invalide (obligatoire, max 50 caracteres).';
        }

        return $errors;
    }

    public function validateProfileData($data, $passwordRequired = false, $excludeId = null)
    {
        $errors = [];

        $nom = trim($data['nom'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $preference = trim($data['preference_alimentaire'] ?? '');

        if ($nom === '' || !preg_match('/^[a-zA-Z\s\-]{2,100}$/', $nom)) {
            $errors[] = 'Nom invalide (lettres, espaces, tirets, min 2 caracteres).';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email invalide.';
        } elseif ($this->emailExists($email, $excludeId)) {
            $errors[] = 'Cet email est deja utilise.';
        }

        if ($passwordRequired && (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password))) {
            $errors[] = 'Mot de passe invalide (min 8 caracteres avec lettres et chiffres).';
        }

        if ($preference === '' || strlen($preference) > 50) {
            $errors[] = 'Preference alimentaire invalide (obligatoire, max 50 caracteres).';
        }

        return $errors;
    }

    public function validateLoginData($data)
    {
        $errors = [];

        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email invalide.';
        }

        if ($password === '') {
            $errors[] = 'Mot de passe obligatoire.';
        }

        return $errors;
    }
}
?>