<?php

if (!defined('ABSPATH')) {
    exit;
}



/**
 * =========================
 * ADD META BOX
 * =========================
 */

add_action('add_meta_boxes', function () {

    add_meta_box(
        'flipnzee_ga_meta',
        'Flipnzee Analytics',
        'flipnzee_render_meta_box',
        'listing',
        'normal',
        'high'
    );
});



/**
 * =========================
 * RENDER META BOX
 * =========================
 */

function flipnzee_render_meta_box($post) {

    wp_nonce_field(
        'flipnzee_save_meta',
        'flipnzee_meta_nonce'
    );

    $property_id = get_post_meta(
        $post->ID,
        '_ga_property_id',
        true
    );

    $domain = get_post_meta(
        $post->ID,
        '_ga_domain',
        true
    );

    ?>

    <p>
        <label>
            <strong>Google Analytics Property ID</strong>
        </label>
    </p>

    <input
        type="text"
        name="flipnzee_property_id"
        value="<?php echo esc_attr($property_id); ?>"
        style="width:100%;"
        placeholder="123456789"
    />

    <br><br>

    <p>
        <label>
            <strong>Website Domain</strong>
        </label>
    </p>

    <input
        type="text"
        name="flipnzee_domain"
        value="<?php echo esc_attr($domain); ?>"
        style="width:100%;"
        placeholder="https://example.com"
    />

    <?php
}



/**
 * =========================
 * SAVE META BOX
 * =========================
 */

add_action('save_post_listing', function ($post_id) {

    if (
        !isset($_POST['flipnzee_meta_nonce']) ||
        !wp_verify_nonce(
            $_POST['flipnzee_meta_nonce'],
            'flipnzee_save_meta'
        )
    ) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST['flipnzee_property_id'])) {

        update_post_meta(
            $post_id,
            '_ga_property_id',
            sanitize_text_field(
                $_POST['flipnzee_property_id']
            )
        );
    }

    if (isset($_POST['flipnzee_domain'])) {

        update_post_meta(
            $post_id,
            '_ga_domain',
            esc_url_raw(
                $_POST['flipnzee_domain']
            )
        );
    }
});