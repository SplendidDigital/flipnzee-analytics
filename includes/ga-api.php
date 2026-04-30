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

    // ===== MOCK SAFE (Replace later with API if needed) =====
    $users = rand(100, 5000);
    $sessions = $users + rand(100, 500);

    // Trend
    $previous_users = max(1, $users - rand(50, 500));
    $trend_percent = round((($users - $previous_users) / $previous_users) * 100);

    $trend_label = '→';
    if ($trend_percent > 0) $trend_label = '↑';
    elseif ($trend_percent < 0) $trend_label = '↓';

    // Countries (demo)
    $countries = [
        ['name' => 'United States', 'percent' => 40],
        ['name' => 'India', 'percent' => 20],
        ['name' => 'United Kingdom', 'percent' => 10]
    ];

    // Sources (demo)
    $sources = [
        ['name' => 'Organic Search', 'percent' => 60],
        ['name' => 'Direct', 'percent' => 25],
        ['name' => 'Referral', 'percent' => 10]
    ];

    // Keywords (demo)
    $keywords = [
        ['query' => 'data science course', 'clicks' => 120, 'position' => 4.2],
        ['query' => 'learn python free', 'clicks' => 95, 'position' => 6.8]
    ];

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
