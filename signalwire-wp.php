<?php

/**
 * Plugin Name: Signalwire SMS Notifications
 * Description: Automatically send SMS when a new post is published.
 * Version: 1.0.1
 * Author: Kervio
 * Author URI: https://www.kervio.com
 * License: GPL-3.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Load Custom Fields
include plugin_dir_path(__FILE__) . 'inc/sw-fields.php';
// Load Signalwire API Settings Page
include plugin_dir_path(__FILE__) . 'inc/sw-settings-page.php';

// Add Plugins list link
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'sw_plugin_settings_link' );
function sw_plugin_settings_link( $links )
{
    $url = esc_url( admin_url( 'options-general.php?page=signalwire-api' ) );
    $_link = '<a href="'.$url.'">' . __( 'API Settings', 'wordpress' ) . '</a>';
    $links[] = $_link;
    return $links;
}

/* Set Signalwire Variables */
function sw_variable($variable) {
    $options = get_option('signalwire-api');
    if ($variable == 'account_sid') {
        return $options['sw_account_sid'];
    } elseif ($variable == 'space_url') {
        return $options['sw_space_url'];
    } elseif ($variable == 'auth_token') {
        return $options['sw_auth_token'];
    } elseif ($variable == 'project_id') {
        return $options['sw_project_id'];
    } elseif ($variable == 'phone_number') {
        return $options['sw_campaign_phone_number'];
    } elseif ($variable == 'site_name') {
        return get_bloginfo( 'name' );
    } else {
        return '';
    }
}

/* Add custom endpoint for receiving SMS messages from SignalWire */
add_action('rest_api_init', 'register_sms_endpoint');
function register_sms_endpoint()
{
    register_rest_route('signalwire-sms/v1', '/receive/', array(
        'methods'  => 'POST',
        'callback' => 'receive_sms',
        'permission_callback' => '__return_true',
    ));
}

/* Callback function for receiving SMS messages */
function receive_sms($request)
{
    $params = $request->get_params();

    // Check for errors in the SignalWire response
    if (isset($params['error_code'])) {
        error_log('SignalWire error: ' . $params['error_code']);
        return new WP_Error('signalwire_error', $params['error_code'], array('status' => 400));
    }

    // Verify ProjectID (AccountSid)
    if ($params['AccountSid'] !== sw_variable('account_sid')) {
        error_log('Error: Invalid AccountSid');
        return new WP_Error('invalid_account_sid', 'Invalid AccountSid', array('status' => 400));
    }

    // Verify or Create Contact
    $from = sanitize_text_field($params['From']);
    $contact_id = get_signalwire_contact($from);
    if (empty($contact_id)) {
        error_log('Error: Invalid Contact ID');
        return new WP_Error('invalid_contact_id', 'Invalid Contact ID', array('status' => 400));
    }

    // Validate search query
    $query = sanitize_text_field(strtolower($params['Body']));
    // Check if query contains more than 5 words
    if ($query == 'stop' || $query == 'unsubscribe') {
        update_post_meta($contact_id, 'sw_contact_unsubscribed', '1');
        send_signalwire_sms($from, 'You have opted out from '.sw_variable('site_name').' SMS Alerts. To opt back in, reply START.');
        return new WP_Error('contact_unsubscribed', 'Contact Unsubscribed', array('status' => 400));
        // Check if re-subscribed
    } elseif ($query == 'start' || $query == 'subscribe') {
        update_post_meta($contact_id, 'sw_contact_unsubscribed', '0');
        send_signalwire_sms($from, 'Thank you for signing up to '.sw_variable('site_name').' SMS Alerts! You will now recieve alerts when new posts are published. Reply STOP to unsubscribe. Reply HELP for help.');
        return new WP_Error('contact_resubscribed', 'Contact Resubscribed', array('status' => 400));
    }
}

/* Function for sending SMS messages using the SignalWire API */
function send_signalwire_sms($to, $message)
{
    // Set up HTTP request to send SMS message
    $args = array(
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode(sw_variable('project_id') . ':' . sw_variable('auth_token')),
        ),
        'body' => array(
            'From' => sw_variable('phone_number'),
            'To' => $to,
            'Body' => $message,
        ),
    );

    // Send the message
    $response = wp_remote_post(sw_variable('space_url') . '/api/laml/2010-04-01/Accounts/' . sw_variable('project_id') . '/Messages', $args);

    // Check for errors in the HTTP response
    if (is_wp_error($response)) {
        error_log('Error sending SMS: ' . $response->get_error_message());
        return new WP_Error('sms_send_error', $response->get_error_message(), array('status' => 500));
    } else {
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code != 201) {
            error_log('Error sending SMS: HTTP response code ' . $response_code);
            return new WP_Error('sms_send_error', 'HTTP response code: ' . $response_code, array('status' => 500));
        }
    }

    return true;
}

/* Get Contact ID. If contact doesn't exist, create a new one. */
function get_signalwire_contact($phone_number)
{
    $contact_query = new WP_Query(array(
        'post_type' => 'sms-contact',
        'meta_key' => 'sw_contact_phone',
        'meta_value' => $phone_number,
        'posts_per_page' => 1,
    ));
    if ($contact_query->have_posts()) {
        // Contact already exists, get post ID
        $contact_query->the_post();
        $contact_id = get_the_ID();
        wp_reset_postdata();
        return $contact_id;
    } else {
        // Contact does not exist, create it
        $new_contact = array(
            'post_type' => 'sms-contact',
            'post_status' => 'publish',
        );
        $contact_id = wp_insert_post($new_contact);

        // Save phone number
        update_post_meta($contact_id, 'sw_contact_phone', $phone_number);
        // Save current date to 'date added' post meta
        update_post_meta($contact_id, 'sw_contact_date_added', current_time('mysql'));
        // When contact is initially created, status is set to unsubscribed. We will then check if they texted 'START' or 'SUBSCRIBE' later.
        update_post_meta($contact_id, 'sw_contact_unsubscribed', '1');
        
        // Return contact ID
        return $contact_id;
    }
}

/* Function to send SMS message when new post is published */
function send_new_post_sms($new_status, $old_status, $post)
{
    // Check if post is new
    if ('publish' == $new_status && 'publish' != $old_status && $post->post_type == 'post') {
        $post = get_post($post);

        if (!$post) {
            return;
        }

        $post_id = $post->ID;

        if ( wp_is_post_revision($post_id) || wp_is_post_autosave($post_id) ) {
            return;
        }

        if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
            return;
        }

        // don't run the echo if the function is called for saving revision.
        if ( $post->post_type == 'revision' ) {
            return;
        }

        // Get the post title and shortlink
        $post_title = get_the_title($post_id);
        $post_shortlink = wp_get_shortlink($post_id);

        // Get all contacts in the 'sms-contact' CPT
        $contacts = get_posts(array(
            'post_type' => 'sms-contact',
            'meta_query' => array(
                array(
                    'key' => 'sw_contact_unsubscribed',
                    'value' => 0,
                )
            ),
            'posts_per_page' => -1,
        ));

        // Loop through the contacts and send SMS
        foreach ($contacts as $contact) {
            // Get the SMS address from post meta
            $to_number = get_post_meta($contact->ID, 'sw_contact_phone', true);
            if (!empty($to_number)) {
                $message = "A new post has been published on " . sw_variable('site_name') . " - " . $post_title . " " . $post_shortlink;
                
                // Send the SMS
                send_signalwire_sms($to_number, $message);
            }
        }
    }
}
add_action('transition_post_status',  'send_new_post_sms', 10, 3);