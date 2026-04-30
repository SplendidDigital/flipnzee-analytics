<?php

function flipnzee_fetch_users($property_id, $post_id = 0) {

    $cache_key = 'flipnzee_' . $property_id;
    $cached = get_transient($cache_key);

    if ($cached) return $cached;

    $users = 0;
    $sessions = 0;
    $countries = [];
    $sources = [];
    $keywords = [];

    // ======================
    // GET ACCESS TOKEN
    // ======================
    $token = get_option('flipnzee_ga_token');

    if (!$token || empty($token['access_token'])) {
        return [
            'users' => 0,
            'sessions' => 0,
            'trend_percent' => 0,
            'trend_label' => '→',
            'countries' => [],
            'sources' => [],
            'keywords' => []
        ];
    }

    $access_token = $token['access_token'];

    // ======================
    // CURRENT 30 DAYS
    // ======================
    $body_current = [
        'dateRanges' => [
            [
                'startDate' => '30daysAgo',
                'endDate'   => 'today'
            ]
        ],
        'metrics' => [
            ['name' => 'totalUsers'],
            ['name' => 'sessions']
        ]
    ];

    $response_current = wp_remote_post(
        'https://analyticsdata.googleapis.com/v1beta/properties/' . $property_id . ':runReport',
        [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type'  => 'application/json'
            ],
            'body' => wp_json_encode($body_current)
        ]
    );

    $data_current = json_decode(wp_remote_retrieve_body($response_current), true);

    $users = intval($data_current['rows'][0]['metricValues'][0]['value'] ?? 0);
    $sessions = intval($data_current['rows'][0]['metricValues'][1]['value'] ?? 0);

    // ======================
    // PREVIOUS 30 DAYS
    // ======================
    $body_previous = [
        'dateRanges' => [
            [
                'startDate' => '60daysAgo',
                'endDate'   => '30daysAgo'
            ]
        ],
        'metrics' => [
            ['name' => 'totalUsers']
        ]
    ];

    $response_previous = wp_remote_post(
        'https://analyticsdata.googleapis.com/v1beta/properties/' . $property_id . ':runReport',
        [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type'  => 'application/json'
            ],
            'body' => wp_json_encode($body_previous)
        ]
    );

    $data_previous = json_decode(wp_remote_retrieve_body($response_previous), true);

    $previous_users = intval($data_previous['rows'][0]['metricValues'][0]['value'] ?? 0);

    // ======================
    // TREND
    // ======================
    $trend_percent = 0;

    if ($previous_users > 0) {
        $trend_percent = round((($users - $previous_users) / $previous_users) * 100);
    }

    $trend_label = '→';
    if ($trend_percent > 0) $trend_label = '↑';
    elseif ($trend_percent < 0) $trend_label = '↓';

    // ======================
    // TEMP DATA (will replace next)
    // ======================
    $countries = [
        ['name' => 'United States', 'percent' => 40],
        ['name' => 'India', 'percent' => 20],
        ['name' => 'United Kingdom', 'percent' => 10]
    ];

    $sources = [
        ['name' => 'Organic Search', 'percent' => 60],
        ['name' => 'Direct', 'percent' => 25],
        ['name' => 'Referral', 'percent' => 10]
    ];

    $keywords = [
        ['query' => 'data science course', 'clicks' => 120, 'position' => 4.2],
        ['query' => 'learn python free', 'clicks' => 95, 'position' => 6.8]
    ];

    // ======================
    // FINAL RESULT
    // ======================
    $result = [
        'users' => $users,
        'sessions' => $sessions,
        'trend_percent' => $trend_percent,
        'trend_label' => $trend_label,
        'updated' => time(),
        'countries' => $countries,
        'sources' => $sources,
        'keywords' => $keywords
    ];

    set_transient($cache_key, $result, 3600);

    return $result;
}
