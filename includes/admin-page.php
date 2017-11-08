<?php

namespace CDevelopers\GeoZoneDetective;

if ( ! defined( 'ABSPATH' ) )
  exit; // disable direct access

class Admin_Page
{
    function __construct()
    {
        $page = new WP_Admin_Page( Utils::OPTION );
        $page->set_args( array(
            'parent'      => 'options-general.php',
            'title'       => __('Определение ГЕО-Локации по IP адресу', DOMAIN),
            'menu'        => __('Geo Zone Detective', DOMAIN),
            'callback'    => array($this, 'page_render'),
            // 'validate'    => array($this, 'validate_options'),
            'permissions' => 'manage_options',
            'tab_sections'=> null,
            'columns'     => 1,
            ) );

        session_start();
        if( current_user_can( 'manage_options' ) ) {
            if( ! empty($_GET['update_cities']) ) {
                // Update cities
                $gz = new GeoZoneDetective();
                $gz->register_files();
                $updated = $gz->update_database( 'Cities' );

                if( $updated['result'] ) {
                    $_SESSION['gz_notice_message'] = (object) array(
                            'status' => 'success',
                            'message' => sprintf( __( '%d cities updated. (Summary: %d at %d)', DOMAIN),
                                $updated['result'], $updated['count'], $updated['size'] ),
                            );
                }
                else {
                    $_SESSION['gz_notice_message'] = (object) array(
                        'message' => __('Cities not updated.', DOMAIN),
                        'status' => 'error',
                        );
                }

                wp_redirect( 'http://wordpress.cms' . remove_query_arg('update_cities') );
                exit;
            }

            if( ! empty($_GET['update_cidr']) ) {
                // Update cities
                $gz = new GeoZoneDetective();
                $gz->register_files();
                $updated = $gz->update_database( 'CIDR' );

                if( $updated['result'] ) {
                    $_SESSION['gz_notice_message'] = (object) array(
                        'status' => 'success',
                        'message' => sprintf( __( '%d ranges updated. (Summary: %d at %d)', DOMAIN),
                            $updated['result'], $updated['count'], $updated['size'] ),
                        );
                }
                else {
                    if( $updated['count'] == $updated['size'] ) {
                        $_SESSION['gz_notice_message'] = (object) array(
                            'status' => 'success',
                            'message' => sprintf( __( 'Ranges was allready updated. (Summary count: %d)', DOMAIN),
                                $updated['result'], $updated['count'], $updated['size'] ),
                            );
                    }

                    $_SESSION['gz_notice_message'] = (object) array(
                        'message' => __('Ranges not updated.', DOMAIN),
                        'status' => 'error',
                        );
                }

                wp_redirect( 'http://wordpress.cms' . remove_query_arg('update_cidr') );
                exit;
            }

            if( ! empty($_SESSION['gz_notice_message']) ) {
                $page::add_notice( $_SESSION['gz_notice_message'] );
                unset($_SESSION['gz_notice_message']);
            }
        }

        // $page->set_assets( array($this, '_assets') );

        // $page->add_metabox( 'metabox1', 'metabox1', array($this, 'metabox1_callback'), $position = 'side');
        // $page->add_metabox( 'metabox2', 'metabox2', array($this, 'metabox2_callback'), $position = 'side');
        // $page->set_metaboxes();
    }

    function _assets()
    {
        // wp_enqueue_style();
        // wp_enqueue_script();
    }

    /**
     * Основное содержимое страницы
     *
     * @access
     *     must be public for the WordPress
     */
    function page_render() {
        $data = array(
            array(
                'id'      => 'charset',
                'type'    => 'text',
                'label'   => __('Charset', DOMAIN),
                'desc'    => '',
                ),
            array(
                'id'      => 'country',
                'type'    => 'text',
                'label'   => __('Default Country', DOMAIN),
                'desc'    => '',
                ),
            array(
                'id'      => 'city',
                'type'    => 'text',
                'label'   => __('Default City', DOMAIN),
                'desc'    => '',
                ),
            array(
                'id'      => 'region',
                'type'    => 'text',
                'label'   => __('Default Region', DOMAIN),
                'desc'    => '',
                ),
            array(
                'id'      => 'district',
                'type'    => 'text',
                'label'   => __('Default District', DOMAIN),
                'desc'    => '',
                ),
            array(
                'id'      => 'test-ip',
                'type'    => 'text',
                'label'   => __('Fake IP for debug', DOMAIN),
                'desc'    => '',
                ),
            );

        $form = new WP_Admin_Forms( $data, $is_table = true, $args = array(
            // Defaults:
            // 'admin_page'  => true,
            // 'item_wrap'   => array('<p>', '</p>'),
            // 'form_wrap'   => array('', ''),
            // 'label_tag'   => 'th',
            // 'hide_desc'   => false,
            ) );
        echo $form->render();

        // $ip = GeoZoneDetective::get_current_ip();
        $ip = '94.181.95.199';
        // $range_item = GeoZoneDetective::get_range_item( $ip );
        // var_dump($range_item);
        // if( isset($range_item->city_id) ) {
        //     echo "<hr>";
        //     $city_item  = GeoZoneDetective::get_city_item( $range_item->city_id );
        //     var_dump($city_item);
        // }
        var_dump( GeoZoneDetective::get_geo_object($ip) );
        echo "<hr>";
        $cities = GeoZoneDetective::get_all_cities(null, null);
        foreach ($cities as $objCity) {
            echo "$objCity->city <br>";
        }

        submit_button( 'Сохранить', 'primary', 'save_changes' );
    }
}
new Admin_Page();
