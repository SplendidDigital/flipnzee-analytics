<?php

// ================== EMPTY RESPONSE ==================
function flipnzee_empty_response() {
    return [
        'users'          => 0,
        'sessions'       => 0,
        'trend_percent'  => 0,
        'trend_label'    => '→',
        'user_diff'      => 0,
        'updated'        => 0
    ];
}


// ================== GET ACCESS TOKEN ==================
function flipnzee_get_access_token() {

    $token = get_option('flipnzee_ga_token');

    if (!$token) {
        return false;
    }

    if (empty($token['created'])) {
        $token['created'] = time();
        update_option('flipnzee_ga_token', $token);
    }

    $access_token  = $token['access_token'] ?? '';
    $refresh_token = $token['refresh_token'] ?? '';
    $expires_in    = $token['expires_in'] ?? 0;
    $created       = $token['created'] ?? 0;

    // Refresh expired token
    if (time() > ($created + $expires_in - 60) && !empty($refresh_token)) {

        $response = wp_remote_post(
            'https://oauth2.googleapis.com/token',
            [
                'body' => [
                    'client_id'     => get_option('flipnzee_client_id'),
                    'client_secret' => get_option('flipnzee_client_secret'),
                    'refresh_token' => $refresh_token,
                    'grant_type'    => 'refresh_token'
                ]
            ]
        );

        if (is_wp_error($response)) {
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!empty($body['access_token'])) {

            $token['access_token'] = $body['access_token'];
            $token['expires_in']   = $body['expires_in'] ?? 3600;
            $token['created']      = time();

            update_option('flipnzee_ga_token', $token);

            return $body['access_token'];
        }
    }

    return $access_token;
}


// ================== FETCH MAIN ==================
function flipnzee_fetch_and_store($property_id, $post_id) {

    $access_token = flipnzee_get_access_token();

    if (!$access_token) {

        set_transient(
            "flipnzee_main_{$post_id}",
            flipnzee_empty_response(),
            HOUR_IN_SECONDS
        );

        return;
    }

    $endpoint = "https://analyticsdata.googleapis.com/v1beta/properties/{$property_id}:runReport";


    // ---------- CURRENT PERIOD ----------
    $response_current = wp_remote_post(
        $endpoint,
        [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type'  => 'application/json'
            ],
            'body' => wp_json_encode([
                'dateRanges' => [
                    [
                        'startDate' => '30daysAgo',
                        'endDate'   => 'today'
                    ]
                ],
                'metrics' => [
                    ['name' => 'activeUsers'],
                    ['name' => 'sessions']
                ]
            ])
        ]
    );

    if (is_wp_error($response_current)) {
        return;
    }

    $data_current = json_decode(
        wp_remote_retrieve_body($response_current),
        true
    );

    $users = intval($data_current['rows'][0]['metricValues'][0]['value'] ?? 0);

    $sessions = intval(
        $data_current['rows'][0]['metricValues'][1]['value'] ?? 0
    );


    // ---------- PREVIOUS PERIOD ----------
    $response_previous = wp_remote_post(
        $endpoint,
        [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type'  => 'application/json'
            ],
            'body' => wp_json_encode([
                'dateRanges' => [
                    [
                        'startDate' => '60daysAgo',
                        'endDate'   => '30daysAgo'
                    ]
                ],
                'metrics' => [
                    ['name' => 'activeUsers']
                ]
            ])
        ]
    );

    if (is_wp_error($response_previous)) {
        return;
    }

    $data_previous = json_decode(
        wp_remote_retrieve_body($response_previous),
        true
    );

    $previous_users = intval(
        $data_previous['rows'][0]['metricValues'][0]['value'] ?? 0
    );

    $trend_percent = $previous_users > 0
        ? round((($users - $previous_users) / $previous_users) * 100)
        : 0;

    $trend_label = $trend_percent > 0
        ? '↑'
        : ($trend_percent < 0 ? '↓' : '→');


    set_transient(
        "flipnzee_main_{$post_id}",
        [
            'users'         => $users,
            'sessions'      => $sessions,
            'trend_percent' => $trend_percent,
            'trend_label'   => $trend_label,
            'user_diff'     => $users - $previous_users,
            'updated'       => time()
        ],
        HOUR_IN_SECONDS
    );
}


