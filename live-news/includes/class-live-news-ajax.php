<?php

class Live_News_Ajax {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	public function create_new_event() {
		check_ajax_referer( 'live_news_nonce_action', 'security' );

		$title = sanitize_text_field( $_POST['title'] );
		$theme = sanitize_text_field( $_POST['theme'] );

		$post_id = wp_insert_post( array(
			'post_title'  => $title,
			'post_status' => 'publish',
			'post_type'   => 'live_news_data'
		) );

		if ( ! is_wp_error( $post_id ) ) {
			update_post_meta( $post_id, '_live_news_theme', $theme );
			update_post_meta( $post_id, 'live_news_total_views', 0 );
			wp_send_json_success( array( 'redirect' => admin_url( 'admin.php?page=live-news-dashboard&action=edit&post_id=' . $post_id ) ) );
		} else {
			wp_send_json_error();
		}
	}

	public function delete_event() {
		check_ajax_referer( 'live_news_nonce_action', 'security' );

		$post_id = intval( $_POST['post_id'] );
		if ( $post_id > 0 ) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'live_news_updates';

			$wpdb->delete( $table_name, array( 'post_id' => $post_id ) );
			delete_transient( 'live_news_viewers_' . $post_id );
			delete_option( 'live_news_results_' . $post_id );
			wp_delete_post( $post_id, true );

			$dir = wp_upload_dir();
			@unlink( $dir['basedir'] . '/live-news/live-' . $post_id . '.json' );
			@unlink( $dir['basedir'] . '/live-news/results-' . $post_id . '.json' );

			wp_send_json_success();
		}
		wp_send_json_error();
	}

	public function get_bulk_viewers() {
		check_ajax_referer( 'live_news_nonce_action', 'security' );

		$post_ids = isset( $_POST['post_ids'] ) ? array_map( 'intval', $_POST['post_ids'] ) : array();
		$results = array();

		foreach ( $post_ids as $post_id ) {
			if ( $post_id > 0 ) {
				$total = (int) get_post_meta( $post_id, 'live_news_total_views', true );
				$viewers = get_transient( 'live_news_viewers_' . $post_id );
				$count = 0;
				if ( is_array( $viewers ) ) {
					$now = time();
					foreach ( $viewers as $id => $time ) {
						if ( $now - $time > 45 ) {
							unset( $viewers[ $id ] );
						} else {
							$count++;
						}
					}
				}
				$results[ $post_id ] = array( 'count' => $count, 'total' => $total );
			}
		}
		wp_send_json_success( $results );
	}

	public function ping() {
		$post_id = intval( $_POST['post_id'] );
		$vid = isset( $_POST['vid'] ) ? sanitize_text_field( $_POST['vid'] ) : '';

		if ( $post_id > 0 && ! empty( $vid ) ) {
			$transient_key = 'live_news_viewers_' . $post_id;
			$viewers = get_transient( $transient_key );
			if ( ! is_array( $viewers ) ) {
				$viewers = array();
			}

			if ( ! isset( $viewers[ $vid ] ) ) {
				$total_views = (int) get_post_meta( $post_id, 'live_news_total_views', true );
				update_post_meta( $post_id, 'live_news_total_views', $total_views + 1 );
			}
			$now = time();
			$viewers[ $vid ] = $now;
			foreach ( $viewers as $id => $timestamp ) {
				if ( $now - $timestamp > 45 ) {
					unset( $viewers[ $id ] );
				}
			}
			set_transient( $transient_key, $viewers, 60 );
		}
		wp_send_json_success();
	}

	public function get_viewers() {
		check_ajax_referer( 'live_news_nonce_action', 'security' );

		$post_id = intval( $_POST['post_id'] );
		$count = 0;
		$total = 0;
		if ( $post_id > 0 ) {
			$total = (int) get_post_meta( $post_id, 'live_news_total_views', true );
			$viewers = get_transient( 'live_news_viewers_' . $post_id );
			if ( is_array( $viewers ) ) {
				$now = time();
				foreach ( $viewers as $id => $time ) {
					if ( $now - $time > 45 ) {
						unset( $viewers[ $id ] );
					}
				}
				$count = count( $viewers );
			}
		}
		wp_send_json_success( array( 'count' => $count, 'total' => $total ) );
	}

	public function purge_data() {
		check_ajax_referer( 'live_news_nonce_action', 'security' );

		global $wpdb;
		$table_name = $wpdb->prefix . 'live_news_updates';
		$post_id = intval( $_POST['post_id'] );

		if ( $post_id > 0 ) {
			$wpdb->delete( $table_name, array( 'post_id' => $post_id ) );
			update_post_meta( $post_id, 'live_news_total_views', 0 );
			delete_transient( 'live_news_viewers_' . $post_id );

			$empty_results = array( 'status' => 'En direct', 'candidates' => array() );
			update_option( 'live_news_results_' . $post_id, $empty_results );

			self::generate_json( $post_id );
			$dir = wp_upload_dir();
			file_put_contents( $dir['basedir'] . '/live-news/results-' . $post_id . '.json', wp_json_encode( $empty_results ) );

			// Allow Pro version to also clean up its own data
			do_action( 'live_news_after_purge_data', $post_id );

			wp_send_json_success();
		}
		wp_send_json_error();
	}

	public function send_message() {
		check_ajax_referer( 'live_news_nonce_action', 'security' );

		global $wpdb;
		$table_name = $wpdb->prefix . 'live_news_updates';

		$post_id = intval( $_POST['post_id'] );
		$edit_id = intval( $_POST['edit_id'] );
		$content = wp_kses_post( wp_unslash( $_POST['content'] ) );
		$importance = sanitize_text_field( $_POST['importance'] );
		$custom_time = isset( $_POST['custom_time'] ) ? sanitize_text_field( $_POST['custom_time'] ) : '';

		if ( empty( $post_id ) || empty( $content ) ) {
			wp_send_json_error( 'Données manquantes.' );
		}
		$gmt_plus_one_timestamp = time() + 3600;

		if ( $edit_id > 0 ) {
			$update_data = array( 'content' => $content, 'importance' => $importance );
			if ( ! empty( $custom_time ) && preg_match( '/^\d{2}:\d{2}$/', $custom_time ) ) {
				$existing_time = $wpdb->get_var( $wpdb->prepare( "SELECT time FROM $table_name WHERE id = %d", $edit_id ) );
				$date_part = $existing_time ? substr( $existing_time, 0, 10 ) : gmdate( 'Y-m-d', $gmt_plus_one_timestamp );
				$update_data['time'] = $date_part . ' ' . $custom_time . ':00';
			}
			$wpdb->update( $table_name, $update_data, array( 'id' => $edit_id ) );
		} else {
			$time_to_save = gmdate( 'Y-m-d H:i:s', $gmt_plus_one_timestamp );
			if ( ! empty( $custom_time ) && preg_match( '/^\d{2}:\d{2}$/', $custom_time ) ) {
				$time_to_save = gmdate( 'Y-m-d', $gmt_plus_one_timestamp ) . ' ' . $custom_time . ':00';
			}
			$wpdb->insert( $table_name, array( 'post_id' => $post_id, 'time' => $time_to_save, 'content' => $content, 'importance' => $importance ) );
		}
		self::generate_json( $post_id );
		wp_send_json_success();
	}

	public function delete_message() {
		check_ajax_referer( 'live_news_nonce_action', 'security' );

		global $wpdb;
		$table_name = $wpdb->prefix . 'live_news_updates';
		$msg_id = intval( $_POST['msg_id'] );
		$post_id = intval( $_POST['post_id'] );

		$wpdb->delete( $table_name, array( 'id' => $msg_id ) );
		self::generate_json( $post_id );
		wp_send_json_success();
	}

	public function get_saved_data() {
		check_ajax_referer( 'live_news_nonce_action', 'security' );

		$post_id = intval( $_POST['post_id'] );
		if ( empty( $post_id ) ) {
			wp_send_json_error();
		}

		$saved_data = get_option( 'live_news_results_' . $post_id, array( 'status' => 'En direct', 'candidates' => array() ) );
		if ( ! isset( $saved_data['status'] ) ) {
			$saved_data = array( 'status' => 'En direct', 'candidates' => $saved_data );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'live_news_updates';
		$history = $wpdb->get_results( $wpdb->prepare( "SELECT id, time, content, importance FROM $table_name WHERE post_id = %d ORDER BY time DESC LIMIT 50", $post_id ) );

		wp_send_json_success( array( 'results_data' => $saved_data, 'history' => $history ) );
	}

	public static function generate_json( $post_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'live_news_updates';
		$messages = $wpdb->get_results( $wpdb->prepare( "SELECT id, time, content, importance FROM $table_name WHERE post_id = %d ORDER BY time DESC LIMIT 50", $post_id ) );

		foreach ( $messages as $msg ) {
			$content = $msg->content;
			$content = preg_replace( '/<a[^>]+href="(https?:\/\/(www\.)?(twitter|x)\.com[^"]+)"[^>]*>.*?<\/a>/i', '$1', $content );
			$content = str_replace( 'https://x.com/', 'https://twitter.com/', $content );
			$content = preg_replace( '/(?:<p>)?(https?:\/\/(www\.)?twitter\.com\/[a-zA-Z0-9_]+\/status\/[0-9]+(?:\?[^\s<]+)?)(?:<\/p>)?/i', '<blockquote class="twitter-tweet"><div class="tweet-loading">⏳ Chargement du tweet en cours...</div><a href="$1"></a></blockquote>', $content );
			$content = wpautop( $content );
			$msg->content = $content;
		}

		$dir = wp_upload_dir();
		$json_data = wp_json_encode( $messages );
		if ( ! $json_data ) {
			$json_data = '[]';
		}
		file_put_contents( $dir['basedir'] . '/live-news/live-' . $post_id . '.json', $json_data );
	}

}
