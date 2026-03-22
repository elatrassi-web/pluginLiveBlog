<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 */

class Live_News {

	protected $loader;
	protected $plugin_name;
	protected $version;

	public function __construct() {
		if ( defined( 'LIVE_NEWS_VERSION' ) ) {
			$this->version = LIVE_NEWS_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'live-news';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_ajax_hooks();
	}

	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-live-news-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-live-news-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-live-news-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-live-news-public.php';

		/**
		 * The class handling ajax calls.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-live-news-ajax.php';

		$this->loader = new Live_News_Loader();

	}

	private function set_locale() {
		$plugin_i18n = new Live_News_i18n();
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	private function define_admin_hooks() {
		$plugin_admin = new Live_News_Admin( $this->get_plugin_name(), $this->get_version() );

		// Register CPT
		$this->loader->add_action( 'init', $plugin_admin, 'register_cpt' );

		// Admin menu
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_admin_menu' );

		// Enqueue scripts & styles for admin
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		// Add body class for styling admin pages
		$this->loader->add_filter( 'admin_body_class', $plugin_admin, 'add_admin_body_class' );
	}

	private function define_public_hooks() {
		$plugin_public = new Live_News_Public( $this->get_plugin_name(), $this->get_version() );

		// Enqueue scripts & styles
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

		// Register shortcode
		$this->loader->add_action( 'init', $plugin_public, 'register_shortcodes' );

		// Prevent redirects on live news pages.
		$this->loader->add_action( 'template_redirect', $plugin_public, 'disable_redirect_canonical', 1 );
		$this->loader->add_filter( 'do_redirect_guess_arg_name', '__return_false' );
	}

	private function define_ajax_hooks() {
		$plugin_ajax = new Live_News_Ajax( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'wp_ajax_live_news_create_new', $plugin_ajax, 'create_new_event' );
		$this->loader->add_action( 'wp_ajax_live_news_delete_event', $plugin_ajax, 'delete_event' );
		$this->loader->add_action( 'wp_ajax_live_news_get_bulk_viewers', $plugin_ajax, 'get_bulk_viewers' );

		$this->loader->add_action( 'wp_ajax_nopriv_live_news_ping', $plugin_ajax, 'ping' );
		$this->loader->add_action( 'wp_ajax_live_news_ping', $plugin_ajax, 'ping' );
		$this->loader->add_action( 'wp_ajax_live_news_get_viewers', $plugin_ajax, 'get_viewers' );

		$this->loader->add_action( 'wp_ajax_live_news_send_message', $plugin_ajax, 'send_message' );
		$this->loader->add_action( 'wp_ajax_live_news_delete_message', $plugin_ajax, 'delete_message' );
		$this->loader->add_action( 'wp_ajax_live_news_get_saved_data', $plugin_ajax, 'get_saved_data' );
		$this->loader->add_action( 'wp_ajax_live_news_purge_data', $plugin_ajax, 'purge_data' );
	}

	public function run() {
		$this->loader->run();
	}

	public function get_plugin_name() {
		return $this->plugin_name;
	}

	public function get_loader() {
		return $this->loader;
	}

	public function get_version() {
		return $this->version;
	}
}