// ================== FETCH INSIGHTS ==================
function flipnzee_fetch_insights($property_id, $post_id) {

    $access_token = flipnzee_get_access_token();

    if (!$access_token) {
        return;
    }

    $endpoint = "https://analyticsdata.googleapis.com/v1beta/properties/{$property_id}:runReport";


    // ---------- COUNTRIES ----------
    $countries = [];

    $response = wp_remote_post(
        $endpoint,
        [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type'  => 'application/json'
            ],
            'body' => wp_json_encode([
                'dateRanges' => [
                    [
                        'startDate' => '30daysAgo',
                        'endDate'   => 'today'
                    ]
                ],
                'dimensions' => [
                    ['name' => 'country']
                ],
                'metrics' => [
                    ['name' => 'activeUsers']
                ],
                'limit' => 10
            ])
        ]
    );

    $data = json_decode(wp_remote_retrieve_body($response), true);

    $total = 0;
    $map   = [];

    foreach ($data['rows'] ?? [] as $row) {

        $name  = $row['dimensionValues'][0]['value'] ?? 'Unknown';
        $value = intval($row['metricValues'][0]['value']);

        $map[$name] = ($map[$name] ?? 0) + $value;

        $total += $value;
    }

    foreach ($map as $name => $value) {

        $countries[] = [
            'name'    => $name,
            'percent' => $total > 0
                ? round(($value / $total) * 100)
                : 0
        ];
    }


    // ---------- SOURCES ----------
    $sources = [];

    $response = wp_remote_post(
        $endpoint,
        [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type'  => 'application/json'
            ],
            'body' => wp_json_encode([
                'dateRanges' => [
                    [
                        'startDate' => '30daysAgo',
                        'endDate'   => 'today'
                    ]
                ],
                'dimensions' => [
                    ['name' => 'sessionDefaultChannelGroup']
                ],
                'metrics' => [
                    ['name' => 'sessions']
                ],
                'limit' => 5
            ])
        ]
    );

    if (!is_wp_error($response)) {

        $data = json_decode(
            wp_remote_retrieve_body($response),
            true
        );

        $total = 0;

        foreach ($data['rows'] ?? [] as $row) {

            $value = intval($row['metricValues'][0]['value']);

            $sources[] = [
                'name'  => $row['dimensionValues'][0]['value'],
                'value' => $value
            ];

            $total += $value;
        }

        foreach ($sources as &$s) {

            $s['percent'] = $total > 0
                ? round(($s['value'] / $total) * 100)
                : 0;
        }
    }


    // ---------- KEYWORDS ----------
    $keywords = [];

    $site_url = get_post_meta($post_id, '_ga_domain', true);

    if ($site_url) {

        // NORMALIZATION FIX
        $domain = preg_replace('#^https?://#', '', trim($site_url));
        $domain = rtrim($domain, '/');

        $variants = [
            'sc-domain:' . $domain,
            'https://' . $domain . '/',
            'http://' . $domain . '/'
        ];

        foreach ($variants as $site_for_api) {

            error_log('SC TRY: ' . $site_for_api);

            $response = wp_remote_post(
                'https://searchconsole.googleapis.com/webmasters/v3/sites/' .
                urlencode($site_for_api) .
                '/searchAnalytics/query',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $access_token,
                        'Content-Type'  => 'application/json'
                    ],
                    'body' => wp_json_encode([
                        'startDate' => date('Y-m-d', strtotime('-30 days')),
                        'endDate'   => date('Y-m-d'),
                        'dimensions' => ['query'],
                        'rowLimit'   => 10,
                        'searchType' => 'web'
                    ])
                ]
            );

            if (is_wp_error($response)) {
                continue;
            }

            $raw  = wp_remote_retrieve_body($response);
            $data = json_decode($raw, true);

            error_log('SC RESPONSE: ' . $raw);

            if (!empty($data['rows'])) {

                foreach ($data['rows'] as $row) {

                    $keywords[] = [
                        'query'    => $row['keys'][0] ?? '',
                        'clicks'   => intval($row['clicks'] ?? 0),
                        'position' => round($row['position'] ?? 0, 1)
                    ];
                }

                break;
            }
        }
    }


    set_transient(
        "flipnzee_meta_{$post_id}",
        [
            'countries' => $countries,
            'sources'   => $sources,
            'keywords'  => $keywords
        ],
        6 * HOUR_IN_SECONDS
    );
}