</main>

<!-- Footer -->
<footer class="bg-light border-top mt-5 py-4">
    <div class="container">
        <div class="row g-4 mb-4">
            <!-- Brand -->
            <div class="col-lg-3 col-md-6">
                <h6 class="fw-bold mb-2">Stabilis™</h6>
                <p class="small text-muted mb-0">
                    Nutrition durable et performance intelligente
                </p>
            </div>

            <!-- Links -->
            <div class="col-lg-2 col-md-6">
                <h6 class="fw-bold mb-3">Navigation</h6>
                <ul class="list-unstyled small">
                    <li class="mb-1"><a href="index.php" class="text-decoration-none text-muted">Accueil</a></li>
                    <li class="mb-1"><a href="challenges.php" class="text-decoration-none text-muted">Défis</a></li>
                    <li class="mb-1"><a href="my-challenges.php" class="text-decoration-none text-muted">Mes défis</a></li>
                    <li class="mb-1"><a href="about.php" class="text-decoration-none text-muted">À propos</a></li>
                    <li class="mb-1"><a href="contact.php" class="text-decoration-none text-muted">Contact</a></li>
                </ul>
            </div>

            <!-- Contact -->
            <div class="col-lg-3 col-md-6">
                <h6 class="fw-bold mb-3">Contact</h6>
                <ul class="list-unstyled small">
                    <li class="mb-1"><a href="mailto:contact@stabilis.tn" class="text-decoration-none text-muted">contact@stabilis.tn</a></li>
                    <li class="mb-1"><span class="text-muted">+216 XX XXX XXX</span></li>
                    <li class="mb-1"><span class="text-muted">Tunis, Tunisie</span></li>
                </ul>
            </div>

            <!-- Project note -->
            <div class="col-lg-4 col-md-6">
                <h6 class="fw-bold mb-3">Projet</h6>
                <p class="small text-muted mb-0">
                    Démonstration autour de la nutrition durable.
                </p>
            </div>
        </div>

        <!-- Bottom -->
        <div class="border-top pt-4 d-flex flex-column flex-md-row justify-content-between align-items-center">
            <p class="small text-muted mb-2 mb-md-0">
                © <?php echo date('Y'); ?> Stabilis™. Tous droits réservés.
            </p>
            <small class="text-muted">
                Fait avec <i class="fas fa-heart text-danger"></i> pour une planète durable
            </small>
        </div>
    </div>
</footer>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="assets/js/challenge-modal.js"></script>

<script>
// Initialize AOS only when the CDN script is available.
if (window.AOS) {
    AOS.init({
        duration: 600,
        easing: 'ease-in-out-cubic',
        once: true
    });
}
</script>

</body>
</html>
