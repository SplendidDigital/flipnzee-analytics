<?php

// ================== EMPTY RESPONSE ==================
function flipnzee_empty_response() {
    return [
        'users' => 0,
        'sessions' => 0,
        'trend_percent' => 0,
        'trend_label' => '→',
        'user_diff' => 0,
        'updated' => 0
    ];
}


// ================== GET ACCESS TOKEN ==================
function flipnzee_get_access_token() {

    $token = get_option('flipnzee_ga_token');
    if (!$token) {
        error_log('TOKEN MISSING');
        return false;
    }

    if (empty($token['created'])) {
        $token['created'] = time();
        update_option('flipnzee_ga_token', $token);
    }

    $access_token = $token['access_token'] ?? '';
    $refresh_token = $token['refresh_token'] ?? '';
    $expires_in = $token['expires_in'] ?? 0;
    $created = $token['created'] ?? 0;

    if (time() > ($created + $expires_in - 60) && !empty($refresh_token)) {

        error_log('TOKEN REFRESHING');

        $response = wp_remote_post('https://oauth2.googleapis.com/token', [
            'body' => [
                'client_id' => get_option('flipnzee_client_id'),
                'client_secret' => get_option('flipnzee_client_secret'),
                'refresh_token' => $refresh_token,
                'grant_type' => 'refresh_token'
            ]
        ]);

        if (is_wp_error($response)) {
            error_log('TOKEN REFRESH ERROR');
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!empty($body['access_token'])) {
            $token['access_token'] = $body['access_token'];
            $token['expires_in'] = $body['expires_in'] ?? 3600;
            $token['created'] = time();

            update_option('flipnzee_ga_token', $token);

            return $body['access_token'];
        }

        error_log('TOKEN REFRESH FAILED: ' . json_encode($body));
    }

    return $access_token;
}


// ================== FETCH MAIN ==================
function flipnzee_fetch_and_store($property_id, $post_id) {

    $access_token = flipnzee_get_access_token();

    if (!$access_token) {
        set_transient("flipnzee_main_{$post_id}", flipnzee_empty_response(), HOUR_IN_SECONDS);
        return;
    }

    $endpoint = "https://analyticsdata.googleapis.com/v1beta/properties/{$property_id}:runReport";

    $response_current = wp_remote_post($endpoint, [
        'headers' => [
            'Authorization' => 'Bearer ' . $access_token,
            'Content-Type' => 'application/json'
        ],
        'body' => wp_json_encode([
            'dateRanges' => [['startDate' => '30daysAgo', 'endDate' => 'today']],
            'metrics' => [
                ['name' => 'activeUsers'],
                ['name' => 'sessions']
            ]
        ])
    ]);

    if (is_wp_error($response_current)) {
        set_transient("flipnzee_main_{$post_id}", flipnzee_empty_response(), HOUR_IN_SECONDS);
        return;
    }

    $data_current = json_decode(wp_remote_retrieve_body($response_current), true);

    $users = intval($data_current['rows'][0]['metricValues'][0]['value'] ?? 0);
    $sessions = intval($data_current['rows'][0]['metricValues'][1]['value'] ?? 0);

    $response_previous = wp_remote_post($endpoint, [
        'headers' => [
            'Authorization' => 'Bearer ' . $access_token,
            'Content-Type' => 'application/json'
        ],
        'body' => wp_json_encode([
            'dateRanges' => [['startDate' => '60daysAgo', 'endDate' => '30daysAgo']],
            'metrics' => [['name' => 'activeUsers']]
        ])
    ]);

    if (is_wp_error($response_previous)) {
        set_transient("flipnzee_main_{$post_id}", flipnzee_empty_response(), HOUR_IN_SECONDS);
        return;
    }

    $data_previous = json_decode(wp_remote_retrieve_body($response_previous), true);
    $previous_users = intval($data_previous['rows'][0]['metricValues'][0]['value'] ?? 0);

    $trend_percent = $previous_users > 0
        ? round((($users - $previous_users) / $previous_users) * 100)
        : 0;

    $trend_label = $trend_percent > 0 ? '↑' : ($trend_percent < 0 ? '↓' : '→');

    set_transient("flipnzee_main_{$post_id}", [
        'users' => $users,
        'sessions' => $sessions,
        'trend_percent' => $trend_percent,
        'trend_label' => $trend_label,
        'user_diff' => $users - $previous_users,
        'updated' => time()
    ], HOUR_IN_SECONDS);
}


