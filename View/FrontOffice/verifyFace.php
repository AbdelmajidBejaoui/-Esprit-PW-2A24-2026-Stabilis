<?php
require_once __DIR__ . '/partials/auth.php';
require_once __DIR__ . '/../../Controller/UserC.php';

$userC = new UserC();
$errors = [];

if (!isset($_SESSION['pending_face_user'])) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['pending_face_user'];
$email = $_SESSION['pending_face_email'] ?? '';
$user = $userC->getUserById($userId);

if (!$user) {
    unset($_SESSION['pending_face_user'], $_SESSION['pending_face_email']);
    header('Location: login.php');
    exit;
}

$faceDescriptor = $user['face_descriptor'] ?? '';
if ($faceDescriptor === '') {
    $errors[] = 'Face ID non configure pour ce compte.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $faceMatch = trim($_POST['face_match'] ?? '0');
    if ($faceMatch !== '1') {
        $errors[] = 'Verification Face ID echouee.';
    }

    if (empty($errors)) {
        // Start 2FA after successful Face ID
        $_SESSION['pending_2fa_user'] = $userId;
        $_SESSION['pending_2fa_email'] = $email;
        $userC->sendTwoFactorCode($userId, $email);

        unset($_SESSION['pending_face_user'], $_SESSION['pending_face_email']);
        header('Location: verify2fa.php');
        exit;
    }
}

$pageTitle = 'Verification Face ID';
$heroTitle = 'Verification Face ID';
$activePage = 'login';
require_once __DIR__ . '/partials/layout_top.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card card-vege">
            <div class="card-header">Face ID</div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <p>Capturez votre visage pour confirmer votre identite.</p>

                <div class="mb-2">
                    <video id="faceVideo" width="320" height="240" autoplay muted></video>
                    <canvas id="faceCanvas" width="320" height="240" class="d-none"></canvas>
                </div>
                <button type="button" id="captureFace" class="btn btn-outline-secondary btn-sm">Capturer le visage</button>
                <div id="faceStatus" class="mt-2 text-muted">En attente de capture.</div>

                <form method="POST" action="" class="mt-3">
                    <input type="hidden" name="face_match" id="faceMatch" value="0">
                    <input type="hidden" name="face_distance" id="faceDistance" value="">
                    <button type="submit" class="btn btn-vege">Verifier</button>
                    <a href="login.php" class="btn btn-outline-secondary">Annuler</a>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/face-api.js@0.22.2/dist/face-api.min.js"></script>
<script>
const faceStatus = document.getElementById('faceStatus');
const captureBtn = document.getElementById('captureFace');
const video = document.getElementById('faceVideo');
const canvas = document.getElementById('faceCanvas');
const faceMatch = document.getElementById('faceMatch');
const faceDistance = document.getElementById('faceDistance');
const modelUrl = '/Projet/assets/face-api';
const threshold = 0.55;
const storedDescriptor = <?php echo json_encode($faceDescriptor !== '' ? json_decode($faceDescriptor, true) : []); ?>;

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
    faceStatus.textContent = 'Analyse en cours...';
    const detection = await faceapi
        .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
        .withFaceLandmarks()
        .withFaceDescriptor();

    if (!detection) {
        faceStatus.textContent = 'Aucun visage detecte. Reessayez.';
        faceMatch.value = '0';
        return;
    }

    if (!Array.isArray(storedDescriptor) || storedDescriptor.length === 0) {
        faceStatus.textContent = 'Face ID non configure pour ce compte.';
        faceMatch.value = '0';
        return;
    }

    const distance = faceapi.euclideanDistance(detection.descriptor, new Float32Array(storedDescriptor));
    faceDistance.value = distance.toFixed(4);

    if (distance <= threshold) {
        faceMatch.value = '1';
        faceStatus.textContent = 'Visage verifie avec succes.';
    } else {
        faceMatch.value = '0';
        faceStatus.textContent = 'Visage non reconnu. Reessayez.';
    }
}

captureBtn.addEventListener('click', captureFace);

(async () => {
    try {
        await loadModels();
        await startVideo();
    } catch (e) {
        faceStatus.textContent = 'Impossible de charger la camera ou les modeles.';
    }
})();

document.querySelector('form').addEventListener('submit', (e) => {
    if (faceMatch.value !== '1') {
        e.preventDefault();
        faceStatus.textContent = 'Veuillez capturer un visage valide avant de valider.';
    }
});
</script>

<?php require_once __DIR__ . '/partials/layout_bottom.php'; ?>
