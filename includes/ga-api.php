<?php

function flipnzee_get_realtime_users($property_id) {

    if (!$property_id) {
        return 0;
    }

    // CACHE
    $cache_key = 'flip_live_' . $property_id;

    $cached = get_transient($cache_key);

if ($cached !== false) {
    return $cached;
}

    // USE EXISTING TOKEN SYSTEM
    $access_token = flipnzee_get_access_token();

    if (!$access_token) {
        return 0;
    }

    $response = wp_remote_post(
        "https://analyticsdata.googleapis.com/v1beta/properties/{$property_id}:runRealtimeReport",
        [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type'  => 'application/json'
            ],

            'body' => wp_json_encode([
                'metrics' => [
                    [
                        'name' => 'activeUsers'
                    ]
                ]
            ]),

            'timeout' => 20
        ]
    );

    if (is_wp_error($response)) {
        return 0;
    }

    $status = wp_remote_retrieve_response_code($response);

    if ($status !== 200) {

        error_log(
            'FLIPNZEE REALTIME ERROR: ' .
            wp_remote_retrieve_body($response)
        );

        return 0;
    }

    $data = json_decode(
        wp_remote_retrieve_body($response),
        true
    );

    $users = intval(
        $data['rows'][0]['metricValues'][0]['value'] ?? 0
    );

    // CACHE FOR 60 SECONDS
    set_transient(
        $cache_key,
        $users,
        60
    );

    return $users;
}

// ================== EMPTY RESPONSE ==================

function flipnzee_empty_response() {

    return [
        'users'          => 0,
        'sessions'       => 0,
        'trend_percent'  => 0,
        'trend_label'    => '→',
        'user_diff'      => 0,
        'updated'        => time()
    ];
}



// ================== GET ACCESS TOKEN ==================

function flipnzee_get_access_token() {

    $token = get_option('flipnzee_ga_token');

    if (!$token || !is_array($token)) {
        return false;
    }

    if (empty($token['created'])) {

        $token['created'] = time();

        update_option(
            'flipnzee_ga_token',
            $token
        );
    }

    $access_token  = $token['access_token'] ?? '';
    $refresh_token = $token['refresh_token'] ?? '';
    $expires_in    = intval($token['expires_in'] ?? 0);
    $created       = intval($token['created'] ?? 0);


    // ================= REFRESH TOKEN =================

    if (
        time() > ($created + $expires_in - 60)
        && !empty($refresh_token)
    ) {

        $response = wp_remote_post(
            'https://oauth2.googleapis.com/token',
            [
                'body' => [
                    'client_id'     => get_option('flipnzee_client_id'),
                    'client_secret' => get_option('flipnzee_client_secret'),
                    'refresh_token' => $refresh_token,
                    'grant_type'    => 'refresh_token'
                ],

                'timeout' => 20
            ]
        );

        if (is_wp_error($response)) {

            error_log(
                'FLIPNZEE TOKEN ERROR: ' .
                $response->get_error_message()
            );

            return false;
        }

        $body = json_decode(
            wp_remote_retrieve_body($response),
            true
        );

        if (!empty($body['access_token'])) {

            $token['access_token'] = $body['access_token'];

            $token['expires_in'] = intval(
                $body['expires_in'] ?? 3600
            );

            $token['created'] = time();

            update_option(
                'flipnzee_ga_token',
                $token
            );

            return $body['access_token'];
        }

        error_log(
            'FLIPNZEE REFRESH FAILED: ' .
            print_r($body, true)
        );

        return false;
    }

    return $access_token;
}



// ================== GOOGLE POST ==================

