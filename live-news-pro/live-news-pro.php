<?php
/**
 * Plugin Name: Live News Pro
 * Plugin URI:  https://example.com/live-news-pro
 * Description: Extension Pro pour Live News. Ajoute les thèmes Élections, Sondages et Scores Sportifs.
 * Version:     1.0.0
 * Author:      Mohamed El Atrassi
 * Author URI:  https://example.com/
 * Text Domain: live-news-pro
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'LIVE_NEWS_PRO_VERSION', '1.0.0' );

/**
 * Check if the core plugin is active
 */
function live_news_pro_check_dependencies() {
	if ( ! is_plugin_active( 'live-news/live-news.php' ) ) {
		add_action( 'admin_notices', 'live_news_pro_admin_notice' );
	}
}
add_action( 'admin_init', 'live_news_pro_check_dependencies' );

function live_news_pro_admin_notice() {
	?>
	<div class="notice notice-error is-dismissible">
		<p><?php _e( '<strong>Live News Pro</strong> nécessite l\'installation et l\'activation du plugin gratuit <strong>Live News</strong> pour fonctionner.', 'live-news-pro' ); ?></p>
	</div>
	<?php
}

require plugin_dir_path( __FILE__ ) . 'includes/class-live-news-pro.php';

function run_live_news_pro() {
	// Only run if the core is active (or if we are testing/developing and it's present)
	if ( class_exists( 'Live_News' ) ) {
		$plugin = new Live_News_Pro();
		$plugin->run();
	}
}
add_action( 'plugins_loaded', 'run_live_news_pro', 20 ); // Load after core

// Activation hook for PRO
function activate_live_news_pro() {
    // We could check dependencies here and prevent activation, but it's often better to just show an admin notice
}
register_activation_hook( __FILE__, 'activate_live_news_pro' );