// ================== FETCH INSIGHTS ==================
function flipnzee_fetch_insights($property_id, $post_id) {

    $access_token = flipnzee_get_access_token();
    if (!$access_token) return;

    $endpoint = "https://analyticsdata.googleapis.com/v1beta/properties/{$property_id}:runReport";

    // ---------- COUNTRIES (WITH FALLBACK) ----------
    $countries = [];

    $response = wp_remote_post($endpoint, [
        'headers' => [
            'Authorization' => 'Bearer ' . $access_token,
            'Content-Type' => 'application/json'
        ],
        'body' => wp_json_encode([
            'dateRanges' => [['startDate' => '30daysAgo', 'endDate' => 'today']],
            'dimensions' => [['name' => 'country']],
            'metrics' => [['name' => 'activeUsers']],
            'limit' => 10
        ])
    ]);

    $raw = wp_remote_retrieve_body($response);
    error_log('GA COUNTRIES: ' . $raw);

    $data = json_decode($raw, true);

    // ߔ FALLBACK TO CITY
    if (empty($data['rows'])) {

        error_log('COUNTRY EMPTY → FALLBACK TO CITY');

        $response = wp_remote_post($endpoint, [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json'
            ],
            'body' => wp_json_encode([
                'dateRanges' => [['startDate' => '30daysAgo', 'endDate' => 'today']],
                'dimensions' => [['name' => 'city']],
                'metrics' => [['name' => 'activeUsers']],
                'limit' => 10
            ])
        ]);

        $raw = wp_remote_retrieve_body($response);
        error_log('GA CITY: ' . $raw);

        $data = json_decode($raw, true);
    }

    $total = 0;
    $map = [];

    foreach ($data['rows'] ?? [] as $row) {
        $name = $row['dimensionValues'][0]['value'] ?? 'Unknown';
        $value = intval($row['metricValues'][0]['value']);

        $map[$name] = ($map[$name] ?? 0) + $value;
        $total += $value;
    }

    foreach ($map as $name => $value) {
        $countries[] = [
            'name' => $name,
            'percent' => $total > 0 ? round(($value / $total) * 100) : 0
        ];
    }

    // ---------- SOURCES ----------
    $sources = [];

    $response = wp_remote_post($endpoint, [
        'headers' => [
            'Authorization' => 'Bearer ' . $access_token,
            'Content-Type' => 'application/json'
        ],
        'body' => wp_json_encode([
            'dateRanges' => [['startDate' => '30daysAgo', 'endDate' => 'today']],
            'dimensions' => [['name' => 'sessionDefaultChannelGroup']],
            'metrics' => [['name' => 'sessions']],
            'limit' => 5
        ])
    ]);

    if (!is_wp_error($response)) {

        $data = json_decode(wp_remote_retrieve_body($response), true);

        $total = 0;

        foreach ($data['rows'] ?? [] as $row) {
            $name = $row['dimensionValues'][0]['value'] ?? 'Unknown';
            $value = intval($row['metricValues'][0]['value']);

            $sources[] = [
                'name' => $name,
                'value' => $value
            ];

            $total += $value;
        }

        foreach ($sources as &$s) {
            $s['percent'] = $total > 0 ? round(($s['value'] / $total) * 100) : 0;
        }
    }

    // ---------- KEYWORDS ----------
    $keywords = [];

    $site_url = get_post_meta($post_id, '_ga_domain', true);

    if ($site_url) {

        if (!preg_match('#^https?://#', $site_url)) {
            $site_url = 'https://' . $site_url;
        }

        $site_url = rtrim($site_url, '/') . '/';

        $response = wp_remote_post(
            'https://searchconsole.googleapis.com/webmasters/v3/sites/' . urlencode($site_url) . '/searchAnalytics/query',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $access_token,
                    'Content-Type' => 'application/json'
                ],
                'body' => wp_json_encode([
                    'startDate' => date('Y-m-d', strtotime('-30 days')),
                    'endDate' => date('Y-m-d'),
                    'dimensions' => ['query'],
                    'rowLimit' => 5
                ])
            ]
        );

        if (!is_wp_error($response)) {

            $raw = wp_remote_retrieve_body($response);
            error_log('SC RESPONSE: ' . $raw);

            $data = json_decode($raw, true);

            foreach ($data['rows'] ?? [] as $row) {
                $keywords[] = [
                    'query' => $row['keys'][0] ?? '',
                    'clicks' => intval($row['clicks'] ?? 0),
                    'position' => round($row['position'] ?? 0, 1)
                ];
            }
        }
    }

    set_transient("flipnzee_meta_{$post_id}", [
        'countries' => $countries,
        'sources' => $sources,
        'keywords' => $keywords
    ], 6 * HOUR_IN_SECONDS);
}