function flipnzee_google_post($endpoint, $body) {
 

    $access_token = flipnzee_get_access_token();

    if (!$access_token) {
        return false;
    }

    $response = wp_remote_post(
        $endpoint,
        [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type'  => 'application/json'
            ],

            'body' => wp_json_encode($body),

            'timeout' => 20
        ]
    );

    

    if (is_wp_error($response)) {

        error_log(
            'FLIPNZEE REQUEST ERROR: ' .
            $response->get_error_message()
        );

        return false;
    }

    $status = wp_remote_retrieve_response_code($response);

    if ($status !== 200) {

        error_log(
            'FLIPNZEE HTTP ERROR: ' .
            $status .
            ' | RESPONSE: ' .
            wp_remote_retrieve_body($response)
        );

        return false;
    }

    return json_decode(
        wp_remote_retrieve_body($response),
        true
    );
}



// ================== FETCH MAIN ==================

function flipnzee_fetch_and_store($property_id, $post_id) {

    $property_id = trim($property_id);
    $post_id     = intval($post_id);

    if (empty($property_id) || !$post_id) {
        return;
    }

    

    $access_token = flipnzee_get_access_token();

    if (!$access_token) {

        set_transient(
            "flipnzee_main_{$post_id}",
            flipnzee_empty_response(),
            HOUR_IN_SECONDS
        );

        return;
    }

    $endpoint =
        "https://analyticsdata.googleapis.com/v1beta/properties/{$property_id}:runReport";



    // ================= CURRENT PERIOD =================

    $data_current = flipnzee_google_post(
        $endpoint,
        [
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
        ]
    );

   
   

    if (!$data_current) {

        set_transient(
            "flipnzee_main_{$post_id}",
            flipnzee_empty_response(),
            HOUR_IN_SECONDS
        );

        return;
    }



    if (!empty($data_current['error'])) {

        error_log(
            'FLIPNZEE GA ERROR: ' .
            print_r($data_current['error'], true)
        );

        set_transient(
            "flipnzee_main_{$post_id}",
            flipnzee_empty_response(),
            HOUR_IN_SECONDS
        );

        return;
    }

    if (
        empty($data_current['rows']) ||
        !isset($data_current['rows'][0]['metricValues'][0]['value'])
    ) {

        error_log(
            'FLIPNZEE ERROR: Invalid GA response for property ID: ' .
            $property_id
        );

        set_transient(
            "flipnzee_main_{$post_id}",
            flipnzee_empty_response(),
            HOUR_IN_SECONDS
        );

        return;
    }

    $users = intval(
        $data_current['rows'][0]['metricValues'][0]['value'] ?? 0
    );

    $sessions = intval(
        $data_current['rows'][0]['metricValues'][1]['value'] ?? 0
    );



    // ================= PREVIOUS PERIOD =================

    $data_previous = flipnzee_google_post(
        $endpoint,
        [
            'dateRanges' => [
                [
                    'startDate' => '60daysAgo',
                    'endDate'   => '30daysAgo'
                ]
            ],

            'metrics' => [
                ['name' => 'activeUsers']
            ]
        ]
    );


  

  

    if (!$data_previous) {

        error_log(
            'FLIPNZEE PREVIOUS REQUEST FAILED: ' .
            $property_id
        );

        $previous_users = 0;

    } elseif (!empty($data_previous['error'])) {

        error_log(
            'FLIPNZEE PREVIOUS ERROR: ' .
            print_r($data_previous['error'], true)
        );

        $previous_users = 0;

   } elseif (
    empty($data_previous['rows']) ||
    !isset($data_previous['rows'][0]['metricValues'][0]['value'])
) {

    $previous_users = 0;

    error_log(
        'FLIPNZEE PREVIOUS PERIOD HAS NO DATA'
    );

    } else {

        $previous_users = intval(
            $data_previous['rows'][0]['metricValues'][0]['value']
        );
    }



    // ================= TREND =================

 
if ($previous_users <= 0){

    if ($users > 0) {

        $trend_percent = 100;
        $trend_label = '↑';

    } else {

        $trend_percent = 0;
        $trend_label = '→';
    }

} else {

    $trend_percent = round(
        (
            ($users - $previous_users)
            / $previous_users
        ) * 100
    );

    $trend_label = $trend_percent > 0
        ? '↑'
        : ($trend_percent < 0 ? '↓' : '→');
}
    // ================= SAVE =================

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

file_put_contents(
    '/tmp/flipnzee-debug.log',
    date('Y-m-d H:i:s') .
    ' ENTERED_FETCH_INSIGHTS Property=' . $property_id .
    ' Post=' . $post_id . PHP_EOL,
    FILE_APPEND
);

error_log(
    'FLIPNZEE FETCH INSIGHTS STARTED | Property: ' .
    $property_id .
    ' | Post: ' .
    $post_id
);
    

    $property_id = trim($property_id);
    $post_id     = intval($post_id);

    if (empty($property_id) || !$post_id) {
        return;
    }

   

    $access_token = flipnzee_get_access_token();

    if (!$access_token) {
        return;
    }

    $endpoint =
        "https://analyticsdata.googleapis.com/v1beta/properties/{$property_id}:runReport";



    // ================= COUNTRIES =================

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
            ]),

            'timeout' => 20
        ]
    );

    if (!is_wp_error($response)) {

        $status = wp_remote_retrieve_response_code($response);

        if ($status === 200) {

            $data = json_decode(
                wp_remote_retrieve_body($response),
                true
            );

            if (empty($data['error'])) {

                $total = 0;
                $map   = [];

                foreach ($data['rows'] ?? [] as $row) {

                    $name = $row['dimensionValues'][0]['value'] ?? 'Unknown';

                    $value = intval(
                        $row['metricValues'][0]['value'] ?? 0
                    );

                    $map[$name] = ($map[$name] ?? 0) + $value;

                    $total += $value;
                }

                foreach ($map as $name => $value) {

    $countries[] = [
        'name'    => $name,

        'users'   => $value,

        'percent' => $total > 0
            ? round(($value / $total) * 100)
            : 0
    ];
}
            }
        }
    }


    // ================= CITIES =================

