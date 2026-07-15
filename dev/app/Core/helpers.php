<?php
declare(strict_types=1);

/**
 * Helper functions — available globally after bootstrap.
 * All output goes through h() for XSS protection.
 */

// ── String ──

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

function h(?string $text): string
{
    return htmlspecialchars($text ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function sanitize(?string $text): string
{
    if ($text === null) return '';
    return strip_tags($text, implode('', [
        '<h1>','<h2>','<h3>','<h4>','<h5>','<h6>',
        '<p>','<a>','<strong>','<em>','<b>','<i>','<u>',
        '<ul>','<ol>','<li>','<br>','<blockquote>','<pre>','<code>',
        '<span>','<div>','<img>','<figure>','<figcaption>',
        '<table>','<thead>','<tbody>','<tr>','<th>','<td>',
        '<hr>','<sup>','<sub>',
    ]));
}

function truncate(string $text, int $length = 200, string $suffix = '...'): string
{
    $text = strip_tags($text);
    if (mb_strlen($text) <= $length) return $text;
    return mb_substr($text, 0, $length) . $suffix;
}

function excerpt(string $text, int $length = 300): string
{
    return truncate($text, $length);
}

function read_time(string $text): int
{
    $text = strip_tags($text);
    $words = str_word_count($text);
    return max(1, (int)ceil($words / 225));
}

// ── Date/Time ──

function format_date(?string $date, string $format = 'M j, Y'): string
{
    if (empty($date)) return '';
    $ts = strtotime($date);
    return $ts !== false ? date($format, $ts) : '';
}

function format_datetime(?string $datetime, string $format = 'M j, Y g:i A'): string
{
    return format_date($datetime, $format);
}

function relative_date(?string $date): string
{
    if (empty($date)) return '';
    $ts = strtotime($date);
    if ($ts === false) return '';
    $diff = time() - $ts;

    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    if ($diff < 2592000) return floor($diff / 604800) . 'w ago';
    return date('M j, Y', $ts);
}

// ── Badges / Labels ──

function status_badge(string $status): string
{
    $class = match(strtolower($status)) {
        'open'     => 'status-open',
        'active'   => 'status-active',
        'resolved' => 'status-resolved',
        'closed'   => 'status-closed',
        'pending'  => 'status-pending',
        'appealed' => 'status-appealed',
        default    => 'status-default',
    };
    return sprintf('<span class="status-badge %s">%s</span>', $class, h($status));
}

// ── CSRF ──

function csrf_token(): string
{
    return $_SESSION['csrf_token'] ?? '';
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function verify_csrf(): bool
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return true;
    $token = $_POST['csrf_token'] ?? '';
    return hash_equals(csrf_token(), $token);
}

// ── HTTP helpers ──

function redirect(string $url, int $code = 302): never
{
    http_response_code($code);
    header("Location: $url");
    exit;
}

function json_response(mixed $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function asset(string $path): string
{
    $path = '/' . ltrim($path, '/');
    $full = PUBLIC_DIR . $path;
    $mtime = is_file($full) ? filemtime($full) : 0;
    return ASSETS_URL . $path . ($mtime ? '?v=' . dechex($mtime) : '');
}

// ── Auth ──

function generate_password_hash(string $password): string
{
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
}

function verify_password(string $password, string $hash): bool
{
    return password_verify($password, $hash);
}

// ── Pagination ──

function paginate(int $total, int $perPage = DEFAULT_PER_PAGE): array
{
    $page = max(1, (int)($_GET['page'] ?? 1));
    $totalPages = max(1, (int)ceil($total / $perPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;

    return [
        'page'       => $page,
        'per_page'   => $perPage,
        'total'      => $total,
        'total_pages'=> $totalPages,
        'offset'     => $offset,
        'has_prev'   => $page > 1,
        'has_next'   => $page < $totalPages,
    ];
}

function pagination_links(array $pagination, string $baseUrl): string
{
    $p = $pagination;
    if ($p['total_pages'] <= 1) return '';

    $sep = str_contains($baseUrl, '?') ? '&amp;page=' : '?page=';

    $html = '<nav class="pagination" aria-label="Page navigation"><ul class="pagination-list">';

    // Previous
    $html .= '<li class="pagination-item">';
    if ($p['has_prev']) {
        $html .= '<a href="' . h($baseUrl . $sep . ($p['page'] - 1)) . '" class="pagination-link" rel="prev">&laquo; Previous</a>';
    } else {
        $html .= '<span class="pagination-link disabled">&laquo; Previous</span>';
    }
    $html .= '</li>';

    // Page numbers (show at most 7)
    $start = max(1, $p['page'] - 3);
    $end = min($p['total_pages'], $p['page'] + 3);
    if ($start > 1) {
        $html .= '<li class="pagination-item"><a href="' . h($baseUrl . $sep . '1') . '" class="pagination-link">1</a></li>';
        if ($start > 2) $html .= '<li class="pagination-item"><span class="pagination-ellipsis">&hellip;</span></li>';
    }
    for ($i = $start; $i <= $end; $i++) {
        $html .= '<li class="pagination-item">';
        if ($i === $p['page']) {
            $html .= '<span class="pagination-link active" aria-current="page">' . $i . '</span>';
        } else {
            $html .= '<a href="' . h($baseUrl . $sep . $i) . '" class="pagination-link">' . $i . '</a>';
        }
        $html .= '</li>';
    }
    if ($end < $p['total_pages']) {
        if ($end < $p['total_pages'] - 1) $html .= '<li class="pagination-item"><span class="pagination-ellipsis">&hellip;</span></li>';
        $html .= '<li class="pagination-item"><a href="' . h($baseUrl . $sep . $p['total_pages']) . '" class="pagination-link">' . $p['total_pages'] . '</a></li>';
    }

    // Next
    $html .= '<li class="pagination-item">';
    if ($p['has_next']) {
        $html .= '<a href="' . h($baseUrl . $sep . ($p['page'] + 1)) . '" class="pagination-link" rel="next">Next &raquo;</a>';
    } else {
        $html .= '<span class="pagination-link disabled">Next &raquo;</span>';
    }
    $html .= '</li>';

    $html .= '</ul></nav>';
    return $html;
}

// ── PRS Helpers ──

function prsStatusBadge(string $status, bool $large = false): string
{
    $colors = [
        'filed'          => '#60a5fa',
        'accepted'       => '#22c55e',
        'investigating'  => '#f59e0b',
        'findings'       => '#ff5a1f',
        'closed'         => '#a0a0a0',
        'appealed'       => '#a78bfa',
    ];
    $color = $colors[$status] ?? '#767676';
    $label = ucfirst(str_replace('_', ' ', $status));
    $size = $large ? 'font-size:1rem;padding:0.25rem 1rem;' : 'font-size:0.75rem;padding:0.125rem 0.5rem;';
    return '<span style="display:inline-block;background:' . $color . ';color:#000;border-radius:999px;' . $size . 'font-weight:600;white-space:nowrap;">' . h($label) . '</span>';
}

function findingBadge(?string $finding): string
{
    $colors = [
        'substantiated'            => '#ef4444',
        'unsubstantiated'          => '#22c55e',
        'partially_substantiated' => '#f59e0b',
    ];
    $color = $colors[$finding ?? ''] ?? '#a0a0a0';
    $label = $finding ? ucwords(str_replace('_', ' ', $finding)) : 'N/A';
    return '<span style="display:inline-block;background:' . $color . ';color:#000;border-radius:4px;padding:0.125rem 0.5rem;font-size:0.75rem;font-weight:600;">' . h($label) . '</span>';
}

function docIcon(string $mime): string
{
    if (str_contains($mime, 'pdf'))  return '📄';
    if (str_contains($mime, 'word') || str_contains($mime, 'doc')) return '📝';
    if (str_contains($mime, 'sheet') || str_contains($mime, 'xls')) return '📊';
    if (str_contains($mime, 'image')) return '🖼️';
    if (str_contains($mime, 'email')) return '✉️';
    return '📎';
}

function yoy_badge(float $current, float $previous): string
{
    if ($previous == 0) return '<span class="stat-delta delta-neutral">—</span>';
    $pct = round((($current - $previous) / $previous) * 100, 1);
    $abs  = abs($pct);
    if ($pct > 0) {
        $arrow = '&#9650;';
        $cls   = 'delta-up';
    } elseif ($pct < 0) {
        $arrow = '&#9660;';
        $cls   = 'delta-down';
    } else {
        $arrow = '&#9644;';
        $cls   = 'delta-neutral';
    }
    return '<span class="stat-delta ' . $cls . '">' . $arrow . ' ' . $abs . '%</span>';
}
