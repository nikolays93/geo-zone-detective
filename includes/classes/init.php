<?php
namespace CDevelopers\GeoZoneDetective;

/**  */
class Init
{
    protected $arrFilenames,
              $charset = 'UTF-8';

    function __construct()
    {
    }

    public static function update_cities( $per_once = 10000 )
    {
        global $wpdb;

        $table = $wpdb->prefix . Utils::TABLE_CITIES;
        $query = "INSERT INTO $table (ID, city_id, city, region, district, lat, lng) VALUES ";

        if( ! $transient = get_transient( 'update_cities' ) ) {
            $wpdb->query("TRUNCATE TABLE $table");
        }

        $args = wp_parse_args( $transient, array(
            'count'  => 0,
            'size'   => 0,
        ) );

        $filename = Utils::get_plugin_dir('includes/bases/cities.txt');
        if( ! is_readable($filename) ) {
            echo "File is not exists";
            return 0;
        }

        $values = array();
        $place_holders = array();

        $result = 0;
        $file = file( $filename );
        foreach ($file as $count => $str) {
            if( $args['count'] && $count <= $args['count'] ) continue;

            $item = explode("\t", trim( !preg_match('#.#u', $str) ?
                iconv('CP1251', 'UTF-8', $str) : $str ) );
            $args['count']++;

            if( !isset($item[5]) ) {
                continue;
            }

            array_push($values, '', $item[0], $item[1], $item[2], $item[3], $item[4], $item[5]);
            $place_holders[] = "('%d', '%d', '%s', '%s', '%s', '%s', '%s')";
            $result++;

            if( $result >= $per_once ) break;
        }

        if( $result ) {
            $query .= implode(', ', $place_holders);
            $wpdb->query( $wpdb->prepare("$query ", $values));
        }

        if( ! $args['size'] ) {
            $args['size'] = sizeof($file) - 1;
        }

        set_transient( 'update_cities', $args, 12 * HOUR_IN_SECONDS );

        return $result;
    }

    public static function update_ranges( $per_once = 10000 )
    {
        global $wpdb;

        $table = $wpdb->prefix . Utils::TABLE_RANGES;
        $query = "INSERT INTO $table (ID, addr_begin, addr_end, addr_range, country, city_id) VALUES ";

        if( ! $transient = get_transient( 'update_ranges' ) ) {
            $wpdb->query("TRUNCATE TABLE $table");
        }

        $args = wp_parse_args( $transient, array(
            'count'  => 0,
            'size'   => 0,
        ) );

        $filename = Utils::get_plugin_dir('includes/bases/cidr_optim.txt');
        if( ! is_readable($filename) ) {
            echo "File is not exists";
            return 0;
        }

        $values = array();
        $place_holders = array();

        $result = 0;
        $file = file( $filename );
        foreach ($file as $count => $str) {
            if( $args['count'] && $count <= $args['count'] ) continue;

            $item = explode("\t", trim( !preg_match('#.#u', $str) ?
                iconv('CP1251', 'UTF-8', $str) : $str ) );
            $args['count']++;

            if( !isset($item[4]) ) {
                continue;
            }

            array_push($values, '', $item[0], $item[1], $item[2], $item[3], $item[4]);
            $place_holders[] = "('%d', '%d', '%d', '%s', '%s', '%d')";
            $result++;

            if( $result >= $per_once ) break;
        }

        if( $result ) {
            $query .= implode(', ', $place_holders);
            $wpdb->query( $wpdb->prepare("$query ", $values));
        }

        if( ! $args['size'] ) {
            $args['size'] = sizeof($file) - 1;
        }

        set_transient( 'update_ranges', $args, 12 * HOUR_IN_SECONDS );

        return $result;
    }

    public function get_cities_count()
    {
        global $wpdb;

        $table = $wpdb->prefix . Utils::TABLE_CITIES;
        $count_query = "select count(*) from $table";
        $num = $wpdb->get_var($count_query);

        return $num;
    }

    public function get_ranges_count()
    {
        global $wpdb;

        $table = $wpdb->prefix . Utils::TABLE_RANGES;
        $count_query = "select count(*) from $table";
        $num = $wpdb->get_var($count_query);

        return $num;
    }

    public static function get_city_item( $args )
    {
        global $wpdb;

        $args = wp_parse_args( $args, array(
            'city_id'   => 0,
            'city_name' => '',
            ) );

        $args['city_id'] = absint($args['city_id']);

        foreach ($args as $key => $value) {
            if( $value ) break;
        }

        if( $key && $value ) {
            $table = $wpdb->prefix . Utils::TABLE_CITIES;
            $query = "SELECT * FROM $table WHERE $key = $value LIMIT 1";

            $res = $wpdb->get_results($query);
            if( is_array($res) ) {
                return current($res);
            }
        }

        return false;
    }

    public static function get_geo_object( $ip = null, $use_session = true )
    {
        if( ! $ip ) $ip = self::get_current_ip();
        if( ! self::is_valid_ip($ip) ) return false;

        if( $use_session && ! isset( $_SESSION ) )
            session_start();

        if( $use_session && isset( $_SESSION['gz_geo_object'] ) ) {
            return $_SESSION['gz_geo_object'];
        }

        global $wpdb;

        $ip = sprintf('%u', ip2long($ip) );
        $tcity = $wpdb->prefix . Utils::TABLE_CITIES;
        $trange = $wpdb->prefix . Utils::TABLE_RANGES;

        $query = "
        SELECT cities.ID, addr_range, country, cities.city_id, city, region, district, lat, lng
        FROM $trange ranges
        INNER JOIN $tcity cities ON cities.city_id = ranges.city_id
        WHERE ranges.addr_begin < $ip AND ranges.addr_end > $ip
        LIMIT 1";

        $result = $wpdb->get_results($query);

        if( is_array($result) ) {
            $result = current($result);

            if( $use_session )
                $_SESSION['gz_geo_object'] = $result;

            return $result;
        }

        return false;
    }

    public static function get_all_cities($string = null, $limit = 10)
    {
        global $wpdb;

        $tcity = $wpdb->prefix . Utils::TABLE_CITIES;
        $query = "SELECT city_id, city FROM $tcity";

        if( $string ) {
            $query.= "\nWHERE `city` LIKE '%{$string}%'";
        }

        if( $limit ) {
            $query.= "\nLIMIT $limit";
        }

        $result = $wpdb->get_results($query);

        return $result;
    }

    /**
     * функция определяет ip адрес по глобальному массиву $_SERVER
     * ip адреса проверяются начиная с приоритетного, для определения возможного использования прокси
     * @return ip-адрес
     */
    static public function get_current_ip() {
        $keys = array('HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR', 'HTTP_X_REAL_IP');
        foreach ($keys as $key) {
            $ip = trim(strtok(filter_input(INPUT_SERVER, $key), ','));
            if (self::is_valid_ip($ip)) {
                return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
            }
        }
    }

    /**
     * функция для проверки валидности ip адреса
     * @param ip адрес в формате 1.2.3.4
     * @return bolean : true - если ip валидный, иначе false
     */
    static public function is_valid_ip( $ip ) {
        return (bool) filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
    }
}
