<?php
if (session_status() === PHP_SESSION_NONE) session_start();
function frontIsLoggedIn(): bool { return isset($_SESSION['user_id']); }
function frontUser(): array { return $_SESSION['user_data'] ?? []; }
function frontUserId(): int { return (int)($_SESSION['user_id'] ?? 0); }
function requireLogin(): void {
    if (!frontIsLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}
function flash(string $type, string $msg): void { $_SESSION['flash'] = ['type'=>$type,'msg'=>$msg]; }
function getFlash(): ?array { $f=$_SESSION['flash']??null; unset($_SESSION['flash']); return $f; }
