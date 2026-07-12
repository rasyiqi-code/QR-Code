<?php
/**
 * Plugin Name:       QR Code Generator & Secure Validator
 * Plugin URI:        https://github.com/rasyiqi/qr-code-validator
 * Description:       Sistem verifikasi & penerbitan dokumen digital mandiri berbasis QR Code offline, tanda tangan basah Media Library, dan segel kriptografi SHA-256 terenkripsi.
 * Version:           1.0.0
 * Author:            Senior Engineer
 * Author URI:        https://github.com/rasyiqi
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       qr-code-validator
 * Domain Path:       /languages
 * Requires PHP:      7.4
 * Requires at least: 5.6
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
