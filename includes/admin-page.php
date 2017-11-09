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

        if( current_user_can( 'manage_options' ) ) {
            if( !isset($_SESSION) ) {
                session_start();
            }

            if( ! empty($_GET['update_cities']) ) {
                if( ($t = get_transient( 'update_cities' )) && $t['count'] == $t['size'] ) {
                    $_SESSION['gz_notice_message'] = (object) array(
                        'message' => __('Cities allready updated.', DOMAIN),
                        'status' => 'info',
                    );
                }
                else {
                    $updated = Init::update_cities(25000);
                    $updated+= Init::update_cities(25000);
                    $updated+= Init::update_cities(25000);
                    $updated+= Init::update_cities(25000);

                    if( $updated ) {
                        $t = get_transient( 'update_cities' );
                        $_SESSION['gz_notice_message'] = (object) array(
                            'status' => 'success',
                            'message' => sprintf( __( '%d cities updated. (Summary: %d at %d)', DOMAIN),
                                $updated, $t['count'], $t['size'] ),
                        );
                    }
                    else {
                        $_SESSION['gz_notice_message'] = (object) array(
                            'message' => __('Cities not updated.', DOMAIN),
                            'status' => 'error',
                        );
                    }
                }

                wp_redirect( 'http://wordpress.cms' . remove_query_arg('update_cities') );
                exit;
            }

            if( ! empty($_GET['update_ranges']) ) {
                if( ($t = get_transient( 'update_ranges' )) && $t['count'] == $t['size'] ) {
                    $_SESSION['gz_notice_message'] = (object) array(
                        'message' => __('Ranges allready updated.', DOMAIN),
                        'status' => 'info',
                    );
                }
                else {
                    $updated = Init::update_ranges(25000);
                    $updated+= Init::update_ranges(25000);
                    $updated+= Init::update_ranges(25000);
                    $updated+= Init::update_ranges(25000);

                    if( $updated ) {
                        $t = get_transient( 'update_ranges' );
                        $_SESSION['gz_notice_message'] = (object) array(
                            'status' => 'success',
                            'message' => sprintf( __( '%d ranges updated. (Summary: %d at %d)', DOMAIN),
                                $updated, $t['count'], $t['size'] ),
                        );
                    }
                    else {
                        $_SESSION['gz_notice_message'] = (object) array(
                            'message' => __('Ranges not updated.', DOMAIN),
                            'status' => 'error',
                        );
                    }
                }

                wp_redirect( 'http://wordpress.cms' . remove_query_arg('update_ranges') );
                exit;
            }

            if( ! empty($_SESSION['gz_notice_message']) ) {
                $page::add_notice( $_SESSION['gz_notice_message'] );
                unset($_SESSION['gz_notice_message']);
            }
        }
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

        $cities = init::get_all_cities();
        ?>
        <style>
            fieldset {
                float: left;
                box-sizing: border-box;
                width: 33%;
                min-width: 330px;
                border: 1px solid #ccc;
                padding: 4px 6px;
                height: 200px;
                overflow: auto;
                margin-bottom: 10px;
            }
            fieldset legend {
                margin-left: auto;
                padding: 0px 15px;
            }
            .half {
                box-sizing: border-box;
                float: left;
                width: 50%;
                min-height: 1px;
            }
        </style>
        <fieldset class="city_select">
            <legend>Основные города</legend>
            <div class='half list'>
            <?php
            foreach ($cities as $city) {
                echo sprintf( '<label for="%1$s[%2$d]"><input type="checkbox" name="%1$s[%2$d]" id="%1$s[%2$d]"> %3$s <br> </label>',
                    Utils::OPTION, $city->city_id, $city->city );
            }
            ?>
            </div>
            <div class='half activated'></div>
        </fieldset>
        <script>
            (function($) {
                $('.city_select label input').on('click', function(event) {
                    var element = $(this).parent();

                    if( element.find('input').is(':checked') ) {
                        element.clone(1).appendTo( $(this).closest('.city_select').find('.activated') );
                    }
                    else {
                        element.clone(1).appendTo( $(this).closest('.city_select').find('.list') );
                    }
                    element.remove();
                });
            })(jQuery);
        </script>
        <?php
        $data = array(
            // array(
            //     'id'      => 'active_cities',
            //     'type'    => 'html',
            //     'value'   => '',
            // ),
            array(
                'id'      => 'update_cities',
                'type'    => 'html',
                'value'    =>
                sprintf('<a href="%s" class="button button-primary">%s</a> ',
                    add_query_arg( array('update_cities' => '1') ),
                    __( 'Update cities', DOMAIN ) ) .
                sprintf('<a href="%s" class="button button-primary">%s</a> ',
                    add_query_arg( array('update_ranges' => '1') ),
                    __( 'Update ranges', DOMAIN ) )
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

        submit_button( 'Сохранить', 'primary', 'save_changes' );
    }
}
new Admin_Page();
