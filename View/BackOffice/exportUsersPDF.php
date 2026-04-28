<?php
require_once __DIR__ . '/../../Controller/UserC.php';

$userC = new UserC();
$users = $userC->getAllUsersForExport();

// Using a simple approach: Generate table in HTML and allow browser to save as PDF
// This ensures compatibility without external libraries

header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: inline; filename="utilisateurs_' . date('Y-m-d') . '.pdf"');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Export Utilisateurs - NutriSmart</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 12px;
            color: #333;
            background: white;
            line-height: 1.4;
        }
        .container {
            max-width: 100%;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 3px solid #007bff;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 26px;
            margin-bottom: 5px;
            color: #007bff;
        }
        .header p {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }
        .date-info {
            text-align: right;
            margin-bottom: 15px;
            font-size: 11px;
            color: #666;
            border-right: 1px solid #ddd;
            padding-right: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        thead {
            background-color: #007bff;
            color: white;
        }
        th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            border: 1px solid #0056b3;
            font-size: 12px;
            text-transform: uppercase;
        }
        td {
            padding: 10px;
            border: 1px solid #ddd;
            font-size: 11px;
        }
        tbody tr:nth-child(odd) {
            background-color: #f9f9f9;
        }
        tbody tr:nth-child(even) {
            background-color: #ffffff;
        }
        tbody tr:hover {
            background-color: #f0f8ff;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 10px;
            text-transform: uppercase;
        }
        .status-active {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #ddd;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        .footer-info {
            margin-top: 10px;
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
        }
        .footer-item {
            margin: 5px 10px;
        }
        .print-note {
            text-align: center;
            padding: 10px;
            background-color: #e7f3ff;
            border: 1px solid #b3d9ff;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 11px;
            color: #0066cc;
        }
        .btn-back {
            display: inline-block;
            padding: 10px 20px;
            background-color: #6c757d;
            color: white !important;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 600;
            transition: background-color 0.3s ease;
            margin-bottom: 15px;
        }
        .btn-back:hover {
            background-color: #5a6268;
            text-decoration: none;
        }
        .btn-back:active {
            background-color: #545b62;
        }
        @media print {
            body {
                margin: 0;
                padding: 0;
                background: white;
            }
            .container {
                padding: 0;
            }
            .print-note {
                display: none;
            }
            .btn-back {
                display: none;
            }
            table {
                box-shadow: none;
            }
            page-break-after: auto;
        }
        @page {
            margin: 1cm;
            size: A4;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Liste des Utilisateurs</h1>
            <p>NutriSmart - Système de Gestion</p>
            <p>Rapport d'Export</p>
        </div>

        <div class="date-info">
            Généré le: <strong><?php echo date('d/m/Y'); ?></strong> à <strong><?php echo date('H:i:s'); ?></strong>
        </div>

        <div class="print-note">
            💡 Utilisez <strong>Ctrl + P</strong> ou le menu <strong>Fichier → Imprimer</strong> pour sauvegarder en PDF
        </div>

        <div style="margin-bottom: 15px;">
            <a href="listUsers.php" class="btn-back">
                <span style="margin-right: 5px;">←</span> Retour à la liste
            </a>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">ID</th>
                    <th style="width: 15%;">Nom</th>
                    <th style="width: 20%;">Email</th>
                    <th style="width: 10%;">Rôle</th>
                    <th style="width: 20%;">Préférence Alimentaire</th>
                    <th style="width: 18%;">Date d'inscription</th>
                    <th style="width: 12%;">Statut</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($users)): ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['id']); ?></td>
                            <td><?php echo htmlspecialchars($user['nom']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($user['role'])); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($user['preference_alimentaire'])); ?></td>
                            <td><?php echo htmlspecialchars($user['date_inscription']); ?></td>
                            <td>
                                <?php if ((int)$user['statut_compte'] === 1): ?>
                                    <span class="status-badge status-active">Actif</span>
                                <?php else: ?>
                                    <span class="status-badge status-inactive">Inactif</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: #999; padding: 30px;">
                            Aucun utilisateur à exporter
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="footer">
            <p>© 2026 NutriSmart - Tous droits réservés</p>
            <div class="footer-info">
                <div class="footer-item">
                    <strong>Total utilisateurs:</strong> <?php echo count($users); ?>
                </div>
                <div class="footer-item">
                    <strong>Date d'export:</strong> <?php echo date('d/m/Y H:i'); ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-trigger print dialog when page loads
        window.addEventListener('load', function() {
            setTimeout(function() {
                window.print();
            }, 500);
        });
    </script>
</body>
</html>