$cities = [];

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
                ['name' => 'city']
            ],

            'metrics' => [
                ['name' => 'activeUsers']
            ],

            'limit' => 10
        ]),

        'timeout' => 20
    ]
);

if (!is_wp_error($response)) {

    $status = wp_remote_retrieve_response_code($response);

    if ($status === 200) {

        $data = json_decode(
            wp_remote_retrieve_body($response),
            true
        );

        if (empty($data['error'])) {

            $total = 0;
            $map   = [];

            foreach ($data['rows'] ?? [] as $row) {

                $name = $row['dimensionValues'][0]['value'] ?? 'Unknown';

                $value = intval(
                    $row['metricValues'][0]['value'] ?? 0
                );

                $map[$name] = ($map[$name] ?? 0) + $value;

                $total += $value;
            }

            foreach ($map as $name => $value) {

                $cities[] = [
                    'name'    => $name,
                    'percent' => $total > 0
                        ? round(($value / $total) * 100)
                        : 0
                ];
            }
        }
    }
}



$avg_duration = 0;
$pages_per_user = 0;

// ================= RETURNING VISITORS =================

$returning_visitors = 0;

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

'metrics' => [
    ['name' => 'activeUsers'],
    ['name' => 'newUsers'],
    ['name' => 'averageSessionDuration'],
    ['name' => 'screenPageViews']
]
        ]),

        'timeout' => 20
    ]
);

