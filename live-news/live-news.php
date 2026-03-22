<?php
/**
 * Plugin Name: Live News
 * Plugin URI:  https://example.com/live-news
 * Description: Système de Live Blogging Premium avec Tableau de bord dynamique. (Version Gratuite : Thème Info)
 * Version:     1.0.0
 * Author:      Mohamed El Atrassi
 * Author URI:  https://example.com/
 * Text Domain: live-news
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 */
define( 'LIVE_NEWS_VERSION', '1.0.0' );
define( 'LIVE_NEWS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LIVE_NEWS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-live-news.php';

/**
 * Begins execution of the plugin.
 */
function run_live_news() {

	$plugin = new Live_News();
	$plugin->run();

}
run_live_news();

/**
 * The code that runs during plugin activation.
 */
function activate_live_news() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-live-news-activator.php';
	Live_News_Activator::activate();
}
register_activation_hook( __FILE__, 'activate_live_news' );

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_live_news() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-live-news-deactivator.php';
	Live_News_Deactivator::deactivate();
}
register_deactivation_hook( __FILE__, 'deactivate_live_news' );
