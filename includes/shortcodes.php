<?php

use CDevelopers\GeoZoneDetective as gz;
// [gz_city_name]
// [gz_select_city]
// [gz_if_city][/gz_if_city]

add_shortcode( 'gz_city_name', 'gz_get_city_name' );
function gz_get_city_name( $atts = array(), $content = '' ) {
    $atts = shortcode_atts( array(
        'city_id'   => '',
        'city_name' => '',
    ), $atts );

    $out = '';
    if( $atts['city_id'] || $atts['city_name'] ) {
        if( $item = gz\Init::get_city_item() )
        {
            if( $atts['city_id'] )
                $out = $item->city_id;

            if( $atts['city_name'] )
                $out = $item->city;
        }
    }
    else {
        // $ip = gz\Init::get_current_ip();
        $ip = '94.181.95.199';
        if( $item = gz\Init::get_geo_object( $ip ) ) {
            $out = $item->city;
        }
    }

    if( ! $out ) {
        $out = __('undefined', gz\DOMAIN);
    }

    return sprintf('<span class="gz-current-city-name">%s</span>', $out);
}

add_shortcode( 'gz_select_city', 'gz_get_select_city' );
function gz_get_select_city() {
    $url = gz\Utils::get_plugin_url('assets');
    wp_enqueue_script( 'gz-city-select', $url . '/public.js', array('jquery'), '1.0', true );
    wp_localize_script( 'gz-city-select', 'gz', array(
        'nonce'    => wp_create_nonce( 'gz-city-select' ),
        'ajax_url' => admin_url('admin-ajax.php'),
        ) );

    $global_cities = array(
        '2097' => 'Москва',
        '2287' => 'Санкт-Петербург',
        '2732' => 'Екатеринбург',
        '1721' => 'Ижевск',
        '1587' => 'Кострома',
        '1750' => 'Магнитогорск',
        '1956' => 'Н. Новгород',
        '2012' => 'Новосибирск',
        '2190' => 'Пермь',
        '1235' => 'Ростов-на-Дону',
        '2275' => 'Саратов',
        '491'  => 'Симферополь',
        '2695' => 'Тюмень',
        '2596' => 'Тольятти',
        '2644' => 'Уфа',
        );

    ob_start();
    ?>
    <div class="gz-current-city-wrap">
        <a href="#"><?php echo gz_get_city_name(); ?></a>
        <div class="city-select-wrap hidden">
            <fieldset>
                <legend>Выберите город</legend>
                <input type="text" id="city_search" name="city_search" placeholder="Начните вводить название города" class="form-control">
                <div class="city-list">
                    <ul class="two-columns">
                        <?php
                        foreach ($global_cities as $city_id => $city) {
                            echo "<li><a href='#' data-geo-id='{$city_id}'>{$city}</a></li>";
                        }
                        ?>
                    </ul>
                    <div class="result-list hidden"></div>
                </div>
            </fieldset>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

add_action('wp_ajax_nopriv_gz_update_geo_item', 'gz_ajax_update_geo_item');
add_action('wp_ajax_gz_update_geo_item', 'gz_ajax_update_geo_item');
function gz_ajax_update_geo_item()
{
    if( ! wp_verify_nonce( $_POST['nonce'], 'gz-city-select' ) ) {
        wp_die('Ошибка! нарушены правила безопасности');
    }

    global $wpdb;

    $city_id = absint($_POST['city_id']);
    $tcity = $wpdb->prefix . gz\Utils::TABLE_CITIES;
    $trange = $wpdb->prefix . gz\Utils::TABLE_RANGES;

    $query = "
    SELECT cities.ID, addr_range, country, cities.city_id, city, region, district, lat, lng
    FROM $trange ranges
    INNER JOIN $tcity cities ON cities.city_id = ranges.city_id
    WHERE cities.city_id = $city_id
    LIMIT 1";

    $result = $wpdb->get_results($query);

    if( is_array($result) ) {
        if( ! isset( $_SESSION ) )
            session_start();

        $result = current($result);

        unset($_SESSION['gz_geo_object']);
        $_SESSION['gz_geo_object'] = $result;
        echo 1;
    }
    else {
        echo wp_die( 'Не удалось сменить гео позицию' );
    }

    wp_die();
}

add_action('wp_ajax_nopriv_gz_cities_name_list', 'gz_ajax_cities_name_list');
add_action('wp_ajax_gz_cities_name_list', 'gz_ajax_cities_name_list');
function gz_ajax_cities_name_list()
{
    echo json_encode( gz\Init::get_all_cities(sanitize_text_field( $_POST['string'] ), $limit = 10) );
    wp_die();
}

function gq_style_enqueue() {
    $url = gz\Utils::get_plugin_url('assets');
    wp_enqueue_style( 'gz-city-select-style', $url . '/public.js', $deps = null, $ver = '1.0' );
}
add_action( 'wp_enqueue_scripts', 'gq_style_enqueue' );
