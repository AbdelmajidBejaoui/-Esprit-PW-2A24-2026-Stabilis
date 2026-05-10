<?php
require_once __DIR__ . '/../../Controller/UserC.php';
require_once __DIR__ . '/../../Model/User.php';

$userC = new UserC();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = $userC->validateUserData($_POST, true);
    $faceErrors = $userC->validateFaceData($_POST, true);
    if (!empty($faceErrors)) {
        $errors = array_merge($errors, $faceErrors);
    }

    if (empty($errors)) {
        $faceImage = $userC->decodeBase64Image($_POST['face_image_data'] ?? '');
        $faceDescriptor = trim($_POST['face_descriptor'] ?? '');

        $user = new User(
            null,
            trim($_POST['nom']),
            trim($_POST['email']),
            $_POST['password'],
            trim($_POST['role']),
            trim($_POST['preference_alimentaire']),
            trim($_POST['date_inscription']),
            (int) $_POST['statut_compte'],
            $faceImage,
            $faceDescriptor
        );

        $userC->insertUser($user);
        header('Location: listUsers.php');
        exit;
    }
}

$pageTitle = 'Ajouter utilisateur';
$activePage = 'add';
require_once __DIR__ . '/partials/layout_top.php';
?>

<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">Nouveau compte</h3>
    </div>
    <form method="POST" action="">
        <div class="card-body">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <h5><i class="icon fas fa-ban"></i> Erreurs de saisie</h5>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label>Nom</label>
                <input class="form-control" type="text" name="nom" value="<?php echo htmlspecialchars($_POST['nom'] ?? ''); ?>" placeholder="Nom complet">
            </div>

            <div class="form-group">
                <label>Email</label>
                <input class="form-control" type="text" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" placeholder="email@exemple.com">
            </div>

            <div class="form-group">
                <label>Mot de passe</label>
                <input class="form-control" type="password" name="password" placeholder="Minimum 8 caracteres">
            </div>

            <div class="form-group">
                <label>Role (admin/client)</label>
                <input class="form-control" type="text" name="role" value="<?php echo htmlspecialchars($_POST['role'] ?? 'client'); ?>">
            </div>

            <div class="form-group">
                <label>Preference alimentaire</label>
                <input class="form-control" type="text" name="preference_alimentaire" value="<?php echo htmlspecialchars($_POST['preference_alimentaire'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Date inscription (YYYY-MM-DD HH:MM:SS)</label>
                <input class="form-control" type="text" name="date_inscription" value="<?php echo htmlspecialchars($_POST['date_inscription'] ?? date('Y-m-d H:i:s')); ?>">
            </div>

            <div class="form-group">
                <label>Statut compte (1 actif, 0 inactif)</label>
                <input class="form-control" type="text" name="statut_compte" value="<?php echo htmlspecialchars($_POST['statut_compte'] ?? '1'); ?>">
            </div>

            <div class="form-group">
                <label>Face ID (capture du visage)</label>
                <div class="mb-2">
                    <video id="faceVideo" width="320" height="240" autoplay muted></video>
                    <canvas id="faceCanvas" width="320" height="240" class="d-none"></canvas>
                </div>
                <button type="button" id="captureFace" class="btn btn-outline-secondary btn-sm">Capturer le visage</button>
                <div id="faceStatus" class="mt-2 text-muted">En attente de capture.</div>
                <input type="hidden" name="face_image_data" id="faceImageData" value="<?php echo htmlspecialchars($_POST['face_image_data'] ?? ''); ?>">
                <input type="hidden" name="face_descriptor" id="faceDescriptor" value="<?php echo htmlspecialchars($_POST['face_descriptor'] ?? ''); ?>">
            </div>
        </div>

        <div class="card-footer">
            <button type="submit" class="btn btn-primary">Ajouter</button>
            <a href="listUsers.php" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
</div>

<script src="https://unpkg.com/face-api.js@0.22.2/dist/face-api.min.js"></script>
<script>
const faceStatus = document.getElementById('faceStatus');
const captureBtn = document.getElementById('captureFace');
const video = document.getElementById('faceVideo');
const canvas = document.getElementById('faceCanvas');
const imageInput = document.getElementById('faceImageData');
const descriptorInput = document.getElementById('faceDescriptor');
const modelUrl = '/Projet/assets/face-api';

async function loadModels() {
    await faceapi.nets.tinyFaceDetector.loadFromUri(modelUrl);
    await faceapi.nets.faceLandmark68Net.loadFromUri(modelUrl);
    await faceapi.nets.faceRecognitionNet.loadFromUri(modelUrl);
}

async function startVideo() {
    const stream = await navigator.mediaDevices.getUserMedia({ video: true });
    video.srcObject = stream;
}

async function captureFace() {
    if (video.readyState < 2) {
        faceStatus.textContent = 'Camera non prete. Attendez 2 secondes et reessayez.';
        return;
    }

    faceStatus.textContent = 'Analyse en cours...';
    try {
        const detection = await faceapi
            .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
            .withFaceLandmarks()
            .withFaceDescriptor();

        if (!detection) {
            faceStatus.textContent = 'Aucun visage detecte. Reessayez.';
            return;
        }

        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        imageInput.value = canvas.toDataURL('image/jpeg');
        descriptorInput.value = JSON.stringify(Array.from(detection.descriptor));
        faceStatus.textContent = 'Visage capture avec succes.';
    } catch (e) {
        faceStatus.textContent = 'Erreur pendant la detection du visage.';
    }
}

captureBtn.addEventListener('click', captureFace);

(async () => {
    try {
        if (!window.faceapi) {
            faceStatus.textContent = 'Face ID bloque (script face-api.js non charge).';
            return;
        }

        await loadModels();
    } catch (e) {
        console.error('Model load error:', e);
        faceStatus.textContent = 'Modeles Face ID introuvables ou bloques.';
        return;
    }

    try {
        await startVideo();
    } catch (e) {
        console.error('Camera error:', e);
        faceStatus.textContent = 'Camera indisponible ou permission refusee.';
    }
})();

document.querySelector('form').addEventListener('submit', (e) => {
    if (imageInput.value === '' || descriptorInput.value === '') {
        e.preventDefault();
        faceStatus.textContent = 'Veuillez capturer votre visage avant de valider.';
    }
});
</script>

<?php require_once __DIR__ . '/partials/layout_bottom.php'; ?>
