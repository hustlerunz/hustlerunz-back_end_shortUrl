<?php
/*
Plugin Name: XML Auth Response
Plugin URI: 
Description: Provides XML response for unauthorized access in YOURLS API
Version: 1.0
Author: Your Name
Author URI: Your URL
*/

// Hook into the auth process to provide XML response for unauthorized access
yourls_add_filter('api_noauth', 'xml_auth_response_noauth');

// Function to send XML response for unauthorized access
function xml_auth_response_noauth() {
    header('Content-Type: application/xml; charset=UTF-8');
    $xml = new SimpleXMLElement('<root/>');
    $xml->addChild('message', 'Please log in');
    $xml->addChild('errorCode', '403');
    $xml->addChild('callback', '');

    echo $xml->asXML();
    exit;
}
?>