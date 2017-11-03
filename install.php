<?php

global $wpdb;

$charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset} COLLATE {$wpdb->collate}";

require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

$table = $wpdb->prefix . 'geo_ip_ranges';
$ranges = "CREATE TABLE $table (
ID  int(11) unsigned NOT NULL auto_increment,
active int(18) NULL,
block_begin  text NULL,
block_end    text NULL,
addr_range   text NULL,
country text NULL,
city_id  text NULL,
PRIMARY KEY (ID),
KEY active (active)
) {$charset_collate};";
dbDelta( $ranges );

$table = $wpdb->prefix . 'geo_ip_cities';
$cities = "CREATE TABLE $table (
ID  int(11) unsigned NOT NULL auto_increment,
active int(18) NULL,
XML_ID   int(18) NULL,
city text NULL,
region  text NULL,
country  text NULL,
lat text NULL,
lng text NULL,
PRIMARY KEY (ID),
KEY active (active),
KEY XML_ID (XML_ID)
) {$charset_collate};";
dbDelta( $cities );
