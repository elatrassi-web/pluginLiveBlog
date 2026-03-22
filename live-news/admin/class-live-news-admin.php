<?php

class Live_News_Admin {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	public function enqueue_styles( $hook_suffix ) {
		if ( strpos( $hook_suffix, 'live-news' ) !== false ) {
			wp_enqueue_style( 'bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css', array(), '5.3.3', 'all' );
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/live-news-admin.css', array(), $this->version, 'all' );
		}
	}

	public function enqueue_scripts( $hook_suffix ) {
		if ( strpos( $hook_suffix, 'live-news' ) !== false ) {
			wp_enqueue_script( 'bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js', array( 'jquery' ), '5.3.3', true );
			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/live-news-admin.js', array( 'jquery' ), $this->version, true );
			wp_localize_script( $this->plugin_name, 'liveNewsAdmin', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
		}
	}

	public function add_admin_body_class( $classes ) {
		$screen = get_current_screen();
		if ( $screen && strpos( $screen->id, 'live-news' ) !== false ) {
			$classes .= ' live-news-admin-page';
		}
		return $classes;
	}

	public function register_cpt() {
		register_post_type( 'live_news_data', array(
			'label'    => 'Live News',
			'public'   => false,
			'show_ui'  => false,
			'supports' => array( 'title' )
		) );
	}

	public function add_plugin_admin_menu() {
		add_menu_page( 'Live News', '🔴 Live News', 'edit_posts', 'live-news-dashboard', array( $this, 'dashboard_router' ), 'dashicons-megaphone', 4 );
		add_submenu_page( 'live-news-dashboard', 'Tous les directs', 'Tableau de bord', 'edit_posts', 'live-news-dashboard', array( $this, 'dashboard_router' ) );
		add_submenu_page( 'live-news-dashboard', 'Créer un Direct', 'Créer un Direct', 'edit_posts', 'live-news-create', array( $this, 'display_create_page' ) );
		add_submenu_page( 'live-news-dashboard', 'Aide & Docs', 'Aide & Docs', 'edit_posts', 'live-news-intro', array( $this, 'display_intro_page' ) );
	}

	public function dashboard_router() {
		$action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : 'list';
		if ( $action === 'edit' && isset( $_GET['post_id'] ) ) {
			$this->display_editor( intval( $_GET['post_id'] ) );
		} else {
			$this->display_dashboard();
		}
	}

	public function display_dashboard() {
		wp_nonce_field( 'live_news_nonce_action', 'live_news_nonce' );
		$lives = get_posts( array(
			'post_type'      => array( 'live_news_data', 'page', 'post' ),
			'meta_key'       => '_live_news_theme',
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'orderby'        => 'date',
			'order'          => 'DESC'
		) );
		include_once plugin_dir_path( __FILE__ ) . 'partials/live-news-admin-dashboard.php';
	}

	public function display_create_page() {
		wp_nonce_field( 'live_news_nonce_action', 'live_news_nonce' );
		include_once plugin_dir_path( __FILE__ ) . 'partials/live-news-admin-create.php';
	}

	public function display_editor( $post_id ) {
		wp_nonce_field( 'live_news_nonce_action', 'live_news_nonce' );
		$selected_theme = get_post_meta( $post_id, '_live_news_theme', true );
		$post_title     = get_the_title( $post_id );
		include_once plugin_dir_path( __FILE__ ) . 'partials/live-news-admin-editor.php';
	}

	public function display_intro_page() {
		include_once plugin_dir_path( __FILE__ ) . 'partials/live-news-admin-intro.php';
	}
}
