<?php
if (!isset($pageTitle)) {
    $pageTitle = 'Utilisateurs';
}

$title = $pageTitle . ' | Stabilis Admin';
require_once __DIR__ . '/../../../partials/header.php';
?>

<style>
.user-admin-page {
    display: grid;
    gap: 18px;
}
.user-admin-page .card {
    background: var(--bg-elevated);
    border: 1px solid var(--border-light);
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-md);
    overflow: hidden;
}
.user-admin-page .card-header,
.user-admin-page .card-footer {
    background: #fff;
    border-color: var(--border-light);
    padding: 18px 20px;
}
.user-admin-page .card-title,
.user-admin-page h3 {
    margin: 0;
    color: var(--text-primary);
    font-size: 20px;
    font-weight: 700;
}
.user-admin-page .card-body {
    padding: 20px;
}
.user-admin-page .table-responsive.p-0 {
    padding: 0 !important;
}
.user-admin-page .table {
    width: 100%;
    border-collapse: collapse;
    margin: 0;
}
.user-admin-page .table th,
.user-admin-page .table td {
    padding: 14px 16px;
    border-bottom: 1px solid var(--border-light);
    text-align: left;
    vertical-align: middle;
    color: var(--text-primary);
}
.user-admin-page .table th {
    background: var(--accent-herb-light);
    color: var(--accent-herb-dark);
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
}
.user-admin-page .form-control {
    min-height: 42px;
    border: 1px solid var(--border-light);
    border-radius: 10px;
    padding: 10px 12px;
    font-family: inherit;
}
.user-admin-page .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    min-height: 38px;
    padding: 9px 13px;
    border-radius: 999px;
    border: 1px solid transparent;
    color: var(--text-primary);
    text-decoration: none;
    font-weight: 700;
    cursor: pointer;
}
.user-admin-page .btn-sm,
.user-admin-page .btn-xs {
    min-height: 32px;
    padding: 7px 10px;
    font-size: 12px;
}
.user-admin-page .btn-primary,
.user-admin-page .btn-success {
    background: var(--accent-herb);
    border-color: var(--accent-herb);
    color: #fff;
}
.user-admin-page .btn-danger {
    background: #B94A48;
    border-color: #B94A48;
    color: #fff;
}
.user-admin-page .btn-warning {
    background: var(--accent-earth-light);
    border-color: var(--accent-earth);
    color: var(--accent-earth-dark);
}
.user-admin-page .btn-dark {
    background: var(--text-primary);
    border-color: var(--text-primary);
    color: #fff;
}
.user-admin-page .btn-default,
.user-admin-page .btn-outline-secondary {
    background: #fff;
    border-color: var(--border-light);
}
.user-admin-page .badge {
    display: inline-flex;
    border-radius: 999px;
    padding: 5px 10px;
    font-size: 12px;
    font-weight: 800;
}
.user-admin-page .badge-success {
    background: var(--accent-herb-light);
    color: var(--accent-herb-dark);
}
.user-admin-page .badge-secondary {
    background: var(--border-light);
    color: var(--text-secondary);
}
.user-admin-page .pagination {
    display: flex;
    gap: 6px;
    list-style: none;
    padding: 0;
}
.user-admin-page .page-link {
    display: inline-flex;
    min-width: 34px;
    height: 34px;
    align-items: center;
    justify-content: center;
    border: 1px solid var(--border-light);
    border-radius: 10px;
    color: var(--text-primary);
    text-decoration: none;
}
.user-admin-page .page-item.active .page-link {
    background: var(--accent-herb);
    color: #fff;
}
.user-admin-page .d-flex { display: flex; }
.user-admin-page .justify-content-between { justify-content: space-between; }
.user-admin-page .align-items-center { align-items: center; }
.user-admin-page .flex-wrap { flex-wrap: wrap; }
.user-admin-page .float-left { float: left; }
.user-admin-page .float-right { float: right; }
.user-admin-page .text-muted { color: var(--text-secondary); }
.user-admin-page .text-center { text-align: center; }
.user-admin-page .mb-2 { margin-bottom: 8px; }
.user-admin-page .mb-sm-0 { margin-bottom: 0; }
.user-admin-page .ml-2 { margin-left: 8px; }
.user-admin-page .mr-1 { margin-right: 4px; }
.user-admin-page .mr-2 { margin-right: 8px; }

.user-admin-page .users-list-toolbar {
    gap: 14px;
}

.user-admin-page .users-list-actions {
    gap: 8px;
    margin-left: auto;
}

.user-admin-page .users-list-search {
    margin: 0;
}

.user-admin-page .users-list-search .input-group {
    display: flex;
    width: 330px;
}

.user-admin-page .users-list-search .form-control {
    min-height: 38px;
    height: 38px;
    border-radius: 4px 0 0 4px;
    background: #fff;
}

.user-admin-page .users-list-search .input-group-append {
    display: flex;
}

.user-admin-page .users-list-search .btn {
    min-width: 40px;
    min-height: 38px;
    height: 38px;
    padding: 0 11px;
    border-radius: 0 4px 4px 0;
}

.user-admin-page .users-list-main-btn {
    min-height: 38px;
    height: 38px;
    border-radius: 12px;
    padding: 8px 14px;
    white-space: nowrap;
}

.user-admin-page .users-list-row-actions {
    white-space: nowrap;
}

.user-admin-page .users-list-row-actions .btn {
    width: 27px;
    min-width: 27px;
    height: 27px;
    min-height: 27px;
    padding: 0;
    border-radius: 4px;
    gap: 0;
}

.user-admin-page .users-list-row-actions .btn + .btn {
    margin-left: 4px;
}

.user-admin-page .users-list-row-actions .btn i {
    font-size: 13px;
    margin: 0;
}

@media (max-width: 980px) {
    .user-admin-page .users-list-actions {
        width: 100%;
        justify-content: flex-start;
        flex-wrap: wrap;
    }

    .user-admin-page .users-list-search .input-group {
        width: min(100%, 330px);
    }
}
</style>

<div class="user-admin-page">
