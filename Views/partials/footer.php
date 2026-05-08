</div>

<div class="main-footer">
    <i class="fas fa-seedling"></i> Stabilis™ — synchronisation métabolique & empreinte contrôlée
    <br>
    <small>&copy; <?php echo date('Y'); ?> - Tous droits réservés</small>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
function showToast(message, type) {
    const toast = document.createElement('div');
    toast.className = `toast-notification ${type}`;
    toast.innerHTML = message;
    document.body.appendChild(toast);
    setTimeout(() => {
        toast.style.animation = 'slideOutRight 0.4s ease-out';
        setTimeout(() => toast.remove(), 400);
    }, 3000);
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.sidebar-group').forEach(group => {
        const key = group.dataset.sidebarGroup;
        const toggle = group.querySelector('.sidebar-group-toggle');
        const hasActiveChild = !!group.querySelector('.sidebar-subnav a.active');
        const savedState = localStorage.getItem('stabilis-sidebar-' + key);

        if (savedState === 'open' || hasActiveChild) {
            group.classList.add('is-open');
            toggle?.classList.add('active');
            toggle?.setAttribute('aria-expanded', 'true');
        } else if (savedState === 'closed') {
            group.classList.remove('is-open');
            if (!hasActiveChild) {
                toggle?.classList.remove('active');
            }
            toggle?.setAttribute('aria-expanded', 'false');
        }

        toggle?.addEventListener('click', function() {
            const isOpen = group.classList.toggle('is-open');
            this.classList.toggle('active', isOpen || hasActiveChild);
            this.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            localStorage.setItem('stabilis-sidebar-' + key, isOpen ? 'open' : 'closed');
        });
    });

    document.querySelectorAll('.form-card, .table-card, .stat-card').forEach((el, i) => {
        el.classList.add('animate-fade-slide');
        el.style.animationDelay = (i * 0.05) + 's';
    });
    
    document.querySelectorAll('table tbody tr').forEach((row, i) => {
        row.classList.add('table-row');
        row.style.animationDelay = (i * 0.03) + 's';
    });
    
    document.querySelectorAll('.badge').forEach(badge => {
        const text = badge.textContent;
        const match = text.match(/(\d+)/);
        if(match && parseInt(match[0]) < 5 && parseInt(match[0]) > 0) {
            badge.classList.add('badge-stock-low');
        }
    });
    
    const exportButton = document.getElementById('export-stats-pdf');
    if (exportButton && window.jspdf && window.jspdf.jsPDF) {
        exportButton.addEventListener('click', function () {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF({ unit: 'pt', format: 'a4' });
            const stats = [
                { label: 'Produits au catalogue', value: document.getElementById('stat-produits')?.dataset.value || '0' },
                { label: 'Stock total', value: document.getElementById('stat-stock')?.dataset.value || '0' },
                { label: 'Commandes', value: document.getElementById('stat-commandes')?.dataset.value || '0' },
                { label: 'Chiffre d\'affaires', value: document.getElementById('stat-revenue')?.dataset.value || '0' },
            ];
            const now = new Date();
            doc.setFontSize(18);
            doc.text('Rapport Backoffice', 40, 50);
            doc.setFontSize(11);
            doc.text(`Date : ${now.toLocaleString('fr-FR')}`, 40, 72);
            let y = 105;
            stats.forEach(stat => {
                doc.setFontSize(12);
                doc.text(`${stat.label} : ${stat.value}`, 40, y);
                y += 20;
            });
            y += 10;
            doc.setFontSize(14);
            doc.text('Points clés', 40, y);
            y += 24;
            const pendingOrders = document.querySelector('.badge-success')?.textContent || '';
            const lowStock = document.querySelector('.badge-warning')?.textContent || '';
            if (pendingOrders) {
                doc.setFontSize(12);
                doc.text(pendingOrders, 40, y);
                y += 18;
            }
            if (lowStock) {
                doc.setFontSize(12);
                doc.text(lowStock, 40, y);
            }
            doc.save('rapport-backoffice.pdf');
            showToast('PDF généré avec succès', 'success');
        });
    }
    
    const urlParams = new URLSearchParams(window.location.search);
    if(urlParams.get('success') === '1') showToast(' Opération réussie !', 'success');
    else if(urlParams.get('deleted') === '1') showToast(' Suppression effectuée', 'info');
    else if(urlParams.get('updated') === '1') showToast(' Modification réussie', 'success');
});
</script>
</body>
</html>
