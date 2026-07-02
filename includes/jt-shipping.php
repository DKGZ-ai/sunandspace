<?php

/** @return array<string, mixed> */
function jt_shipping_config(): array
{
    static $config = null;
    if ($config !== null) {
        return $config;
    }

    $defaults = [
        'enabled' => true,
        'origin_city' => 'MAKATI',
        'origin_label' => '3488 General Lucban St., Bangkal, Makati City',
        'product_type' => 'EZ',
        'api_base' => 'https://ylofficialjw.jtexpress.ph',
        'area_cache_ttl' => 604800,
        'min_weight_kg' => 0.5,
    ];

    $path = ss_data_root() . '/config/jt-shipping.php';
    if (is_file($path)) {
        $loaded = require $path;
        if (is_array($loaded)) {
            $config = array_merge($defaults, $loaded);
            return $config;
        }
    }

    $config = $defaults;
    return $config;
}

function jt_shipping_enabled(): bool
{
    $config = jt_shipping_config();
    return !empty($config['enabled']);
}

function jt_shipping_origin_label(): string
{
    $config = jt_shipping_config();
    $label = trim((string) ($config['origin_label'] ?? ''));
    if ($label !== '') {
        return $label;
    }

    return jt_format_city_label((string) ($config['origin_city'] ?? ''));
}

function jt_cache_path(): string
{
    return ss_data_root() . '/cache/jt-areas-ph.json';
}

/** @return array<string, mixed> */
function jt_cache_load(): array
{
    $path = jt_cache_path();
    if (!is_file($path)) {
        return ['fetched_at' => 0, 'provinces' => [], 'cities' => []];
    }

    $raw = file_get_contents($path);
    if ($raw === false || $raw === '') {
        return ['fetched_at' => 0, 'provinces' => [], 'cities' => []];
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return ['fetched_at' => 0, 'provinces' => [], 'cities' => []];
    }

    if (!isset($data['provinces']) || !is_array($data['provinces'])) {
        $data['provinces'] = [];
    }
    if (!isset($data['cities']) || !is_array($data['cities'])) {
        $data['cities'] = [];
    }

    return $data;
}

/** @param array<string, mixed> $data */
function jt_cache_save(array $data): void
{
    $dir = ss_data_root() . '/cache';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    file_put_contents(jt_cache_path(), json_encode($data, JSON_UNESCAPED_UNICODE));
}

/**
 * @param array<string, string|int> $query
 * @return array{ok: true, data: mixed}|array{ok: false, error: string}
 */
function jt_api_get(string $path, array $query = []): array
{
    $config = jt_shipping_config();
    $base = rtrim((string) $config['api_base'], '/');
    $url = $base . $path;
    if ($query !== []) {
        $url .= '?' . http_build_query($query);
    }

    $ch = curl_init($url);
    if ($ch === false) {
        return ['ok' => false, 'error' => 'Could not connect to J&T.'];
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ]);

    $body = curl_exec($ch);
    $errno = curl_errno($ch);
    curl_close($ch);

    if ($errno !== 0 || $body === false) {
        return ['ok' => false, 'error' => 'Could not reach J&T shipping service.'];
    }

    $json = json_decode($body, true);
    if (!is_array($json) || empty($json['succ'])) {
        return ['ok' => false, 'error' => 'J&T area lookup failed.'];
    }

    return ['ok' => true, 'data' => $json['data'] ?? null];
}

/**
 * @param array<string, mixed> $payload
 * @return array{ok: true, data: mixed}|array{ok: false, error: string}
 */
function jt_api_post(string $path, array $payload): array
{
    $config = jt_shipping_config();
    $base = rtrim((string) $config['api_base'], '/');
    $url = $base . $path;

    $ch = curl_init($url);
    if ($ch === false) {
        return ['ok' => false, 'error' => 'Could not connect to J&T.'];
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
        ],
    ]);

    $body = curl_exec($ch);
    $errno = curl_errno($ch);
    curl_close($ch);

    if ($errno !== 0 || $body === false) {
        return ['ok' => false, 'error' => 'Could not reach J&T shipping service.'];
    }

    $json = json_decode($body, true);
    if (!is_array($json) || empty($json['succ'])) {
        return ['ok' => false, 'error' => 'J&T could not calculate shipping for this destination.'];
    }

    return ['ok' => true, 'data' => $json['data'] ?? []];
}

/**
 * @return list<array{id: int, code: string, nativeName: string, type: int}>
 */
function jt_fetch_area_pages(?int $parentId = null): array
{
    $records = [];
    $current = 1;
    $pages = 1;

    do {
        $query = [
            'countryCode' => 'PH',
            'current' => $current,
            'size' => 100,
        ];
        if ($parentId !== null) {
            $query['parentId'] = $parentId;
        }

        $result = jt_api_get('/website/base/info/area', $query);
        if (!$result['ok'] || !is_array($result['data'])) {
            break;
        }

        $pageRecords = $result['data']['records'] ?? [];
        if (!is_array($pageRecords)) {
            break;
        }

        foreach ($pageRecords as $row) {
            if (!is_array($row)) {
                continue;
            }
            $records[] = [
                'id' => (int) ($row['id'] ?? 0),
                'code' => (string) ($row['code'] ?? ''),
                'nativeName' => (string) ($row['nativeName'] ?? ''),
                'type' => (int) ($row['type'] ?? 0),
            ];
        }

        $pages = max(1, (int) ($result['data']['pages'] ?? 1));
        $current++;
    } while ($current <= $pages);

    usort($records, static fn (array $a, array $b): int => strcmp($a['nativeName'], $b['nativeName']));

    return $records;
}

