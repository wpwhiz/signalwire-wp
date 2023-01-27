<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/* Register Contacts Post Type */
add_action('init', 'create_sms_contact_post_type');

function create_sms_contact_post_type()
{
    register_post_type(
        'sms-contact',
        array(
            'labels' => array(
                'name' => 'SMS Contacts',
                'singular_name' => 'SMS Contact',
                'add_new' => 'Add New',
                'add_new_item' => 'Add New SMS Contact',
                'edit' => 'Edit',
                'edit_item' => 'Edit SMS Contact',
                'new_item' => 'New SMS Contact',
                'view' => 'View',
                'view_item' => 'View SMS Contact',
                'search_items' => 'Search SMS Contacts',
                'not_found' => 'No SMS Contacts found',
                'not_found_in_trash' => 'No SMS Contacts found in Trash',
                'parent' => 'Parent SMS Contact'
            ),

            'public' => true,
            'supports' => array(''),
            'taxonomies' => array(''),
            'menu_icon' => 'dashicons-id',
            'has_archive' => false,
            'hierarchical' => false,
            'publicly_queryable'  => false,
        )
    );
}

/* Register Custom Fields */

add_action('add_meta_boxes', 'sms_contact_custom_fields');

function sms_contact_custom_fields()
{
    add_meta_box('sms_contact_custom_fields', 'SMS Contact Info', 'sms_contact_custom_fields_render', 'sms-contact', 'normal', 'high');
}

function sms_contact_custom_fields_render()
{
    global $post;
    $custom = get_post_custom($post->ID);
    $sw_contact_phone = $custom["sw_contact_phone"][0];
    $sw_contact_first_name = $custom["sw_contact_first_name"][0];
    $sw_contact_last_name = $custom["sw_contact_last_name"][0];
    $sw_contact_date_added = $custom["sw_contact_date_added"][0];
    $sw_contact_unsubscribed = $custom["sw_contact_unsubscribed"][0];
?>
    <table class="form-table" role="presentation">
        <tbody>
            <tr>
                <th><label>Phone:</label></th>
                <td><input class="regular-text" name="sw_contact_phone" value="<?php echo $sw_contact_phone; ?>" /></td>
            </tr>
            <tr>
                <th><label>First Name:</label></th>
                <td><input class="regular-text" name="sw_contact_first_name" value="<?php echo $sw_contact_first_name; ?>" /></td>
            </tr>
            <tr>
                <th><label>Last Name:</label></th>
                <td><input class="regular-text" name="sw_contact_last_name" value="<?php echo $sw_contact_last_name; ?>" /></td>
            </tr>
            <tr>
                <th><label>Date Added:</label></th>
                <td><input class="regular-text" name="sw_contact_date_added" readonly value="<?php echo $sw_contact_date_added; ?>" /></td>
            </tr>
            <tr>
                <th><label>Unsubscribed:</label></th>
                <td><input type="checkbox" name="sw_contact_unsubscribed" value="<?php echo $sw_contact_unsubscribed; ?>" /></td>
            </tr>
        </tbody>
    </table>
<?php
}

/* Save Custom Fields */
add_action('save_post', 'save_sms_contact_custom_fields');
function save_sms_contact_custom_fields()
{
    global $post;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return $post->ID;
    }
    update_post_meta($post->ID, "sw_contact_phone", $_POST["sw_contact_phone"]);
    update_post_meta($post->ID, "sw_contact_first_name", $_POST["sw_contact_first_name"]);
    update_post_meta($post->ID, "sw_contact_last_name", $_POST["sw_contact_last_name"]);
    update_post_meta($post->ID, "sw_contact_date_added", $_POST["sw_contact_date_added"]);
    update_post_meta($post->ID, "sw_contact_unsubscribed", $_POST["sw_contact_unsubscribed"]);
}

/* Create Admin Columns */

add_filter('manage_edit-sms-contact_columns', 'sms_contact_custom_columns');

function sms_contact_custom_columns($columns)
{
    unset($columns['date']);
    $columns['title'] = __('Contact ID', 'sms-contact');
    $columns['sw_contact_phone'] = __('Phone', 'sms-contact');
    $columns['sw_contact_first_name'] = __('First Name', 'sms-contact');
    $columns['sw_contact_last_name'] = __('Last Name', 'sms-contact');
    $columns['sw_contact_date_added'] = __('Date Added', 'sms-contact');
    $columns['sw_contact_unsubscribed'] = __('Unsubscribed', 'sms-contact');
    return $columns;
}

/* Load data for admin columns */
add_action('manage_sms-contact_posts_custom_column', 'sms_contact_custom_columns_data', 10, 2);

function sms_contact_custom_columns_data($column, $post_id)
{
    $custom = get_post_custom($post_id);
    switch ($column) {
        case 'sw_contact_phone':
            echo esc_html($custom["sw_contact_phone"][0]);
            break;
        case 'sw_contact_first_name':
            echo esc_html($custom["sw_contact_first_name"][0]);
            break;
        case 'sw_contact_last_name':
            echo esc_html($custom["sw_contact_last_name"][0]);
            break;
        case 'sw_contact_date_added':
            echo esc_html($custom["sw_contact_date_added"][0]);
            break;
        case 'sw_contact_unsubscribed':
            echo esc_html($custom["sw_contact_unsubscribed"][0]);
            break;
    }
}

/* Change "title" admin column */
add_action(
    'admin_head-edit.php',
    'wpse152971_edit_post_change_title_in_list'
);
function wpse152971_edit_post_change_title_in_list()
{
    if (get_post_type() === 'sms-contact') {
        add_filter('the_title', 'wpse152971_construct_new_title', 100, 2);
    }
}
function wpse152971_construct_new_title($title, $id)
{
    return $id;
}
