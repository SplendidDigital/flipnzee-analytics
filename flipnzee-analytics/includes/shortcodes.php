<?php


// ================= HELPERS =================
function flipnzee_get_stats($post_id) {


    // Force integer safety

    $post_id = intval($post_id);


    if (!$post_id) {
        return flipnzee_empty_response();
    }

 // Unique transient key
$cache_key = "flipnzee_main_{$post_id}";

// Force refresh temporarily
$stats = get_transient($cache_key);

    // ONLY fetch if transient truly missing
    // (not when users = 0)
    if ($stats === false) {

        $property_id = get_post_meta(
            $post_id,
            '_ga_property_id',
            true
        );

        $property_id = trim($property_id);

        if (!empty($property_id)) {
  

            // Fresh fetch
            flipnzee_fetch_and_store(
                $property_id,
                $post_id
            );

            // Reload transient after fetch
            $stats = get_transient($cache_key);
        }
    }

    // Final fallback
    if (!$stats || !is_array($stats)) {

        return [
            'users'         => 0,
            'sessions'      => 0,
            'trend_percent' => 0,
            'trend_label'   => '→',
            'user_diff'     => 0,
            'updated'       => time()
        ];
    }

    return $stats;
}



function flipnzee_get_meta($post_id) {

  file_put_contents(
    '/tmp/flipnzee-debug.log',
    date('Y-m-d H:i:s') .
    ' GET_META Post=' . $post_id . PHP_EOL,
    FILE_APPEND
);
    

    // Force integer safety
    $post_id = intval($post_id);

    if (!$post_id) {
        return [];
    }

    // Unique transient key
    $cache_key = "flipnzee_meta_{$post_id}";

    $meta = get_transient($cache_key);

    // ONLY fetch if transient missing
    if ($meta === false) {

        $property_id = get_post_meta(
            $post_id,
            '_ga_property_id',
            true
        );

        $property_id = trim($property_id);

        if (!empty($property_id)) {

    file_put_contents(
        '/tmp/flipnzee-debug.log',
        date('Y-m-d H:i:s') .
        ' ABOUT_TO_CALL_FETCH_INSIGHTS Property=' . $property_id .
        ' Post=' . $post_id . PHP_EOL,
        FILE_APPEND
    );

    file_put_contents(
    '/tmp/flipnzee-debug.log',
    date('Y-m-d H:i:s') .
    ' FUNCTION_EXISTS=' .
    (function_exists('flipnzee_fetch_insights') ? 'YES' : 'NO') .
    PHP_EOL,
    FILE_APPEND
);

    flipnzee_fetch_insights(
        $property_id,
        $post_id
    );
}

            // Reload transient
            $meta = get_transient($cache_key);
        }
    

    // Safety fallback
    if (!$meta || !is_array($meta)) {

    return [
    'countries'          => [],
    'cities'             => [],
    'sources'            => [],
    'keywords'           => [],
     'indexed_pages'      => 0,
    'returning_visitors' => 0,
    'avg_duration'       => 0,
    'pages_per_user'     => 0
];
}

    return $meta;
}



function flipnzee_clean_source_name($name) {

    $map = [
        'Organic Search' => 'SEO',
        'Direct'         => 'Direct',
        'Referral'       => 'Referrals',
        'Paid Search'    => 'Ads',
        'Organic Social' => 'Social',
        'Email'          => 'Email'
    ];

    return $map[$name] ?? $name;
}



// ================= VERIFIED DASHBOARD =================

