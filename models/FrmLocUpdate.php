<?php

class FrmLocUpdate extends FrmAddon {

	/**
	 * @param string
	 */
	public $plugin_file;

	/**
	 * @param string
	 */
	public $plugin_name = 'Locations';

	/**
	 * @param string
	 */
	public $version = '2.02';

	public function __construct() {
		$this->plugin_file = dirname( dirname( __FILE__ ) ) . '/us_locations.php';
		parent::__construct();
	}

	/**
	 * @return void
	 */
	public static function load_hooks() {
		add_filter( 'frm_include_addon_page', '__return_true' );
		new FrmLocUpdate();
	}

}
