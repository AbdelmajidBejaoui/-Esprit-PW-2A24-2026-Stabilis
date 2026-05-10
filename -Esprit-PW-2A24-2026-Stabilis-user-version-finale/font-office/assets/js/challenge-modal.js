class ChallengeModal {
    constructor() {
        this.overlay = document.querySelector('.challenge-modal-overlay');
        this.closeBtn = document.querySelector('.modal-close');
        this.cancelBtn = document.querySelector('.modal-cancel');
        this.submitBtn = document.getElementById('submitParticipation');
        this.form = document.getElementById('participationForm');
        this.currentChallenge = null;

        this.init();
    }

    init() {
        if (!this.overlay || !this.closeBtn || !this.cancelBtn || !this.submitBtn || !this.form) {
            return;
        }

        document.querySelectorAll('.challenge-trigger').forEach((button) => {
            button.addEventListener('click', (event) => this.open(event.currentTarget));
        });

        this.closeBtn.addEventListener('click', () => this.close());
        this.cancelBtn.addEventListener('click', () => this.close());
        this.overlay.addEventListener('click', (event) => {
            if (event.target === this.overlay) {
                this.close();
            }
        });
        this.submitBtn.addEventListener('click', (event) => this.submit(event));

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && this.overlay.classList.contains('active')) {
                this.close();
            }
        });
    }

    open(button) {
        this.currentChallenge = {
            id: button.dataset.challengeId,
            title: button.dataset.challengeTitle,
            objective: button.dataset.challengeObjective,
            type: button.dataset.challengeType,
            reward: button.dataset.challengeReward
        };

        this.fillModal();
        this.overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    fillModal() {
        const challenge = this.currentChallenge;
        document.getElementById('modalTitle').textContent = challenge.title;
        document.getElementById('modalDescription').textContent = challenge.objective;
        document.getElementById('modalReward').textContent = this.formatReward(challenge.reward);

        const typeLabels = {
            aliment: 'Alimentation',
            entrainement: 'Entrainement',
            compensation: 'Compensation'
        };
        const typeBadge = document.getElementById('modalType');
        typeBadge.textContent = typeLabels[challenge.type] || challenge.type || 'Defi';
        typeBadge.className = `challenge-type-badge type-${challenge.type || 'aliment'}`;

        const difficulty = this.getDifficulty(challenge.reward);
        const difficultyBadge = document.getElementById('modalDifficulty');
        difficultyBadge.textContent = difficulty.label;
        difficultyBadge.className = `difficulty-badge ${difficulty.className}`;

        const error = document.getElementById('participationFormError');
        if (error) {
            error.style.display = 'none';
            error.textContent = '';
        }
    }

    getDifficulty(reward) {
        const points = parseInt(reward, 10) || 0;
        if (points > 150) {
            return { label: 'Avance', className: 'difficulty-hard' };
        }
        if (points > 100) {
            return { label: 'Intermediaire', className: 'difficulty-medium' };
        }
        return { label: 'Accessible', className: 'difficulty-easy' };
    }

    formatReward(reward) {
        const value = String(reward || '').trim();
        if (!value) {
            return '0 point';
        }
        return /\bpoints?\b|pts\b/i.test(value) ? value : `${value} points`;
    }

    async submit(event) {
        event.preventDefault();

        if (!this.form.checkValidity()) {
            this.form.reportValidity();
            return;
        }

        const formData = new FormData(this.form);
        const payload = {
            id_utilisateur: parseInt(formData.get('id_utilisateur'), 10),
            id_defi: parseInt(this.currentChallenge.id, 10)
        };

        this.setBusy(true);

        try {
            const response = await fetch('../app/api/create-participation.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.error || 'Impossible de demarrer ce defi.');
            }

            this.showMessage('Defi demarre. Ajoutez ensuite une preuve depuis Mes defis.', 'success');
            setTimeout(() => {
                this.close();
                window.location.reload();
            }, 1200);
        } catch (error) {
            this.showMessage(error.message, 'danger');
            this.setBusy(false);
        }
    }

    setBusy(isBusy) {
        this.submitBtn.disabled = isBusy;
        this.submitBtn.innerHTML = isBusy
            ? '<i class="fas fa-spinner fa-spin me-2"></i>Demarrage...'
            : '<i class="fas fa-play me-2"></i>Demarrer le defi';
    }

    showMessage(message, type) {
        const error = document.getElementById('participationFormError');
        if (!error) {
            return;
        }
        error.className = `alert alert-${type}`;
        error.textContent = message;
        error.style.display = 'block';
    }

    close() {
        this.overlay.classList.remove('active');
        document.body.style.overflow = 'auto';
        this.form.reset();
        this.setBusy(false);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new ChallengeModal();
});
