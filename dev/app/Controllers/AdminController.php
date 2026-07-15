<?php declare(strict_types=1);
namespace App\Controllers;

use App\Core\View;
use App\Core\Database;

/**
 * Admin auth: login, logout, dashboard.
 * All other admin controllers extend this for auth checking.
 */
class AdminController
{
    /** Require valid admin session. Redirects to login if not authenticated. */
    protected function requireAuth(): array
    {
        $token = $_COOKIE['admin_session'] ?? '';
        if (empty($token)) {
            redirect('/admin/login');
        }

        $session = Database::fetch(
            "SELECT s.*, u.username, u.display_name, u.role, u.is_active
             FROM admin_sessions s
             JOIN admin_users u ON s.admin_user_id = u.id
             WHERE s.session_token = ? AND s.expires_at > NOW() AND u.is_active = 1",
            [$token]
        );

        if (!$session) {
            // Expired or invalid — clear cookie
            setcookie('admin_session', '', time() - 3600, '/', '', false, true);
            redirect('/admin/login');
        }

        return $session;
    }

    /** Log an audit event. */
    protected function audit(string $action, ?string $entityType = null, ?int $entityId = null, ?string $entityLabel = null, ?array $oldValues = null, ?array $newValues = null): void
    {
        $user = $this->requireAuth();
        Database::insert(
            "INSERT INTO audit_log (admin_user_id, action, entity_type, entity_id, entity_label, old_values, new_values, ip_address, user_agent)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $user['admin_user_id'],
                $action,
                $entityType,
                $entityId,
                $entityLabel,
                $oldValues ? json_encode($oldValues) : null,
                $newValues ? json_encode($newValues) : null,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
            ]
        );
    }

    /** GET /admin — dashboard */
    public function dashboard(array $params = []): void
    {
        $user = $this->requireAuth();

        $counts = [
            'articles'    => (int)Database::fetchColumn("SELECT COUNT(*) FROM articles WHERE is_active = 1"),
            'cases'       => (int)Database::fetchColumn("SELECT COUNT(*) FROM cases WHERE is_active = 1"),
            'organizations'=> (int)Database::fetchColumn("SELECT COUNT(*) FROM organizations WHERE is_active = 1"),
            'documents'   => (int)Database::fetchColumn("SELECT COUNT(*) FROM documents"),
            'submissions' => (int)Database::fetchColumn("SELECT COUNT(*) FROM submissions WHERE status = 'pending'"),
            'updates'     => (int)Database::fetchColumn("SELECT COUNT(*) FROM updates WHERE is_active = 1"),
        ];

        View::renderAdmin('dashboard', [
            'page_title' => 'Admin Dashboard',
            'user'       => $user,
            'counts'     => $counts,
        ]);
    }

    /** GET /admin/login */
    public function login(array $params = []): void
    {
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf()) {
                $error = 'Invalid request. Please try again.';
            } else {
                $username = trim($_POST['username'] ?? '');
                $password = $_POST['password'] ?? '';
                $user = Database::fetch(
                    "SELECT * FROM admin_users WHERE (username = ? OR email = ?) AND is_active = 1",
                    [$username, $username]
                );
                if ($user) {
                    if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                        $error = 'Account is temporarily locked.';
                    } elseif (verify_password($password, $user['password_hash'])) {
                        $token = bin2hex(random_bytes(32));
                        $expires = date('Y-m-d H:i:s', time() + ADMIN_SESSION_HOURS * 3600);
                        Database::insert(
                            "INSERT INTO admin_sessions (admin_user_id, session_token, ip_address, user_agent, expires_at) VALUES (?,?,?,?,?)",
                            [$user['id'], $token, $_SERVER['REMOTE_ADDR']??null, $_SERVER['HTTP_USER_AGENT']??null, $expires]
                        );
                        Database::execute("UPDATE admin_users SET last_login_at=NOW(), last_login_ip=?, login_attempts=0, locked_until=NULL WHERE id=?", [$_SERVER['REMOTE_ADDR']??null, $user['id']]);
                        Database::insert("INSERT INTO audit_log (admin_user_id, action, ip_address, user_agent) VALUES (?,'login',?,?)", [$user['id'], $_SERVER['REMOTE_ADDR']??null, $_SERVER['HTTP_USER_AGENT']??null]);
                        setcookie('admin_session', $token, ['expires'=>time()+ADMIN_SESSION_HOURS*3600, 'path'=>'/', 'httponly'=>true, 'samesite'=>'Lax']);
                        redirect('/admin');
                    } else {
                        $attempts = ($user['login_attempts']??0) + 1;
                        $lockedUntil = $attempts >= MAX_LOGIN_ATTEMPTS ? date('Y-m-d H:i:s', time()+LOGIN_LOCKOUT_MINUTES*60) : null;
                        Database::execute("UPDATE admin_users SET login_attempts=?, locked_until=? WHERE id=?", [$attempts, $lockedUntil, $user['id']]);
                        $error = 'Invalid credentials.';
                    }
                } else {
                    $error = 'Invalid credentials.';
                }
            }
        }

        View::renderAdmin('login', [
            'page_title' => 'Admin Login',
            'error'      => $error,
        ]);
    }

    /** GET /admin/logout */
    public function logout(array $params = []): void
    {
        $token = $_COOKIE['admin_session'] ?? '';
        if ($token) {
            Database::execute("DELETE FROM admin_sessions WHERE session_token = ?", [$token]);
        }
        setcookie('admin_session', '', time() - 3600, '/', '', false, true);
        redirect('/admin/login');
    }
}
