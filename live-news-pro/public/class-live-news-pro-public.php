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

		// Fallback for AMP if needed
		$saved_sports = get_option( 'live_news_sports_' . $post_id, array() );
		$t1_name  = isset($saved_sports['team1']['name']) ? $saved_sports['team1']['name'] : 'Équipe 1';
		$t1_score = isset($saved_sports['team1']['score']) ? $saved_sports['team1']['score'] : '0';
		$t1_color = isset($saved_sports['team1']['color']) ? $saved_sports['team1']['color'] : '#000';

		$t2_name  = isset($saved_sports['team2']['name']) ? $saved_sports['team2']['name'] : 'Équipe 2';
		$t2_score = isset($saved_sports['team2']['score']) ? $saved_sports['team2']['score'] : '0';
		$t2_color = isset($saved_sports['team2']['color']) ? $saved_sports['team2']['color'] : '#000';

		$timer_val = isset($saved_sports['timer']['value']) ? $saved_sports['timer']['value'] : '00:00';
		$period    = isset($saved_sports['timer']['period']) ? $saved_sports['timer']['period'] : 'Avant-match';
		?>
		<div id="live-news-sports-board" data-sports-url="<?php echo esc_url( $sports_url ); ?>" style="<?php echo $is_amp ? 'display:block;' : 'display:none;'; ?>">
			<div class="sports-header">
				<div class="sports-period" id="sports-board-period"><?php echo esc_html($period); ?></div>
				<div class="sports-timer" id="sports-board-timer"><?php echo esc_html($timer_val); ?></div>
			</div>
			<div class="sports-body">
				<div class="sports-team">
					<div class="sports-team-color" id="sports-board-t1-color" style="background-color:<?php echo esc_attr($t1_color); ?>;"></div>
					<div class="sports-team-name" id="sports-board-t1-name"><?php echo esc_html($t1_name); ?></div>
				</div>
				<div class="sports-score">
					<span id="sports-board-t1-score"><?php echo esc_html($t1_score); ?></span>
					<span class="sports-score-divider">-</span>
					<span id="sports-board-t2-score"><?php echo esc_html($t2_score); ?></span>
				</div>
				<div class="sports-team team-right">
					<div class="sports-team-name" id="sports-board-t2-name"><?php echo esc_html($t2_name); ?></div>
					<div class="sports-team-color" id="sports-board-t2-color" style="background-color:<?php echo esc_attr($t2_color); ?>;"></div>
				</div>
			</div>
		</div>
		<?php
	}

}