add_shortcode('flipnzee_verified_badge', function () {

 
   if (is_admin() && defined('REST_REQUEST') && REST_REQUEST) {
    return '';
}

   
if (!is_singular('listing')) {
    return '';
}


    $post_id = get_the_ID();
    



    $stats = flipnzee_get_stats($post_id);

    $property_id = get_post_meta(
    $post_id,
    '_ga_property_id',
    true
);

$live_users = flipnzee_get_realtime_users($property_id);

$recent = flipnzee_get_recent_activity(
    $post_id
);

$meta = flipnzee_get_meta($post_id);
$avg_duration = intval(
    $meta['avg_duration'] ?? 0
);

$pages_per_user = round(
    floatval($meta['pages_per_user'] ?? 0),
    1
);

$organic_clicks = intval(
    $meta['organic_clicks'] ?? 0
);

$organic_impressions = intval(
    $meta['organic_impressions'] ?? 0
);

$ranking_keywords = intval(
    $meta['ranking_keywords'] ?? 0
);
$indexed_pages = intval(
    $meta['indexed_pages'] ?? 0
);

$avg_minutes = floor($avg_duration / 60);

$avg_seconds = $avg_duration % 60;

$avg_time_display =
    $avg_minutes . 'm ' .
    str_pad(
        $avg_seconds,
        2,
        '0',
        STR_PAD_LEFT
    ) . 's';

    ob_start();

?>

<div class="flip-wrap">

    <?php if (current_user_can('manage_options')) : ?>

        <p style="margin-bottom:20px;">

            <a
                href="<?php echo esc_url(
                    admin_url(
                        'admin-post.php?action=flipnzee_refresh_data&post_id=' . get_the_ID()
                    )
                ); ?>"
                class="button button-primary"
            >
                Refresh Analytics
            </a>

        </p>

    <?php endif; ?>


    
    <div class="flip-header">

        <div class="flip-title-big">
            ✔ Google Verified Analytics
        </div>

        <div class="flip-rank-badge">
            <?php echo number_format($stats['users']); ?> Users
        </div>

    </div>


    <div class="flip-kpi-grid">

    <div class="flip-kpi-box live-users-box">

    <div class="flip-kpi-value flip-live-users">
        <?php echo number_format($live_users); ?>
    </div>

    <div class="flip-kpi-label">
        Live Users
    </div>

</div>
<div class="flip-kpi-box">

    <div class="flip-kpi-value">
        <?php echo number_format(
            $recent['users_24h'] ?? 0
        ); ?>
    </div>

    <div class="flip-kpi-label">
        Visitors (24h)
    </div>

</div>

<div class="flip-kpi-box">

    <div class="flip-kpi-value">
        <?php
        echo number_format(
            $meta['returning_visitors'] ?? 0
        );
        ?>
    </div>

    <div class="flip-kpi-label">
        Returning Visitors
    </div>

</div>

<div class="flip-kpi-box">

    <div class="flip-kpi-value">
        <?php echo esc_html($avg_time_display); ?>
    </div>

    <div class="flip-kpi-label">
        Avg Time
    </div>

</div>

<div class="flip-kpi-box">

    <div class="flip-kpi-value">
        <?php echo esc_html($pages_per_user); ?>
    </div>

    <div class="flip-kpi-label">
        Pages / User
    </div>

</div>

<div class="flip-kpi-box">

    <div class="flip-kpi-value">
        <?php echo number_format($ranking_keywords); ?>
    </div>

    <div class="flip-kpi-label">
        Ranking Keywords
    </div>

</div>

<div class="flip-kpi-box">

    <div class="flip-kpi-value">
        <?php echo number_format($indexed_pages); ?>
    </div>

    <div class="flip-kpi-label">
        Search Visible Pages (90d)
    </div>

</div>

<div class="flip-kpi-box">

    <div class="flip-kpi-value">
        <?php echo number_format($organic_impressions); ?>
    </div>

    <div class="flip-kpi-label">
        Google Impressions
    </div>

</div>



<div class="flip-kpi-box">

    <div class="flip-kpi-value">
        <?php echo number_format($organic_clicks); ?>
    </div>

    <div class="flip-kpi-label">
        Organic Clicks
    </div>

</div>

        <div class="flip-kpi-box">
            <div class="flip-kpi-value">
                <?php echo number_format($stats['users']); ?>
            </div>

            <div class="flip-kpi-label">
                Users
            </div>
        </div>


        <div class="flip-kpi-box">
            <div class="flip-kpi-value">
                <?php echo number_format($stats['sessions']); ?>
            </div>

            <div class="flip-kpi-label">
                Sessions
            </div>
        </div>


        <div class="flip-kpi-box">

            <div class="flip-kpi-value <?php echo ($stats['trend_percent'] >= 0 ? 'flip-trend-up' : 'flip-trend-down'); ?>">

                <?php
                echo $stats['trend_label'] . " " .
                abs($stats['trend_percent']);
                ?>%

            </div>

            <div class="flip-kpi-label">
                Growth
            </div>

        </div>

    </div>


    <div class="flip-kpi-label">

        Updated
        <?php
        echo human_time_diff(
            $stats['updated'],
            current_time('timestamp')
        );
        ?>
        ago

    </div>

    <?php if (!empty($recent['cities_24h'])) : ?>

<div class="flip-section">

    <h4>Top Cities (24h)</h4>

    <?php foreach ($recent['cities_24h'] as $city) : ?>

        <div class="flip-keyword">

            <span>
                <?php
                echo esc_html(
                    $city['name']
                );
                ?>
            </span>

            <span>
                <?php
                echo number_format(
                    $city['users']
                );
                ?>
            </span>

        </div>

    <?php endforeach; ?>

</div>

<?php endif; ?>


    <!-- COUNTRIES -->

    <?php if (!empty($meta['countries'])) : ?>

        <div class="flip-section">

            <h4>Top Countries</h4>

            <?php foreach ($meta['countries'] as $c) : ?>

                <?php $percent = $c['percent'] ?? 0; ?>

                <div class="flip-row">

                    <div class="flip-keyword">

                        <span>
                            <?php echo esc_html($c['name'] ?? ''); ?>
                        </span>

                        <span>
                            <?php echo esc_html($percent); ?>%
                        </span>

                    </div>

                    <div class="flip-bar">

                        <div
                            class="flip-bar-fill"
                            style="width:<?php echo esc_attr($percent); ?>%"
                        ></div>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>


    <!-- CITIES -->

<?php if (!empty($meta['cities'])) : ?>

    <div class="flip-section">

        <h4>Top Cities</h4>

        <?php foreach ($meta['cities'] as $c) : ?>

            <?php $percent = $c['percent'] ?? 0; ?>

            <div class="flip-row">

                <div class="flip-keyword">

                    <span>
                        <?php echo esc_html($c['name'] ?? ''); ?>
                    </span>

                    <span>
                        <?php echo esc_html($percent); ?>%
                    </span>

                </div>

                <div class="flip-bar">

                    <div
                        class="flip-bar-fill"
                        style="width:<?php echo esc_attr($percent); ?>%"
                    ></div>

                </div>

            </div>

        <?php endforeach; ?>

    </div>

<?php endif; ?>


    <!-- SOURCES -->

    <?php if (!empty($meta['sources'])) : ?>

        <div class="flip-section">

            <h4>Traffic Sources</h4>

            <?php foreach ($meta['sources'] as $s) : ?>

                <?php $percent = $s['percent'] ?? 0; ?>

                <div class="flip-row">

                    <div class="flip-keyword">

                        <span>
                            <?php
                            echo esc_html(
                                flipnzee_clean_source_name(
                                    $s['name'] ?? ''
                                )
                            );
                            ?>
                        </span>

                        <span>
                            <?php echo esc_html($percent); ?>%
                        </span>

                    </div>

                    <div class="flip-bar">

                        <div
                            class="flip-bar-fill"
                            style="width:<?php echo esc_attr($percent); ?>%"
                        ></div>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>


    <!-- KEYWORDS -->

    <?php if (!empty($meta['keywords'])) : ?>

        <div class="flip-section">

            <h4>Top Keywords</h4>

            <?php foreach ($meta['keywords'] as $k) : ?>

                <div class="flip-keyword <?php echo (($k['position'] ?? 0) <= 3 ? 'flip-top-keyword' : ''); ?>">

                    <span>
                        <?php echo esc_html($k['query'] ?? ''); ?>
                    </span>

                    <span>
                        #<?php echo esc_html($k['position'] ?? 0); ?>
                    </span>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</div>

<?php

?>

<script>

setInterval(function () {

    fetch(window.location.pathname + '?flipnzee_live=1')

    .then(response => response.text())

    .then(data => {

        const el = document.querySelector('.flip-live-users');

if (el && data.trim() !== '') {
    el.innerHTML = data;
}

    });

}, 30000);

</script>

<?php

    return ob_get_clean();

});



