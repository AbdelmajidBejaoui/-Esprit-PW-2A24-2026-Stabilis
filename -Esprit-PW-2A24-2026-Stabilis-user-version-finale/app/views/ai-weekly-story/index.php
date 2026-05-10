<?php require_once __DIR__ . '/../layout/header.php'; ?>

<style>
    .weekly-story-shell {
        display: grid;
        gap: 1.5rem;
    }

    .weekly-story-hero {
        border-radius: 18px;
        padding: 1.5rem;
        color: #fff;
        background: linear-gradient(135deg, #0f766e 0%, #2563eb 100%);
        box-shadow: 0 18px 45px rgba(15, 118, 110, 0.25);
    }

    .weekly-story-panel {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);
        padding: 1.25rem;
    }

    .story-output {
        min-height: 150px;
        border: 1px dashed rgba(15, 23, 42, 0.18);
        border-radius: 14px;
        padding: 1.25rem;
        background: #f8fafc;
        color: #334155;
        line-height: 1.7;
        font-size: 1rem;
        white-space: pre-wrap;
    }

    .story-status {
        min-height: 1.5rem;
        font-weight: 700;
    }

    .summary-grid-mini {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.75rem;
    }

    .summary-mini-card {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 12px;
        padding: 1rem;
        background: #f8fafc;
    }

    .summary-mini-value {
        display: block;
        font-size: 1.35rem;
        font-weight: 800;
        color: #0f766e;
    }

    @media (max-width: 768px) {
        .summary-grid-mini {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
</style>

<div class="weekly-story-shell">
    <section class="weekly-story-hero">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <span class="badge bg-light text-primary mb-3">
                    <i class="fas fa-lock me-1"></i>Réservé au back-office
                </span>
                <h1 class="text-white mb-2">Récit IA de la semaine</h1>
                <p class="mb-0 text-white-50">
                    Générez un résumé narratif court à partir des données hebdomadaires déjà agrégées.
                </p>
            </div>
            <div class="text-white-50 small text-end">
                <div><i class="fas fa-key me-1"></i>Clé API côté serveur</div>
                <div><i class="fas fa-chart-line me-1"></i>Données envoyées sous forme résumée</div>
            </div>
        </div>
    </section>

    <section class="weekly-story-panel">
        <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mb-3">
            <div>
                <h2 class="h4 mb-1">Récit IA de la semaine</h2>
                <p class="text-muted mb-0">Le récit n’est pas enregistré en base de données.</p>
            </div>
            <button type="button" class="btn btn-primary" id="generateWeeklyStoryBtn">
                <i class="fas fa-feather-pointed me-2"></i>Générer le récit
            </button>
        </div>

        <div class="story-status text-muted mb-3" id="weeklyStoryStatus"></div>
        <div class="story-output" id="weeklyStoryOutput">
            Cliquez sur « Générer le récit » pour créer un résumé professionnel de l’activité de la semaine.
        </div>
    </section>

    <section class="weekly-story-panel">
        <h2 class="h5 mb-3">Données résumées utilisées</h2>
        <div class="summary-grid-mini">
            <div class="summary-mini-card">
                <span class="summary-mini-value" id="weeklyActiveUsers">-</span>
                <span class="text-muted">Utilisateurs actifs</span>
            </div>
            <div class="summary-mini-card">
                <span class="summary-mini-value" id="weeklyCompletions">-</span>
                <span class="text-muted">Défis terminés</span>
            </div>
            <div class="summary-mini-card">
                <span class="summary-mini-value" id="weeklyPoints">-</span>
                <span class="text-muted">Points distribués</span>
            </div>
            <div class="summary-mini-card">
                <span class="summary-mini-value" id="weeklyTopUser">-</span>
                <span class="text-muted">Meilleur utilisateur</span>
            </div>
        </div>
    </section>
</div>

<script>
    const generateWeeklyStoryBtn = document.getElementById('generateWeeklyStoryBtn');
    const weeklyStoryStatus = document.getElementById('weeklyStoryStatus');
    const weeklyStoryOutput = document.getElementById('weeklyStoryOutput');

    generateWeeklyStoryBtn.addEventListener('click', generateWeeklyStory);

    async function generateWeeklyStory() {
        setWeeklyStoryBusy(true, 'Génération du récit en cours...');

        try {
            const response = await fetch('../app/api/generate-weekly-story.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Admin-Feature': 'ai-weekly-story'
                },
                body: JSON.stringify({})
            });
            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.error || 'La génération du récit a échoué.');
            }

            weeklyStoryOutput.textContent = data.story;
            updateWeeklySummary(data.summary || {});
            setWeeklyStoryStatus('Récit généré avec succès.', 'success');
        } catch (error) {
            setWeeklyStoryStatus(error.message || 'Erreur lors de la génération.', 'danger');
        } finally {
            setWeeklyStoryBusy(false);
        }
    }

    function updateWeeklySummary(summary) {
        const stats = summary.statistiques_hebdomadaires || {};
        document.getElementById('weeklyActiveUsers').textContent = stats.utilisateurs_actifs ?? '-';
        document.getElementById('weeklyCompletions').textContent = stats.defis_termines ?? '-';
        document.getElementById('weeklyPoints').textContent = stats.points_distribues ?? '-';
        document.getElementById('weeklyTopUser').textContent = summary.meilleur_utilisateur?.nom || '-';
    }

    function setWeeklyStoryBusy(isBusy, message = '') {
        generateWeeklyStoryBtn.disabled = isBusy;
        generateWeeklyStoryBtn.innerHTML = isBusy
            ? '<i class="fas fa-spinner fa-spin me-2"></i>Génération...'
            : '<i class="fas fa-feather-pointed me-2"></i>Générer le récit';

        if (isBusy) {
            setWeeklyStoryStatus(message, 'muted');
        }
    }

    function setWeeklyStoryStatus(message, type = 'muted') {
        weeklyStoryStatus.className = `story-status text-${type} mb-3`;
        weeklyStoryStatus.textContent = message;
    }
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
