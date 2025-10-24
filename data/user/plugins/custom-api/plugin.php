<?php
/*
Plugin Name: Custom API
Plugin URI: 
Description: Adds a custom API endpoint to YOURLS
Version: 1.0
Author: Your Name
Author URI: Your URL
*/

// Register the custom API action
yourls_add_filter('api_actions', 'custom_api_action');
function custom_api_action($api_actions) {
    $api_actions['add_custom_link'] = 'custom_api_add_link';
    return $api_actions;
}

// Define the custom API function
function custom_api_add_link() {
    // Check for required parameters
    if (!isset($_REQUEST['url'])) {
        return array(
            'status' => 'fail',
            'code' => 'missing_param',
            'message' => 'Missing URL parameter'
        );
    }

    // Optional parameters
    $keyword = isset($_REQUEST['keyword']) ? $_REQUEST['keyword'] : '';
    $title = isset($_REQUEST['title']) ? $_REQUEST['title'] : '';
    $userid = isset($_REQUEST['userid']) ? $_REQUEST['userid'] : null;

    // Sanitize URL
    $url = yourls_sanitize_url($_REQUEST['url']);

    // Check if the URL is valid
    if (!yourls_is_valid_url($url)) {
        return array(
            'status' => 'fail',
            'code' => 'invalid_url',
            'message' => 'Invalid URL'
        );
    }

    // Generate short URL keyword if not provided
    if ($keyword == '') {
        $keyword = yourls_sanitize_keyword(yourls_unique_url_prefix());
    }

    // Insert the URL into the database
    $timestamp = date('Y-m-d H:i:s');
    $ip = yourls_get_IP();
    $table = YOURLS_DB_PREFIX . 'url';
    $insert = yourls_get_db()->query("INSERT INTO `$table` (keyword, url, timestamp, ip, title, userid) VALUES ('$keyword', '$url', '$timestamp', '$ip', '$title', '$userid')");

    if ($insert) {
        return array(
            'status' => 'success',
            'url' => array(
                'keyword' => $keyword,
                'shorturl' => yourls_link($keyword),
                'url' => $url,
                'title' => $title,
                'timestamp' => $timestamp,
                'ip' => $ip
            )
        );
    } else {
        return array(
            'status' => 'fail',
            'code' => 'db_insert_fail',
            'message' => 'Database insert failed'
        );
    }
}
?>