// ================= ALL LISTINGS GRID =================

add_shortcode('flipnzee_all_listings', function () {

if (is_admin() && defined('REST_REQUEST') && REST_REQUEST) {
    return '';
}

    $posts = get_posts([
        'post_type'      => 'listing',
        'posts_per_page' => -1,
        'post_status'    => 'publish'
    ]);

    if (!$posts) {
        return "<p>No listings found.</p>";
    }

    $items = [];

    foreach ($posts as $post) {

        $stats = flipnzee_get_stats($post->ID);

        $property_id = get_post_meta(
            $post->ID,
            '_ga_property_id',
            true
        );

        if (!$property_id) {
            continue;
        }

        $post->flip_stats = $stats;

        $items[] = $post;
    }

   $sort = sanitize_text_field(
    $_GET['sort'] ?? 'users'
);

echo '<!-- SORT=' . esc_html($sort) . ' -->';

switch ($sort) {

    case 'growth':

        usort($items, function ($a, $b) {

            return ($b->flip_stats['trend_percent'] ?? 0)
                <=>
                ($a->flip_stats['trend_percent'] ?? 0);

        });

        break;

    case 'keywords':

        usort($items, function ($a, $b) {

            return intval(
                get_post_meta(
                    $b->ID,
                    '_flip_keywords',
                    true
                )
            )
            <=>
            intval(
                get_post_meta(
                    $a->ID,
                    '_flip_keywords',
                    true
                )
            );

        });

        break;

    case 'clicks':

        usort($items, function ($a, $b) {

            return intval(
                get_post_meta(
                    $b->ID,
                    '_flip_clicks',
                    true
                )
            )
            <=>
            intval(
                get_post_meta(
                    $a->ID,
                    '_flip_clicks',
                    true
                )
            );

        });

        break;

    case 'impressions':

        usort($items, function ($a, $b) {

            return intval(
                get_post_meta(
                    $b->ID,
                    '_flip_impressions',
                    true
                )
            )
            <=>
            intval(
                get_post_meta(
                    $a->ID,
                    '_flip_impressions',
                    true
                )
            );

        });

        break;

    case 'indexed_pages':

        usort($items, function ($a, $b) {

            return intval(
                get_post_meta(
                    $b->ID,
                    '_flip_indexed_pages',
                    true
                )
            )
            <=>
            intval(
                get_post_meta(
                    $a->ID,
                    '_flip_indexed_pages',
                    true
                )
            );

        });

        break;

    default:

        usort($items, function ($a, $b) {

            return ($b->flip_stats['users'] ?? 0)
                <=>
                ($a->flip_stats['users'] ?? 0);

        });

}

    ob_start();

?>

<form method="get" style="margin-bottom:20px;">

    <label>
        <strong>Sort By:</strong>
    </label>

    <select
        name="sort"
        onchange="this.form.submit();"
        style="margin-left:10px;padding:6px;"
    >

        <option value="users"
            <?php selected($sort, 'users'); ?>>
            Users
        </option>

        <option value="growth"
            <?php selected($sort, 'growth'); ?>>
            Growth
        </option>

        <option value="keywords"
            <?php selected($sort, 'keywords'); ?>>
            Ranking Keywords
        </option>

        <option value="clicks"
            <?php selected($sort, 'clicks'); ?>>
            Organic Clicks
        </option>

        <option value="impressions"
            <?php selected($sort, 'impressions'); ?>>
            Google Impressions
        </option>

        <option value="indexed_pages"
            <?php selected($sort, 'indexed_pages'); ?>>
            Search Visible Pages (90d)
        </option>

    </select>

</form>
<div class="flip-grid">

<?php $rank = 1; ?>

<?php foreach ($items as $post) : ?>

    <?php $stats = $post->flip_stats; ?>

    <div class="flip-card-listing">

    

        <div class="flip-rank <?php echo ($rank <= 3 ? "top-$rank" : ""); ?>">
            #<?php echo $rank; ?>
        </div>

        <h3>
            <?php echo esc_html(get_the_title($post->ID)); ?>
        </h3>

        <?php if ($stats['trend_percent'] > 20) : ?>

            <div class="flip-trending">
                Fast Growing
            </div>

        <?php endif; ?>


        <div class="flip-users">

            <?php
            echo ($stats['users'] > 0)
                ? number_format($stats['users'])
                : '—';
            ?>

        </div>

        <div class="flip-sub">
            Users (30 days)
        </div>

        <?php if ($stats['users'] == 0) : ?>

            <div class="flip-sub">
                Fetching data...
            </div>

        <?php endif; ?>


        <div class="flip-sub">

            <?php
            echo $stats['trend_label'] .
            " " .
            abs($stats['trend_percent']);
            ?>%

        </div>


        <a href="<?php echo esc_url(get_permalink($post->ID)); ?>">
            View Details →
        </a>

    </div>

<?php $rank++; ?>

<?php endforeach; ?>

</div>

<?php

    return ob_get_clean();

});

