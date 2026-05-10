<?php require_once __DIR__ . '/../layout/header.php'; ?>

<style>
    .ai-generator-shell {
        display: grid;
        gap: 1.5rem;
    }

    .ai-generator-hero {
        border-radius: 18px;
        padding: 1.5rem;
        color: #fff;
        background: linear-gradient(135deg, #0f766e 0%, #2563eb 100%);
        box-shadow: 0 18px 45px rgba(15, 118, 110, 0.25);
    }

    .ai-generator-panel,
    .ai-draft-card {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);
    }

    .ai-generator-panel {
        padding: 1.25rem;
    }

    .ai-draft-card {
        padding: 1rem;
    }

    .ai-draft-toolbar {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        align-items: center;
        flex-wrap: wrap;
    }

    .ai-status {
        min-height: 1.5rem;
        font-weight: 600;
    }

    .ai-empty-state {
        border: 1px dashed rgba(15, 23, 42, 0.18);
        border-radius: 14px;
        padding: 2rem;
        text-align: center;
        color: #64748b;
        background: #f8fafc;
    }

    .recent-defis-table td {
        vertical-align: middle;
    }
</style>

<div class="ai-generator-shell">
    <section class="ai-generator-hero">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <span class="badge bg-light text-primary mb-3">
                    <i class="fas fa-lock me-1"></i>R&eacute;serv&eacute; au back-office
                </span>
                <h1 class="text-white mb-2">G&eacute;n&eacute;rateur IA de d&eacute;fis</h1>
                <p class="mb-0 text-white-50">
                    G&eacute;n&eacute;rez des d&eacute;fis Stabilis avec la m&ecirc;me structure que la table defis.
                </p>
            </div>
            <div class="text-white-50 small text-end">
                <div><i class="fas fa-key me-1"></i>Cl&eacute; API c&ocirc;t&eacute; serveur</div>
                <div><i class="fas fa-database me-1"></i>Enregistrement dans defis</div>
            </div>
        </div>
    </section>

    <section class="ai-generator-panel">
        <form id="aiGeneratorForm" class="row g-3">
            <div class="col-lg-4">
                <label for="topic" class="form-label fw-semibold">Sujet *</label>
                <input type="text" class="form-control" id="topic" name="topic" maxlength="120" required placeholder="Repas de saison, z&eacute;ro d&eacute;chet, marche quotidienne...">
            </div>
            <div class="col-lg-2">
                <label for="difficulty" class="form-label fw-semibold">Difficult&eacute;</label>
                <select class="form-select" id="difficulty" name="difficulty">
                    <option value="beginner">D&eacute;butant</option>
                    <option value="easy">Facile</option>
                    <option value="medium" selected>Moyen</option>
                    <option value="hard">Difficile</option>
                    <option value="advanced">Avanc&eacute;</option>
                </select>
            </div>
            <div class="col-lg-2">
                <label for="count" class="form-label fw-semibold">Nombre</label>
                <input type="number" class="form-control" id="count" name="count" min="1" max="6" value="3">
            </div>
            <div class="col-lg-4">
                <label for="technology" class="form-label fw-semibold">Focus optionnel</label>
                <input type="text" class="form-control" id="technology" name="technology" maxlength="80" placeholder="Budget quotidien, routine simple, niveau d&eacute;butant...">
            </div>
            <div class="col-12">
                <div class="ai-draft-toolbar action-cluster">
                    <div class="toolbar-group">
                        <span class="toolbar-group-label">IA</span>
                        <div class="toolbar-actions">
                            <button type="submit" class="btn btn-primary" id="generateBtn">
                                <i class="fas fa-wand-magic-sparkles me-2"></i>G&eacute;n&eacute;rer
                            </button>
                            <button type="button" class="btn btn-outline-primary" id="regenerateBtn" disabled>
                                <i class="fas fa-rotate me-2"></i>Reg&eacute;n&eacute;rer
                            </button>
                        </div>
                    </div>
                    <div class="toolbar-group">
                        <span class="toolbar-group-label">Validation</span>
                        <div class="toolbar-actions">
                            <button type="button" class="btn btn-success" id="saveDraftBtn" disabled>
                                <i class="fas fa-floppy-disk me-2"></i>Enregistrer comme defis
                            </button>
                        </div>
                    </div>
                    <div class="ai-status text-muted" id="aiStatus"></div>
                </div>
            </div>
        </form>
    </section>

    <section id="draftPreview" class="ai-generator-panel">
        <div class="ai-empty-state">
            <i class="fas fa-lightbulb fa-2x mb-3 text-primary"></i>
            <h5 class="mb-2">Aucun defi genere pour le moment</h5>
            <p class="mb-0">Generez des defis, modifiez nom/type/objectif/recompense, puis enregistrez-les dans la table defis.</p>
        </div>
    </section>

    <section class="ai-generator-panel">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h4 mb-0">Derniers defis enregistres</h2>
            <span class="badge bg-secondary"><?php echo count($recentDefis); ?> affich&eacute;(s)</span>
        </div>

        <?php if (empty($recentDefis)): ?>
            <div class="text-muted">Aucun defi enregistre pour le moment.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover recent-defis-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Type</th>
                            <th>Objectif</th>
                            <th>Recompense</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentDefis as $defi): ?>
                            <tr>
                                <td>#<?php echo (int)$defi['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($defi['nom']); ?></strong></td>
                                <td><?php echo htmlspecialchars($defi['type']); ?></td>
                                <td><?php echo htmlspecialchars(mb_substr($defi['objectif'], 0, 80)); ?>...</td>
                                <td><?php echo htmlspecialchars($defi['recompense']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>

<script>
    const aiForm = document.getElementById('aiGeneratorForm');
    const generateBtn = document.getElementById('generateBtn');
    const regenerateBtn = document.getElementById('regenerateBtn');
    const saveDraftBtn = document.getElementById('saveDraftBtn');
    const preview = document.getElementById('draftPreview');
    const aiStatus = document.getElementById('aiStatus');

    let currentChallenges = [];

    aiForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        await generateChallenges();
    });

    regenerateBtn.addEventListener('click', generateChallenges);
    saveDraftBtn.addEventListener('click', saveChallenges);

    async function generateChallenges() {
        setBusy(true, 'Generation des defis...');
        saveDraftBtn.disabled = true;

        try {
            const payload = getPromptPayload();
            const data = await postJson('../app/api/generate-challenges.php', payload);
            currentChallenges = data.challenges || [];
            renderChallenges(currentChallenges);
            regenerateBtn.disabled = false;
            saveDraftBtn.disabled = currentChallenges.length === 0;
            setStatus(`${currentChallenges.length} defi(s) modifiable(s) genere(s).`, 'success');
        } catch (error) {
            setStatus(error.message || 'La generation a echoue.', 'danger');
        } finally {
            setBusy(false);
        }
    }

    async function saveChallenges() {
        const editedChallenges = collectChallengesFromPreview();
        if (editedChallenges.length === 0) {
            setStatus('Aucun defi a enregistrer.', 'warning');
            return;
        }

        setBusy(true, 'Enregistrement dans la table defis...');

        try {
            const data = await postJson('../app/api/save-generated-challenges.php', { challenges: editedChallenges });
            saveDraftBtn.disabled = true;
            setStatus(`${data.savedCount || editedChallenges.length} defi(s) enregistre(s). Actualisation...`, 'success');
            setTimeout(() => window.location.reload(), 800);
        } catch (error) {
            setStatus(error.message || 'L\'enregistrement a echoue.', 'danger');
        } finally {
            setBusy(false);
        }
    }

    function getPromptPayload() {
        return {
            topic: document.getElementById('topic').value.trim(),
            difficulty: document.getElementById('difficulty').value,
            count: Number(document.getElementById('count').value || 3),
            technology: document.getElementById('technology').value.trim()
        };
    }

    async function postJson(url, payload) {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Admin-Feature': 'ai-generator'
            },
            body: JSON.stringify(payload)
        });
        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.error || 'Request failed.');
        }

        return data;
    }

    function renderChallenges(challenges) {
        if (!challenges.length) {
            preview.innerHTML = '<div class="ai-empty-state">Aucun defi valide retourne.</div>';
            return;
        }

        preview.innerHTML = challenges.map((challenge, index) => `
            <article class="ai-draft-card mb-3" data-challenge-index="${index}">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="h5 mb-0">Defi ${index + 1}</h3>
                    <span class="badge bg-primary">Schema defis</span>
                </div>
                <div class="row g-3">
                    <div class="col-lg-7">
                        <label class="form-label fw-semibold">Nom</label>
                        <input class="form-control challenge-nom" maxlength="255" value="${escapeAttribute(challenge.nom)}">
                    </div>
                    <div class="col-lg-3">
                        <label class="form-label fw-semibold">Type</label>
                        <select class="form-select challenge-type">
                            <option value="aliment" ${challenge.type === 'aliment' ? 'selected' : ''}>aliment</option>
                            <option value="entrainement" ${challenge.type === 'entrainement' ? 'selected' : ''}>entrainement</option>
                            <option value="compensation" ${challenge.type === 'compensation' ? 'selected' : ''}>compensation</option>
                        </select>
                    </div>
                    <div class="col-lg-2">
                        <label class="form-label fw-semibold">Recompense</label>
                        <input class="form-control challenge-recompense" maxlength="255" value="${escapeAttribute(challenge.recompense)}">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Objectif</label>
                        <textarea class="form-control challenge-objectif" rows="5">${escapeHtml(challenge.objectif)}</textarea>
                    </div>
                </div>
            </article>
        `).join('');
    }

    function collectChallengesFromPreview() {
        return Array.from(preview.querySelectorAll('.ai-draft-card')).map((card) => ({
            nom: card.querySelector('.challenge-nom').value.trim(),
            type: card.querySelector('.challenge-type').value,
            objectif: card.querySelector('.challenge-objectif').value.trim(),
            recompense: card.querySelector('.challenge-recompense').value.trim()
        }));
    }

    function setBusy(isBusy, message = '') {
        generateBtn.disabled = isBusy;
        regenerateBtn.disabled = isBusy || currentChallenges.length === 0;
        if (isBusy) {
            setStatus(message, 'muted');
        }
    }

    function setStatus(message, type = 'muted') {
        aiStatus.className = `ai-status text-${type}`;
        aiStatus.textContent = message;
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function escapeAttribute(value) {
        return escapeHtml(value).replace(/`/g, '&#096;');
    }
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
