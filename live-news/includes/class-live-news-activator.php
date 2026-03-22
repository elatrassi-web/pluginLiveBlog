<?php
/**
 * Fired during plugin activation
 */
class Live_News_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

		global $wpdb;

		// Create the `live_news_updates` table.
		$table_name = $wpdb->prefix . 'live_news_updates';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			post_id bigint(20) NOT NULL,
			time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			content text NOT NULL,
			importance varchar(50) DEFAULT 'normal' NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

		// Create upload directory for JSON feeds.
		$upload_dir = wp_upload_dir();
		$live_dir = $upload_dir['basedir'] . '/live-news';
		if ( ! file_exists( $live_dir ) ) {
			wp_mkdir_p( $live_dir );
		}

		// Flush rewrite rules for CPT.
		flush_rewrite_rules();

	}

}
