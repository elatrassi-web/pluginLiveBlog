<?php

class Live_News_Public {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	public function enqueue_styles() {
		wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css', array(), '4.7.0', 'all' );
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/live-news-public.css', array(), $this->version, 'all' );
	}

	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/live-news-public.js', array( 'jquery' ), $this->version, true );
		wp_localize_script( $this->plugin_name, 'liveNewsPublic', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
	}

	public function register_shortcodes() {
		add_shortcode( 'live_news', array( $this, 'render_shortcode' ) );
	}

	public function render_shortcode( $atts ) {
		global $post, $wpdb;

		$atts = shortcode_atts( array( 'id' => 0 ), $atts, 'live_news' );
		$post_id = intval( $atts['id'] );
		if ( $post_id === 0 ) {
			$post_id = isset( $post->ID ) ? $post->ID : 0;
		}

		$upload_dir  = wp_upload_dir();
		$feed_url    = $upload_dir['baseurl'] . '/live-news/live-' . $post_id . '.json';
		$results_url = $upload_dir['baseurl'] . '/live-news/results-' . $post_id . '.json';

		$is_amp = false;
		if ( function_exists( 'is_amp_endpoint' ) && is_amp_endpoint() ) { $is_amp = true; }
		if ( function_exists( 'amp_is_request' ) && amp_is_request() ) { $is_amp = true; }

		$theme = get_post_meta( $post_id, '_live_news_theme', true );

		ob_start();
		?>
		<?php if ( ! $is_amp ) : ?>
			<script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>
		<?php endif; ?>

		<div class="live-news-wrapper">

			<?php do_action( 'live_news_pro_public_top', $post_id, $theme, $is_amp, $results_url ); ?>

			<div id="live-news-feed-wrapper" class="live-news-container">
				<div class="live-news-header">
					LE FIL INFO EN DIRECT <?php if ( ! $is_amp ) echo '<span class="live-blinking-dot"></span>'; ?>

					<?php if ( ! $is_amp ) : ?>
						<div style="margin-left: auto;">
							<span id="live-sound-toggle" class="sound-toggle-btn" title="Activer les alertes sonores"><i class="fa fa-bell-slash-o"></i></span>
						</div>
					<?php endif; ?>
				</div>

				<?php if ( ! $is_amp ) : ?>
					<div class="live-filter-bar">
						<span class="filter-label"><i class="fa fa-bolt" style="color:#ffb300; margin-right:5px;"></i> Temps forts uniquement</span>
						<label class="live-toggle-switch">
							<input type="checkbox" id="live-filter-toggle">
							<span class="live-slider"></span>
						</label>
					</div>

					<div id="live-news-floating-btn" class="floating-new-msg-btn">
						<span class="floating-btn-dot"></span> ⬆ Nouveaux messages
					</div>
				<?php endif; ?>

				<div id="live-news-feed" data-json-url="<?php echo esc_url( $feed_url ); ?>" data-post-id="<?php echo esc_attr( $post_id ); ?>">
					<?php if ( $is_amp ) :
						$table_name = $wpdb->prefix . 'live_news_updates';
						$messages = $wpdb->get_results( $wpdb->prepare( "SELECT time, content, importance FROM $table_name WHERE post_id = %d ORDER BY time DESC LIMIT 50", $post_id ) );

						if ( empty( $messages ) ) {
							echo '<p style="padding:20px; text-align:center; color:#999; font-style:italic;">Le direct n\'a pas encore commencé...</p>';
						} else {
							foreach ( $messages as $msg ) {
								$time_parts = explode( ' ', $msg->time );
								$hour_parts = explode( ':', $time_parts[1] );
								$time_str = $hour_parts[0] . 'h' . $hour_parts[1];

								$content = preg_replace( '/<a[^>]+href="(https?:\/\/(www\.)?(twitter|x)\.com[^"]+)"[^>]*>.*?<\/a>/i', '$1', $msg->content );
								$content = preg_replace( '/(?:<p>)?(https?:\/\/(www\.)?(twitter|x)\.com\/[a-zA-Z0-9_]+\/status\/[0-9]+(?:\?[^\s<]+)?)(?:<\/p>)?/i', '<a href="$1" target="_blank" style="display:block; padding:12px; background:#f4f8fb; border:1px solid #b1d2eb; border-radius:8px; color:#1da1f2; text-align:center; text-decoration:none; font-weight:bold;">👉 Voir le Tweet sur X</a>', $content );
								$content = wpautop( $content );

								echo '<div class="live-post live-importance-' . esc_attr( $msg->importance ) . '">';
								echo '<div class="timeline-circle"></div>';
								echo '<div class="live-time-wrapper"><span class="live-time">' . $time_str . '</span></div>';
								echo '<div class="live-content">' . $content . '</div>';
								echo '</div>';
							}
						}
					else : ?>
						<div class="skeleton-post"><div class="skeleton-circle"></div><div class="skeleton-time"></div><div class="skeleton-line"></div></div>
						<div class="skeleton-post"><div class="skeleton-circle"></div><div class="skeleton-time"></div><div class="skeleton-line"></div></div>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<?php
		return ob_get_clean();
	}

	public function disable_redirect_canonical() {
		if ( is_single() ) {
			global $post;
			if ( has_shortcode( $post->post_content, 'live_news' ) ) {
				remove_action( 'template_redirect', 'redirect_canonical' );
			}
		}
	}
}
