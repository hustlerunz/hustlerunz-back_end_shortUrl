<?php
/*
Plugin Name: XML Response
Plugin URI: 
Description: Provides XML responses for certain YOURLS API actions
Version: 1.0
Author: Your Name
Author URI: Your URL
*/

// Register the custom API action
yourls_add_filter('api_actions', 'xml_response_api_action');
function xml_response_api_action($api_actions) {
    $api_actions['add_xml_link'] = 'xml_response_add_link';
    return $api_actions;
}

// Define the custom API function
function xml_response_add_link() {
    // Check for required parameters
    if (!isset($_REQUEST['url'])) {
        return xml_response(array(
            'status' => 'fail',
            'message' => 'Missing URL parameter',
            'errorCode' => 400
        ));
    }

    // Optional parameters
    $keyword = isset($_REQUEST['keyword']) ? $_REQUEST['keyword'] : '';
    $title = isset($_REQUEST['title']) ? $_REQUEST['title'] : '';
    $userid = isset($_REQUEST['userid']) ? $_REQUEST['userid'] : null;

    // Sanitize URL
    $url = yourls_sanitize_url($_REQUEST['url']);

    // Check if the URL is valid
    if (!yourls_is_valid_url($url)) {
        return xml_response(array(
            'status' => 'fail',
            'message' => 'Invalid URL',
            'errorCode' => 400
        ));
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
        return xml_response(array(
            'status' => 'success',
            'url' => array(
                'keyword' => $keyword,
                'shorturl' => yourls_link($keyword),
                'url' => $url,
                'title' => $title,
                'timestamp' => $timestamp,
                'ip' => $ip
            )
        ));
    } else {
        return xml_response(array(
            'status' => 'fail',
            'message' => 'Database insert failed',
            'errorCode' => 500
        ));
    }
}

// Function to send XML response
function xml_response($response) {
    header('Content-Type: application/xml; charset=UTF-8');
    $xml = new SimpleXMLElement('<root/>');

    foreach ($response as $key => $value) {
        if (is_array($value)) {
            $child = $xml->addChild($key);
            foreach ($value as $sub_key => $sub_value) {
                $child->addChild($sub_key, htmlspecialchars($sub_value));
            }
        } else {
            $xml->addChild($key, htmlspecialchars($value));
        }
    }

    echo $xml->asXML();
    exit;
}
?>