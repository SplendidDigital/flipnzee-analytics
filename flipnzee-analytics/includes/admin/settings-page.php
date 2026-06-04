<?php
function flipnzee_admin_page() {

    if (isset($_POST['flipnzee_save'])) {

        update_option(
            'flipnzee_client_id',
            sanitize_text_field($_POST['client_id'])
        );

        update_option(
            'flipnzee_client_secret',
            sanitize_text_field($_POST['client_secret'])
        );

        echo "<div class='updated'><p>Settings saved.</p></div>";
    }

    $client_id = get_option('flipnzee_client_id');
    $client_secret = get_option('flipnzee_client_secret');
    $token = get_option('flipnzee_ga_token');

    $redirect_uri = FLIPNZEE_REDIRECT_URI;

    $scope = urlencode(
        'https://www.googleapis.com/auth/analytics.readonly https://www.googleapis.com/auth/webmasters.readonly'
    );

    $auth_url =
        "https://accounts.google.com/o/oauth2/v2/auth" .
        "?response_type=code" .
        "&client_id={$client_id}" .
        "&redirect_uri={$redirect_uri}" .
        "&scope={$scope}" .
        "&access_type=offline" .
        "&prompt=consent";

?>

<div class="wrap">

    <h1>Flipnzee Analytics</h1>

    <form method="post">

        <table class="form-table">

            <tr>
                <th>Client ID</th>

                <td>
                    <input
                        type="text"
                        name="client_id"
                        value="<?php echo esc_attr($client_id); ?>"
                        style="width:400px;"
                    >
                </td>
            </tr>

            <tr>
                <th>Client Secret</th>

                <td>
                    <input
                        type="text"
                        name="client_secret"
                        value="<?php echo esc_attr($client_secret); ?>"
                        style="width:400px;"
                    >
                </td>
            </tr>

        </table>

        <p>
            <button
                type="submit"
                name="flipnzee_save"
                class="button button-primary"
            >
                Save
            </button>
        </p>

    </form>

    <hr>

    <h2>Google Connection</h2>

    <?php if (!empty($token['access_token'])) : ?>

        <p style="color:green;">✅ Connected</p>

    <?php endif; ?>

    <?php if ($client_id && $client_secret) : ?>

        <a
            href="<?php echo esc_url($auth_url); ?>"
            class="button button-primary"
        >
            Connect Google
        </a>

    <?php else : ?>

        <p>Enter credentials first.</p>

    <?php endif; ?>

</div>

<?php
}