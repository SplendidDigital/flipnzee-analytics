<?php

add_shortcode('flipnzee_verified_badge', function () {

    if (!is_singular('listing')) return '';

    $post_id = get_the_ID();
    $property_id = get_post_meta($post_id, '_ga_property_id', true);

    if (!$property_id) return '';

    $stats = flipnzee_fetch_users($property_id, $post_id);

    ob_start();
?>

<div style="border:1px solid #ddd;padding:20px;border-radius:10px;margin:20px 0;">

```
<h3>✔ Google Verified Traffic</h3>

<h1><?php echo number_format($stats['users']); ?></h1>
<p>Users (Last 30 Days)</p>

<p>Sessions: <?php echo number_format($stats['sessions']); ?></p>

<p><?php echo $stats['trend_label']; ?> <?php echo $stats['trend_percent']; ?>%</p>

<hr>

<strong>Top Countries</strong>
<?php foreach ($stats['countries'] as $c): ?>
    <div><?php echo $c['name']; ?> — <?php echo $c['percent']; ?>%</div>
<?php endforeach; ?>

<hr>

<strong>Traffic Sources</strong>
<?php foreach ($stats['sources'] as $s): ?>
    <div><?php echo $s['name']; ?> — <?php echo $s['percent']; ?>%</div>
<?php endforeach; ?>

<hr>

<strong>Top Keywords</strong>
<?php foreach ($stats['keywords'] as $k): ?>
    <div>
        <?php echo $k['query']; ?> — <?php echo $k['clicks']; ?> clicks (Pos <?php echo $k['position']; ?>)
    </div>
<?php endforeach; ?>
```

</div>

<?php
    return ob_get_clean();
});



add_action('admin_menu', function () {
add_menu_page(
'Flipnzee Analytics',
'Flipnzee Analytics',
'manage_options',
'flipnzee-analytics',
'flipnzee_analytics_settings_page'
);
});

function flipnzee_analytics_settings_page() {

```
if (isset($_POST['flipnzee_token'])) {
    update_option('flipnzee_ga_token', [
        'access_token' => sanitize_text_field($_POST['flipnzee_token'])
    ]);
    echo '<div class="updated"><p>Token Saved</p></div>';
}

?>
<div class="wrap">
    <h1>Flipnzee Analytics</h1>

    <form method="post">
        <label>Google Access Token</label><br><br>

        <input type="text" name="flipnzee_token" style="width:500px;" />

        <br><br>
        <button type="submit" class="button button-primary">Save Token</button>
    </form>
</div>
<?php
```

}

