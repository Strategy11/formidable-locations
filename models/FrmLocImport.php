<?php

class FrmLocImport{
	
	private static $countries_total = 271;
	private static $states_total = 3897;// Check this
	private static $us_states_total = 57;
	private static $counties_total = 3097;
	private static $cities_total = 51935;

	/**
	* Import locations, starting with the XML files
	*
	* @since 2.0
	*/
	public static function import_locations(){
		$data_to_import = FrmAppHelper::get_post_param( 'frm_import_files', '', 'sanitize_title' );

		$xml_func_name = 'get_' . $data_to_import . '_xml';
		$file = self::$xml_func_name();
		FrmXMLHelper::import_xml( $file );

		$opts = get_option( 'frm_usloc_options' );
		$remaining = self::remaining_count( $opts, $data_to_import );

		include(dirname(dirname(__FILE__)) . '/views/importing_page.php');
	}

	private static function get_countries_xml(){
		return dirname(dirname(__FILE__)) .'/templates/countries.xml';
	}

	private static function get_countries_states_xml(){
		return dirname(dirname(__FILE__)) .'/templates/countries_states.xml';
	}

	private static function get_states_counties_cities_xml(){
		return dirname(dirname(__FILE__)) .'/templates/states_counties_cities.xml';
	}

	private static function get_states_cities_xml(){
		return dirname(dirname(__FILE__)) .'/templates/states_cities.xml';
	}

	public static function import_locations_csv( $opts ){
		$data_to_import = FrmAppHelper::get_param( 'data_to_import', '', 'post', 'sanitize_title' );

		$opts = self::get_option( 'frm_usloc_options' );

		$csv_func_name = 'import_' . $data_to_import;
		self::$csv_func_name( $opts );

		die();
	}

	/**
	* Import only the countries
	*
	* @since 2.0
	*/
	private static function import_countries( $opts ){
		$countries_total = self::import_countries_csv( $opts );
		echo $countries_total;

		die();
	}

	/**
	* Import the countries and states
	*
	* @since 2.0
	*/
	private static function import_countries_states( $opts ){
		// Import countries
		$countries_total = self::import_countries_csv( $opts );

		// Import States
		if ( $countries_total >= self::$countries_total ) {
			$states_total = self::import_global_states_csv( $opts );
		} else {
			$states_total = 0;
		}

		// Get total imported
		$total_imported = $countries_total + $states_total;
		echo $total_imported;

		die();
	}

	/**
	* Import U.S. States, Counties, and Cities
	*
	* @since 2.0
	*/
	private static function import_states_counties_cities( $opts ){
		$states_total = $counties_total = $cities_total = 0;

		// Import states
		$states_total = self::import_us_states_csv( $opts );

		// Import counties
		if ( $states_total >= self::$us_states_total ) {
			$counties_total = self::import_counties_csv( $opts );
		}

		// Import cities
		if ( $states_total >= self::$us_states_total && $counties_total >= self::$counties_total ) {
			$cities_total = self::import_cities_with_counties_csv( $opts );
		}

		// Get total imported
		$total_imported = $states_total + $counties_total + $cities_total;
		echo $total_imported;

		die();
	}

	/**
	* Import U.S. States and Cities
	*
	* @since 2.0
	*/
	private static function import_states_cities( $opts ){
		$states_total = $cities_total = 0;

		// Import states
		$states_total = self::import_us_states_csv( $opts );

		// Import cities
		if ( $states_total >= self::$us_states_total ) {
			$cities_total = self::import_cities_csv( $opts );
		}

		// Get total imported
		$total_imported = $states_total + $cities_total;
		echo $total_imported;

		die();
	}

	/**
	* Import the countries CSV
	*
	* @since 2.0
	* @return int - number of imported countries
	*/
	private static function import_countries_csv( $opts ){
		self::maybe_initialize_option( 'countries', $opts );

		if ( $opts['countries'] < self::$countries_total ) {

			// Get country form ID
			$form_id = FrmForm::getIdByKey( 'frm_usloc_countries' );
			if ( ! $form_id ) {
				return;
			}

			// Get field IDs
			$country = FrmField::get_id_by_key( 'frm_usloc_country' );
			$code2 = FrmField::get_id_by_key( 'frm_usloc_country_code2' );
			$code3 = FrmField::get_id_by_key( 'frm_usloc_country_code3' );

			// Import countries from CSV
			$opts['countries'] = FrmProXMLHelper::import_csv( dirname(dirname(__FILE__)) . '/locations_data/countries.csv', $form_id, array(
				0 => $country, 1 => $code2, 2 => $code3), 2, $opts['countries']+1
			);

			if ( $opts['countries'] >= self::$countries_total ) {
				$opts['countries_complete'] = 1;
			}
			update_option('frm_usloc_options', $opts);
		}

		return $opts['countries'];
	}

