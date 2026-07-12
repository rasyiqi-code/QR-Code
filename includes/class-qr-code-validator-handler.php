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
				$status_label = esc_html__( 'DOKUMEN VALID / ASLI', 'qr-code-validator' );
				$status_icon  = '✓';
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
						<stop offset="0%" stop-color="#00f2fe" />
						<stop offset="25%" stop-color="#a855f7" />
						<stop offset="50%" stop-color="#ff0844" />
						<stop offset="75%" stop-color="#3b82f6" />
						<stop offset="100%" stop-color="#00f2fe" />
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
										<span class="status-icon"><?php echo esc_html( $status_icon ); ?></span>
									</div>
								</div>

								<h1 class="qrcv-status-title <?php echo esc_attr( $status_class . '-text' ); ?>">
									<?php echo esc_html( $status_label ); ?>
								</h1>

								<p class="qrcv-subtitle">Informasi keaslian dokumen resmi telah terverifikasi oleh sistem.</p>

								<!-- Segel Keamanan Hologram -->
								<div class="qrcv-hologram-container">
									<div class="qrcv-hologram-seal">
										<?php echo QR_Code_Generator::generate_svg( $data['uuid'], 'M' ); ?>
									</div>
								</div>

								<div class="qrcv-actions">
									<button onclick="window.print()" class="btn btn-primary">
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
							</div>
						</div>
					<?php else : ?>
						<!-- Visual Status Lencana untuk Error -->
						<div class="qrcv-status-badge <?php echo esc_attr( $status_class ); ?>">
							<div class="status-badge-glow"></div>
							<div class="status-badge-inner">
								<span class="status-icon"><?php echo esc_html( $status_icon ); ?></span>
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
					<p class="footer-note">Halaman ini dihasilkan secara otomatis oleh sistem validasi enkripsi tanda tangan digital.</p>
				</footer>
			</div>
			<?php wp_footer(); ?>
		</body>
		</html>
		<?php
	}
}
