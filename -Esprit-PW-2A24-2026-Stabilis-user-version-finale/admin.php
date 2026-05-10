<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Back Office | Stabilis</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="back-office/assets/css/back-style.css">
    <style>
        body {
            min-height: 100vh;
            margin: 0;
            font-family: Inter, Arial, sans-serif;
            color: #102016;
            background: linear-gradient(135deg, #f0fdf4 0%, #f0f9ff 50%, #faf5ff 100%);
        }

        .admin-shell {
            min-height: 100vh;
            display: grid;
            align-content: center;
            padding: 48px 0;
        }

        .admin-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        .admin-card {
            border: 1px solid #dce9df;
            border-radius: 16px;
            background: #fff;
            padding: 1.5rem;
            box-shadow: 0 18px 45px rgba(16, 32, 22, 0.08);
        }

        .icon-box {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #16a34a;
            background: #eef8ef;
            margin-bottom: 1rem;
        }

        @media (max-width: 800px) {
            .admin-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <main class="admin-shell">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mb-4">
                <div>
                    <span class="badge text-bg-success mb-2">Back office unifie</span>
                    <h1 class="display-5 fw-bold mb-1">Choisir une gestion</h1>
                    <p class="text-muted mb-0">Un seul point d'entree admin, avec les fonctionnalites de chaque teammate intactes.</p>
                </div>
                <a class="btn btn-outline-dark" href="front-office.php">
                    <i class="fa-solid fa-globe me-2"></i>Front office
                </a>
            </div>

            <div class="admin-grid">
                <article class="admin-card">
                    <span class="icon-box"><i class="fa-solid fa-users fa-lg"></i></span>
                    <h2 class="h4 fw-bold">Gestion utilisateurs</h2>
                    <p class="text-muted">Liste, ajout, modification, blocage, suppression, statistiques et export PDF des utilisateurs.</p>
                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-success" href="View/BackOffice/listUsers.php">Ouvrir</a>
                        <a class="btn btn-outline-secondary" href="View/BackOffice/addUser.php">Ajouter</a>
                        <a class="btn btn-outline-secondary" href="View/BackOffice/statistics.php">Statistiques</a>
                    </div>
                </article>

                <article class="admin-card">
                    <span class="icon-box"><i class="fa-solid fa-trophy fa-lg"></i></span>
                    <h2 class="h4 fw-bold">Gestion des defis</h2>
                    <p class="text-muted">Defis, participations, preuves, progression, generation IA, recit IA et statistiques de l'activite.</p>
                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-success" href="back-office/index.php?entity=defis">Defis</a>
                        <a class="btn btn-outline-secondary" href="back-office/index.php?entity=participations">Participations</a>
                        <a class="btn btn-outline-secondary" href="back-office/index.php?entity=ai-generator">IA</a>
                    </div>
                </article>
            </div>
        </div>
    </main>
</body>
</html>