if (
    !is_wp_error($response) &&
    wp_remote_retrieve_response_code($response) === 200
) {

    $data = json_decode(
        wp_remote_retrieve_body($response),
        true
    );

    $active_users = intval(
        $data['rows'][0]['metricValues'][0]['value'] ?? 0
    );

    $new_users = intval(
        $data['rows'][0]['metricValues'][1]['value'] ?? 0
    );
    

$avg_duration = intval(
    $data['rows'][0]['metricValues'][2]['value'] ?? 0
);

$pageviews = intval(
    $data['rows'][0]['metricValues'][3]['value'] ?? 0
);

    $returning_visitors = max(
        0,
        $active_users - $new_users
    );
    $pages_per_user = $active_users > 0
    ? round($pageviews / $active_users, 1)
    : 0;

  
}    



    // ================= SOURCES =================

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
            ]),

            'timeout' => 20
        ]
    );

    if (!is_wp_error($response)) {

        $status = wp_remote_retrieve_response_code($response);

        if ($status === 200) {

            $data = json_decode(
                wp_remote_retrieve_body($response),
                true
            );

            if (empty($data['error'])) {

                $total = 0;

                foreach ($data['rows'] ?? [] as $row) {

                    $value = intval(
                        $row['metricValues'][0]['value'] ?? 0
                    );

                    $sources[] = [
                        'name'  => $row['dimensionValues'][0]['value'] ?? '',
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
        }
    }



    // ================= KEYWORDS =================

    $keywords = [];

$organic_clicks = 0;
$organic_impressions = 0;
$ranking_keywords = 0;
$indexed_pages = 0;
    $site_url = get_post_meta(
        $post_id,
        '_ga_domain',
        true
    );

    if ($site_url) {

        $domain = preg_replace(
            '#^https?://#',
            '',
            trim($site_url)
        );

        $domain = rtrim($domain, '/');

        $variants = [
            'https://' . $domain . '/',
            'sc-domain:' . $domain,
            'http://' . $domain . '/'
        ];

        foreach ($variants as $site_for_api) {

            

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

                        'startDate' => date(
                            'Y-m-d',
                            strtotime('-30 days')
                        ),

                        'endDate' => date('Y-m-d'),

                        'dimensions' => ['query'],

                        'rowLimit' => 50,

                        'searchType' => 'web'
                    ]),

                    'timeout' => 20
                ]
            );

            if (is_wp_error($response)) {
                continue;
            }

            $status = wp_remote_retrieve_response_code($response);

            if ($status !== 200) {

                error_log(
                    'FLIPNZEE SC HTTP ERROR: ' .
                    $status .
                    ' | RESPONSE: ' .
                    wp_remote_retrieve_body($response)
                );

                continue;
            }

            $raw = wp_remote_retrieve_body($response);

            $data = json_decode($raw, true);
            file_put_contents(
    '/tmp/flipnzee-debug.log',
    date('Y-m-d H:i:s') .
    ' SC_ROWS=' .
    count($data['rows'] ?? []) .
    PHP_EOL,
    FILE_APPEND
);

          

            if (!empty($data['rows'])) {

                foreach ($data['rows'] as $row) {

    $clicks = intval(
        $row['clicks'] ?? 0
    );

    $impressions = intval(
        $row['impressions'] ?? 0
    );

    $organic_clicks += $clicks;

    $organic_impressions += $impressions;

    $keywords[] = [
        'query' => $row['keys'][0] ?? '',

        'clicks' => $clicks,

        'impressions' => $impressions,

        'position' => round(
            $row['position'] ?? 0,
            1
        )
    ];
}
$ranking_keywords = count($keywords);
$page_response = wp_remote_post(
    'https://searchconsole.googleapis.com/webmasters/v3/sites/' .
    urlencode($site_for_api) .
    '/searchAnalytics/query',

    [
        'headers' => [
            'Authorization' => 'Bearer ' . $access_token,
            'Content-Type'  => 'application/json'
        ],

        'body' => wp_json_encode([

            'startDate' => date(
                'Y-m-d',
                strtotime('-90 days')
            ),

            'endDate' => date('Y-m-d'),

            'dimensions' => ['page'],

            'rowLimit' => 25000,

            'searchType' => 'web'
        ]),

        'timeout' => 20
    ]
);

if (
    !is_wp_error($page_response) &&
    wp_remote_retrieve_response_code($page_response) === 200
) {

    $page_data = json_decode(
        wp_remote_retrieve_body($page_response),
        true
    );

    $indexed_pages = count(
        $page_data['rows'] ?? []
    );
    file_put_contents(
    '/tmp/flipnzee-debug.log',
    date('Y-m-d H:i:s') .
    ' INDEXED_PAGES=' .
    $indexed_pages .
    PHP_EOL,
    FILE_APPEND
);
}
file_put_contents(
    '/tmp/flipnzee-debug.log',
    date('Y-m-d H:i:s') .
    ' CLICKS=' . $organic_clicks .
    ' IMPRESSIONS=' . $organic_impressions .
    ' KEYWORDS=' . $ranking_keywords .
    PHP_EOL,
    FILE_APPEND
);

                usort($keywords, function($a, $b) {

                    return ($a['position'] ?? 999)
                        <=> ($b['position'] ?? 999);

                });

                break;
            }
        }
    }