// ================= MARKET OVERVIEW =================

add_shortcode('flipnzee_market_overview', function () {

    $posts = get_posts([
        'post_type'      => 'listing',
        'posts_per_page' => -1,
        'post_status'    => 'publish'
    ]);

    if (!$posts) {
        return '<p>No listings found.</p>';
    }

   $total_users = 0;
$total_sessions = 0;

$items = [];

$all_countries = [];

    foreach ($posts as $post) {

        $property_id = get_post_meta(
            $post->ID,
            '_ga_property_id',
            true
        );

        if (!$property_id) {
            continue;
        }

        $stats = flipnzee_get_stats($post->ID);

        $meta = flipnzee_get_meta($post->ID);

if (!empty($meta['countries'])) {

    foreach ($meta['countries'] as $country) {

    $name = $country['name'] ?? '';

    $users = intval(
        $country['users'] ?? 0
    );

    if (!$name) {
        continue;
    }

    if (!isset($all_countries[$name])) {
        $all_countries[$name] = 0;
    }

    $all_countries[$name] += $users;
}
}

        $total_users += intval($stats['users']);
        $total_sessions += intval($stats['sessions']);

        $post->flip_stats = $stats;

        $items[] = $post;
    }

    usort($items, function ($a, $b) {

        return ($b->flip_stats['users'] ?? 0)
            <=>
            ($a->flip_stats['users'] ?? 0);

    });

    arsort($all_countries);

    ob_start();

?>

<div class="flip-wrap">

    <div class="flip-header">

        <div class="flip-title-big">
            Market Analytics Overview
        </div>

    </div>

    <div class="flip-kpi-grid">

        <div class="flip-kpi-box">

            <div class="flip-kpi-value">
                <?php echo number_format($total_users); ?>
            </div>

            <div class="flip-kpi-label">
                Total Users
            </div>

        </div>

        <div class="flip-kpi-box">

            <div class="flip-kpi-value">
                <?php echo number_format($total_sessions); ?>
            </div>

            <div class="flip-kpi-label">
                Total Sessions
            </div>

        </div>

        <div class="flip-kpi-box">

            <div class="flip-kpi-value">
                <?php echo count($items); ?>
            </div>

            <div class="flip-kpi-label">
                Properties Tracked
            </div>

        </div>

    </div>

    <div class="flip-section">

        <h3>Property Rankings</h3>

        <?php $rank = 1; ?>

<?php foreach ($items as $post) : ?>

    <?php $stats = $post->flip_stats; ?>

    <div class="flip-card-listing">

        <div class="flip-rank">
            #<?php echo $rank; ?>
        </div>

        <h4>
            <a href="<?php echo esc_url(get_permalink($post->ID)); ?>">
                <?php echo esc_html(get_the_title($post->ID)); ?>
            </a>
        </h4>

        <p>
            Users:
            <strong>
                <?php echo number_format($stats['users']); ?>
            </strong>
        </p>

        <p>
            Sessions:
            <strong>
                <?php echo number_format($stats['sessions']); ?>
            </strong>
        </p>

        <p>
            Growth:
            <strong>
                <?php
                echo $stats['trend_label']
                . ' '
                . abs($stats['trend_percent']);
                ?>%
            </strong>
        </p>

    </div>

    <?php $rank++; ?>

<?php endforeach; ?>

<?php if (!empty($all_countries)) : ?>

    <div class="flip-section">

        <h3>Market Geography</h3>

        <?php foreach (
            array_slice($all_countries, 0, 10, true)
            as $country => $score
        ) : ?>

            <div class="flip-keyword">

                <span>
                    <?php echo esc_html($country); ?>
                </span>

                <span>
                    <?php echo number_format($score); ?> users
                </span>

            </div>

        <?php endforeach; ?>

    </div>

<?php endif; ?>

    </div>

</div>

<?php

return ob_get_clean();

});


