<?php

class Live_News_Pro {

	protected $loader;
	protected $plugin_name;
	protected $version;

	public function __construct() {
		$this->version = LIVE_NEWS_PRO_VERSION;
		$this->plugin_name = 'live-news-pro';
		$this->load_dependencies();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_ajax_hooks();
	}

	private function load_dependencies() {
		// Use the loader from the core plugin
		require_once plugin_dir_path( dirname( __FILE__ ) ) . '../live-news/includes/class-live-news-loader.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-live-news-pro-admin.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-live-news-pro-public.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-live-news-pro-ajax.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-live-news-pro-licensing.php'; // Placeholder for EDD/Freemius

		$this->loader = new Live_News_Loader();
	}

	private function define_admin_hooks() {
		$plugin_admin = new Live_News_Pro_Admin( $this->get_plugin_name(), $this->get_version() );

		// Add themes to create page
		$this->loader->add_action( 'live_news_pro_add_themes', $plugin_admin, 'add_pro_themes' );

		// Add sidebars to editor
		$this->loader->add_action( 'live_news_pro_editor_sidebar', $plugin_admin, 'add_editor_sidebar' );

		// Filter dashboard badges
		$this->loader->add_filter( 'live_news_theme_data', $plugin_admin, 'filter_theme_data', 10, 2 );

		// Enqueue PRO scripts
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
	}

	private function define_public_hooks() {
		$plugin_public = new Live_News_Pro_Public( $this->get_plugin_name(), $this->get_version() );

		// Add results/scores board
		$this->loader->add_action( 'live_news_pro_public_top', $plugin_public, 'render_boards', 10, 4 );

		// Enqueue PRO styles/scripts
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
	}

	private function define_ajax_hooks() {
		$plugin_ajax = new Live_News_Pro_Ajax( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_ajax_live_news_update_results', $plugin_ajax, 'update_results' );
		$this->loader->add_action( 'wp_ajax_live_news_update_sports', $plugin_ajax, 'update_sports' );
		$this->loader->add_action( 'wp_ajax_live_news_get_sports_data', $plugin_ajax, 'get_sports_data' );

		// Hook into core purge action to clean up sports data
		$this->loader->add_action( 'live_news_after_purge_data', $plugin_ajax, 'purge_sports_data' );
	}

	public function run() {
		$this->loader->run();
	}

	public function get_plugin_name() {
		return $this->plugin_name;
	}

	public function get_version() {
		return $this->version;
	}
}
