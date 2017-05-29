<?php
/*
	Class for working with ipgeobase.ru geo database.

	Copyright (C) 2013, Vladislav Ross

	This library is free software; you can redistribute it and/or
	modify it under the terms of the GNU Lesser General Public
	License as published by the Free Software Foundation; either
	version 2.1 of the License, or (at your option) any later version.

	This library is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
	Lesser General Public License for more details.

	You should have received a copy of the GNU Lesser General Public
	License along with this library; if not, write to the Free Software
	Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA

    E-mail: vladislav.ross@gmail.com
	URL: https://github.com/rossvs/ipgeobase.php
	
*/
/*
 * @class IPGeoBase
 * @brief Класс для работы с текстовыми базами ipgeobase.ru
 * @see example.php
 *
 * Определяет страну, регион и город по IP для России и Украины
 */

if( ! defined('GEO_LISTS_DIR') )
	define('GEO_LISTS_DIR', dirname(__FILE__) . '/geo_files/');

class IPGeoBase 
{
	const COOKIE_NAME = 'IPGeoBase';

	private $ip;
	private $fhandleCIDR, $fhandleCities, $fSizeCIDR, $fsizeCities;

	public $charset = 'UTF-8';

    /*
     * @brief Конструктор
     *
     * @param CIDRFile файл базы диапазонов IP (cidr_optim.txt)
     * @param CitiesFile файл базы городов (cities.txt)
     */
	function __construct($ip = false, $CIDRFile = false, $CitiesFile = false){
		if(!$CIDRFile)
			$CIDRFile = GEO_LISTS_DIR . 'cidr_optim.txt';

		if(!$CitiesFile)
			$CitiesFile = GEO_LISTS_DIR . 'cities.txt';

		$this->fhandleCIDR = fopen($CIDRFile, 'r') or die("Cannot open $CIDRFile");
		$this->fhandleCities = fopen($CitiesFile, 'r') or die("Cannot open $CitiesFile");
		$this->fSizeCIDR = filesize($CIDRFile);
		$this->fsizeCities = filesize($CitiesFile);

			$this->ip = $this->is_valid_ip($ip) ? $ip : $this->get_ip();
	}

	/** GET SINGLE INFO ***********************************/

    /*
     * @brief Получение информации о городе по индексу
     * @param idx индекс города
     * @param cookie использовать cookie
     * @return массив или false, если не найдено
     */
	private function getCityByIdx($idx){
		rewind($this->fhandleCities);
		while(!feof($this->fhandleCities)){
			$str = fgets($this->fhandleCities);
			$arRecord = explode("\t", trim($str));
			if($arRecord[0] == $idx){

				if($this->charset != 'cp1251'){
					$result = array(
						'city'     => iconv('cp1251', $this->charset, $arRecord[1]),
						'region'   => iconv('cp1251', $this->charset, $arRecord[2]),
						'district' => iconv('cp1251', $this->charset, $arRecord[3]),
						'lat'      => iconv('cp1251', $this->charset, $arRecord[4]),
						'lng'      => iconv('cp1251', $this->charset, $arRecord[5]),
						);
				}
				else {
					$result = array(
						'city'     => $arRecord[1],
						'region'   => $arRecord[2],
						'district' => $arRecord[3],
						'lat'      => $arRecord[4],
						'lng'      => $arRecord[5],
						);
				}

				return $result;
			}
		}
		return false;
	}

