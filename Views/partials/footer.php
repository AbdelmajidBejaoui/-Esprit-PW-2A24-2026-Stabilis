</div>

<div class="main-footer">
    <i class="fas fa-seedling"></i> Stabilis™ — synchronisation métabolique & empreinte contrôlée
    <br>
    <small>&copy; <?php echo date('Y'); ?> - Tous droits réservés</small>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf-autotable@3.8.2/dist/jspdf.plugin.autotable.min.js"></script>
<script>
window.exportStyledBackofficeTableToPdf = function (options) {
    const settings = Object.assign({
        tableSelector: '',
        title: 'Export Stabilis',
        filename: 'stabilis-export.pdf',
        excludeHeaders: ['actions'],
    }, options || {});

    if (!window.jspdf || !window.jspdf.jsPDF) {
        alert('Bibliotheque PDF indisponible.');
        return;
    }

    const table = document.querySelector(settings.tableSelector);
    if (!table) {
        alert('Tableau introuvable pour l export PDF.');
        return;
    }

    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({ orientation: 'landscape', unit: 'pt', format: 'a4' });

    if (typeof doc.autoTable !== 'function') {
        alert('Style PDF indisponible. Verifiez le chargement de jsPDF AutoTable.');
        return;
    }

    const excluded = settings.excludeHeaders.map(header => header.toLowerCase());
    const columns = Array.from(table.querySelectorAll('thead th'))
        .map((th, index) => ({
            index,
            text: th.textContent.trim().replace(/\s+/g, ' '),
            hasInput: !!th.querySelector('input, button, select'),
        }))
        .filter(column => column.text && !column.hasInput && !excluded.includes(column.text.toLowerCase()));

    const rows = Array.from(table.querySelectorAll('tbody tr'))
        .filter(row => row.offsetParent !== null || row.style.display !== 'none')
        .map(row => {
            const cells = Array.from(row.cells);
            return columns.map(column => (cells[column.index]?.innerText || cells[column.index]?.textContent || '')
                .replace(/\s+/g, ' ')
                .trim());
        })
        .filter(row => row.some(Boolean) && !row.join(' ').toLowerCase().includes('aucun'));

    if (!columns.length || !rows.length) {
        alert('Aucune donnee a exporter.');
        return;
    }

    const pageWidth = doc.internal.pageSize.getWidth();
    const pageHeight = doc.internal.pageSize.getHeight();
    const generatedAt = new Date().toLocaleString('fr-FR');

    doc.setFillColor(18, 159, 114);
    doc.rect(0, 0, pageWidth, 86, 'F');
    doc.setTextColor(255, 255, 255);
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(19);
    doc.text(settings.title, 40, 34);
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(10);
    doc.text('Stabilis Admin - Genere le ' + generatedAt, 40, 55);
    doc.text(rows.length + ' ligne(s) exportee(s)', 40, 72);

    doc.autoTable({
        head: [columns.map(column => column.text)],
        body: rows,
        startY: 108,
        theme: 'grid',
        margin: { left: 40, right: 40, bottom: 34 },
        styles: {
            font: 'helvetica',
            fontSize: 8,
            cellPadding: 6,
            overflow: 'linebreak',
            valign: 'middle',
            lineColor: [224, 232, 225],
            lineWidth: 0.6,
            textColor: [42, 48, 43],
        },
        headStyles: {
            fillColor: [26, 77, 58],
            textColor: [255, 255, 255],
            fontStyle: 'bold',
        },
        alternateRowStyles: {
            fillColor: [248, 252, 249],
        },
        didDrawPage: function () {
            const pageNumber = doc.internal.getNumberOfPages();
            doc.setFontSize(8);
            doc.setTextColor(120, 130, 122);
            doc.text('Stabilis BackOffice', 40, pageHeight - 18);
            doc.text('Page ' + pageNumber, pageWidth - 76, pageHeight - 18);
        },
    });

    doc.save(settings.filename);
    if (typeof showToast === 'function') {
        showToast('PDF genere avec succes', 'success');
    }
};

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