error_log(
    'FLIPNZEE SEO: ' .
    print_r(
        [
            'clicks' => $organic_clicks,
            'impressions' => $organic_impressions,
            'keywords' => $ranking_keywords
        ],
        true
    )
);


    // ================= SAVE META =================
file_put_contents(
    '/tmp/flipnzee-debug.log',
    date('Y-m-d H:i:s') .
    ' SAVING_META clicks=' . $organic_clicks .
    ' impressions=' . $organic_impressions .
    ' keywords=' . $ranking_keywords .
    PHP_EOL,
    FILE_APPEND
);
  set_transient(
    "flipnzee_meta_{$post_id}",
    [
        'countries'            => $countries,
        'cities'               => $cities,
        'sources'              => $sources,
        'keywords'             => $keywords,

       'organic_clicks'       => $organic_clicks,
'organic_impressions'  => $organic_impressions,
'ranking_keywords'     => $ranking_keywords,
'indexed_pages'        => $indexed_pages,

'returning_visitors'   => $returning_visitors,
        'avg_duration'         => $avg_duration,
        'pages_per_user'       => $pages_per_user
    ],
    6 * HOUR_IN_SECONDS
);  
}
function flipnzee_fetch_recent_activity(
    $property_id,
    $post_id
) {

    $endpoint =
        "https://analyticsdata.googleapis.com/v1beta/properties/{$property_id}:runReport";

    $data = flipnzee_google_post(
        $endpoint,
        [
            'dateRanges' => [
                [
                    'startDate' => '1daysAgo',
                    'endDate'   => 'today'
                ]
            ],

            'metrics' => [
                [
                    'name' => 'activeUsers'
                ]
            ],

            'dimensions' => [
                [
                    'name' => 'city'
                ]
            ],

            'limit' => 10
        ]
    );

    if (!$data || empty($data['rows'])) {
        

        set_transient(
            "flipnzee_recent_{$post_id}",
            [
                'users_24h' => 0,
                'cities_24h' => []
            ],
            30 * MINUTE_IN_SECONDS
        );

        return;
    }

    $users_24h = 0;
    $cities_24h = [];

    foreach ($data['rows'] as $row) {

        $city =
            $row['dimensionValues'][0]['value']
            ?? 'Unknown';

        $users =
            intval(
                $row['metricValues'][0]['value']
                ?? 0
            );

        $users_24h += $users;

        $cities_24h[] = [
            'name' => $city,
            'users' => $users
        ];
    }

    set_transient(
        "flipnzee_recent_{$post_id}",
        [
            'users_24h' => $users_24h,
            'cities_24h' => $cities_24h
        ],
        30 * MINUTE_IN_SECONDS
    );
}

function flipnzee_get_recent_activity(
    $post_id
) {

    $cache =
        get_transient(
            "flipnzee_recent_{$post_id}"
        );

    if ($cache !== false) {
        return $cache;
    }

    $property_id = get_post_meta(
        $post_id,
        '_ga_property_id',
        true
    );

    if ($property_id) {

        flipnzee_fetch_recent_activity(
            $property_id,
            $post_id
        );

        return get_transient(
            "flipnzee_recent_{$post_id}"
        );
    }

    return [
        'users_24h' => 0,
        'cities_24h' => []
    ];
}