	/**
	 * Get Record (getRecord)
	 * @param  boolean $key    Ключ возвращаемого значения ( @see $key_array )
	 * @param  boolean $cookie Исользовать Cookie
	 * @return mixed   $result false  - если значение не корректно или отсутствует
	 *                         array  - если значение найдено и не указан ключ
	 *                         string - если указан ключ, выдаст значение ключа масива
	 */
	public function getRecord($key = false, $cookie = true ){
		var_dump($this->ip);
		// Если ключ указан и его не существует, вернет false
		$key_array = array('range', 'country', 'city', 'region', 'district', 'lat', 'lng');
		if ( $key && ! in_array($key, $key_array) )
			return false;

		// Если работаем с куками
		if ($cookie && filter_input(INPUT_COOKIE, self::COOKIE_NAME)){
            $result = unserialize(filter_input(INPUT_COOKIE, self::COOKIE_NAME));

            // Ищем ключ в куке, если нету ищем дальше
            if( $key ){
            	if( isset($result[$key]) )
            		return $result[$key];
            }
            // Если ключ не указан возвращаем массив из кука
            else {
            	return $result;
            }
		}

		$ip = sprintf('%u', ip2long($this->ip));
		
		rewind($this->fhandleCIDR);
		$rad = floor($this->fSizeCIDR / 2);
		$pos = $rad;
		while(fseek($this->fhandleCIDR, $pos, SEEK_SET) != -1)			
		{
			if($rad)
				$str = fgets($this->fhandleCIDR);
			else
				rewind($this->fhandleCIDR);
			
			$str = fgets($this->fhandleCIDR);
			
			if(!$str)
				return false;
			
			$arRecord = explode("\t", trim($str));

			$rad = floor($rad / 2);
			if(!$rad && ($ip < $arRecord[0] || $ip > $arRecord[1]))
				return false;
			
			if($ip < $arRecord[0]){
				$pos -= $rad;
			}
			elseif($ip > $arRecord[1]){
				$pos += $rad;
			}
			else {
				$result = array('range' => $arRecord[2], 'country' => $arRecord[3]);
				if($arRecord[4] != '-' && $cityResult = $this->getCityByIdx($arRecord[4]))
					$result += $cityResult;

				if( $cookie )
					setcookie(self::COOKIE_NAME, serialize($result), time() + 3600 * 24 * 7, '/');

				if( $key )
            		return isset($result[$key]) ? $result[$key] : false;

				return $result;
			}
		}
		return false;		
	}

	/** GET LIST INFO ***********************************/

	public function getListBy( $value, $get, $by, $detail = false ){
		$vals = array(
			'city'     => 1,
			'region'   => 2,
			'district' => 3,
			'lat'      => 4,
			'lng'      => 5,
			);

		if (! array_key_exists($get, $vals) || ! array_key_exists($by, $vals))
			return false;

		$result = array();
		rewind($this->fhandleCities);
		while(!feof($this->fhandleCities)){
			$str = fgets($this->fhandleCities);
			if (empty($str)) continue;

			if($this->charset != 'cp1251')
				$str = iconv('cp1251', $this->charset, $str);

			$column = explode("\t", trim($str));

			if($value == $column[ $vals[$by] ]){
				if($detail){
					$result[$column[0]] = array(
							'city'     => !empty($column[1]) ? $column[1] : '',
							'region'   => !empty($column[2]) ? $column[2] : '',
							'district' => !empty($column[3]) ? $column[3] : '',
							'lat'      => !empty($column[4]) ? $column[4] : '',
							'lng'      => !empty($column[5]) ? $column[5] : '',
							);
				}
				else {
					$result[$column[0]] = !empty($column[ $vals[$get] ]) ? $column[ $vals[$get] ] : '';
					$result = array_unique($result);
				}
				
			}
		}
		return $result;
	}

	public function getRegionsByDistrict( $district, $detail = false ) {
		
		return $this->getListBy($district, 'region', 'district', $detail);
	}
	public function getCitiesByRegion( $region, $detail = false ) {

		return $this->getListBy($region, 'city', 'region', $detail);
	}
	public function getCitiesByDistrict( $district, $detail = false ) {

		return $this->getListBy($district, 'city', 'district', $detail);
	}

	/**
	 * @todo : Get All Countries
	 * @todo : Get All Cities
	 * @todo : Get All Districts
	 * @todo : Get All Regions
	 */

	/** GET USER IP ***********************************/
	
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