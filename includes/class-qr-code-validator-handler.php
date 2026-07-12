<?php
/**
 * Handler frontend untuk memproses scan QR Code dan menampilkan bukti validitas.
 *
 * @package QR_Code_Validator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Keluar jika diakses langsung.
}

class QR_Code_Validator_Handler {

	/**
	 * Instance tunggal kelas.
	 *
	 * @var QR_Code_Validator_Handler
	 */
	private static $instance = null;

	/**
	 * Mendapatkan instance kelas.
	 *
	 * @return QR_Code_Validator_Handler
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Konstruktor.
	 */
	private function __construct() {
		add_action( 'template_redirect', array( $this, 'handle_validation_request' ) );
	}

	/**
	 * Memeriksa dan memproses permintaan verifikasi QR Code.
	 */
	public function handle_validation_request() {
		if ( ! isset( $_GET['qr_verify'] ) ) {
			return;
		}

		$uuid = sanitize_text_field( wp_unslash( $_GET['qr_verify'] ) );

		if ( empty( $uuid ) ) {
			return;
		}

		// Cari data QR Code berdasarkan UUID
		$db      = QR_Code_DB::get_instance();
		$qr_data = $db->get_qr_code_by_uuid( $uuid );

		// Render Halaman Bukti Validitas Premium
		$this->render_validity_page( $qr_data, $uuid );
		exit;
	}

	/**
	 * Merender tampilan HTML landing page bukti validasi.
	 *
	 * @param array|null $data Data QR Code dari database.
	 * @param string     $uuid UUID dokumen.
	 */
	private function render_validity_page( $data, $uuid ) {
		// Mengambil metadata jika ada
		$metadata = array();
		if ( ! empty( $data['metadata'] ) ) {
			$metadata = maybe_unserialize( $data['metadata'] );
			if ( ! is_array( $metadata ) ) {
				$metadata = json_decode( $data['metadata'], true );
			}
		}

		$site_name = get_bloginfo( 'name' );
		$status    = $data ? strtolower( $data['status'] ) : 'not_found';

		// Tentukan status kelas & pesan
		$status_class = 'status-invalid';
		$status_label = esc_html__( 'TIDAK VALID / TIDAK DITEMUKAN', 'qr-code-validator' );
		$status_icon  = '✕';

		if ( $data ) {
			if ( $status === 'valid' ) {
				$status_class = 'status-valid';
				$status_label = esc_html__( 'VALID', 'qr-code-validator' );
				$status_icon  = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="28" height="28" fill="currentColor" style="display: block;"><path d="M23 12l-2.44-2.78.34-3.68-3.61-.82-1.89-3.18L12 3 8.6 1.54 6.71 4.72l-3.61.81.34 3.68L1 12l2.44 2.78-.34 3.69 3.61.82 1.89 3.18L12 21l3.4 1.46 1.89-3.18 3.61-.82-.34-3.68L23 12zm-13 5l-4-4 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" /></svg>';
			} elseif ( $status === 'expired' ) {
				$status_class = 'status-expired';
				$status_label = esc_html__( 'MASA BERLAKU KADALUARSA', 'qr-code-validator' );
				$status_icon  = '⚠';
			} elseif ( $status === 'revoked' ) {
				$status_class = 'status-revoked';
				$status_label = esc_html__( 'DOKUMEN DICABUT / TIDAK BERLAKU', 'qr-code-validator' );
				$status_icon  = '✕';
			}
		}

		// Menyiapkan aset CSS URL
		$css_url = QRCV_URL . 'assets/css/validator.css';

		// Output halaman HTML mandiri
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>">
			<meta name="viewport" content="width=device-width, initial-scale=initial-scale=1.0">
			<title><?php esc_html_e( 'Verifikasi QR Code Dokumen', 'qr-code-validator' ); ?> | <?php echo esc_html( $site_name ); ?></title>
			<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
			<link rel="stylesheet" href="<?php echo esc_url( $css_url ); ?>" type="text/css" media="all">
			<?php wp_head(); ?>
		</head>
		<body class="qrcv-validation-body">
			<!-- SVG Definitions for Hologram Gradient -->
			<svg width="0" height="0" style="position: absolute; width: 0; height: 0; overflow: hidden;">
				<defs>
					<linearGradient id="qrcv-holo-grad" x1="0%" y1="0%" x2="100%" y2="100%">
						<stop offset="0%" stop-color="#b91c1c" />
						<stop offset="50%" stop-color="#d4af37" />
						<stop offset="100%" stop-color="#0f172a" />
					</linearGradient>
				</defs>
			</svg>
			<div class="qrcv-page-container">
				<header class="qrcv-header">
					<div class="qrcv-logo">
						<span class="qrcv-logo-icon">🔒</span>
						<span class="qrcv-logo-text"><?php echo esc_html( $site_name ); ?></span>
					</div>
					<div class="qrcv-system-tag">E-Document Validation System</div>
				</header>

				<main class="qrcv-card">
					<?php if ( $data ) : ?>
						<div class="qrcv-layout-container">
							<!-- Kolom Kiri: Status & Segel Hologram -->
							<div class="qrcv-layout-sidebar">
								<!-- Visual Status Lencana -->
								<div class="qrcv-status-badge <?php echo esc_attr( $status_class ); ?>">
									<div class="status-badge-glow"></div>
									<div class="status-badge-inner">
										<span class="status-icon"><?php echo $status_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
									</div>
								</div>

								<h1 class="qrcv-status-title <?php echo esc_attr( $status_class . '-text' ); ?>">
									<?php echo esc_html( $status_label ); ?>
								</h1>

								<?php
								$file_url = '';
								if ( ! empty( $metadata ) ) {
									foreach ( $metadata as $meta ) {
										$key_lower = strtolower( $meta['key'] );
										if ( strpos( $key_lower, 'file' ) !== false || strpos( $key_lower, 'url' ) !== false || strpos( $key_lower, 'dokumen' ) !== false || strpos( $key_lower, 'link' ) !== false || strpos( $key_lower, 'ttd' ) !== false ) {
											if ( filter_var( $meta['value'], FILTER_VALIDATE_URL ) ) {
												$file_url = $meta['value'];
												break;
											}
										}
									}
								}
								?>

								<!-- Representasi Berkas Dokumen & Tanda Tangan Terverifikasi -->
								<div class="qrcv-signature-file-vault">
									<div class="qrcv-file-preview-card">
										<div class="qrcv-file-header">
											<span class="qrcv-file-badge">🔒 SECURE ARCHIVE</span>
										</div>
										<div class="qrcv-file-icon-wrapper">
											<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="qrcv-file-icon">
												<path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z" fill="currentColor"/>
											</svg>
										</div>
											<?php if ( ! empty( $file_url ) ) : 
												$ext = pathinfo( strtok( $file_url, '?' ), PATHINFO_EXTENSION );
												$img_exts = array( 'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp' );
												if ( in_array( strtolower( $ext ), $img_exts, true ) ) : ?>
													<div class="qrcv-file-signature-seal">
														<img src="<?php echo esc_url( $file_url ); ?>" alt="Tanda Tangan Terverifikasi" class="qrcv-sig-image">
													</div>
												<?php endif; ?>
											<?php endif; ?>
									</div>
								</div>

								<div class="qrcv-actions">
									<?php if ( ! empty( $file_url ) ) : ?>
										<a href="<?php echo esc_url( $file_url ); ?>" target="_blank" class="btn btn-secondary qrcv-btn-file" style="margin-bottom: 10px; width: 100%; justify-content: center;">
											📂 Buka Berkas Dokumen
										</a>
									<?php endif; ?>
									<button onclick="window.print()" class="btn btn-primary" style="width: 100%; justify-content: center;">
										<span class="btn-icon">🖨️</span> Cetak Bukti Validitas
									</button>
								</div>
							</div>

							<!-- Kolom Kanan: Detail Dokumen -->
							<div class="qrcv-layout-main">
								<div class="qrcv-details-section">
									<h3 class="section-title">Detail Dokumen</h3>
									<div class="detail-row">
										<div class="detail-label">Nama/Judul Dokumen</div>
										<div class="detail-value text-highlight"><?php echo esc_html( $data['title'] ); ?></div>
									</div>
									<div class="detail-row">
										<div class="detail-label">ID Token Validasi</div>
										<div class="detail-value code-font"><?php echo esc_html( $data['uuid'] ); ?></div>
									</div>
									<div class="detail-row">
										<div class="detail-label">Tanggal Rilis</div>
										<div class="detail-value"><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' H:i', strtotime( $data['created_at'] ) ) ); ?></div>
									</div>
									<div class="detail-row">
										<div class="detail-label">Keterangan / Deskripsi</div>
										<div class="detail-value"><?php echo wp_kses_post( nl2br( $data['description'] ) ); ?></div>
									</div>
								</div>

								<?php if ( ! empty( $metadata ) ) : ?>
									<div class="qrcv-details-section">
										<h3 class="section-title">Informasi Tambahan</h3>
										<?php foreach ( $metadata as $meta ) : ?>
											<?php if ( empty( $meta['key'] ) || empty( $meta['value'] ) ) continue; ?>
											<div class="detail-row">
												<div class="detail-label"><?php echo esc_html( $meta['key'] ); ?></div>
												<div class="detail-value"><?php echo esc_html( $meta['value'] ); ?></div>
											</div>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>

								<p class="qrcv-details-subtitle">Informasi keaslian dokumen resmi telah terverifikasi oleh sistem.</p>
							</div>
						</div>
					<?php else : ?>
						<!-- Visual Status Lencana untuk Error -->
						<div class="qrcv-status-badge <?php echo esc_attr( $status_class ); ?>">
							<div class="status-badge-glow"></div>
							<div class="status-badge-inner">
								<span class="status-icon"><?php echo $status_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							</div>
						</div>

						<h1 class="qrcv-status-title <?php echo esc_attr( $status_class . '-text' ); ?>">
							<?php echo esc_html( $status_label ); ?>
						</h1>

						<div class="qrcv-error-box">
							<p><strong>Peringatan!</strong> QR Code ini tidak terdaftar dalam database kami atau tautan validasi tidak sah.</p>
							<p>Pastikan Anda melakukan pemindaian pada QR Code resmi yang dikeluarkan oleh <strong><?php echo esc_html( $site_name ); ?></strong>.</p>
						</div>
						<div class="qrcv-actions">
							<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn btn-secondary">Kembali ke Beranda</a>
						</div>
					<?php endif; ?>
				</main>

				<footer class="qrcv-footer">
					<p>&copy; <?php echo esc_html( date( 'Y' ) ); ?> <?php echo esc_html( $site_name ); ?>. Hak Cipta Dilindungi.</p>
				</footer>
			</div>
			<?php wp_footer(); ?>
		</body>
		</html>
		<?php
	}
}
