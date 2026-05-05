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
        $sql = 'INSERT INTO `user` (nom, email, password, role, preference_alimentaire, date_inscription, statut_compte, face_image, face_descriptor)
            VALUES (:nom, :email, :password, :role, :preference_alimentaire, :date_inscription, :statut_compte, :face_image, :face_descriptor)';
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
                'statut_compte' => $user->getStatutCompte(),
                'face_image' => $user->getFaceImage(),
                'face_descriptor' => $user->getFaceDescriptor()
            ]);
            return (int)$db->lastInsertId();
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
                            statut_compte = :statut_compte,
                            face_image = :face_image,
                            face_descriptor = :face_descriptor
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
                    'face_image' => $user->getFaceImage(),
                    'face_descriptor' => $user->getFaceDescriptor(),
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
                        statut_compte = :statut_compte,
                        face_image = :face_image,
                        face_descriptor = :face_descriptor
                    WHERE id = :id';

            $query = $db->prepare($sql);
            $query->execute([
                'nom' => $user->getNom(),
                'email' => $user->getEmail(),
                'role' => $user->getRole(),
                'preference_alimentaire' => $user->getPreferenceAlimentaire(),
                'date_inscription' => $user->getDateInscription(),
                'statut_compte' => $user->getStatutCompte(),
                'face_image' => $user->getFaceImage(),
                'face_descriptor' => $user->getFaceDescriptor(),
                'id' => $id
            ]);
        } catch (Exception $e) {
            die('Error: ' . $e->getMessage());
        }
    }

    public function validateFaceData($data, $required = true)
    {
        $errors = [];
        $imageData = trim($data['face_image_data'] ?? '');
        $descriptor = trim($data['face_descriptor'] ?? '');

        if ($required) {
            if ($imageData === '' || $descriptor === '') {
                $errors[] = 'Face ID obligatoire. Veuillez capturer votre visage.';
                return $errors;
            }
        }

        if ($imageData !== '' && $this->decodeBase64Image($imageData) === null) {
            $errors[] = 'Image visage invalide.';
        }

        if ($descriptor !== '' && !preg_match('/^\[.*\]$/', $descriptor)) {
            $errors[] = 'Descripteur visage invalide.';
        }

        return $errors;
    }

    public function decodeBase64Image($data)
    {
        $data = trim((string) $data);
        if ($data === '') {
            return null;
        }

        if (!preg_match('/^data:image\/(png|jpe?g);base64,/', $data)) {
            return null;
        }

        $parts = explode(',', $data, 2);
        if (count($parts) !== 2) {
            return null;
        }

        $decoded = base64_decode($parts[1], true);
        if ($decoded === false) {
            return null;
        }

        return $decoded;
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

    public function blockUser($id)
    {
        $sql = 'UPDATE `user` SET statut_compte = 0 WHERE id = :id';
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

    // --- Password reset flow ---
    public function createPasswordReset(string $email)
    {
        $db = config::getConnexion();

        try {
            $query = $db->prepare('SELECT id, nom FROM `user` WHERE email = :email LIMIT 1');
            $query->execute(['email' => $email]);
            $user = $query->fetch();

            if (!$user) {
                return false; // do not reveal whether email exists
            }

            $token = bin2hex(random_bytes(16));
            $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour

            $insert = $db->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)');
            $insert->execute([
                'user_id' => (int)$user['id'],
                'token' => $token,
                'expires_at' => $expiresAt
            ]);

            // Send email (simple mail() usage)
            $subject = 'Réinitialisation de votre mot de passe';
            $resetLink = $this->buildResetLink($token);
            $message = "Bonjour " . $user['nom'] . ",\n\n";
            $message .= "Vous avez demandé la réinitialisation de votre mot de passe.\n";
            $message .= "Veuillez utiliser le lien suivant pour définir un nouveau mot de passe (valide 1 heure):\n\n";
            $message .= $resetLink . "\n\n";
            $message .= "Si vous n'avez pas demandé cette action, ignorez ce message.\n\nCordialement,\nNutriSmart";

            $headers = 'From: no-reply@localhost' . "\r\n" . 'Reply-To: no-reply@localhost' . "\r\n";
            // Suppress errors, return true/false based on mail() result
            @mail($email, $subject, $message, $headers);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    private function buildResetLink(string $token): string
    {
        // Build a reset URL with correct path
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $url = sprintf('http://%s/Projet/View/FrontOffice/resetPassword.php?token=%s', $host, $token);
        return $url;
    }

    public function verifyPasswordResetToken(string $token)
    {
        $db = config::getConnexion();
        try {
            $query = $db->prepare('SELECT pr.id as pr_id, pr.user_id, pr.expires_at, u.email, u.nom FROM password_resets pr JOIN `user` u ON pr.user_id = u.id WHERE pr.token = :token LIMIT 1');
            $query->execute(['token' => $token]);
            $row = $query->fetch();
            if (!$row) {
                return null;
            }
            if (strtotime($row['expires_at']) < time()) {
                return null;
            }
            return $row;
        } catch (Exception $e) {
            return null;
        }
    }

    public function verifyRecaptcha(string $responseToken): bool
    {
        if (empty($responseToken)) {
            return false;
        }

        $configPath = __DIR__ . '/../config_recaptcha.php';
        if (!file_exists($configPath)) {
            return false;
        }

        $cfg = include $configPath;
        $secret = $cfg['secret'] ?? '';
        if ($secret === '') {
            return false;
        }

        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $data = http_build_query([
            'secret' => $secret,
            'response' => $responseToken,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);

        $opts = ['http' => [
            'method' => 'POST',
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'content' => $data,
            'timeout' => 5,
        ]];

        $context = stream_context_create($opts);
        $result = @file_get_contents($url, false, $context);
        if ($result === false) {
            return false;
        }

        $json = json_decode($result, true);
        return !empty($json['success']) && $json['success'] === true;
    }

    public function resetPasswordWithToken(string $token, string $newPassword)
    {
        $db = config::getConnexion();
        try {
            $row = $this->verifyPasswordResetToken($token);
            if (!$row) {
                return false;
            }

            $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
            $update = $db->prepare('UPDATE `user` SET password = :password WHERE id = :id');
            $update->execute(['password' => $hashed, 'id' => (int)$row['user_id']]);

            $delete = $db->prepare('DELETE FROM password_resets WHERE user_id = :user_id');
            $delete->execute(['user_id' => (int)$row['user_id']]);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    // --- Email verification for new users ---
    public function createEmailVerification(int $userId, string $email)
    {
        $db = config::getConnexion();
        try {
            $token = bin2hex(random_bytes(16));
            $expiresAt = date('Y-m-d H:i:s', time() + 86400); // 24 hours

            $insert = $db->prepare('INSERT INTO email_verifications (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)');
            $insert->execute([
                'user_id' => $userId,
                'token' => $token,
                'expires_at' => $expiresAt
            ]);

            $subject = 'Verification de votre email - NutriSmart';
            $verifyLink = sprintf('http://%s/Projet/View/FrontOffice/verifyEmail.php?token=%s', $_SERVER['HTTP_HOST'] ?? 'localhost', $token);
            $message = "Bonjour,\n\nBienvenue sur NutriSmart. Cliquez sur le lien suivant pour verifier votre adresse email :\n\n" . $verifyLink . "\n\nCe lien est valable 24 heures.\n\nCordialement,\nNutriSmart";
            $headers = 'From: no-reply@localhost' . "\r\n" . 'Reply-To: no-reply@localhost' . "\r\n";
            @mail($email, $subject, $message, $headers);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function verifyEmailToken(string $token)
    {
        $db = config::getConnexion();
        try {
            $query = $db->prepare('SELECT ev.id, ev.user_id, ev.expires_at FROM email_verifications ev WHERE ev.token = :token LIMIT 1');
            $query->execute(['token' => $token]);
            $row = $query->fetch();
            if (!$row) {
                return false;
            }
            if (strtotime($row['expires_at']) < time()) {
                $del = $db->prepare('DELETE FROM email_verifications WHERE id = :id');
                $del->execute(['id' => $row['id']]);
                return false;
            }

            $update = $db->prepare('UPDATE `user` SET statut_compte = 1 WHERE id = :id');
            $update->execute(['id' => (int)$row['user_id']]);

            $del = $db->prepare('DELETE FROM email_verifications WHERE user_id = :user_id');
            $del->execute(['user_id' => (int)$row['user_id']]);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    // --- Two-factor authentication (email OTP) ---
    public function sendTwoFactorCode(int $userId, string $email)
    {
        $db = config::getConnexion();
        try {
            $code = random_int(100000, 999999); // 6-digit code
            $expiresAt = date('Y-m-d H:i:s', time() + 300); // 5 minutes

            $insert = $db->prepare('INSERT INTO two_factor_codes (user_id, code, expires_at) VALUES (:user_id, :code, :expires_at)');
            $insert->execute([
                'user_id' => $userId,
                'code' => (string)$code,
                'expires_at' => $expiresAt
            ]);

            // Send email
            $subject = 'Code de verification - NutriSmart';
            $message = "Bonjour,\n\nVotre code de verification est : " . $code . "\nIl est valable 5 minutes.\n\nCordialement,\nNutriSmart";
            $headers = 'From: no-reply@localhost' . "\r\n" . 'Reply-To: no-reply@localhost' . "\r\n";
            @mail($email, $subject, $message, $headers);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function verifyTwoFactorCode(int $userId, string $code)
    {
        $db = config::getConnexion();
        try {
            $query = $db->prepare('SELECT id, expires_at FROM two_factor_codes WHERE user_id = :user_id AND code = :code LIMIT 1');
            $query->execute(['user_id' => $userId, 'code' => $code]);
            $row = $query->fetch();
            if (!$row) {
                return false;
            }
            if (strtotime($row['expires_at']) < time()) {
                // expired -> remove
                $del = $db->prepare('DELETE FROM two_factor_codes WHERE id = :id');
                $del->execute(['id' => $row['id']]);
                return false;
            }

            // valid -> delete all codes for user
            $del = $db->prepare('DELETE FROM two_factor_codes WHERE user_id = :user_id');
            $del->execute(['user_id' => $userId]);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getStatistics()
    {
        $db = config::getConnexion();
        $stats = [];

        try {
            // Total users
            $totalUsers = (int) $db->query('SELECT COUNT(*) FROM `user`')->fetchColumn();
            $stats['totalUsers'] = $totalUsers;

            // Users by role
            $roleQuery = $db->query('SELECT role, COUNT(*) as count FROM `user` GROUP BY role');
            $stats['usersByRole'] = $roleQuery->fetchAll(PDO::FETCH_ASSOC);

            // Users by dietary preference
            $preferenceQuery = $db->query('SELECT preference_alimentaire, COUNT(*) as count FROM `user` GROUP BY preference_alimentaire');
            $stats['usersByPreference'] = $preferenceQuery->fetchAll(PDO::FETCH_ASSOC);

            // Users by account status
            $statusQuery = $db->query('SELECT statut_compte, COUNT(*) as count FROM `user` GROUP BY statut_compte');
            $stats['usersByStatus'] = $statusQuery->fetchAll(PDO::FETCH_ASSOC);

            // Users by month (registration trend)
            $monthQuery = $db->query('SELECT DATE_FORMAT(date_inscription, "%Y-%m") as month, COUNT(*) as count FROM `user` GROUP BY DATE_FORMAT(date_inscription, "%Y-%m") ORDER BY month DESC LIMIT 12');
            $stats['usersByMonth'] = $monthQuery->fetchAll(PDO::FETCH_ASSOC);

            return $stats;
        } catch (Exception $e) {
            die('Error: ' . $e->getMessage());
        }
    }

    public function getAllUsersForExport()
    {
        $sql = 'SELECT * FROM `user` ORDER BY id DESC';
        $db = config::getConnexion();

        try {
            $query = $db->query($sql);
            return $query->fetchAll();
        } catch (Exception $e) {
            die('Error: ' . $e->getMessage());
        }
    }
}
?>