<?php
/*
Plugin Name: Geo Zone Detective
Plugin URI: 
Description: Created special for you company
Version: 0.1a
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

require_once GEO_PLUG_DIR . 'inc/class-geo.php';

if(is_admin()){
  require_once GEO_PLUG_DIR . 'inc/class-wp-admin-page-render.php';
  require_once GEO_PLUG_DIR . 'inc/class-wp-form-render.php';

  $page = new WPAdminPageRender( GEO_OPTION,
  array(
    'parent' => 'options-general.php',
    'title' => __('Определение ГЕО-Локации по IP адресу'),
    'menu' => __('Geo Zone Detective'),
    ),
  'GEO_ZONE\geo_zone_render_page'
  );
}

function get_geo_value( $value = false ){
  $options = get_option(GEO_OPTION);

  $geo_target = new \Geo( array(
    'ip'      => !empty($options['test-ip']) ? $options['test-ip'] : null,
    'charset' => !empty($options['charset']) ? $options['charset'] : null,
  ) );

  $return = $geo_target->get_value();

  if( $value ){
    if(empty($return[$value]))
      return $options[$value];

    return $return[$value];
  }

  return !empty($return) ? $return : $options;
}

register_activation_hook(__FILE__, function(){
    $geo_value = get_geo_value();

    $default = array(
      'charset'  => GEO_DEFAULT_CHARSET,
      'country'  => isset($geo_value['country']) ? $geo_value['country'] : '',
      'city'     => isset($geo_value['city']) ? $geo_value['city'] : '',
      'region'   => isset($geo_value['region']) ? $geo_value['region'] : '',
      'district' => isset($geo_value['district']) ? $geo_value['district'] : '',
      );
    add_option( GEO_OPTION, $default );
});

function get_city(){ return get_geo_value( 'city' ); }
function get_country(){ return get_geo_value( 'country' ); }
function get_region(){ return get_geo_value( 'region' ); }
function get_district(){ return get_geo_value( 'district' ); }

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
      ),
    );

  /**
   * @todo:
   * echo '<input type="button" id="clear_geo_cache" class="button" value="Очистить Гео Кэш">';
   */
  WPForm::render(
    $data,
    WPForm::active(GEO_OPTION, false, true),
    true,
    array(
      'clear_value' => false,
      'admin_page' => true,
      )
    );

/**
 *  $geo_target->get_value() has inetnum, country, city, region, district, lat, lng
 */

  submit_button();
}