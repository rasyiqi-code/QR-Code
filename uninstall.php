<?php
/**
 * Logika pembersihan saat plugin di-uninstall dari WordPress.
 *
 * @package QR_Code_Validator
 */

// Keluar jika proses uninstall tidak dipicu oleh WordPress Core.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Menghapus tabel database kustom untuk menjaga kebersihan database pengguna
global $wpdb;
$table_name = $wpdb->prefix . 'qr_codes';
$wpdb->query( "DROP TABLE IF EXISTS $table_name" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
