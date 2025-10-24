<?php
/*
Plugin Name: Users in Database
Plugin URI: 
Description: Puts users in the database
Version: 1.0
Author: Nick Bair
Author URI: https://github.com/njbair
*/

use PDO;

yourls_add_action( 'pre_login', 'uidb_pre_login' );

function uidb_pre_login() {
    global $yourls_user_passwords;

    if (   !defined( 'YOURLS_DB_USER' )
        or !defined( 'YOURLS_DB_PASS' )
        or !defined( 'YOURLS_DB_NAME' )
        or !defined( 'YOURLS_DB_HOST' )
        or !defined( 'YOURLS_DB_PREFIX' )
    ) yourls_die ( yourls__( 'Incorrect DB config, or could not connect to DB' ), yourls__( 'Fatal error' ), 503 );

    try {
        $prefix = YOURLS_DB_PREFIX;
        $db = uidb_connect_to_database();

        uidb_create_users_table_if_missing($db);

        $stmt = $db->query("SELECT username,password FROM ${prefix}users");
        $rows = $stmt->fetchAll();

        //var_export($rows); die();
    } catch (PDOException $e) {
        yourls_die ( yourls__( 'Could not connect to database.' ), yourls__( 'Fatal error' ), 503 );
    }

    $yourls_user_passwords = array();
    foreach ($rows as $row) {
        $yourls_user_passwords[$row['username']] = $row['password'];
    }

    uidb_hash_passwords_now($db);
}

function uidb_connect_to_database() {
    $dbhost = YOURLS_DB_HOST;
    $user   = YOURLS_DB_USER;
    $pass   = YOURLS_DB_PASS;
    $dbname = YOURLS_DB_NAME;

    // Get custom port if any
    if ( false !== strpos( $dbhost, ':' ) ) {
        list( $dbhost, $dbport ) = explode( ':', $dbhost );
        $dbhost = sprintf( '%1$s;port=%2$d', $dbhost, $dbport );
    }

    $charset = yourls_apply_filter( 'db_connect_charset', 'utf8' );
    $dsn = sprintf( 'mysql:host=%s;dbname=%s;charset=%s', $dbhost, $dbname, $charset );
    $dsn = yourls_apply_filter( 'db_connect_custom_dsn', $dsn );
    $driver_options = yourls_apply_filter( 'db_connect_driver_option', array() ); // driver options as key-value pairs

    try {
        $db = new PDO( $dsn, $user, $pass, $driver_options );
    } catch (PDOException $e) {
        echo $e->getMessage();
    }

    return $db;
}

function uidb_create_users_table_if_missing($db) {
    try {
        $prefix = YOURLS_DB_PREFIX;
        $charset = yourls_apply_filter( 'db_connect_charset', 'utf8' );
        $sql = <<<EOT
CREATE TABLE IF NOT EXISTS `${prefix}users` (
    id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(200),
    password VARCHAR(255) 
) CHARACTER SET ${charset};
EOT;

        $db->exec($sql);
    } catch(PDOException $e) {
        echo $e->getMessage();
    }
}

function uidb_hash_passwords_now($db) {
    global $yourls_user_passwords;

    $prefix = YOURLS_DB_PREFIX;

	$to_hash = array(); // keep track of number of passwords that need hashing
	foreach ( $yourls_user_passwords as $user => $password ) {
		if ( !yourls_has_phpass_password( $user ) && !yourls_has_md5_password( $user ) ) {
			$hash = yourls_phpass_hash( $password );
			// PHP would interpret $ as a variable, so replace it in storage.
            $hash = str_replace( '$', '!', $hash );
            
			$to_hash[$user] = $hash;
		}
    }
    
	if( empty($to_hash) )
        return 0; // There was no password to encrypt
        
    foreach ($to_hash as $user => $hash) {
        $stmt = $db->prepare("UPDATE ${prefix}users SET password=? WHERE username=?");
        $stmt->execute([ 'phpass:' . $hash, $user ]);
    }

	return true;
}