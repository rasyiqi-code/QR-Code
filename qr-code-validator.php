<?php
/**
 * Plugin Name: QR Code Generator & Validator
 * Plugin URI:  https://github.com/rasyiqi/qr-code-validator
 * Description: Plugin untuk men-generate QR Code secara lokal dan memvalidasi keaslian dokumen melalui halaman bukti validitas khusus di WordPress.
 * Version:     1.0.0
 * Author:      Senior Engineer
 * Author URI:  https://github.com/rasyiqi
 * License:     MIT
 * Text Domain: qr-code-validator
 *
 * @package QR_Code_Validator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Keluar jika diakses langsung.
}

// Muat loader utama plugin
require_once plugin_dir_path( __FILE__ ) . 'includes/class-qr-code-loader.php';

/**
 * Memulai plugin.
 */
function run_qr_code_validator() {
	return QR_Code_Loader::get_instance();
}

run_qr_code_validator();
