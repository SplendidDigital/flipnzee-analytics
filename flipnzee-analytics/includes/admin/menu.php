<?php

add_action('admin_menu', function () {

    add_menu_page(
        'Flipnzee Analytics',
        'Flipnzee Analytics',
        'manage_options',
        'flipnzee-analytics',
        'flipnzee_admin_page',
        'dashicons-chart-area',
        25
    );

});
