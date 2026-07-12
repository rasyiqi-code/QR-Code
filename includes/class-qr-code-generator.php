<?php
/**
 * Modul generator QR Code untuk mengintegrasikan pustaka phpQRCode.
 *
 * @package QR_Code_Validator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Keluar jika diakses langsung.
}

use splitbrain\phpQRCode\QRCode;

class QR_Code_Generator {

	/**
	 * Menghasilkan URL validasi untuk suatu UUID.
	 *
	 * @param string $uuid UUID unik dokumen.
	 * @return string URL validasi lengkap.
	 */
	public static function get_validation_url( $uuid ) {
		return add_query_arg( 'qr_verify', $uuid, home_url( '/' ) );
	}

	/**
	 * Membuat markup SVG QR Code secara lokal menggunakan data UUID.
	 *
	 * @param string $uuid    UUID dokumen.
	 * @param string $ecc     Tingkat Error Correction ('L', 'M', 'Q', 'H').
	 * @return string Markup SVG QR Code.
	 */
	public static function generate_svg( $uuid, $ecc = 'L' ) {
		if ( ! class_exists( 'splitbrain\phpQRCode\QRCode' ) ) {
			return '';
		}

		$url = self::get_validation_url( $uuid );

		// Petakan tingkat koreksi error ke opsi splitbrain library
		$option_s = 'qrl'; // default L
		$ecc      = strtoupper( $ecc );
		if ( $ecc === 'M' ) {
			$option_s = 'qrm';
		} elseif ( $ecc === 'Q' ) {
			$option_s = 'qrq';
		} elseif ( $ecc === 'H' ) {
			$option_s = 'qrh';
		}

		try {
			// Menghasilkan SVG menggunakan class splitbrain
			return QRCode::svg( $url, array( 's' => $option_s ) );
		} catch ( Exception $e ) {
			return '<!-- Gagal men-generate QR Code: ' . esc_html( $e->getMessage() ) . ' -->';
		}
	}

	/**
	 * Melakukan download file SVG QR Code secara langsung dari query parameter.
	 */
	public static function download_svg( $uuid, $filename = 'qr-code' ) {
		$svg = self::generate_svg( $uuid, 'H' ); // Kualitas tinggi untuk dicetak/download

		if ( empty( $svg ) || strpos( $svg, '<!--' ) === 0 ) {
			wp_die( esc_html__( 'Gagal men-download QR Code.', 'qr-code-validator' ) );
		}

		header( 'Content-Type: image/svg+xml' );
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $filename ) . '.svg"' );
		header( 'Content-Length: ' . strlen( $svg ) );
		header( 'Cache-Control: no-cache, no-store, must-revalidate' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		echo $svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}
}