	/**
	* Import the states/provinces CSV
	*
	* @since 2.0
	* @return int - number of imported states/provinces
	*/
	private static function import_global_states_csv( $opts ){
		self::maybe_initialize_option( 'states', $opts );

		if ( isset( $opts['countries_complete'] ) && $opts['states'] < self::$states_total ) {
			// Get state form ID
			$form_id = FrmForm::getIdByKey( 'frm_usloc_states' );
			if ( ! $form_id ) {
				return;
			}

			// Get field IDs
			$country = FrmField::get_id_by_key( 'frm_usloc_country_data' );
			$state = FrmField::get_id_by_key( 'frm_usloc_state' );
			$abr = FrmField::get_id_by_key( 'frm_usloc_state_abr' );
			$code = FrmField::get_id_by_key( 'frm_usloc_country_code2' );

			// Import states from CSV
			$opts['states'] = FrmProXMLHelper::import_csv( dirname(dirname(__FILE__)) . '/locations_data/states.csv', $form_id, array(
				0 => $state, 1 => $abr, 2 => array('field_id' => $country, 'type' => 'data', 'linked' => $code)),
				1, $opts['states']+1
			);

			if ( $opts['states'] >= self::$states_total ) {
				$opts['states_complete'] = 1;
			}
			update_option('frm_usloc_options', $opts);
		}

		return $opts['states'];
	}

	/**
	* Import the U.S. States CSV
	*
	* @since 2.0
	* @return int - number of imported states
	*/
	private static function import_us_states_csv( $opts ){
		self::maybe_initialize_option( 'us_states', $opts );

		if ( $opts['us_states'] < self::$us_states_total ) {
			// Get state form ID
			$form_id = FrmForm::getIdByKey( 'frm_usloc_us_states' );
			if ( ! $form_id ) {
				return;
			}

			// Get field IDs
			$state = FrmField::get_id_by_key( 'frm_usloc_us_state' );
			$abr = FrmField::get_id_by_key( 'frm_usloc_us_state_abr' );

			// Import US states from CSV
			$opts['us_states'] = FrmProXMLHelper::import_csv( dirname(dirname(__FILE__)) . '/locations_data/us_states.csv', $form_id, array(
				0 => $state, 1 => $abr), 1, $opts['us_states']+1
			);

			if ( $opts['us_states'] >= self::$us_states_total ) {
				$opts['us_states_complete'] = 1;
			}
			update_option('frm_usloc_options', $opts);
		}

		return $opts['us_states'];
	}

	/**
	* Import the U.S. Counties CSV
	* Note: Intended to be imported along with U.S. States
	*
	* @since 2.0
	* @return int - number of imported counties
	*/
	private static function import_counties_csv( $opts ){
		self::maybe_initialize_option( 'counties', $opts );

		if ( isset( $opts['us_states_complete'] ) && $opts['counties'] < self::$counties_total ) {
			// Get counties form ID
			$form_id = FrmForm::getIdByKey( 'frm_usloc_counties' );
			if ( ! $form_id ) {
				return;
			}

			// Get field IDs
			$state = FrmField::get_id_by_key( 'frm_usloc_state_data' );
			$linked_state = FrmField::get_id_by_key( 'frm_usloc_us_state_abr' );
			$county = FrmField::get_id_by_key( 'frm_usloc_county' );
			$county_id = FrmField::get_id_by_key( 'frm_usloc_countyid' );

			// Import counties from CSV
			$opts['counties'] = FrmProXMLHelper::import_csv( dirname(dirname(__FILE__)) . '/locations_data/us_counties.csv', $form_id, array(
				0 => array('field_id' => $state, 'type' => 'data', 'linked' => $linked_state), 1 => $county, 2 => $county_id ),
				1, $opts['counties']+1
			);

			if ( $opts['counties'] >= self::$counties_total ) {
				$opts['counties_complete'] = 1;
			}
			update_option('frm_usloc_options', $opts);
		}

		return $opts['counties'];
	}

