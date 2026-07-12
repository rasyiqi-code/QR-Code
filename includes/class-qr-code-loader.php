<?php
/**
 * Loader utama untuk QR Code Validator & Generator.
 *
 * @package QR_Code_Validator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Keluar jika diakses langsung.
}

class QR_Code_Loader {

	/**
	 * Instance tunggal dari kelas ini.
	 *
	 * @var QR_Code_Loader
	 */
	private static $instance = null;

	/**
	 * Mendapatkan instance dari kelas ini (Singleton).
	 *
	 * @return QR_Code_Loader
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Konstruktor privat untuk mencegah instansiasi langsung.
	 */
	private function __construct() {
		$this->define_constants();
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Mendefinisikan konstanta plugin.
	 */
	private function define_constants() {
		if ( ! defined( 'QRCV_PATH' ) ) {
			define( 'QRCV_PATH', plugin_dir_path( dirname( __FILE__ ) ) );
		}
		if ( ! defined( 'QRCV_URL' ) ) {
			define( 'QRCV_URL', plugin_dir_url( dirname( __FILE__ ) ) );
		}
		if ( ! defined( 'QRCV_VERSION' ) ) {
			define( 'QRCV_VERSION', '1.0.0' );
		}
	}

	/**
	 * Memuat file dependency.
	 */
	private function includes() {
		// Memuat pustaka QR Code
		if ( file_exists( QRCV_PATH . 'lib/QRCode.php' ) ) {
			require_once QRCV_PATH . 'lib/QRCode.php';
		}

		// Memuat modul internal
		require_once QRCV_PATH . 'includes/class-qr-code-db.php';
		require_once QRCV_PATH . 'includes/class-qr-code-generator.php';
		require_once QRCV_PATH . 'includes/class-qr-code-validator-handler.php';

		if ( is_admin() ) {
			require_once QRCV_PATH . 'includes/class-qr-code-admin.php';
		}
	}

	/**
	 * Inisialisasi hooks WordPress.
	 */
	private function init_hooks() {
		// Aktivasi dan Deaktivasi
		register_activation_hook( QRCV_PATH . 'qr-code-validator.php', array( $this, 'activate' ) );
		register_deactivation_hook( QRCV_PATH . 'qr-code-validator.php', array( $this, 'deactivate' ) );

		// Load assets
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );

		// Inisialisasi modul setelah semua plugin dimuat
		add_action( 'plugins_loaded', array( $this, 'init_modules' ) );
	}

	/**
	 * Logika yang dijalankan saat plugin diaktifkan.
	 */
	public function activate() {
		// Panggil inisialisasi tabel database
		if ( class_exists( 'QR_Code_DB' ) ) {
			QR_Code_DB::create_table();
		}
		flush_rewrite_rules();
	}

	/**
	 * Logika yang dijalankan saat plugin dinonaktifkan.
	 */
	public function deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * Menginisialisasi kelas-kelas modul.
	 */
	public function init_modules() {
		// Inisialisasi database
		QR_Code_DB::get_instance();

		// Inisialisasi validator handler (Frontend)
		QR_Code_Validator_Handler::get_instance();

		// Inisialisasi admin panel jika di area admin
		if ( is_admin() ) {
			QR_Code_Admin::get_instance();
		}
	}

	/**
	 * Memuat aset CSS & JS untuk halaman admin.
	 *
	 * @param string $hook Halaman admin saat ini.
	 */
	public function enqueue_admin_assets( $hook ) {
		// Hanya load jika di halaman submenu QR Code kita
		if ( strpos( $hook, 'qr-code-generator' ) === false ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_style(
			'qrcv-admin-css',
			QRCV_URL . 'assets/css/admin.css',
			array(),
			QRCV_VERSION
		);

		wp_enqueue_script(
			'qrcv-admin-js',
			QRCV_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			QRCV_VERSION,
			true
		);
	}

	/**
	 * Memuat aset CSS untuk frontend (halaman verifikasi).
	 */
	public function enqueue_frontend_assets() {
		// Hanya load jika halaman verifikasi diakses
		if ( isset( $_GET['qr_verify'] ) && ! empty( $_GET['qr_verify'] ) ) {
			wp_enqueue_style(
				'qrcv-validator-css',
				QRCV_URL . 'assets/css/validator.css',
				array(),
				QRCV_VERSION
			);
		}
	}
}