/** @return list<array{id: int, code: string, nativeName: string, type: int}> */
function jt_areas_provinces(): array
{
    $config = jt_shipping_config();
    $cache = jt_cache_load();
    $ttl = (int) ($config['area_cache_ttl'] ?? 604800);

    if (!empty($cache['provinces']) && (time() - (int) ($cache['fetched_at'] ?? 0)) < $ttl) {
        return $cache['provinces'];
    }

    $provinces = jt_fetch_area_pages(null);
    $cache['fetched_at'] = time();
    $cache['provinces'] = $provinces;
    if (!isset($cache['cities']) || !is_array($cache['cities'])) {
        $cache['cities'] = [];
    }
    jt_cache_save($cache);

    return $provinces;
}

/** @return list<array{id: int, code: string, nativeName: string, type: int}> */
function jt_areas_cities(int $provinceId): array
{
    if ($provinceId < 1) {
        return [];
    }

    $config = jt_shipping_config();
    $cache = jt_cache_load();
    $ttl = (int) ($config['area_cache_ttl'] ?? 604800);
    $key = (string) $provinceId;

    if (
        isset($cache['cities'][$key])
        && is_array($cache['cities'][$key])
        && (time() - (int) ($cache['cities_fetched_at'][$key] ?? $cache['fetched_at'] ?? 0)) < $ttl
    ) {
        return $cache['cities'][$key];
    }

    $cities = jt_fetch_area_pages($provinceId);
    if (!isset($cache['cities']) || !is_array($cache['cities'])) {
        $cache['cities'] = [];
    }
    if (!isset($cache['cities_fetched_at']) || !is_array($cache['cities_fetched_at'])) {
        $cache['cities_fetched_at'] = [];
    }

    $cache['cities'][$key] = $cities;
    $cache['cities_fetched_at'][$key] = time();
    if (empty($cache['fetched_at'])) {
        $cache['fetched_at'] = time();
    }
    jt_cache_save($cache);

    return $cities;
}

function jt_validate_city(string $city, int $provinceId): bool
{
    $city = strtoupper(trim($city));
    if ($city === '' || $provinceId < 1) {
        return false;
    }

    foreach (jt_areas_cities($provinceId) as $row) {
        if (strtoupper($row['nativeName']) === $city) {
            return true;
        }
    }

    return false;
}

function jt_province_label(int $provinceId): string
{
    foreach (jt_areas_provinces() as $row) {
        if ((int) $row['id'] === $provinceId) {
            return (string) $row['nativeName'];
        }
    }

    return '';
}

/**
 * @return array{ok: true, shippingCents: int, rawFee: string, weightKg: float}|array{ok: false, error: string}
 */
function jt_quote_local(string $receiverCity, float $weightKg): array
{
    if (!jt_shipping_enabled()) {
        return ['ok' => false, 'error' => 'Shipping quotes are not available.'];
    }

    $config = jt_shipping_config();
    $receiverCity = strtoupper(trim($receiverCity));
    if ($receiverCity === '') {
        return ['ok' => false, 'error' => 'Select a destination city.'];
    }

    $minWeight = (float) ($config['min_weight_kg'] ?? 0.5);
    $weightKg = max($minWeight, round($weightKg, 2));

    $payload = [
        'country' => 'PH',
        'senderAddr' => strtoupper((string) $config['origin_city']),
        'receiverAddr' => $receiverCity,
        'weight' => $weightKg,
        'goodType' => 'PARCEL',
        'productType' => (string) ($config['product_type'] ?? 'EZ'),
        'goodsValue' => '',
        'dimensionW' => 0,
        'dimensionH' => 0,
        'dimensionL' => 0,
        'pouchSize' => '',
    ];

    $result = jt_api_post('/website/fee/findRates', $payload);
    if (!$result['ok']) {
        return $result;
    }

    $data = $result['data'];
    if (!is_array($data) || $data === []) {
        return ['ok' => false, 'error' => 'J&T has no rate for this destination. Contact us for a quote.'];
    }

    $first = $data[0] ?? null;
    if (!is_array($first)) {
        return ['ok' => false, 'error' => 'J&T returned an invalid shipping quote.'];
    }

    $rawFee = (string) ($first['fees'] ?? '');
    if ($rawFee === '' || !is_numeric($rawFee)) {
        return ['ok' => false, 'error' => 'J&T returned an invalid shipping fee.'];
    }

    $shippingCents = (int) round((float) $rawFee * 100);

    return [
        'ok' => true,
        'shippingCents' => $shippingCents,
        'rawFee' => $rawFee,
        'weightKg' => $weightKg,
    ];
}

function jt_format_city_label(string $nativeName): string
{
    return ucwords(strtolower(str_replace('-', ' ', $nativeName)));
}

function jt_build_shipping_address(string $street, string $provinceName, string $cityName): string
{
    $parts = array_filter([
        trim($street),
        trim($cityName) !== '' && trim($provinceName) !== ''
            ? jt_format_city_label($cityName) . ', ' . jt_format_city_label($provinceName)
            : (trim($cityName) !== '' ? jt_format_city_label($cityName) : jt_format_city_label($provinceName)),
        'Philippines',
    ]);

    return implode("\n", $parts);
}
