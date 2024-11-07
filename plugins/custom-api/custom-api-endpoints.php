<?php
/*
Plugin Name: Custom API Endpoints
Description: A custom plugin to handle media uploads and store WhatsApp user data.
Version: 1.0
Author: Your Name
*/

// Include the second file
include_once(plugin_dir_path(__FILE__) . 'custom-post-type-and-admin.php');

// Enable media upload capabilities
function custom_api_media_permissions($caps, $cap, $user_id, $args)
{
    if ($cap === 'upload_files') {
        return array('upload_files');
    }
    return $caps;
}
add_filter('map_meta_cap', 'custom_api_media_permissions', 10, 4);

// Register custom REST route for media upload
add_action('rest_api_init', function () {
    register_rest_route('custom/v1', '/upload', array(
        'methods' => 'POST',
        'callback' => 'custom_handle_upload',
        'permission_callback' => function () {
            return current_user_can('upload_files');
        },
    ));
});

function custom_handle_upload(WP_REST_Request $request)
{
    $files = $request->get_file_params();
    $profile_id = $request->get_param('profile_id');

    if (empty($files) || empty($files['file'])) {
        return new WP_Error('no_file', 'No file uploaded', array('status' => 400));
    }

    require_once(ABSPATH . 'wp-admin/includes/file.php');

    $file = $files['file'];
    $upload_overrides = array('test_form' => false);
    $movefile = wp_handle_upload($file, $upload_overrides);

    if ($movefile && !isset($movefile['error'])) {
        $wp_upload_dir = wp_upload_dir();
        $attachment = array(
            'guid' => $wp_upload_dir['url'] . '/' . basename($movefile['file']),
            'post_mime_type' => $movefile['type'],
            'post_title' => preg_replace('/\.[^.]+$/', '', basename($movefile['file'])),
            'post_content' => '',
            'post_status' => 'inherit'
        );

        $attach_id = wp_insert_attachment($attachment, $movefile['file']);
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $movefile['file']);
        wp_update_attachment_metadata($attach_id, $attach_data);

        // Retrieve user data
        $user_post = get_post($profile_id);
        $username = $user_post->post_title;
        $phone = get_post_meta($profile_id, 'phone', true);

        // Create a new receipt post
        $receipt_post = array(
            'post_title' => "Receipt - $attach_id - $username - $phone",
            'post_content' => '',
            'post_status' => 'publish',
            'post_type' => 'receipt',
        );

        $receipt_id = wp_insert_post($receipt_post);
        update_post_meta($receipt_id, '_thumbnail_id', $attach_id);
        update_post_meta($receipt_id, 'profile_id', $profile_id); // Store profile ID in receipt

        return array('success' => true, 'receipt_id' => $receipt_id);
    } else {
        return new WP_Error('upload_error', $movefile['error'], array('status' => 500));
    }
}

add_action('rest_api_init', function () {
    register_rest_route('custom/v1', '/upload/', array(
        'methods' => 'POST',
        'callback' => 'custom_handle_upload',
        'permission_callback' => function () {
            return current_user_can('edit_posts');
        },
    ));
});
// Register custom REST route for storing WhatsApp user data
add_action('rest_api_init', function () {
    register_rest_route('custom/v1', '/store-whatsapp-user', array(
        'methods' => 'POST',
        'callback' => 'custom_store_whatsapp_user_data',
        'permission_callback' => function () {
            return current_user_can('edit_posts'); // Adjust permissions as needed
        }
    ));
});

// Store WhatsApp user data
function custom_store_whatsapp_user_data(WP_REST_Request $request)
{
    $params = $request->get_params();
    $phone = sanitize_text_field($params['phone']);
    $name = sanitize_text_field($params['name']);

    // Check if a post exists with this phone number
    $existing_posts = get_posts(array(
        'post_type'   => 'whatsapp_user',
        'meta_key'    => 'phone',
        'meta_value'  => $phone,
        'post_status' => 'publish',
        'numberposts' => 1,
    ));

    if (!empty($existing_posts)) {
        // Post already exists, update it
        $post_id = $existing_posts[0]->ID;
        $post_data = array(
            'ID'           => $post_id,
            'post_title'    => $name,
            'post_content'  => 'Updated data for ' . $name,
        );
        wp_update_post($post_data);
    } else {
        // Create a new post
        $post_data = array(
            'post_type'    => 'whatsapp_user',
            'post_title'   => $name,
            'post_content' => 'Data for ' . $name,
            'post_status'  => 'publish',
        );
        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id)) {
            return new WP_Error('post_creation_failed', 'Failed to create post.', array('status' => 500));
        }
    }

    // Store phone number as meta field
    update_post_meta($post_id, 'phone', $phone);

    // Return the stored data
    return array(
        'post_id' => $post_id,
        'phone'   => $phone,
        'name'    => $name,
    );
}

// Add meta box for loyalty points
add_action('add_meta_boxes', 'add_receipt_meta_box');
function add_receipt_meta_box()
{
    add_meta_box(
        'receipt_meta_box',        // ID
        'Receipt Details',         // Title
        'display_receipt_meta_box', // Callback
        'receipt',                 // Post type
        'normal',                  // Context
        'high'                     // Priority
    );
}

