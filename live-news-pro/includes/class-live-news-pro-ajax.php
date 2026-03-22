<?php

class Live_News_Pro_Ajax {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	public function update_results() {
		check_ajax_referer( 'live_news_nonce_action', 'security' );

		$post_id = intval( $_POST['post_id'] );
		$status = isset( $_POST['results_status'] ) ? sanitize_text_field( $_POST['results_status'] ) : 'En direct';
		$candidates = isset( $_POST['candidates'] ) ? $_POST['candidates'] : array();

		$data_to_save = array( 'status' => $status, 'candidates' => $candidates );
		update_option( 'live_news_results_' . $post_id, $data_to_save );

		$dir = wp_upload_dir();
		file_put_contents( $dir['basedir'] . '/live-news/results-' . $post_id . '.json', wp_json_encode( $data_to_save ) );
		wp_send_json_success();
	}

	public function update_sports() {
		check_ajax_referer( 'live_news_nonce_action', 'security' );

		$post_id = intval( $_POST['post_id'] );

		$team1_name  = sanitize_text_field( $_POST['team1_name'] );
		$team1_score = intval( $_POST['team1_score'] );
		$team1_color = sanitize_text_field( $_POST['team1_color'] );
		$team1_logo  = esc_url_raw( $_POST['team1_logo'] );

		$team2_name  = sanitize_text_field( $_POST['team2_name'] );
		$team2_score = intval( $_POST['team2_score'] );
		$team2_color = sanitize_text_field( $_POST['team2_color'] );
		$team2_logo  = esc_url_raw( $_POST['team2_logo'] );

		$timer_status = sanitize_text_field( $_POST['timer_status'] ); // stopped, running
		$timer_value  = sanitize_text_field( $_POST['timer_value'] ); // MM:SS
		$period       = sanitize_text_field( $_POST['period'] ); // 1MT, 2MT, Fin

		$data_to_save = array(
			'team1' => array( 'name' => $team1_name, 'score' => $team1_score, 'color' => $team1_color, 'logo' => $team1_logo ),
			'team2' => array( 'name' => $team2_name, 'score' => $team2_score, 'color' => $team2_color, 'logo' => $team2_logo ),
			'timer' => array( 'status' => $timer_status, 'value' => $timer_value, 'period' => $period, 'last_update' => time() )
		);

		update_option( 'live_news_sports_' . $post_id, $data_to_save );

		$dir = wp_upload_dir();
		file_put_contents( $dir['basedir'] . '/live-news/sports-' . $post_id . '.json', wp_json_encode( $data_to_save ) );
		wp_send_json_success();
	}

	public function get_sports_data() {
		check_ajax_referer( 'live_news_nonce_action', 'security' );

		$post_id = intval( $_POST['post_id'] );
		if ( empty( $post_id ) ) {
			wp_send_json_error();
		}

		$saved_data = get_option( 'live_news_sports_' . $post_id, array() );
		wp_send_json_success( $saved_data );
	}

	public function purge_sports_data( $post_id ) {
		delete_option( 'live_news_sports_' . $post_id );
		$dir = wp_upload_dir();
		@unlink( $dir['basedir'] . '/live-news/sports-' . $post_id . '.json' );
	}

}
