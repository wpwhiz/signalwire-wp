<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', 'signalwire_api_settings_page');

function signalwire_api_settings_page() {
    add_options_page(
        'SignalWire API Settings', 
        'SignalWire API', 
        'manage_options', 
        'signalwire-api', 
        'signalwire_api_settings_page_render'
    );
}

function signalwire_api_settings_page_render() {
    ?>
    <div class="wrap">
        <h1>SignalWire API Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('signalwire-api');
            do_settings_sections('signalwire-api');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

add_action('admin_init', 'signalwire_api_settings_page_init');

function signalwire_api_settings_page_init() {
    register_setting(
        'signalwire-api', 
        'signalwire-api', 
        'signalwire_api_settings_validate'
    );

    add_settings_section(
        'signalwire-api-section', 
        'SignalWire API Credentials', 
        'signalwire_api_settings_section_render', 
        'signalwire-api'
    );

    add_settings_field(
        'sw_account_sid', 
        'Account SID', 
        'sw_account_sid_render', 
        'signalwire-api', 
        'signalwire-api-section'
    );

    add_settings_field(
        'sw_space_url', 
        'Space URL', 
        'sw_space_url_render', 
        'signalwire-api', 
        'signalwire-api-section'
    );

    add_settings_field(
        'sw_auth_token', 
        'Auth Token', 
        'sw_auth_token_render', 
        'signalwire-api', 
        'signalwire-api-section'
    );

    add_settings_field(
        'sw_project_id', 
        'Project ID', 
        'sw_project_id_render', 
        'signalwire-api', 
        'signalwire-api-section'
    );

    add_settings_field(
        'sw_campaign_phone_number', 
        'Campaign Phone Number', 
        'sw_campaign_phone_number_render', 
        'signalwire-api', 
        'signalwire-api-section'
    );
}

function signalwire_api_settings_validate($input) {
    return $input;
}

function signalwire_api_settings_section_render() {
    echo '<h2>1.</h2> <p>In your Signalwire dashboard, go to Phone Numbers > Edit > Messaging Settings, and set <strong>Handle Messages Using</strong> to "LaML Webhooks". <br>Set <strong>When a Message Comes In</strong> to:</p>';
    echo '<input readonly size="100" value="'.get_home_url().'/wp-json/signalwire-sms/v1/receive/">';
    echo '<p>Set the <strong>Method</strong> to "Post".</p>';
    echo '<h2>2.</h2> <p>Enter your Signal Wire API credentials below:</p>';
}

function sw_account_sid_render() {
$options = get_option('signalwire-api');
?>
<input class="regular-text" type="text" name="signalwire-api[sw_account_sid]" value="<?php echo $options['sw_account_sid']; ?>" />
<?php
}

function sw_space_url_render() {
$options = get_option('signalwire-api');
?>
<input class="regular-text" type="text" name="signalwire-api[sw_space_url]" value="<?php echo $options['sw_space_url']; ?>" />
<?php
}

function sw_auth_token_render() {
$options = get_option('signalwire-api');
?>
<input class="regular-text" type="text" name="signalwire-api[sw_auth_token]" value="<?php echo $options['sw_auth_token']; ?>" />
<?php
}

function sw_project_id_render() {
$options = get_option('signalwire-api');
?>
<input class="regular-text" type="text" name="signalwire-api[sw_project_id]" value="<?php echo $options['sw_project_id']; ?>" />
<?php
}

function sw_campaign_phone_number_render() {
$options = get_option('signalwire-api');
?>
<input class="regular-text" type="text" name="signalwire-api[sw_campaign_phone_number]" value="<?php echo $options['sw_campaign_phone_number']; ?>" />
<?php
}

