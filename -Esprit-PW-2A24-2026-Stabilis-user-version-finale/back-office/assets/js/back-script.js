// Back-office Scripts
document.addEventListener('DOMContentLoaded', function () {
    // Confirm delete forms (legacy)
    const deleteForms = document.querySelectorAll('form[action*="delete"]');
    deleteForms.forEach(form => {
        form.addEventListener('submit', function (e) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer ce défi? Cette action est irréversible.')) {
                e.preventDefault();
            }
        });
    });

    // Form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function (e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.style.borderColor = '#dc3545';
                    isValid = false;
                } else {
                    field.style.borderColor = '#28a745';
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert('Veuillez remplir tous les champs obligatoires.');
            }
        });
    });

    // Auto-hide alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alertEl => {
        setTimeout(() => {
            alertEl.style.opacity = '0';
            setTimeout(() => alertEl.remove(), 300);
        }, 5000);
    });

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const href = this.getAttribute('href');
            const target = href ? document.querySelector(href) : null;
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Dashboard enhancements (only if table exists)
    const table = document.querySelector('.defis-table');
    if (!table) return;

    const tableBody = table.querySelector('tbody');
    if (!tableBody) return;

    const allRows = Array.from(tableBody.querySelectorAll('tr'));
    const searchInput = document.getElementById('searchInput');
    const typeFilter = document.getElementById('typeFilter');
    const statutFilter = document.getElementById('statutFilter');
    const sortBtn = document.getElementById('sortBtn');
    const sortModal = document.getElementById('sortModal');
    const sortOptionBtns = document.querySelectorAll('.sort-option-btn');
    const sortModalClose = sortModal?.querySelector('.btn-close');
    const selectAll = document.getElementById('selectAll');
    const prevPage = document.getElementById('prevPage');
    const nextPage = document.getElementById('nextPage');
    const exportPdfBtn = document.getElementById('exportPdfBtn');

    let filteredRows = [...allRows];
    let currentPage = 1;
    let currentSort = 'id-desc';
    const rowsPerPage = 8;

    function getRowCheckboxes() {
        return tableBody.querySelectorAll('.row-checkbox');
    }

    function sortRows(rows) {
        if (!currentSort) return rows;
        
        const sortValue = currentSort;
        const sortedRows = [...rows];

        if (sortValue.startsWith('id-')) {
            const order = sortValue.endsWith('asc') ? 'asc' : 'desc';
            sortedRows.sort((a, b) => {
                const idA = parseInt(a.dataset.id) || 0;
                const idB = parseInt(b.dataset.id) || 0;
                return order === 'asc' ? idA - idB : idB - idA;
            });
        } else if (sortValue.startsWith('date-')) {
            const order = sortValue.endsWith('asc') ? 'asc' : 'desc';
            sortedRows.sort((a, b) => {
                const dateA = new Date(a.dataset.date || 0);
                const dateB = new Date(b.dataset.date || 0);
                return order === 'asc' ? dateA - dateB : dateB - dateA;
            });
        } else if (sortValue.startsWith('progression-')) {
            const order = sortValue.endsWith('asc') ? 'asc' : 'desc';
            sortedRows.sort((a, b) => {
                const progA = parseInt(a.dataset.progression) || 0;
                const progB = parseInt(b.dataset.progression) || 0;
                return order === 'asc' ? progA - progB : progB - progA;
            });
        } else if (sortValue.startsWith('points-')) {
            // Extract numeric value from recompense field
            const order = sortValue.endsWith('asc') ? 'asc' : 'desc';
            sortedRows.sort((a, b) => {
                const pointsA = extractNumericValue(a);
                const pointsB = extractNumericValue(b);
                return order === 'asc' ? pointsA - pointsB : pointsB - pointsA;
            });
        }

        return sortedRows;
    }

    function extractNumericValue(row) {
        // Try to use data-recompense attribute first, then extract from cell
        const recompenseAttr = row.dataset.recompense;
        if (recompenseAttr) {
            const match = recompenseAttr.match(/\d+/);
            return match ? parseInt(match[0]) : 0;
        }
        // Fallback: try to extract numeric value from recompense column (5th column)
        const cells = row.querySelectorAll('td');
        if (cells.length >= 5) {
            const recompenseText = cells[5]?.textContent || '';
            const match = recompenseText.match(/\d+/);
            return match ? parseInt(match[0]) : 0;
        }
        return 0;
    }

    function renderTablePage() {
        const totalPages = Math.max(1, Math.ceil(filteredRows.length / rowsPerPage));
        if (currentPage > totalPages) currentPage = totalPages;

        const start = (currentPage - 1) * rowsPerPage;
        const end = start + rowsPerPage;

        // Hide all rows first
        allRows.forEach(row => {
            row.style.display = 'none';
        });

        // Show and reorder filtered rows
        const visibleRows = filteredRows.slice(start, end);
        visibleRows.forEach(row => {
            row.style.display = '';
            // Append to tbody to ensure correct DOM order
            tableBody.appendChild(row);
        });

        if (prevPage) {
            prevPage.parentElement.classList.toggle('disabled', currentPage <= 1);
        }
        if (nextPage) {
            nextPage.parentElement.classList.toggle('disabled', currentPage >= totalPages);
        }
    }

    function applyFilters() {
        const term = (searchInput?.value || '').toLowerCase().trim();
        const type = (typeFilter?.value || '').toLowerCase().trim();
        const statut = (statutFilter?.value || '').toLowerCase().trim();

        filteredRows = allRows.filter(row => {
            const id = (row.dataset.id || '').toLowerCase();
            const userid = (row.dataset.userid || '').toLowerCase();
            const nom = (row.dataset.nom || '').toLowerCase();
            const rowType = (row.dataset.type || '').toLowerCase();
            const rowStatut = (row.dataset.statut || '').toLowerCase();

            // Check ID/Name search
            const matchesSearch = !term || id.includes(term) || userid.includes(term) || nom.includes(term);
            const matchesType = !type || rowType === type;
            const matchesStatut = !statut || rowStatut === statut;

            return matchesSearch && matchesType && matchesStatut;
        });

        // Apply sorting
        filteredRows = sortRows(filteredRows);

        currentPage = 1;
        renderTablePage();
        updateSelectionState();
    }

    function updateSelectionState() {
        allRows.forEach(row => {
            const cb = row.querySelector('.row-checkbox');
            row.classList.toggle('selected', !!cb?.checked);
        });

        if (selectAll) {
            const visibleCheckboxes = filteredRows
                .filter(row => row.style.display !== 'none')
                .map(row => row.querySelector('.row-checkbox'))
                .filter(Boolean);

            const allVisibleChecked = visibleCheckboxes.length > 0 &&
                visibleCheckboxes.every(cb => cb.checked);

            selectAll.checked = allVisibleChecked;
        }
    }

    // Search + filter events
    if (searchInput) searchInput.addEventListener('input', applyFilters);
    if (typeFilter) typeFilter.addEventListener('change', applyFilters);
    if (statutFilter) statutFilter.addEventListener('change', applyFilters);

    // Sort Modal Handlers
    if (sortBtn && sortModal) {
        sortBtn.addEventListener('click', function () {
            sortModal.classList.add('active');
        });

        sortModalClose?.addEventListener('click', function () {
            sortModal.classList.remove('active');
        });

        sortModal.addEventListener('click', function (e) {
            if (e.target === sortModal) {
                sortModal.classList.remove('active');
            }
        });

        sortOptionBtns.forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                currentSort = this.dataset.sort;
                sortModal.classList.remove('active');
                applyFilters();
            });
        });
    }

    // Select all visible rows
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            filteredRows.forEach(row => {
                if (row.style.display !== 'none') {
                    const cb = row.querySelector('.row-checkbox');
                    if (cb) cb.checked = this.checked;
                }
            });
            updateSelectionState();
        });
    }

    // Per-row checkbox events
    getRowCheckboxes().forEach(cb => {
        cb.addEventListener('change', updateSelectionState);
    });

    // Single delete buttons
    const deleteBtns = table.querySelectorAll('.delete-btn');
    deleteBtns.forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const id = this.dataset.id;
            if (confirm(`Confirmer la suppression de l'element #${id} ?`)) {
                const targetHref = this.getAttribute('href');
                window.location.href = targetHref && targetHref !== '#' ? targetHref : `index.php?action=delete&id=${id}`;
            }
        });
    });

    // Refresh
    const refreshBtn = document.getElementById('refreshBtn');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function () {
            window.location.reload();
        });
    }

    if (exportPdfBtn) {
        exportPdfBtn.addEventListener('click', function () {
            exportVisibleTableToPdf(table, this.dataset.exportTitle || 'Export back-office');
        });
    }

    function exportVisibleTableToPdf(sourceTable, title) {
        const jsPdfNamespace = window.jspdf;
        if (!jsPdfNamespace?.jsPDF) {
            alert('Export PDF indisponible. Verifiez la connexion aux bibliotheques PDF.');
            return;
        }

        const exportableColumnIndexes = Array.from(sourceTable.querySelectorAll('thead th'))
            .map((th, index) => ({ text: th.textContent.trim(), index, hasInput: !!th.querySelector('input') }))
            .filter(col => col.text && col.text.toLowerCase() !== 'actions' && !col.hasInput);

        const headers = exportableColumnIndexes.map(col => col.text);
        const rows = Array.from(sourceTable.querySelectorAll('tbody tr'))
            .filter(row => row.style.display !== 'none')
            .map(row => {
                const cells = row.querySelectorAll('td');
                return exportableColumnIndexes.map(col => (cells[col.index]?.innerText || cells[col.index]?.textContent || '')
                    .replace(/\s+/g, ' ')
                    .trim());
            });

        if (rows.length === 0) {
            alert('Aucune ligne visible a exporter.');
            return;
        }

        const doc = new jsPdfNamespace.jsPDF({ orientation: 'landscape', unit: 'pt', format: 'a4' });
        const generatedAt = new Date().toLocaleString('fr-FR');

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(16);
        doc.text(title, 40, 42);
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(9);
        doc.setTextColor(95, 95, 95);
        doc.text(`Stabilis Admin - ${generatedAt}`, 40, 58);
        doc.text(`${rows.length} ligne(s) visible(s) exportee(s)`, 40, 72);

        doc.autoTable({
            head: [headers],
            body: rows,
            startY: 92,
            theme: 'grid',
            styles: {
                font: 'helvetica',
                fontSize: 8,
                cellPadding: 6,
                overflow: 'linebreak',
                valign: 'middle'
            },
            headStyles: {
                fillColor: [16, 185, 129],
                textColor: 255,
                fontStyle: 'bold'
            },
            alternateRowStyles: {
                fillColor: [248, 250, 252]
            },
            margin: { left: 40, right: 40 },
            didDrawPage: function (data) {
                const pageCount = doc.internal.getNumberOfPages();
                doc.setFontSize(8);
                doc.setTextColor(120);
                doc.text(`Page ${data.pageNumber} / ${pageCount}`, doc.internal.pageSize.width - 86, doc.internal.pageSize.height - 24);
            }
        });

        const fileName = `${title.toLowerCase().replace(/[^a-z0-9]+/gi, '-')}-${new Date().toISOString().slice(0, 10)}.pdf`;
        doc.save(fileName);
    }

    // Pagination controls
    if (prevPage) {
        prevPage.addEventListener('click', function (e) {
            e.preventDefault();
            if (currentPage > 1) {
                currentPage--;
                renderTablePage();
                updateSelectionState();
            }
        });
    }

    if (nextPage) {
        nextPage.addEventListener('click', function (e) {
            e.preventDefault();
            const totalPages = Math.max(1, Math.ceil(filteredRows.length / rowsPerPage));
            if (currentPage < totalPages) {
                currentPage++;
                renderTablePage();
                updateSelectionState();
            }
        });
    }

    // Init
    applyFilters();
    updateSelectionState();
});
