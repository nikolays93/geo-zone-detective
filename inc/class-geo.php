<?php

// Клас является форком http://faniska.ru/php-kusochki/geotargeting-novyj-php-klass-dlya-raboty-s-bazoj-ipgeobase-ru.html

class Geo {

    public $ip;
    static protected $charset = 'utf-8';

    /**
     * Создаем объект с заданными настройками.
     * @param array $options
     * @return void
     */
    public function __construct(array $options = array()) {
        // ip
        if (isset($options['ip']) && $this->is_valid_ip($options['ip'])) {
            $this->ip = $options['ip'];
        } else {
            $this->ip = $this->get_ip();
        }
        // кодировка
        if (isset($options['charset']) && is_string($options['charset'])) {
            self::$charset = $options['charset'];
        }
    }

    static public function getListCountries(){
        $countries = array();

        if( ! is_readable(GEO_COUNTRIES_FILE) )
            wp_die( 'Файл countries.txt не найден' );

        $countries_file = fopen(GEO_COUNTRIES_FILE, 'r');

        rewind($countries_file);
        while(!feof($countries_file)){
            $str = fgets($countries_file);
            if (empty($str)) continue;

            if (self::$charset != 'windows-1251' && function_exists('iconv')) {
                $str = iconv('windows-1251', self::$charset, $str);
            }

            $column = explode("\t", trim($str));
            $countries[ $column[3] ] = $column[0];
        }

        return $countries;
    }

    static public function getListCities( $id = false ){
        $cities = array();

        if( ! is_readable(GEO_CITIES_FILE) )
            wp_die( 'Файл cities.txt не найден' );

        $cities_file = fopen(GEO_CITIES_FILE, 'r');

        rewind($cities_file);
        while(!feof($cities_file)){
            $str = fgets($cities_file);
            if (empty($str)) continue;

            if (self::$charset != 'windows-1251' && function_exists('iconv')) {
                $str = iconv('windows-1251', self::$charset, $str);
            }
            
            $column = explode("\t", trim($str));

            if( $id && $id == $column[0] ){
                $city_info = array(
                    'id'       => $column[0],
                    'city'     => !empty($column[1]) ? $column[1] : '',
                    'region'   => !empty($column[2]) ? $column[2] : '',
                    'district' => !empty($column[3]) ? $column[3] : '',
                    'lat'      => !empty($column[4]) ? $column[4] : '',
                    'lng'      => !empty($column[5]) ? $column[5] : '',
                    );
                return $city_info;
            }
            else {
                $cities[ $column[0] ] = $column[1];
            }
        }   

        asort($cities); // Сортирует массив по значению

        return $cities;
    }

    static public function getCityInfo( $id ){
        return self::getListCities( $id );
    }

    /**
     * функция возвращет конкретное значение из полученного массива данных по ip
     * @param string - ключ массива. Если интересует конкретное значение. 
     * Ключ может быть равным 'inetnum', 'country', 'city', 'region', 'district', 'lat', 'lng'
     * @param bolean - устанавливаем хранить данные в куки или нет
     * Если true, то в куки будут записаны данные по ip и повторные запросы на ipgeobase происходить не будут.
     * Если false, то данные постоянно будут запрашиваться с ipgeobase
     * @return array OR string - дополнительно читайте комментарии внутри функции.
     */
    public function get_value($key = null, $cookie = true) {
        $key_array = array('inetnum', 'country', 'city', 'region', 'district', 'lat', 'lng');
        if (!in_array($key, $key_array)) {
            $key = null;
        }
        $data = $this->get_data($cookie);
        if ($key) { // если указан ключ 
            if (isset($data[$key])) { // и значение есть в массиве данных
                return $data[$key]; // возвращаем строку с нужными данными
            } elseif ($cookie) { // иначе если были включены куки
                return $this->get_value($key, false); // пытаемся вернуть данные без использования cookie
            }
            return NULL; // если ничего нет - отдаем NULL
        }
        return $data; // иначе возвращаем массив со всеми данными            
    }

    /**
     * Получаем данные с сервера или из cookie
     * @param boolean $cookie
     * @return string|array
     */
    public function get_data($cookie = true) {
        // если используем куки и параметр уже получен, то достаем и возвращаем данные из куки
        if ($cookie && filter_input(INPUT_COOKIE, 'geobase')) {
            return unserialize(filter_input(INPUT_COOKIE, 'geobase'));
        }
        $data = $this->get_geobase_data();
        if (!empty($data)) {
            setcookie('geobase', serialize($data), time() + 3600 * 24 * 7, '/'); //устанавливаем куки на неделю
        }
        return $data;
    }

    /**
     * функция получает данные по ip.
     * @return array - возвращает массив с данными
     */
    protected function get_geobase_data() {
        // получаем данные по ip
        $ch = curl_init('http://ipgeobase.ru:7020/geo?ip=' . $this->ip);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        $string = curl_exec($ch);
        // если указана кодировка отличная от windows-1251, изменяем кодировку
        if (self::$charset != 'windows-1251' && function_exists('iconv')) {
            $string = iconv('windows-1251', self::$charset, $string);
        }
        $data = $this->parse_string($string);
        return $data;
    }

    /**
     * функция парсит полученные в XML данные в случае, если на сервере не установлено расширение Simplexml
     * @return array - возвращает массив с данными
     */
    protected function parse_string($string) {
        $params = array('inetnum', 'country', 'city', 'region', 'district', 'lat', 'lng');
        $data = $out = array();
        foreach ($params as $param) {
            if (preg_match('#<' . $param . '>(.*)</' . $param . '>#is', $string, $out)) {
                $data[$param] = trim($out[1]);
            }
        }
        return $data;
    }

    /**
     * функция определяет ip адрес по глобальному массиву $_SERVER
     * ip адреса проверяются начиная с приоритетного, для определения возможного использования прокси
     * @return ip-адрес
     */
    public function get_ip() {
        $keys = array('HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR', 'HTTP_X_REAL_IP');
        foreach ($keys as $key) {
            $ip = trim(strtok(filter_input(INPUT_SERVER, $key), ','));
            if ($this->is_valid_ip($ip)) {
                return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
            }
        }
    }

    /**
     * функция для проверки валидности ip адреса
     * @param ip адрес в формате 1.2.3.4
     * @return bolean : true - если ip валидный, иначе false
     */
    public function is_valid_ip($ip) {
        return (bool)filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
    }

}

// cidr_optim.txt имеет следующий формат записи:
// <начало блока> <конец блока> <блок адресов> <страна> <идентификатор города>
// <начало блока> - число, полученное из первого ip адреса блока (диапазона) ip-адресов вида a.b.c.d по формуле a*256*256*256+b*256*256+c*256+d
// <конец блока> - число, полученное из второго ip адреса блока (диапазона) ip-адресов вида e.f.g.h по формуле e*256*256*256+f*256*256+g*256+h
// <блок адресов> - блок (диапазон) ip-адресов вида a.b.c.d - e.f.g.h, для кторого определено положение
// <страна> - двухбуквенный код страны, к которой относится блок
// <идентификатор города> - идентификатор города из файла cities.txt. Если вместо идентификатора стоит прочерк, значит, либо город не удалось определить, либо страна блока не Россия и не Украина.