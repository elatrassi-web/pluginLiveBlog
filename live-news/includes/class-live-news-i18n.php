<?php
/**
 * Define the internationalization functionality
 */

class Live_News_i18n {

	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'live-news',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}

}
