<?php

class Live_News_Pro_Public {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/live-news-pro-public.css', array(), $this->version, 'all' );
	}

	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/live-news-pro-public.js', array( 'jquery', 'live-news' ), $this->version, true );
	}

	public function render_boards( $post_id, $theme, $is_amp, $results_url ) {
		if ( $theme === 'elections' || $theme === 'votes' ) {
			$this->render_votes_board( $post_id, $is_amp, $results_url );
		} elseif ( $theme === 'sports' ) {
			$this->render_sports_board( $post_id, $is_amp );
		}
	}

	private function render_votes_board( $post_id, $is_amp, $results_url ) {
		$saved_results = get_option( 'live_news_results_' . $post_id, array( 'status' => 'En direct', 'candidates' => array() ) );
		$amp_cands     = isset( $saved_results['candidates'] ) ? $saved_results['candidates'] : ( is_array( $saved_results ) ? $saved_results : array() );
		$amp_status    = isset( $saved_results['status'] ) ? $saved_results['status'] : 'En direct';
		$show_results_amp = ( $is_amp && ! empty( $amp_cands ) );
		?>
		<div id="live-news-results-board" data-results-url="<?php echo esc_url( $results_url ); ?>" style="<?php echo $show_results_amp ? 'display:block;' : 'display:none;'; ?>">
			<div class="results-header" id="live-news-results-title">📊 RÉSULTATS DES VOTES <?php echo $show_results_amp ? '(' . esc_html( $amp_status ) . ')' : ''; ?></div>
			<div id="candidates-render-area" class="results-body">
				<?php
				if ( $show_results_amp ) {
					$total_votes = 0;
					foreach ( $amp_cands as $c ) { $total_votes += intval( $c['votes'] ); }
					usort( $amp_cands, function( $a, $b ) { return $b['votes'] - $a['votes']; } );

					foreach ( $amp_cands as $cand ) {
						$percent = $total_votes > 0 ? number_format( ( $cand['votes'] / $total_votes ) * 100, 2 ) : 0;
						$photoHtml = ! empty( $cand['photo'] ) ? '<img src="' . esc_url( $cand['photo'] ) . '" class="candidate-photo">' : '<div class="candidate-photo"></div>';
						echo '<div class="candidate-row">' . $photoHtml . '<div class="candidate-data">';
						echo '<div class="candidate-stats"><span>' . esc_html( $cand['name'] ) . ' <span class="candidate-votes-text">(' . esc_html( $cand['votes'] ) . ' voix)</span></span><span>' . $percent . '%</span></div>';
						echo '<div class="progress-bar-bg"><div class="progress-bar-fill" style="width:' . $percent . '%; background-color:' . esc_attr( $cand['color'] ) . ';"></div></div>';
						echo '</div></div>';
					}
				}
				?>
			</div>
		</div>
		<?php
	}

	private function render_sports_board( $post_id, $is_amp ) {
		$upload_dir = wp_upload_dir();
		$sports_url = $upload_dir['baseurl'] . '/live-news/sports-' . $post_id . '.json';

		$saved_sports = get_option( 'live_news_sports_' . $post_id, array() );
		$show_editor = isset( $saved_sports['show_editor'] ) ? $saved_sports['show_editor'] : true;

		if ( ! $show_editor ) {
			echo '<style>#live-news-feed-wrapper { display: none !important; }</style>';
		}

		$matches = isset( $saved_sports['matches'] ) ? $saved_sports['matches'] : array();

		// Fallback if old data format
		if ( empty( $matches ) && isset( $saved_sports['team1'] ) ) {
			$matches[] = $saved_sports;
		}

		?>
		<div id="live-news-sports-board" data-sports-url="<?php echo esc_url( $sports_url ); ?>" style="<?php echo $is_amp && !empty($matches) ? 'display:block;' : 'display:none;'; ?>">
			<div id="sports-matches-render-area">
				<?php
				if ( $is_amp && ! empty( $matches ) ) {
					foreach ( $matches as $index => $match ) {
						$t1_name  = isset($match['team1']['name']) ? $match['team1']['name'] : '';
						$t1_score = isset($match['team1']['score']) ? $match['team1']['score'] : '0';
						$t1_color = isset($match['team1']['color']) ? $match['team1']['color'] : '#000';

						$t2_name  = isset($match['team2']['name']) ? $match['team2']['name'] : '';
						$t2_score = isset($match['team2']['score']) ? $match['team2']['score'] : '0';
						$t2_color = isset($match['team2']['color']) ? $match['team2']['color'] : '#000';

						$timer_val = isset($match['timer']['value']) ? $match['timer']['value'] : '00:00';
						$period    = isset($match['timer']['period']) ? $match['timer']['period'] : '';

						echo '<div class="sports-match-item mb-3">';
						echo '<div class="sports-header"><div class="sports-period">'.esc_html($period).'</div><div class="sports-timer">'.esc_html($timer_val).'</div></div>';
						echo '<div class="sports-body">';
						echo '<div class="sports-team"><div class="sports-team-color" style="background-color:'.esc_attr($t1_color).';"></div><div class="sports-team-name">'.esc_html($t1_name).'</div></div>';
						echo '<div class="sports-score"><span>'.esc_html($t1_score).'</span><span class="sports-score-divider">-</span><span>'.esc_html($t2_score).'</span></div>';
						echo '<div class="sports-team team-right"><div class="sports-team-name">'.esc_html($t2_name).'</div><div class="sports-team-color" style="background-color:'.esc_attr($t2_color).';"></div></div>';
						echo '</div></div>';
					}
				}
				?>
			</div>
		</div>
		<?php
	}

}
