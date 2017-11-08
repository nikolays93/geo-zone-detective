<?php

function get_geo_value( $key_data = false ){
  $options = get_option(GEO_OPTION);

  $check_ip = !empty($options['test-ip']) ? $options['test-ip'] : null;
  $geo_target = new \IPGeoBase( $check_ip );

  return $geo_target->getRecord( $key_data );
}

// require_once GEO_PLUG_DIR . 'inc/ipgeobase.php';

  /**
   * @todo: echo '<input type="button" id="clear_geo_cache" class="button" value="Очистить Гео Кэш">';
   * @todo: echo '<input type="button" id="clear_geo_cache" class="button" value="Определить стандартные значения">';
   */
  // echo "<pre>";
  // $options = get_option(GEO_OPTION);

  // $check_ip = !empty($options['test-ip']) ? $options['test-ip'] : null;
  // $geo_target = new \IPGeoBase( $check_ip );

  // $record = $geo_target->getRecord();
  // print_r($record);
  // if( isset($record['region']) )
  //   print_r( $geo_target->getCitiesByRegion( $record['region'] ) );

  // echo "</pre>";


// function get_city(){ return get_geo_value( 'city' ); }
// function get_country(){ return get_geo_value( 'country' ); }
// function get_region(){ return get_geo_value( 'region' ); }
// function get_district(){ return get_geo_value( 'district' ); }