function display_receipt_meta_box($post)
{
    $profile_id = get_post_meta($post->ID, 'profile_id', true);
    $loyalty_points = get_post_meta($post->ID, 'loyalty_points', true);
    $attachment_id = get_post_thumbnail_id($post->ID);

    $username = get_the_title($profile_id);
    $phone = get_post_meta($profile_id, 'phone', true);

    echo "<p><strong>User:</strong> $username</p>";
    echo "<p><strong>Phone:</strong> $phone</p>";
    if ($attachment_id) {
        echo wp_get_attachment_image($attachment_id, 'medium');
    }

    // Loyalty points input
    echo '<p><label for="loyalty_points">Loyalty Points: </label>';
    echo '<input type="number" name="loyalty_points" value="' . esc_attr($loyalty_points) . '" /></p>';
}

// Save the loyalty points
add_action('save_post', 'save_receipt_meta');
function save_receipt_meta($post_id)
{
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (wp_is_post_revision($post_id)) {
        return;
    }

    if (get_post_type($post_id) !== 'receipt') {
        return;
    }

    if (isset($_POST['loyalty_points'])) {
        $loyalty_points = intval($_POST['loyalty_points']);
        update_post_meta($post_id, 'loyalty_points', $loyalty_points);

        // Update the WhatsApp user's loyalty points
        $profile_id = get_post_meta($post_id, 'profile_id', true);
        if ($profile_id) {
            $user_post = get_post($profile_id);
            if ($user_post) {
                // Get current points and cast to integer
                $current_points = intval(get_post_meta($user_post->ID, 'loyalty_points', true));
                $new_points = $current_points + $loyalty_points;
                update_post_meta($user_post->ID, 'loyalty_points', $new_points);
            }
        }
    }
}

// Add custom columns to WhatsApp user post type
add_filter('manage_whatsapp_user_posts_columns', 'custom_whatsapp_user_columns');
function custom_whatsapp_user_columns($columns)
{
    $columns['phone'] = __('Phone');
    $columns['loyalty_points'] = __('Loyalty Points');
    return $columns;
}

// Populate custom columns with data
add_action('manage_whatsapp_user_posts_custom_column', 'custom_whatsapp_user_custom_columns', 10, 2);
function custom_whatsapp_user_custom_columns($column, $post_id)
{
    switch ($column) {
        case 'phone':
            $phone = get_post_meta($post_id, 'phone', true);
            echo esc_html($phone);
            break;

        case 'loyalty_points':
            $loyalty_points = get_post_meta($post_id, 'loyalty_points', true);
            echo esc_html($loyalty_points);
            break;
    }
}

// Make custom columns sortable
add_filter('manage_edit-whatsapp_user_sortable_columns', 'custom_whatsapp_user_sortable_columns');
function custom_whatsapp_user_sortable_columns($columns)
{
    $columns['phone'] = 'phone';
    $columns['loyalty_points'] = 'loyalty_points';
    return $columns;
}

// Handle sorting of custom columns
add_action('pre_get_posts', 'custom_whatsapp_user_sort_order');
function custom_whatsapp_user_sort_order($query)
{
    if (!is_admin()) {
        return;
    }

    $orderby = $query->get('orderby');

    if ('phone' === $orderby) {
        $query->set('meta_key', 'phone');
        $query->set('orderby', 'meta_value');
    }

    if ('loyalty_points' === $orderby) {
        $query->set('meta_key', 'loyalty_points');
        $query->set('orderby', 'meta_value_num'); // Use meta_value_num for numerical sorting
    }
}

// Register REST route to get user profile data
add_action('rest_api_init', function () {
    register_rest_route('custom/v1', '/user-profile', array(
        'methods' => 'GET',
        'callback' => 'get_user_profile_data',
        'permission_callback' => function () {
            return current_user_can('read'); // Adjust permissions as needed
        },
    ));
});

function get_user_profile_data(WP_REST_Request $request)
{
    $profile_id = $request->get_param('profile_id');

    if (!$profile_id) {
        return new WP_Error('no_profile_id', 'Profile ID is required', array('status' => 400));
    }

    $user_post = get_post($profile_id);

    if (!$user_post) {
        return new WP_Error('profile_not_found', 'Profile not found', array('status' => 404));
    }

    $phone = get_post_meta($profile_id, 'phone', true);
    $loyalty_points = get_post_meta($profile_id, 'loyalty_points', true);

    return array(
        'name' => $user_post->post_title,
        'phone' => $phone,
        'loyalty_points' => $loyalty_points,
    );
}


// Register custom REST route for fetching user receipts
add_action('rest_api_init', function () {
    register_rest_route('custom/v1', '/receipts', array(
        'methods' => 'GET',
        'callback' => 'custom_get_user_receipts',
        'permission_callback' => function () {
            return current_user_can('read'); // Adjust permissions as needed
        },
    ));
});


// Handle fetching user receipts
function custom_get_user_receipts(WP_REST_Request $request)
{
    $profile_id = $request->get_param('profile_id');

    if (!$profile_id) {
        return new WP_Error('missing_profile_id', 'Profile ID is required.', array('status' => 400));
    }

    // Query for receipts associated with the given profile ID
    $args = array(
        'post_type'   => 'receipt',
        'meta_key'    => 'profile_id',
        'meta_value'  => $profile_id,
        'post_status' => 'publish',
        'posts_per_page' => -1, // Retrieve all receipts
    );

    $query = new WP_Query($args);

    if (!$query->have_posts()) {
        return array('message' => 'No receipts found for this profile.');
    }

    $receipts = array();

    foreach ($query->posts as $post) {
        $attachment_id = get_post_thumbnail_id($post->ID);
        $receipt = array(
            'id'             => $post->ID,
            'date_uploaded'  => get_the_date('Y-m-d', $post->ID),
            'receipt_image'  => $attachment_id ? wp_get_attachment_url($attachment_id) : '',
        );
        $receipts[] = $receipt;
    }

    return $receipts;
}
