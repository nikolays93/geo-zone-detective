<?php

/*
Plugin Name: Geo Zone Detective
Plugin URI:
Description: Created special for you company
Version: 1.0 alpha
Author: NikolayS93
Author URI: https://vk.com/nikolays_93
Author EMAIL: nikolayS93@ya.ru
License: GNU General Public License v2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

namespace CDevelopers\GeoZoneDetective;

if ( ! defined( 'ABSPATH' ) )
  exit; // disable direct access

const DOMAIN = 'geo-zone-detective';

class Utils
{
    const TABLE_CITIES = 'geo_zone_cities';
    const TABLE_RANGES = 'geo_zone_ranges';
    const OPTION = 'gz-detective';

    private static $initialized;
    private static $settings;
    private function __construct() {}
    private function __clone() {}

    static function uninstall() { delete_option(self::OPTION); }
    static function activate() {
        include_once self::get_plugin_dir() . '/.install.php';
        add_option( self::OPTION, array() );
    }

    private static function include_required_classes()
    {
        $class_dir = self::get_plugin_dir('classes');
        $includes_dir = self::get_plugin_dir('includes');
        $classes = array(
            __NAMESPACE__ . '\init'               => $class_dir . '/init.php',
            __NAMESPACE__ . '\Example_List_Table' => $includes_dir . '/wp-list-table.php',
            __NAMESPACE__ . '\WP_Admin_Page'      => $class_dir . '/wp-admin-page.php',
            __NAMESPACE__ . '\WP_Admin_Forms'     => $class_dir . '/wp-admin-forms.php',
            __NAMESPACE__ . '\WP_Post_Boxes'      => $class_dir . '/wp-post-boxes.php',
            );

        foreach ($classes as $classname => $path) {
            if( ! class_exists($classname) ) {
                require_once $path;
            }
        }

        // includes
        // require_once __DIR__ . '/includes/register-post_type.php';
        require_once __DIR__ . '/includes/shortcodes.php';
        require_once __DIR__ . '/includes/admin-page.php';
    }

    public static function initialize()
    {
        if( self::$initialized ) {
            return false;
        }

        load_plugin_textdomain( DOMAIN, false, DOMAIN . '/languages/' );
        self::include_required_classes();

        self::$initialized = true;
    }

    /**
     * Записываем ошибку
     */
    public static function write_debug( $msg, $dir )
    {
        if( ! defined('WP_DEBUG_LOG') || ! WP_DEBUG_LOG )
            return;

        $dir = str_replace(__DIR__, '', $dir);
        $msg = str_replace(__DIR__, '', $msg);

        $date = new \DateTime();
        $date_str = $date->format(\DateTime::W3C);

        $handle = fopen(__DIR__ . "/debug.log", "a+");
        fwrite($handle, "[{$date_str}] {$msg} ({$dir})\r\n");
        fclose($handle);
    }

    /**
     * Загружаем файл если существует
     */
    public static function load_file_if_exists( $file_array, $args )
    {
        $cant_be_loaded = __('The file %s can not be included', DOMAIN);
        if( is_array( $file_array ) ) {
            $result = array();
            foreach ( $file_array as $id => $path ) {
                if ( ! is_readable( $path ) ) {
                    self::write_debug(sprintf($cant_be_loaded, $path), __FILE__);
                    continue;
                }

                $result[] = include_once( $path );
            }
        }
        else {
            if ( ! is_readable( $file_array ) ) {
                self::write_debug(sprintf($cant_be_loaded, $file_array), __FILE__);
                return false;
            }

            $result = include_once( $file_array );
        }

        return $result;
    }

    public static function get_plugin_dir( $path = false )
    {
        $result = __DIR__;

        switch ( $path ) {
            case 'classes': $result .= '/includes/classes'; break;
            case 'settings': $result .= '/includes/settings'; break;
            default: $result .= '/' . $path;
        }

        return $result;
    }

    public static function get_plugin_url( $path = false )
    {
        $result = plugins_url(basename(__DIR__) );

        switch ( $path ) {
            default: $result .= '/' . $path;
        }

        return $result;
    }

    /**
     * Получает настройку из self::$settings или из кэша или из базы данных
     */
    public static function get( $prop_name, $default = false )
    {
        if( ! self::$settings )
            self::$settings = get_option( self::OPTION, array() );

        if( 'all' === $prop_name ) {
            if( is_array(self::$settings) && count(self::$settings) )
                return self::$settings;

            return $default;
        }

        return isset( self::$settings[ $prop_name ] ) ? self::$settings[ $prop_name ] : $default;
    }

    public static function get_settings( $filename, $args )
    {

        return self::load_file_if_exists( self::get_plugin_dir('settings') . '/' . $filename, $args );
    }
}

register_activation_hook( __FILE__, array( __NAMESPACE__ . '\Utils', 'activate' ) );
register_uninstall_hook( __FILE__, array( __NAMESPACE__ . '\Utils', 'uninstall' ) );
// register_deactivation_hook( __FILE__, array( __NAMESPACE__ . '\Utils', 'deactivate' ) );

add_action( 'plugins_loaded', array( __NAMESPACE__ . '\Utils', 'initialize' ), 10 );
