<?php

// ================= HELPERS =================

function flipnzee_get_stats($post_id) {

    $stats = get_transient("flipnzee_main_{$post_id}");

    if (!$stats || empty($stats['users'])) {

        $property_id = get_post_meta(
            $post_id,
            '_ga_property_id',
            true
        );

        if ($property_id) {

            flipnzee_fetch_and_store($property_id, $post_id);

            $stats = get_transient("flipnzee_main_{$post_id}");
        }
    }

    if (!$stats) {

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

    $meta = get_transient("flipnzee_meta_{$post_id}");

    if (!$meta) {

        $property_id = get_post_meta(
            $post_id,
            '_ga_property_id',
            true
        );

        if ($property_id) {

            flipnzee_fetch_insights($property_id, $post_id);

            $meta = get_transient("flipnzee_meta_{$post_id}");
        }
    }

    return $meta ?: [];
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

    if (!is_singular('listing')) {
        return '';
    }

    $post_id = get_the_ID();

    $stats = flipnzee_get_stats($post_id);
    $meta  = flipnzee_get_meta($post_id);

    ob_start();

?>

<style>

.flip-wrap{
    border:1px solid #e5e7eb;
    border-radius:16px;
    padding:24px;
    background:#fff;
    margin:20px 0;
}

.flip-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:20px;
}

.flip-title-big{
    font-size:20px;
    font-weight:700;
}

.flip-rank-badge{
    background:linear-gradient(135deg,#16a34a,#22c55e);
    color:#fff;
    padding:6px 12px;
    border-radius:999px;
    font-size:13px;
}

.flip-kpi-grid{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:16px;
    margin-bottom:20px;
}

.flip-kpi-box{
    background:#f9fafb;
    border-radius:12px;
    padding:16px;
    text-align:center;
}

.flip-kpi-value{
    font-size:26px;
    font-weight:700;
}

.flip-kpi-label{
    font-size:12px;
    color:#6b7280;
}

.flip-trend-up{
    color:#16a34a;
}

.flip-trend-down{
    color:#dc2626;
}

.flip-section{
    margin-top:20px;
}

.flip-bar{
    height:8px;
    background:#e5e7eb;
    border-radius:6px;
    overflow:hidden;
}

.flip-bar-fill{
    height:100%;
    background:#3b82f6;
}

.flip-row{
    margin-bottom:10px;
}

.flip-keyword{
    display:flex;
    justify-content:space-between;
    font-size:14px;
}

.flip-top-keyword{
    color:#16a34a;
    font-weight:600;
}

</style>


<div class="flip-wrap">

    <div class="flip-header">

        <div class="flip-title-big">
            ✔ Google Verified Analytics
        </div>

        <div class="flip-rank-badge">
            <?php echo number_format($stats['users']); ?> Users
        </div>

    </div>


    <div class="flip-kpi-grid">

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

    return ob_get_clean();

});


// ================= ALL LISTINGS GRID =================

add_shortcode('flipnzee_all_listings', function () {

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

    usort($items, function ($a, $b) {

        return ($b->flip_stats['users'] ?? 0)
            <=>
            ($a->flip_stats['users'] ?? 0);

    });

    ob_start();

?>

<style>

.flip-grid{
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(300px,1fr));
    gap:28px;
}

.flip-card-listing{
    border-radius:18px;
    padding:20px;
    background:#ffffff;
    border:1px solid #e5e7eb;
    box-shadow:0 6px 20px rgba(0,0,0,0.06);
    transition:0.3s ease;
    position:relative;
}

.flip-card-listing:hover{
    transform:translateY(-6px);
    box-shadow:0 14px 35px rgba(0,0,0,0.12);
}

.flip-rank{
    position:absolute;
    top:-12px;
    left:-12px;
    padding:8px 14px;
    border-radius:999px;
    color:#fff;
    font-size:13px;
    font-weight:700;
}

.flip-rank.top-1{
    background:linear-gradient(135deg,#facc15,#eab308);
}

.flip-rank.top-2{
    background:linear-gradient(135deg,#9ca3af,#6b7280);
}

.flip-rank.top-3{
    background:linear-gradient(135deg,#fb923c,#ea580c);
}

.flip-users{
    font-size:30px;
    font-weight:700;
    margin-top:10px;
}

.flip-sub{
    font-size:13px;
    color:#6b7280;
}

.flip-trending{
    font-size:11px;
    background:#ecfdf5;
    color:#16a34a;
    padding:5px 10px;
    border-radius:8px;
    display:inline-block;
    margin-top:6px;
}

</style>


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