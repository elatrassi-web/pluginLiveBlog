<?php

class Live_News_Pro_Admin {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	public function enqueue_scripts( $hook_suffix ) {
		if ( strpos( $hook_suffix, 'live-news' ) !== false ) {
			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/live-news-pro-admin.js', array( 'jquery', 'live-news' ), $this->version, true );
			wp_localize_script( $this->plugin_name, 'liveNewsProAdmin', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/live-news-pro-admin.css', array(), $this->version, 'all' );
		}
	}

	public function filter_theme_data( $data, $theme ) {
		if ( $theme == 'elections' ) { $data['badge'] = 'bg-primary'; $data['name'] = '🗳️ Info+Votes'; }
		if ( $theme == 'votes' ) { $data['badge'] = 'bg-warning text-dark'; $data['name'] = '📊 Sondage'; }
		if ( $theme == 'sports' ) { $data['badge'] = 'bg-success'; $data['name'] = '⚽ Sportif'; }
		return $data;
	}

	public function add_pro_themes() {
		?>
		<div class="col-md-6 col-lg-3">
			<div class="card h-100 border-0 shadow-sm theme-card" data-theme="elections">
				<div class="card-body text-center p-4">
					<span style="font-size: 50px;">🗳️</span>
					<h4 class="fw-bold mt-3">Info + Votes</h4>
					<p class="text-muted small mb-4">Le flux d'actualité combiné avec un tableau de scores interactif pour les candidats.</p>
					<button class="btn btn-outline-primary w-100 fw-bold stretched-link">Générer ce direct</button>
				</div>
			</div>
		</div>

		<div class="col-md-6 col-lg-3">
			<div class="card h-100 border-0 shadow-sm theme-card" data-theme="votes">
				<div class="card-body text-center p-4">
					<span style="font-size: 50px;">📊</span>
					<h4 class="fw-bold mt-3">Sondage</h4>
					<p class="text-muted small mb-4">Affichage uniquement du tableau des résultats pour une soirée électorale pure.</p>
					<button class="btn btn-outline-primary w-100 fw-bold stretched-link">Générer ce direct</button>
				</div>
			</div>
		</div>

		<div class="col-md-6 col-lg-3">
			<div class="card h-100 border-0 shadow-sm theme-card" data-theme="sports">
				<div class="card-body text-center p-4">
					<span style="font-size: 50px;">⚽</span>
					<h4 class="fw-bold mt-3">Score Sportif</h4>
					<p class="text-muted small mb-4">Chronomètre en direct, affichage des équipes et alertes buts/cartons.</p>
					<button class="btn btn-outline-primary w-100 fw-bold stretched-link">Générer ce direct</button>
				</div>
			</div>
		</div>
		<?php
	}

	public function add_editor_sidebar( $selected_theme ) {
		if ( $selected_theme === 'elections' || $selected_theme === 'votes' ) {
			include plugin_dir_path( __FILE__ ) . 'partials/live-news-pro-admin-votes.php';
		} elseif ( $selected_theme === 'sports' ) {
			include plugin_dir_path( __FILE__ ) . 'partials/live-news-pro-admin-sports.php';
		}
	}
}
