</div>

<div class="main-footer">
    <i class="fas fa-seedling"></i> Stabilis™ — synchronisation métabolique & empreinte contrôlée
    <br>
    <small>&copy; <?php echo date('Y'); ?> - Tous droits réservés</small>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
    
    const urlParams = new URLSearchParams(window.location.search);
    if(urlParams.get('success') === '1') showToast(' Opération réussie !', 'success');
    else if(urlParams.get('deleted') === '1') showToast(' Suppression effectuée', 'info');
    else if(urlParams.get('updated') === '1') showToast(' Modification réussie', 'success');
});
</script>
</body>
</html>