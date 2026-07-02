<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';

header('Content-Type: application/json');

if (!customer_logged_in()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Sign in required.']);
    exit;
}

if (!jt_shipping_enabled()) {
    http_response_code(503);
    echo json_encode(['ok' => false, 'error' => 'Shipping quotes are not available.']);
    exit;
}

if (cart_lines_for_checkout() === []) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'No items selected for checkout.']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'provinces' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $provinces = jt_areas_provinces();
    echo json_encode([
        'ok' => true,
        'provinces' => array_map(static fn (array $row): array => [
            'id' => (int) $row['id'],
            'name' => (string) $row['nativeName'],
            'label' => jt_format_city_label((string) $row['nativeName']),
        ], $provinces),
    ]);
    exit;
}

if ($action === 'cities' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $provinceId = (int) ($_GET['province_id'] ?? 0);
    if ($provinceId < 1) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Province is required.']);
        exit;
    }

    $cities = jt_areas_cities($provinceId);
    echo json_encode([
        'ok' => true,
        'cities' => array_map(static fn (array $row): array => [
            'id' => (int) $row['id'],
            'name' => (string) $row['nativeName'],
            'label' => jt_format_city_label((string) $row['nativeName']),
        ], $cities),
    ]);
    exit;
}

if ($action === 'quote' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Invalid request.']);
        exit;
    }

    $receiverCity = strtoupper(trim((string) ($_POST['receiver_city'] ?? '')));
    $provinceId = (int) ($_POST['receiver_province_id'] ?? 0);

    if ($receiverCity === '' || $provinceId < 1) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Select a province and city.']);
        exit;
    }

    if (!jt_validate_city($receiverCity, $provinceId)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Invalid destination city.']);
        exit;
    }

    $weightKg = cart_checkout_weight_kg();
    $quote = jt_quote_local($receiverCity, $weightKg);
    if (!$quote['ok']) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => $quote['error']]);
        exit;
    }

    $provinceName = jt_province_label($provinceId);
    echo json_encode([
        'ok' => true,
        'shippingCents' => (int) $quote['shippingCents'],
        'shippingLabel' => jt_format_city_label($receiverCity) . ', ' . jt_format_city_label($provinceName),
        'weightKg' => (float) $quote['weightKg'],
        'subtotalCents' => cart_total_cents_for_checkout(),
        'totalCents' => cart_total_cents_for_checkout() + (int) $quote['shippingCents'],
    ]);
    exit;
}

http_response_code(400);
echo json_encode(['ok' => false, 'error' => 'Unknown action.']);
