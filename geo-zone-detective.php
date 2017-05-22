<?php
/*
Plugin Name: GEO PLUGIN
Plugin URI: 
Description: 
Version: 1.1b
Author: NikolayS93
Author URI: https://vk.com/nikolays_93
Author EMAIL: nikolayS93@ya.ru
License: GNU General Public License v2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/
namespace GEO_ZONE;

if ( ! defined( 'ABSPATH' ) )
  exit; // disable direct access

define('GEO_OPTION', 'geo-zone');
define('GEO_PLUG_DIR', plugin_dir_path( __FILE__ ));

define('GEO_DEFAULT_CHARSET', apply_filters( 'GEO_DEFAULT_CHARSET', 'utf-8' ));
define('GEO_COUNTRIES_FILE', GEO_PLUG_DIR . 'inc/geo_files/countries.txt');
define('GEO_CITIES_FILE', GEO_PLUG_DIR . 'inc/geo_files/cities.txt');

register_activation_hook(__FILE__, function(){
    $geo_target = new \Geo( array(
      'charset' => GEO_DEFAULT_CHARSET,
    ) );
    $value = $geo_target->get_value();
    $default = array(
      'charset'  => GEO_DEFAULT_CHARSET,
      'country'  => $value['country'],
      'city'     => $value['city'],
      'region'   => $value['region'],
      'district' => $value['district'],
      );
    add_option( GEO_OPTION, $default );
});

require_once GEO_PLUG_DIR . 'inc/class-geo.php';

if(is_admin()){
  require_once GEO_PLUG_DIR . 'inc/class-wp-admin-page-render.php';
  require_once GEO_PLUG_DIR . 'inc/class-wp-form-render.php';

  $page = new WPAdminPageRender( GEO_OPTION,
  array(
    'parent' => 'options-general.php',
    'title' => __('Test New Plugin'),
    'menu' => __('New Plug Page'),
    ),
  'GEO_ZONE\geo_zone_render_page'
  );
}

global $geo_target;

$geo_target = new \Geo( array(
  'ip' => '178.204.102.30', // г. Казань
  'charset' => 'utf-8', // default
) );

function get_city(){
  global $geo_target;
  return $geo_target->get_value('city');
}
function get_country(){
  global $geo_target;
  return $geo_target->get_value('country');
}
function get_region(){
  global $geo_target;
  return $geo_target->get_value('region');
}
function get_district(){
  global $geo_target;
  return $geo_target->get_value('district');
}

/**
 * Admin Page
 */
function geo_zone_render_page(){
  $data = array(
    array(
      'id'      => 'charset',
      'type'    => 'text',
      'label'   => 'Charset',
      'desc'    => '',
      ),
    array(
      'id'      => 'country',
      'type'    => 'text',
      'label'   => 'Default Country',
      'desc'    => '',
      ),
    array(
      'id'      => 'city',
      'type'    => 'text',
      'label'   => 'Default City',
      'desc'    => '',
      ),
    array(
      'id'      => 'region',
      'type'    => 'text',
      'label'   => 'Default Region',
      'desc'    => '',
      ),
    array(
      'id'      => 'district',
      'type'    => 'text',
      'label'   => 'Default District',
      'desc'    => '',
      ),
    array(
      'id'          => 'test-ip',
      'type'        => 'text',
      'label'       => 'Fake IP for debug',
      'desc'        => '',
      'placeholder' => '178.204.102.30',
      ),
    );

  WPForm::render(
    apply_filters( 'GEO_ZONE\dt_admin_options', $data ),
    WPForm::active(GEO_OPTION, false, true),
    true,
    array('clear_value' => false)
    );

/**
 *  $geo_target->get_value() has inetnum, country, city, region, district, lat, lng
 */

  var_dump( get_city() );

  submit_button();
}