	/**
	* Import the U.S. Cities CSV
	* Note: intended to be imported with U.S. Counties CSV
	*
	* @since 2.0
	* @return int - number of imported cities
	*/
	private static function import_cities_with_counties_csv( $opts ){
		self::maybe_initialize_option( 'cities', $opts );

		if ( isset( $opts['us_states_complete'] ) && isset( $opts['counties_complete'] ) && $opts['cities'] < self::$cities_total ) {
			// Get city form ID
			$form_id = FrmForm::getIdByKey( 'frm_usloc_cities' );
			if ( ! $form_id ) {
				return;
			}

			// Get field IDs
			$state = FrmField::get_id_by_key( 'frm_usloc_statecounty_data' );
			$linked_state = FrmField::get_id_by_key( 'frm_usloc_us_state_abr' );
			$county = FrmField::get_id_by_key( 'frm_usloc_county_data' );
			$linked_county = FrmField::get_id_by_key( 'frm_usloc_countyid' );
			$city = FrmField::get_id_by_key( 'frm_usloc_city' );

			// Import cities from CSV
			$opts['cities'] = FrmProXMLHelper::import_csv( dirname(dirname(__FILE__)) . '/locations_data/us_cities.csv', $form_id, array(
				1 => $city,
				3 => array('field_id' => $county, 'type' => 'data', 'linked' => $linked_county),
				5 => array('field_id' => $state, 'type' => 'data', 'linked' => $linked_state)), 1, $opts['cities']+1
			);
    
			if ( $opts['cities'] >= self::$cities_total ) {
				$opts['cities_complete'] = 1;
			}
			update_option('frm_usloc_options', $opts);
		}

		return $opts['cities'];
	}

	/**
	* Import the U.S. Cities CSV
	* Note: intended to be imported with States, but not counties
	*
	* @since 2.0
	* @return int - number of imported cities
	*/
	private static function import_cities_csv( $opts ){
		self::maybe_initialize_option( 'cities', $opts );

		if ( isset( $opts['us_states_complete'] ) && $opts['cities'] < self::$cities_total ) {
			// Get city form ID
			$form_id = FrmForm::getIdByKey( 'frm_usloc_cities' );
			if ( ! $form_id ) {
				return;
			}

			// Get field IDs
			$state = FrmField::get_id_by_key( 'frm_usloc_statecounty_data' );
			$city = FrmField::get_id_by_key( 'frm_usloc_city' );
			$abr = FrmField::get_id_by_key( 'frm_usloc_state_abr' );

			// Import cities from CSV
			$opts['cities'] = FrmProXMLHelper::import_csv( dirname(dirname(__FILE__)) . '/locations_data/us_cities.csv', $form_id, array(
				1 => $city,
				5 => array('field_id' => $state, 'type' => 'data', 'linked' => $abr)), 1, $opts['cities']+1
			);

			if ( $opts['cities'] >= self::$cities_total ) {
				$opts['cities_complete'] = 1;
			}
			update_option('frm_usloc_options', $opts);
		}

		return $opts['cities'];
	}

	private static function get_option( $opt_name ){
		$opts = get_option( $opt_name );
		if ( ! is_array( $opts ) ) {
			$opts = array();
		}

		return $opts;
	}

	private static function maybe_initialize_option( $item, &$opts ) {
		if ( ! isset( $opts[ $item ] ) ) {
			$opts[ $item ] = 1;
		}
	}

	private static function remaining_count( $opts, $data_to_import ){
		// Get number of imported entries
		$imported = $total = 0;

		$data_types = array( 'countries' => 0, 'states' => 0, 'us_states' => 0, 'counties' => 0, 'cities' => 0 );
		foreach ( $data_types as $loc => $loc_imported ) {
			if ( isset( $opts[ $loc ] ) ) {
				$data_types[ $loc ] = (int) $opts[ $loc ];
			}
		}

		// Get expected total
		if ( $data_to_import == 'countries' ) {
			$imported = $data_types['countries'];
			$total = self::$countries_total;
		} else if ( $data_to_import == 'countries_states' ) {
			$imported = $data_types['countries'] + $data_types['states'];
			$total = self::$countries_total + self::$states_total;
		} else if ( $data_to_import == 'states_counties_cities' ) {
			$imported = $data_types['us_states'] + $data_types['counties'] + $data_types['cities'];
			$total = self::$us_states_total + self::$counties_total + self::$cities_total;
		} else if ( $data_to_import == 'states_cities' ) {
			$imported = $data_types['us_states'] + $data_types['cities'];
			$total = self::$us_states_total + self::$cities_total;
		}

		return $total - $imported;
	}
}