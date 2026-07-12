<?php
/**
 * Logika database untuk QR Code Validator.
 *
 * @package QR_Code_Validator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Keluar jika diakses langsung.
}

class QR_Code_DB {

	/**
	 * Instance tunggal kelas.
	 *
	 * @var QR_Code_DB
	 */
	private static $instance = null;

	/**
	 * Nama tabel database kustom.
	 *
	 * @var string
	 */
	private static $table_name = '';

	/**
	 * Mendapatkan instance kelas.
	 *
	 * @return QR_Code_DB
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
		global $wpdb;
		self::$table_name = $wpdb->prefix . 'qr_codes';
	}

	/**
	 * Mendapatkan nama tabel lengkap beserta prefix.
	 *
	 * @return string
	 */
	public static function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'qr_codes';
	}

	/**
	 * Membuat tabel database kustom saat aktivasi plugin.
	 */
	public static function create_table() {
		global $wpdb;
		$table_name = self::get_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			uuid varchar(36) NOT NULL UNIQUE,
			title varchar(255) NOT NULL,
			description text NOT NULL,
			status varchar(50) DEFAULT 'valid' NOT NULL,
			metadata longtext DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id),
			KEY uuid_idx (uuid)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Mengambil entri QR Code dari database.
	 *
	 * @param int    $limit      Batas jumlah data.
	 * @param int    $offset     Offset data.
	 * @param string $search     Kata kunci pencarian.
	 * @param string $orderby    Kolom pengurutan.
	 * @param string $order      Arah pengurutan (ASC/DESC).
	 * @return array
	 */
	public function get_qr_codes( $limit = 10, $offset = 0, $search = '', $orderby = 'created_at', $order = 'DESC' ) {
		global $wpdb;
		$table = self::get_table_name();

		// Validasi kolom orderby untuk mencegah SQL injection
		$allowed_orderby = array( 'id', 'title', 'status', 'created_at' );
		if ( ! in_array( $orderby, $allowed_orderby, true ) ) {
			$orderby = 'created_at';
		}

		$order = strtoupper( $order ) === 'ASC' ? 'ASC' : 'DESC';

		if ( ! empty( $search ) ) {
			$search_like = '%' . $wpdb->esc_like( $search ) . '%';
			$query = $wpdb->prepare(
				"SELECT * FROM $table WHERE title LIKE %s OR description LIKE %s OR status LIKE %s ORDER BY $orderby $order LIMIT %d OFFSET %d",
				$search_like,
				$search_like,
				$search_like,
				$limit,
				$offset
			);
		} else {
			$query = $wpdb->prepare(
				"SELECT * FROM $table ORDER BY $orderby $order LIMIT %d OFFSET %d",
				$limit,
				$offset
			);
		}

		return $wpdb->get_results( $query, ARRAY_A );
	}

	/**
	 * Menghitung total entri QR Code.
	 *
	 * @param string $search Kata kunci pencarian.
	 * @return int
	 */
	public function count_qr_codes( $search = '' ) {
		global $wpdb;
		$table = self::get_table_name();

		if ( ! empty( $search ) ) {
			$search_like = '%' . $wpdb->esc_like( $search ) . '%';
			$query = $wpdb->prepare(
				"SELECT COUNT(*) FROM $table WHERE title LIKE %s OR description LIKE %s OR status LIKE %s",
				$search_like,
				$search_like,
				$search_like
			);
		} else {
			$query = "SELECT COUNT(*) FROM $table";
		}

		return (int) $wpdb->get_var( $query );
	}

	/**
	 * Mengambil entri QR Code berdasarkan UUID.
	 *
	 * @param string $uuid UUID unik.
	 * @return array|null
	 */
	public function get_qr_code_by_uuid( $uuid ) {
		global $wpdb;
		$table = self::get_table_name();

		$query = $wpdb->prepare(
			"SELECT * FROM $table WHERE uuid = %s",
			$uuid
		);

		return $wpdb->get_row( $query, ARRAY_A );
	}

	/**
	 * Mengambil entri QR Code berdasarkan ID.
	 *
	 * @param int $id ID QR Code.
	 * @return array|null
	 */
	public function get_qr_code_by_id( $id ) {
		global $wpdb;
		$table = self::get_table_name();

		$query = $wpdb->prepare(
			"SELECT * FROM $table WHERE id = %d",
			$id
		);

		return $wpdb->get_row( $query, ARRAY_A );
	}

	/**
	 * Menyisipkan data QR Code baru.
	 *
	 * @param array $data Data yang akan dimasukkan.
	 * @return int|bool ID entri baru jika sukses, false jika gagal.
	 */
	public function insert_qr_code( $data ) {
		global $wpdb;
		$table = self::get_table_name();

		$uuid = wp_generate_uuid4();

		$insert_data = array(
			'uuid'        => $uuid,
			'title'       => sanitize_text_field( $data['title'] ),
			'description' => wp_kses_post( $data['description'] ),
			'status'      => sanitize_text_field( $data['status'] ),
			'metadata'    => ! empty( $data['metadata'] ) ? maybe_serialize( $data['metadata'] ) : '',
			'created_at'  => current_time( 'mysql' ),
		);

		$formats = array( '%s', '%s', '%s', '%s', '%s', '%s' );

		$result = $wpdb->insert( $table, $insert_data, $formats );

		if ( $result ) {
			return $wpdb->insert_id;
		}

		return false;
	}

	/**
	 * Memperbarui data QR Code.
	 *
	 * @param int   $id   ID entri yang akan diperbarui.
	 * @param array $data Data baru.
	 * @return bool True jika sukses, false jika gagal.
	 */
	public function update_qr_code( $id, $data ) {
		global $wpdb;
		$table = self::get_table_name();

		$update_data = array(
			'title'       => sanitize_text_field( $data['title'] ),
			'description' => wp_kses_post( $data['description'] ),
			'status'      => sanitize_text_field( $data['status'] ),
		);
		$formats = array( '%s', '%s', '%s' );

		if ( isset( $data['metadata'] ) ) {
			$update_data['metadata'] = maybe_serialize( $data['metadata'] );
			$formats[] = '%s';
		}

		$result = $wpdb->update(
			$table,
			$update_data,
			array( 'id' => $id ),
			$formats,
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Menghapus data QR Code.
	 *
	 * @param int $id ID entri yang akan dihapus.
	 * @return bool
	 */
	public function delete_qr_code( $id ) {
		global $wpdb;
		$table = self::get_table_name();

		$result = $wpdb->delete(
			$table,
			array( 'id' => $id ),
			array( '%d' )
		);

		return $result !== false;
	}
}
