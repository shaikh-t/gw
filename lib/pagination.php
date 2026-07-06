<?php
// lib/pagination.php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

/**
 * Get current page from request, sanitize and clamp.
 * @param string $param query param name (default 'page')
 * @param int $min minimum page (default 1)
 * @return int
 */
function get_current_page(string $param = 'page', int $min = 1): int {
    $p = isset($_GET[$param]) ? intval($_GET[$param]) : $min;
    return max($min, $p);
}

/**
 * Build query string preserving existing GET params except the page param.
 * @param array $overrides key => value pairs to override or add
 * @param string $pageParam name of page param
 * @return string (leading ? included if non-empty)
 */
function build_query_string(array $overrides = [], string $pageParam = 'page'): string {
    $qs = $_GET;
    unset($qs[$pageParam]);
    foreach ($overrides as $k => $v) {
        if ($v === null) { unset($qs[$k]); } else { $qs[$k] = $v; }
    }
    if (empty($qs)) return '';
    return '?' . http_build_query($qs);
}

/**
 * Create pagination metadata for SQL LIMIT/OFFSET and rendering.
 * @param int $total total number of items
 * @param int $perPage items per page
 * @param int $current current page (1-based)
 * @return array ['total'=>$total,'perPage'=>$perPage,'current'=>$current,'pages'=>$pages,'offset'=>$offset,'limit'=>$perPage]
 */
function paginate(int $total, int $perPage, int $current): array {
    $perPage = max(1, intval($perPage));
    $pages = (int)ceil($total / $perPage);
    $current = max(1, min($pages > 0 ? $pages : 1, intval($current)));
    $offset = ($current - 1) * $perPage;
    return [
        'total' => $total,
        'perPage' => $perPage,
        'current' => $current,
        'pages' => $pages,
        'offset' => $offset,
        'limit' => $perPage
    ];
}

/**
 * Render Bootstrap-compatible pagination HTML.
 * @param array $meta result of paginate()
 * @param string $baseQuery query string prefix (e.g., '?q=foo&')
 * @param int $adjacent number of pages to show on each side of current
 * @return string HTML
 */
function render_pagination(array $meta, string $baseQuery = '', int $adjacent = 2): string {
    if ($meta['pages'] <= 1) return '';
    $current = $meta['current'];
    $pages = $meta['pages'];
    $html = '<nav aria-label="Pagination"><ul class="pagination">';
    // Previous
    if ($current > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($baseQuery . 'page=' . ($current - 1), ENT_QUOTES) . '">Previous</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">Previous</span></li>';
    }
    // First
    if ($current - $adjacent > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($baseQuery . 'page=1', ENT_QUOTES) . '">1</a></li>';
        if ($current - $adjacent > 2) $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
    }
    // Range
    $start = max(1, $current - $adjacent);
    $end = min($pages, $current + $adjacent);
    for ($i = $start; $i <= $end; $i++) {
        if ($i === $current) {
            $html .= '<li class="page-item active" aria-current="page"><span class="page-link">' . $i . '</span></li>';
        } else {
            $html .= '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($baseQuery . 'page=' . $i, ENT_QUOTES) . '">' . $i . '</a></li>';
        }
    }
    // Last
    if ($current + $adjacent < $pages) {
        if ($current + $adjacent < $pages - 1) $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
        $html .= '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($baseQuery . 'page=' . $pages, ENT_QUOTES) . '">' . $pages . '</a></li>';
    }
    // Next
    if ($current < $pages) {
        $html .= '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($baseQuery . 'page=' . ($current + 1), ENT_QUOTES) . '">Next</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">Next</span></li>';
    }
    $html .= '</ul></nav>';
    return $html;
}
