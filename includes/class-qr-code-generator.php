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

	/**
	 * Membuat stream gambar PNG QR Code secara lokal menggunakan data UUID.
	 *
	 * @param string $uuid        UUID dokumen.
	 * @param string $ecc         Tingkat Error Correction ('L', 'M', 'Q', 'H').
	 * @param int    $pixel_size  Ukuran piksel tiap modul.
	 * @param int    $outer_frame Margin/Quiet zone keliling.
	 * @return string Data biner PNG.
	 */
	public static function generate_png( $uuid, $ecc = 'L', $pixel_size = 10, $outer_frame = 4 ) {
		if ( ! class_exists( 'splitbrain\phpQRCode\QRCode' ) ) {
			return '';
		}

		$url = self::get_validation_url( $uuid );

		$option_s = 'qrl';
		$ecc      = strtoupper( $ecc );
		if ( $ecc === 'M' ) {
			$option_s = 'qrm';
		} elseif ( $ecc === 'Q' ) {
			$option_s = 'qrq';
		} elseif ( $ecc === 'H' ) {
			$option_s = 'qrh';
		}

		try {
			$qr_helper = new QR_Code_Generator_Helper( $url, array( 's' => $option_s ) );
			$code      = $qr_helper->get_matrix();

			$matrix = $code['b'];
			$width  = $code['s'][0];
			$height = $code['s'][1];

			$img_w = ( $width + 2 * $outer_frame ) * $pixel_size;
			$img_h = ( $height + 2 * $outer_frame ) * $pixel_size;

			if ( ! function_exists( 'imagecreatetruecolor' ) ) {
				return '';
			}

			$img   = imagecreatetruecolor( $img_w, $img_h );
			$white = imagecolorallocate( $img, 255, 255, 255 );
			$black = imagecolorallocate( $img, 0, 0, 0 );

			imagefill( $img, 0, 0, $white );

			foreach ( $matrix as $y => $row ) {
				foreach ( $row as $x => $val ) {
					if ( $val ) {
						$x1 = ( $x + $outer_frame ) * $pixel_size;
						$y1 = ( $y + $outer_frame ) * $pixel_size;
						$x2 = $x1 + $pixel_size - 1;
						$y2 = $y1 + $pixel_size - 1;
						imagefilledrectangle( $img, $x1, $y1, $x2, $y2, $black );
					}
				}
			}

			ob_start();
			imagepng( $img );
			$png_data = ob_get_clean();

			imagedestroy( $img );

			return $png_data;
		} catch ( Exception $e ) {
			return '';
		}
	}

	/**
	 * Melakukan download file PNG QR Code secara langsung dari query parameter.
	 */
	public static function download_png( $uuid, $filename = 'qr-code' ) {
		$png = self::generate_png( $uuid, 'H' ); // Kualitas tinggi untuk dicetak

		if ( empty( $png ) ) {
			wp_die( esc_html__( 'Gagal men-download QR Code (PNG).', 'qr-code-validator' ) );
		}

		header( 'Content-Type: image/png' );
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $filename ) . '.png"' );
		header( 'Content-Length: ' . strlen( $png ) );
		header( 'Cache-Control: no-cache, no-store, must-revalidate' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		echo $png; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}
}

/**
 * Subclass pembantu untuk mengekstrak modul matriks QR Code dari splitbrain library.
 */
class QR_Code_Generator_Helper extends splitbrain\phpQRCode\QRCode {
	public function get_matrix() {
		return $this->dispatch_encode( $this->data, $this->options );
	}
}
