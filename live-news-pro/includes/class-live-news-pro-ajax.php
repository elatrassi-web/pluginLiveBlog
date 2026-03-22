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
		$show_editor = isset( $_POST['show_editor'] ) ? sanitize_text_field( $_POST['show_editor'] ) === 'true' : true;

		$matches_raw = isset( $_POST['matches'] ) && is_array( $_POST['matches'] ) ? $_POST['matches'] : array();
		$matches_to_save = array();

		foreach ( $matches_raw as $match ) {
			$matches_to_save[] = array(
				'team1' => array(
					'name'  => sanitize_text_field( $match['team1_name'] ),
					'score' => intval( $match['team1_score'] ),
					'color' => sanitize_text_field( $match['team1_color'] ),
					'logo'  => esc_url_raw( $match['team1_logo'] ),
				),
				'team2' => array(
					'name'  => sanitize_text_field( $match['team2_name'] ),
					'score' => intval( $match['team2_score'] ),
					'color' => sanitize_text_field( $match['team2_color'] ),
					'logo'  => esc_url_raw( $match['team2_logo'] ),
				),
				'timer' => array(
					'status'      => sanitize_text_field( $match['timer_status'] ),
					'value'       => sanitize_text_field( $match['timer_value'] ),
					'period'      => sanitize_text_field( $match['period'] ),
					'last_update' => time()
				)
			);
		}

		$data_to_save = array(
			'show_editor' => $show_editor,
			'matches'     => $matches_to_save
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
