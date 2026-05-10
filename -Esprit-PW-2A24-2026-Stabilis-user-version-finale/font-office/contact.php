<?php
$page_title = "Contact";
include 'config.php';
include 'header.php';
?>

<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h1>Contactez-nous</h1>
            <p class="lead">Nous sommes là pour vous aider</p>
        </div>
        <div class="row">
            <div class="col-lg-6 mx-auto">
                <form>
                    <div class="form-group">
                        <label for="name">Nom</label>
                        <input type="text" class="form-control" id="name" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="email" required>
                    </div>
                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea class="form-control" id="message" rows="5" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Envoyer</button>
                </form>
            </div>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>
