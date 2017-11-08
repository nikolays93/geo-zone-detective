<?php

namespace CDevelopers\GeoZoneDetective;

if ( ! defined( 'ABSPATH' ) )
  exit; // disable direct access

global $wpdb;

$charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset} COLLATE {$wpdb->collate}";

require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

$table = $wpdb->prefix . Utils::TABLE_RANGES;
$ranges = "CREATE TABLE $table (
    ID  int(18) unsigned NOT NULL auto_increment,
    addr_begin int(18) unsigned NULL,
    addr_end   int(18) unsigned NULL,
    addr_range   text NULL,
    country text NULL,
    city_id  text NULL,
    PRIMARY KEY (ID),
    KEY addr_begin (addr_begin),
    KEY addr_end (addr_end)
) {$charset_collate};";
dbDelta( $ranges );

$table = $wpdb->prefix . Utils::TABLE_CITIES;
$cities = "CREATE TABLE $table (
    ID  int(18) unsigned NOT NULL auto_increment,
    city_id   int(18) NULL,
    city text NULL,
    region  text NULL,
    district  text NULL,
    lat text NULL,
    lng text NULL,
    PRIMARY KEY (ID),
    KEY city_id (city_id)
) {$charset_collate};";
dbDelta( $cities );

// $geo_value = get_geo_value();

// $default = array(
//   'charset'  => GEO_DEFAULT_CHARSET,
//   'country'  => isset($geo_value['country']) ? $geo_value['country'] : '',
//   'city'     => isset($geo_value['city']) ? $geo_value['city'] : '',
//   'region'   => isset($geo_value['region']) ? $geo_value['region'] : '',
//   'district' => isset($geo_value['district']) ? $geo_value['district'] : '',
//   );
// add_option( GEO_OPTION, $default );