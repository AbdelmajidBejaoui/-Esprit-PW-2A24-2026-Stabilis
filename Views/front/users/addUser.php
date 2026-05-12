<?php
require_once __DIR__ . '/partials/auth.php';
require_once __DIR__ . '/../../../Controllers/UserC.php';
// load site key if config exists
$siteKey = '';
$recfg = __DIR__ . '/../../../config/recaptcha.php';
if (file_exists($recfg)) {
    $cfg = include $recfg;
    $siteKey = $cfg['site_key'] ?? '';
}
require_once __DIR__ . '/../../../Models/User.php';

if (frontofficeIsLoggedIn()) {
    header('Location: updateUser.php');
    exit;
}

$userC = new UserC();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = $userC->validateRegistrationData($_POST);
    $faceErrors = $userC->validateFaceData($_POST, true);
    if (!empty($faceErrors)) {
        $errors = array_merge($errors, $faceErrors);
    }

    if (empty($errors)) {
        // verify reCAPTCHA server-side
        $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
        if (!$userC->verifyRecaptcha($recaptchaResponse)) {
            $errors[] = 'Veuillez confirmer que vous n\'etes pas un robot.';
        }

        if (empty($errors)) {
            $faceImage = $userC->decodeBase64Image($_POST['face_image_data'] ?? '');
            $faceDescriptor = trim($_POST['face_descriptor'] ?? '');

            $user = new User(
                null,
                trim($_POST['nom']),
                trim($_POST['email']),
                $_POST['password'],
                'client',
                trim($_POST['preference_alimentaire']),
                date('Y-m-d H:i:s'),
                0,
                $faceImage,
                $faceDescriptor
            );

            $newId = $userC->insertUser($user);
            if ($newId) {
                $userC->createEmailVerification($newId, trim($_POST['email']));
                header('Location: login.php?verify_email=1');
                exit;
            }
        }
    }
}

$pageTitle = 'Inscription';
$heroTitle = 'Create Your Athlete Profile';
$heroSubtitle = 'Rejoignez la communaute Stabilis';
$activePage = 'signup';
require_once __DIR__ . '/partials/layout_top.php';
?>

<div class="auth-shell" data-reveal="up">
    <div class="auth-panel" data-lift-hover>
        <aside class="auth-aside">
            <div>
                <span class="auth-kicker"><i class="fa-solid fa-wand-magic-sparkles"></i> Nouveau profil</span>
                <h2>Un compte fluide pour acheter, suivre et personnaliser.</h2>
                <p>Ton espace client relie boutique, preferences alimentaires et verification Face ID.</p>
            </div>
            <ul class="auth-aside-list">
                <li><i class="fa-solid fa-circle-check"></i> Creation en quelques champs</li>
                <li><i class="fa-solid fa-camera"></i> Face ID integre</li>
                <li><i class="fa-solid fa-seedling"></i> Experience Stabilis unifiee</li>
            </ul>
        </aside>

        <div class="auth-card">
            <h3>Inscription athlete</h3>
            <p class="auth-copy">Cree ton compte client et capture ton visage pour proteger l'acces.</p>
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="auth-form">
                    <div class="auth-grid">
                        <div class="form-group auth-field">
                            <label>Nom</label>
                            <input class="form-control" type="text" name="nom" autocomplete="name" value="<?php echo htmlspecialchars($_POST['nom'] ?? ''); ?>">
                        </div>

                        <div class="form-group auth-field">
                            <label>Email</label>
                            <input class="form-control" type="email" name="email" autocomplete="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="auth-grid">
                        <div class="form-group auth-field">
                            <label>Mot de passe</label>
                            <input class="form-control" type="password" name="password" autocomplete="new-password">
                        </div>

                        <div class="form-group auth-field">
                            <label>Preference alimentaire</label>
                            <input class="form-control" type="text" name="preference_alimentaire" value="<?php echo htmlspecialchars($_POST['preference_alimentaire'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-group auth-field">
                        <label>Face ID</label>
                        <div class="auth-face-box">
                            <div class="auth-face-video">
                                <video id="faceVideo" width="320" height="240" autoplay muted></video>
                            </div>
                            <canvas id="faceCanvas" width="320" height="240" class="d-none"></canvas>
                            <div class="auth-face-actions">
                                <p>Cadre ton visage, puis capture une fois la camera prete.</p>
                                <button type="button" id="captureFace" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-camera mr-1"></i> Capturer le visage</button>
                                <div id="faceStatus" class="text-muted">En attente de capture.</div>
                            </div>
                        </div>
                        <input type="hidden" name="face_image_data" id="faceImageData" value="<?php echo htmlspecialchars($_POST['face_image_data'] ?? ''); ?>">
                        <input type="hidden" name="face_descriptor" id="faceDescriptor" value="<?php echo htmlspecialchars($_POST['face_descriptor'] ?? ''); ?>">
                    </div>

                    <div class="auth-actions">
                        <button type="submit" class="btn btn-vege"><i class="fa-solid fa-user-plus mr-1"></i> S'inscrire</button>
                        <a href="login.php" class="btn btn-outline-secondary">J'ai deja un compte</a>
                    </div>
                    <?php if ($siteKey !== ''): ?>
                        <div class="mt-3">
                            <script src="https://www.google.com/recaptcha/api.js" async defer></script>
                            <div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars($siteKey); ?>"></div>
                        </div>
                    <?php endif; ?>
                </form>
        </div>
    </div>
</div>

<script src="https://unpkg.com/face-api.js@0.22.2/dist/face-api.min.js"></script>
<script>
const faceStatus = document.getElementById('faceStatus');
const captureBtn = document.getElementById('captureFace');
const video = document.getElementById('faceVideo');
const canvas = document.getElementById('faceCanvas');
const imageInput = document.getElementById('faceImageData');
const descriptorInput = document.getElementById('faceDescriptor');
const modelUrl = '/AdminLTE3/assets/face-api';

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
