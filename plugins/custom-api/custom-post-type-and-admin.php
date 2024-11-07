<?php
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

/**
 * Register a custom post type for storing receipts
 */
function custom_register_receipt_post_type()
{
    $labels = array(
        'name'                  => _x('Receipts', 'Post type general name', 'textdomain'),
        'singular_name'         => _x('Receipt', 'Post type singular name', 'textdomain'),
        'menu_name'             => _x('Receipts', 'Admin Menu text', 'textdomain'),
        'name_admin_bar'        => _x('Receipt', 'Add New on Toolbar', 'textdomain'),
        'add_new'               => __('Add New', 'textdomain'),
        'add_new_item'          => __('Add New Receipt', 'textdomain'),
        'new_item'              => __('New Receipt', 'textdomain'),
        'edit_item'             => __('Edit Receipt', 'textdomain'),
        'view_item'             => __('View Receipt', 'textdomain'),
        'all_items'             => __('All Receipts', 'textdomain'),
        'search_items'          => __('Search Receipts', 'textdomain'),
        'not_found'             => __('No receipts found.', 'textdomain'),
        'not_found_in_trash'    => __('No receipts found in Trash.', 'textdomain'),
        'featured_image'        => _x('Receipt Image', 'Overrides the “Featured Image” phrase for this post type. Added in 4.3', 'textdomain'),
        'set_featured_image'    => _x('Set receipt image', 'Overrides the “Set featured image” phrase for this post type. Added in 4.3', 'textdomain'),
        'remove_featured_image' => _x('Remove receipt image', 'Overrides the “Remove featured image” phrase for this post type. Added in 4.3', 'textdomain'),
        'use_featured_image'    => _x('Use as receipt image', 'Overrides the “Use as featured image” phrase for this post type. Added in 4.3', 'textdomain'),
        'archives'              => _x('Receipt archives', 'The post type archive label used in nav menus. Added in 4.4', 'textdomain'),
        'insert_into_item'      => _x('Insert into receipt', 'Overrides the “Insert into post” phrase for this post type. Added in 4.4', 'textdomain'),
        'uploaded_to_this_item' => _x('Uploaded to this receipt', 'Overrides the “Uploaded to this post” phrase for this post type. Added in 4.4', 'textdomain'),
        'filter_items_list'     => _x('Filter receipts list', 'Screen reader text. Added in 4.4', 'textdomain'),
        'items_list_navigation' => _x('Receipts list navigation', 'Screen reader text. Added in 4.4', 'textdomain'),
        'items_list'            => _x('Receipts list', 'Screen reader text. Added in 4.4', 'textdomain'),
    );

    $args = array(
        'labels'                => $labels,
        'public'                => true,
        'publicly_queryable'    => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'query_var'             => true,
        'rewrite'               => array('slug' => 'receipt'),
        'capability_type'       => 'post',
        'has_archive'           => true,
        'hierarchical'          => false,
        'menu_position'         => null,
        'supports'              => array('title', 'editor', 'thumbnail'),
    );

    register_post_type('receipt', $args);
}
add_action('init', 'custom_register_receipt_post_type');

/**
 * Register WhatsApp User custom post type
 */
function custom_register_whatsapp_user_post_type()
{
    $labels = array(
        'name'                  => _x('WhatsApp Users', 'Post type general name', 'textdomain'),
        'singular_name'         => _x('WhatsApp User', 'Post type singular name', 'textdomain'),
        'menu_name'             => _x('WhatsApp Users', 'Admin Menu text', 'textdomain'),
        'name_admin_bar'        => _x('WhatsApp User', 'Add New on Toolbar', 'textdomain'),
        'add_new'               => __('Add New', 'textdomain'),
        'add_new_item'          => __('Add New WhatsApp User', 'textdomain'),
        'new_item'              => __('New WhatsApp User', 'textdomain'),
        'edit_item'             => __('Edit WhatsApp User', 'textdomain'),
        'view_item'             => __('View WhatsApp User', 'textdomain'),
        'all_items'             => __('All WhatsApp Users', 'textdomain'),
        'search_items'          => __('Search WhatsApp Users', 'textdomain'),
        'not_found'             => __('No WhatsApp users found.', 'textdomain'),
        'not_found_in_trash'    => __('No WhatsApp users found in Trash.', 'textdomain'),
    );

    $args = array(
        'labels'                => $labels,
        'public'                => true,
        'publicly_queryable'    => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'query_var'             => true,
        'rewrite'               => array('slug' => 'whatsapp_user'),
        'capability_type'       => 'post',
        'has_archive'           => true,
        'hierarchical'          => false,
        'menu_position'         => null,
        'supports'              => array('title', 'editor'),
    );

    register_post_type('whatsapp_user', $args);
}
add_action('init', 'custom_register_whatsapp_user_post_type');
