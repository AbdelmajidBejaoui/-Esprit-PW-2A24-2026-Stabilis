<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Back-Office - Stabilis</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom Back-Office Styles -->
    <link rel="stylesheet" href="assets/css/back-style.css">
</head>
<body>
    <!-- Header Navigation -->
    <header class="back-header">
        <nav class="navbar navbar-expand-lg navbar-light container">
            <div class="container-fluid">
                <a class="navbar-brand fw-bold fs-3" href="../admin.php">
                    <i class="fas fa-leaf me-2"></i>Stabilis Admin
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="../admin.php"><i class="fas fa-layer-group me-1"></i>Hub admin</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../View/BackOffice/listUsers.php"><i class="fas fa-users me-1"></i>Utilisateurs</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="?entity=defis"><i class="fas fa-trophy me-1"></i>Défis</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="?entity=participations"><i class="fas fa-users me-1"></i>Participations</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="?entity=ai-generator"><i class="fas fa-wand-magic-sparkles me-1"></i>G&eacute;n&eacute;rateur IA</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="?entity=ai-weekly-story"><i class="fas fa-feather-pointed me-1"></i>R&eacute;cit IA</a>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link btn stats-btn-modern" id="statsBtn" style="background: none; border: none; cursor: pointer; position: relative; overflow: hidden;">
                                <i class="fas fa-chart-bar me-1"></i><span>Statistiques</span>
                                <span class="stats-pulse"></span>
                            </button>
                        </li>
                        <li class="nav-item ms-lg-2">
                            <a class="nav-link btn btn-success text-white px-3" href="../front-office.php">
                                <i class="fas fa-external-link-alt me-1"></i>Front Office
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="container">
    
    <!-- Statistics Modal - Enhanced Modern Design -->
    <div class="modal fade" id="statsModal" tabindex="-1" aria-labelledby="statsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content stats-modal-modern">
                <div class="modal-header stats-modal-header">
                    <div class="stats-header-content">
                        <h5 class="modal-title" id="statsModalLabel">
                            <i class="fas fa-chart-bar me-2"></i>Tableau de Bord - Statistiques
                        </h5>
                        <p class="stats-subtitle">Dernière mise à jour: <span id="statsTimestamp">--:--:--</span></p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body stats-modal-body">
                    <!-- Summary Cards - First Priority -->
                    <div class="stats-summary-grid">
                        <div class="summary-card total-card">
                            <div class="summary-icon"><i class="fas fa-tasks"></i></div>
                            <div class="summary-content">
                                <span class="summary-value" id="defisTotal">0</span>
                                <span class="summary-label">Défis totaux</span>
                            </div>
                        </div>
                        <div class="summary-card">
                            <div class="summary-icon"><i class="fas fa-users"></i></div>
                            <div class="summary-content">
                                <span class="summary-value" id="participationsTotal">0</span>
                                <span class="summary-label">Participations</span>
                            </div>
                        </div>
                        <div class="summary-card success-card">
                            <div class="summary-icon"><i class="fas fa-check-circle"></i></div>
                            <div class="summary-content">
                                <span class="summary-value" id="participationsReussi">0</span>
                                <span class="summary-label">Terminées</span>
                            </div>
                        </div>
                        <div class="summary-card warning-card">
                            <div class="summary-icon"><i class="fas fa-hourglass-half"></i></div>
                            <div class="summary-content">
                                <span class="summary-value" id="participationsEnCours">0</span>
                                <span class="summary-label">En cours</span>
                            </div>
                        </div>
                    </div>

                    <hr class="stats-divider">

                    <div class="row g-4">
                        <!-- Défis Stats -->
                        <div class="col-lg-6">
                            <div class="stats-card-modal premium">
                                <div class="stats-header">
                                    <div class="stats-title-group">
                                        <i class="fas fa-trophy stats-icon-large"></i>
                                        <h6>Statistiques Défis</h6>
                                    </div>
                                    <span class="badge bg-warning">Dynamique</span>
                                </div>
                                <div class="stats-content">
                                    <div class="progress-wrapper">
                                        <div class="progress-item">
                                            <div class="progress-header">
                                                <span class="progress-label">
                                                    <span class="badge bg-success me-2">Alimentaire</span>
                                                </span>
                                                <span class="progress-percent" id="defisAlimentPercent">0%</span>
                                            </div>
                                            <div class="progress" style="height: 8px;">
                                                <div class="progress-bar bg-success" id="defisAlimentBar" style="width: 0%;"></div>
                                            </div>
                                            <span class="progress-count" id="defisAliment">0</span>
                                        </div>
                                        <div class="progress-item">
                                            <div class="progress-header">
                                                <span class="progress-label">
                                                    <span class="badge bg-warning me-2">Entraînement</span>
                                                </span>
                                                <span class="progress-percent" id="defisEntrainementPercent">0%</span>
                                            </div>
                                            <div class="progress" style="height: 8px;">
                                                <div class="progress-bar bg-warning" id="defisEntrainementBar" style="width: 0%;"></div>
                                            </div>
                                            <span class="progress-count" id="defisEntrainement">0</span>
                                        </div>
                                        <div class="progress-item">
                                            <div class="progress-header">
                                                <span class="progress-label">
                                                    <span class="badge bg-info me-2">Compensation</span>
                                                </span>
                                                <span class="progress-percent" id="defisCompensationPercent">0%</span>
                                            </div>
                                            <div class="progress" style="height: 8px;">
                                                <div class="progress-bar bg-info" id="defisCompensationBar" style="width: 0%;"></div>
                                            </div>
                                            <span class="progress-count" id="defisCompensation">0</span>
                                        </div>
                                    </div>
                                    <div class="chart-container-modern">
                                        <canvas id="defisChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Participations Stats -->
                        <div class="col-lg-6">
                            <div class="stats-card-modal premium">
                                <div class="stats-header">
                                    <div class="stats-title-group">
                                        <i class="fas fa-chart-pie stats-icon-large"></i>
                                        <h6>Statistiques Participations</h6>
                                    </div>
                                    <span class="badge bg-info">Temps réel</span>
                                </div>
                                <div class="stats-content">
                                    <div class="progress-wrapper">
                                        <div class="progress-item">
                                            <div class="progress-header">
                                                <span class="progress-label">
                                                    <span class="badge bg-primary me-2">En cours</span>
                                                </span>
                                                <span class="progress-percent" id="participationsEnCoursPercent">0%</span>
                                            </div>
                                            <div class="progress" style="height: 8px;">
                                                <div class="progress-bar bg-primary" id="participationsEnCoursBar" style="width: 0%;"></div>
                                            </div>
                                            <span class="progress-count" id="participationsEnCoursCount">0</span>
                                        </div>
                                        <div class="progress-item">
                                            <div class="progress-header">
                                                <span class="progress-label">
                                                    <span class="badge bg-success me-2">Terminée</span>
                                                </span>
                                                <span class="progress-percent" id="participationsReussiPercent">0%</span>
                                            </div>
                                            <div class="progress" style="height: 8px;">
                                                <div class="progress-bar bg-success" id="participationsReussiBar" style="width: 0%;"></div>
                                            </div>
                                            <span class="progress-count" id="participationsReussiCount">0</span>
                                        </div>
                                        <div class="progress-item">
                                            <div class="progress-header">
                                                <span class="progress-label">
                                                    <span class="badge bg-danger me-2">Échouée</span>
                                                </span>
                                                <span class="progress-percent" id="participationsEchouePercent">0%</span>
                                            </div>
                                            <div class="progress" style="height: 8px;">
                                                <div class="progress-bar bg-danger" id="participationsEchoueBar" style="width: 0%;"></div>
                                            </div>
                                            <span class="progress-count" id="participationsEchoueCount">0</span>
                                        </div>
                                    </div>
                                    <div class="chart-container-modern">
                                        <canvas id="participationsChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer stats-modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    <button type="button" class="btn btn-primary" id="refreshStatsBtn">
                        <i class="fas fa-sync-alt me-1"></i>Actualiser
                    </button>
                </div>
            </div>
        </div>
    </div>
