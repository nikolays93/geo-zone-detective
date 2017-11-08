<?php
namespace CDevelopers\GeoZoneDetective;

if( ! defined('GEO_LISTS_DIR') )
	define('GEO_LISTS_DIR', dirname(__FILE__) . '/geo_files/');

/**  */
class Init
{
    protected $arrFilenames,
              $charset = 'UTF-8';

    function __construct()
    {
    }

    public function register_files( $arrFilenames = null )
    {
        $bases_dir = Utils::get_plugin_dir('includes/bases');
        $arrFilenames = (array) $arrFilenames;
        $arrFilenames = wp_parse_args( $arrFilenames, array(
            'CIDR' => $bases_dir . '/cidr_optim.txt',
            'Cities' => $bases_dir . '/cities.txt',
            ) );

        $this->arrFilenames['CIDR'] = is_readable( $arrFilenames['CIDR'] )
            ? $arrFilenames['CIDR'] : '';
        $this->arrFilenames['Cities'] = is_readable( $arrFilenames['Cities'] )
            ? $arrFilenames['Cities'] : '';
    }

    private static function insert_item($type, $table, $item, $filename)
    {
        global $wpdb;

        if( $type == 'Cities' ) {
            if( ! isset($item[5]) ) {
                echo "Строка $count файла ".basename($filename)." повреждена.";
                return false;
            }

            return $wpdb->insert($table, array(
                'city_id'  => $item[0],
                'city'     => $item[1],
                'region'   => $item[2],
                'district' => $item[3],
                'lat'      => $item[4],
                'lng'      => $item[5],
                ) );
        }
        else {
            if( ! isset($item[4]) ) {
                echo "Строка $count файла " . basename($filename) . " повреждена.";
                return false;
            }

            return $wpdb->insert($wpdb->prefix . Utils::TABLE_RANGES, array(
                'addr_begin' => $item[0],
                'addr_end'   => $item[1],
                'addr_range' => $item[2],
                'country'    => $item[3],
                'city_id'    => $item[4],
                ) );
        }
    }

    public function update_database( $type = 'Cities', $per_once = 100000 )
    {
        $debug = false;
        if( ! in_array($type, array('Cities', 'CIDR')) ) {
            return false;
        }

        global $wpdb;

        $table = $wpdb->prefix;
        $table.= ('Cities' === $type) ? Utils::TABLE_CITIES : Utils::TABLE_RANGES;

        if( ! $transient = get_transient( 'update_' . $type ) ) {
            if( ! $debug )
                $wpdb->query("TRUNCATE TABLE $table");
        }

        $args = wp_parse_args( $transient, array(
            'count' => 0,
            'size' => 0,
            'result' => 0,
            ) );

        $filename = $this->arrFilenames[ $type ];

        if( ! is_readable($filename) ) {
            if( $debug )
                echo "File is not exists";

            return $args;
        }

        $file = file( $filename );
        foreach ($file as $count => $str) {
            // var_dump( $count,$args['count'],$per_once  );
            if( $args['count'] && $count <= $args['count'] ) continue;

            if( ! preg_match('#.#u', $str) ){
                $str = iconv('CP1251', 'UTF-8', $str);
            }

            $item = explode("\t", trim($str));

            if( ! $debug ) {
                if( self::insert_item($type, $table, $item, $filename) ) {
                    $args['result']++;
                }
            }
            else {
                $args['result']++;
            }

            $args['count']++;

            if( $args['result'] >= $per_once ) break;
        }

        if( ! $args['size'] ) {
            $args['size'] = sizeof($file);
        }

        set_transient( 'update_' . $type, array(
            // 'file' => $filename,
            'count' => $args['count'],
            'size' => $args['size'] ? $args['size'] : sizeof($file),
            ), 12 * HOUR_IN_SECONDS );

        return $args;
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

    public static function get_range_item( $ip )
    {
        if( ! self::is_valid_ip($ip) ) return false;

        global $wpdb;

        $ip = sprintf('%u', ip2long($ip) );

        $table = $wpdb->prefix . Utils::TABLE_RANGES;
        $query = "
        SELECT * FROM $table
        WHERE `addr_begin` < $ip
        AND `addr_end` > $ip
        LIMIT 1";

        $result = $wpdb->get_results($query);
        if( is_array($result) )
            $result = current($result);

        return $result;
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
