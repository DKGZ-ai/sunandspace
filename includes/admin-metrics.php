<?php

/** @return array{sql: string, params: array} */
function admin_period_filter(string $period): array
{
    return match ($period) {
        'today' => [' AND DATE(created_at) = CURDATE()', []],
        '7d' => [' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)', []],
        '30d' => [' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)', []],
        default => ['', []],
    };
}

function admin_valid_period(?string $period): string
{
    $allowed = ['overall', 'today', '7d', '30d'];
    return in_array($period, $allowed, true) ? $period : 'overall';
}

function admin_period_label(string $period): string
{
    return match ($period) {
        'today' => 'Today',
        '7d' => '7 Days',
        '30d' => '30 Days',
        default => 'Overall',
    };
}

/** @param list<string> $statuses */
function admin_status_in_sql(array $statuses): string
{
    if ($statuses === []) {
        return "''";
    }

    return implode(',', array_map(
        static fn(string $status): string => "'" . str_replace("'", "''", $status) . "'",
        $statuses
    ));
}

/** @return array{delivered: list<string>, pending_revenue: list<string>, pending_orders: list<string>, in_progress: list<string>} */
function admin_metric_status_groups(): array
{
    $all = order_statuses();

    return [
        'delivered' => ['delivered'],
        'pending_revenue' => array_values(array_filter($all, static fn(string $s): bool => $s !== 'delivered')),
        'pending_orders' => ['pending', 'approved'],
        'in_progress' => ['in_progress'],
    ];
}

function admin_dashboard_metrics(string $period): array
{
    global $pdo;
    [$dateSql, $dateParams] = admin_period_filter($period);
    $groups = admin_metric_status_groups();

    $deliveredIn = admin_status_in_sql($groups['delivered']);
    $pendingRevenueIn = admin_status_in_sql($groups['pending_revenue']);
    $inProgressIn = admin_status_in_sql($groups['in_progress']);

    $stmt = $pdo->prepare(
        "SELECT
            COALESCE(SUM(CASE WHEN status IN ($deliveredIn) THEN total_cents ELSE 0 END), 0) AS revenue_cents,
            COALESCE(SUM(CASE WHEN status IN ($pendingRevenueIn) THEN total_cents ELSE 0 END), 0) AS pending_cents,
            COALESCE(SUM(CASE WHEN status IN ($deliveredIn) THEN 1 ELSE 0 END), 0) AS completed_orders,
            COALESCE(SUM(CASE WHEN status IN ($inProgressIn) THEN 1 ELSE 0 END), 0) AS in_progress_orders,
            COALESCE(SUM(CASE WHEN status IN ($inProgressIn) THEN total_cents ELSE 0 END), 0) AS in_progress_value_cents,
            COALESCE(SUM(CASE WHEN status = 'refunded' THEN total_cents ELSE 0 END), 0) AS refunded_cents,
            COUNT(*) AS order_count
         FROM orders
         WHERE 1=1 $dateSql"
    );
    $stmt->execute($dateParams);
    $row = $stmt->fetch() ?: [];

    $revenue = (int) ($row['revenue_cents'] ?? 0);
    $refunded = (int) ($row['refunded_cents'] ?? 0);
    $completed = (int) ($row['completed_orders'] ?? 0);
    $orderCount = (int) ($row['order_count'] ?? 0);
    $successRate = $orderCount > 0 ? round(($completed / $orderCount) * 100, 1) : 0.0;
    $avgOrder = $completed > 0 ? (int) round($revenue / $completed) : 0;

    $custSql = 'SELECT COUNT(*) FROM users WHERE role = ?';
    $custParams = ['customer'];
    if ($period === 'today') {
        $custSql .= ' AND DATE(created_at) = CURDATE()';
    } elseif ($period === '7d') {
        $custSql .= ' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
    } elseif ($period === '30d') {
        $custSql .= ' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
    }
    $custStmt = $pdo->prepare($custSql);
    $custStmt->execute($custParams);
    $customers = (int) $custStmt->fetchColumn();

    $prodStmt = $pdo->query('SELECT COUNT(*) AS c, COALESCE(SUM(price_cents), 0) AS catalog_cents FROM products WHERE active = 1');
    $prodRow = $prodStmt->fetch() ?: ['c' => 0, 'catalog_cents' => 0];

    $pendingOrdersIn = admin_status_in_sql($groups['pending_orders']);
    $pendingOrdersStmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ($pendingOrdersIn)");
    $pendingOrders = (int) $pendingOrdersStmt->fetchColumn();

    return [
        'revenue_cents' => $revenue,
        'pending_cents' => (int) ($row['pending_cents'] ?? 0),
        'refunded_cents' => $refunded,
        'completed_orders' => $completed,
        'in_progress_orders' => (int) ($row['in_progress_orders'] ?? 0),
        'in_progress_value_cents' => (int) ($row['in_progress_value_cents'] ?? 0),
        'success_rate' => $successRate,
        'avg_order_cents' => $avgOrder,
        'customers' => $customers,
        'active_products' => (int) ($prodRow['c'] ?? 0),
        'catalog_cents' => (int) ($prodRow['catalog_cents'] ?? 0),
        'pending_orders' => $pendingOrders,
        'order_count' => $orderCount,
        'gross_cents' => $revenue,
        'net_cents' => max(0, $revenue - $refunded),
    ];
}

function admin_get_distinct_order_statuses(): array
{
    return order_statuses();
}
