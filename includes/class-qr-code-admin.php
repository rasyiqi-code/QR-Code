<?php
/**
 * Modul dashboard admin untuk QR Code Generator & Validator.
 *
 * @package QR_Code_Validator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Keluar jika diakses langsung.
}

class QR_Code_Admin {

	/**
	 * Instance tunggal kelas.
	 *
	 * @var QR_Code_Admin
	 */
	private static $instance = null;

	/**
	 * Mendapatkan instance kelas.
	 *
	 * @return QR_Code_Admin
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
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'handle_admin_actions' ) );
	}

	/**
	 * Menambahkan menu plugin ke sidebar admin WordPress.
	 */
	public function add_admin_menu() {
		add_menu_page(
			esc_html__( 'QR Code Generator', 'qr-code-validator' ),
			esc_html__( 'QR Validator', 'qr-code-validator' ),
			'manage_options',
			'qr-code-generator',
			array( $this, 'render_admin_page' ),
			'dashicons-qr',
			26
		);
	}

	/**
	 * Memproses aksi admin seperti Simpan, Edit, Hapus, dan Download.
	 */
	public function handle_admin_actions() {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';

		// Aksi Hapus
		if ( 'delete' === $action ) {
			$id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
			if ( $id && check_admin_referer( 'qrcv_delete_qr_' . $id ) ) {
				QR_Code_DB::get_instance()->delete_qr_code( $id );
				wp_safe_redirect( add_query_arg( array( 'page' => 'qr-code-generator', 'msg' => 'deleted' ), admin_url( 'admin.php' ) ) );
				exit;
			}
		}

		// Aksi Download PNG
		if ( 'download' === $action ) {
			$uuid = isset( $_GET['uuid'] ) ? sanitize_text_field( wp_unslash( $_GET['uuid'] ) ) : '';
			$title = isset( $_GET['title'] ) ? sanitize_title( wp_unslash( $_GET['title'] ) ) : 'qr-code';
			if ( $uuid && check_admin_referer( 'qrcv_download_qr_' . $uuid ) ) {
				QR_Code_Generator::download_png( $uuid, $title );
				exit;
			}
		}

		// Aksi Simpan (Tambah Baru / Edit)
		if ( isset( $_POST['qrcv_save_submit'] ) ) {
			check_admin_referer( 'qrcv_save_qr_action', 'qrcv_save_qr_nonce' );

			$id           = isset( $_POST['qr_id'] ) ? (int) $_POST['qr_id'] : 0;
			$title        = isset( $_POST['qr_title'] ) ? sanitize_text_field( wp_unslash( $_POST['qr_title'] ) ) : '';
			$description  = isset( $_POST['qr_description'] ) ? wp_kses_post( wp_unslash( $_POST['qr_description'] ) ) : '';
			$status       = isset( $_POST['qr_status'] ) ? sanitize_text_field( wp_unslash( $_POST['qr_status'] ) ) : 'valid';
			$document_url = isset( $_POST['qr_document_url'] ) ? esc_url_raw( wp_unslash( $_POST['qr_document_url'] ) ) : '';

			// Format metadata
			$meta_keys   = isset( $_POST['meta_key'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['meta_key'] ) ) : array();
			$meta_values = isset( $_POST['meta_value'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['meta_value'] ) ) : array();
			$metadata    = array();

			// Sematkan URL Dokumen/TTD ke metadata paling pertama
			if ( ! empty( $document_url ) ) {
				$metadata[] = array(
					'key'   => esc_html__( 'File Dokumen', 'qr-code-validator' ),
					'value' => $document_url,
				);
			}

			for ( $i = 0; $i < count( $meta_keys ); $i++ ) {
				if ( ! empty( $meta_keys[ $i ] ) && ! empty( $meta_values[ $i ] ) ) {
					// Hindari duplikasi File Dokumen dari inputan manual
					if ( $meta_keys[ $i ] === esc_html__( 'File Dokumen', 'qr-code-validator' ) ) {
						continue;
					}
					$metadata[] = array(
						'key'   => $meta_keys[ $i ],
						'value' => $meta_values[ $i ],
					);
				}
			}

			$data = array(
				'title'       => $title,
				'description' => $description,
				'status'      => $status,
				'metadata'    => $metadata,
			);

			$db = QR_Code_DB::get_instance();

			if ( $id > 0 ) {
				// Update
				$db->update_qr_code( $id, $data );
				wp_safe_redirect( add_query_arg( array( 'page' => 'qr-code-generator', 'msg' => 'updated' ), admin_url( 'admin.php' ) ) );
			} else {
				// Insert Baru
				$db->insert_qr_code( $data );
				wp_safe_redirect( add_query_arg( array( 'page' => 'qr-code-generator', 'msg' => 'created' ), admin_url( 'admin.php' ) ) );
			}
			exit;
		}
	}

	/**
	 * Merender halaman utama dashboard admin plugin.
	 */
	public function render_admin_page() {
		$tab  = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'list';
		$msg  = isset( $_GET['msg'] ) ? sanitize_text_field( wp_unslash( $_GET['msg'] ) ) : '';
		$edit_id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;

		// override tab jika edit
		if ( $edit_id > 0 && isset( $_GET['action'] ) && 'edit' === $_GET['action'] ) {
			$tab = 'edit';
		}
		?>
		<div class="wrap qrcv-admin-wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'QR Code Generator & Validator', 'qr-code-validator' ); ?></h1>
			<hr class="wp-header-end">

			<!-- Notifikasi Status -->
			<?php if ( 'created' === $msg ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'QR Code Dokumen berhasil dibuat!', 'qr-code-validator' ); ?></p></div>
			<?php elseif ( 'updated' === $msg ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Detail QR Code Dokumen berhasil diperbarui!', 'qr-code-validator' ); ?></p></div>
			<?php elseif ( 'deleted' === $msg ) : ?>
				<div class="notice notice-info is-dismissible"><p><?php esc_html_e( 'Data QR Code Dokumen berhasil dihapus!', 'qr-code-validator' ); ?></p></div>
			<?php endif; ?>

			<!-- Navigasi Tab -->
			<nav class="nav-tab-wrapper qrcv-tab-wrapper">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=qr-code-generator&tab=list' ) ); ?>" class="nav-tab <?php echo 'list' === $tab ? 'nav-tab-active' : ''; ?>">
					📂 Daftar Dokumen QR
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=qr-code-generator&tab=add' ) ); ?>" class="nav-tab <?php echo 'add' === $tab ? 'nav-tab-active' : ''; ?>">
					➕ Tambah Dokumen Baru
				</a>
				<?php if ( 'edit' === $tab ) : ?>
					<a href="#" class="nav-tab nav-tab-active">📝 Edit Dokumen</a>
				<?php endif; ?>
			</nav>

			<div class="qrcv-tab-content">
				<?php
				if ( 'list' === $tab ) {
					$this->render_list_tab();
				} elseif ( 'add' === $tab || 'edit' === $tab ) {
					$this->render_form_tab( $edit_id );
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Merender daftar tabel QR Code (Tab List).
	 */
	private function render_list_tab() {
		$db = QR_Code_DB::get_instance();

		// Mengatur pencarian & paginasi
		$search  = isset( $_POST['s'] ) ? sanitize_text_field( wp_unslash( $_POST['s'] ) ) : ( isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '' );
		$per_page = 10;
		$paged    = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;
		$offset   = ( $paged - 1 ) * $per_page;

		$items       = $db->get_qr_codes( $per_page, $offset, $search );
		$total_items = $db->count_qr_codes( $search );
		$total_pages = ceil( $total_items / $per_page );

		// Render pencarian
		?>
		<div class="qrcv-list-toolbar">
			<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
				<input type="hidden" name="page" value="qr-code-generator">
				<p class="search-box">
					<label class="screen-reader-text" for="qr-search-input">Cari Dokumen:</label>
					<input type="search" id="qr-search-input" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="Ketik kata kunci...">
					<input type="submit" id="search-submit" class="button" value="Cari Dokumen">
				</p>
			</form>
		</div>

		<table class="wp-list-table widefat fixed striped table-view-list qrcv-table">
			<thead>
				<tr>
					<th scope="col" class="column-id">ID</th>
					<th scope="col" class="column-qr">QR Code</th>
					<th scope="col" class="column-title">Judul Dokumen</th>
					<th scope="col" class="column-status">Status</th>
					<th scope="col" class="column-date">Tanggal Rilis</th>
					<th scope="col" class="column-actions">Aksi</th>
				</tr>
			</thead>
			<tbody>
				<?php if ( ! empty( $items ) ) : ?>
					<?php foreach ( $items as $item ) : ?>
						<?php
						$validation_url = QR_Code_Generator::get_validation_url( $item['uuid'] );
						$qr_svg_raw     = QR_Code_Generator::generate_svg( $item['uuid'], 'L' );
						
						// Label status
						$status_label = '';
						$status_class = '';
						switch ( strtolower( $item['status'] ) ) {
							case 'valid':
								$status_label = '✅ Valid';
								$status_class = 'badge-success';
								break;
							case 'expired':
								$status_label = '⚠ Expired';
								$status_class = 'badge-warning';
								break;
							case 'revoked':
								$status_label = '❌ Dicabut';
								$status_class = 'badge-danger';
								break;
						}
						?>
						<tr>
							<td class="column-id"><?php echo (int) $item['id']; ?></td>
							<td class="column-qr">
								<div class="qrcv-table-qr-preview">
									<?php echo $qr_svg_raw; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</div>
							</td>
							<td class="column-title">
								<strong><a href="<?php echo esc_url( add_query_arg( array( 'page' => 'qr-code-generator', 'action' => 'edit', 'id' => $item['id'] ), admin_url( 'admin.php' ) ) ); ?>"><?php echo esc_html( $item['title'] ); ?></a></strong>
								<p class="description"><?php echo esc_html( wp_trim_words( $item['description'], 10, '...' ) ); ?></p>
								<code class="qrcv-table-uuid"><?php echo esc_html( $item['uuid'] ); ?></code>
							</td>
							<td class="column-status">
								<span class="qrcv-badge <?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( $status_label ); ?></span>
							</td>
							<td class="column-date"><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' H:i', strtotime( $item['created_at'] ) ) ); ?></td>
							<td class="column-actions">
								<div class="qrcv-actions-wrapper">
									<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'page' => 'qr-code-generator', 'action' => 'download', 'uuid' => $item['uuid'], 'title' => $item['title'] ), admin_url( 'admin.php' ) ), 'qrcv_download_qr_' . $item['uuid'] ) ); ?>" class="button button-small" title="Download QR Code (PNG)">
										📥 Download
									</a>
									<button class="button button-small qrcv-copy-btn" data-link="<?php echo esc_url( $validation_url ); ?>" title="Salin URL Validasi">
										🔗 Salin Link
									</button>
									<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'qr-code-generator', 'action' => 'edit', 'id' => $item['id'] ), admin_url( 'admin.php' ) ) ); ?>" class="button button-small button-primary" title="Edit Dokumen">
										✏️ Edit
									</a>
									<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'page' => 'qr-code-generator', 'action' => 'delete', 'id' => $item['id'] ), admin_url( 'admin.php' ) ), 'qrcv_delete_qr_' . $item['id'] ) ); ?>" class="button button-small qrcv-delete-btn" onclick="return confirm('Apakah Anda yakin ingin menghapus data QR dokumen ini?');" title="Hapus Dokumen">
										🗑️ Hapus
									</a>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr>
						<td colspan="6" style="text-align: center; padding: 20px;">
							<?php esc_html_e( 'Tidak ada dokumen QR Code ditemukan.', 'qr-code-validator' ); ?>
						</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>

		<!-- Render Paginasi -->
		<?php if ( $total_pages > 1 ) : ?>
			<div class="tablenav">
				<div class="tablenav-pages">
					<span class="displaying-num"><?php printf( esc_html( _n( '%s item', '%s items', $total_items, 'qr-code-validator' ) ), number_format_i18n( $total_items ) ); ?></span>
					<span class="pagination-links">
						<?php
						echo paginate_links( array(
							'base'      => add_query_arg( 'paged', '%#%' ),
							'format'    => '',
							'prev_text' => __( '&laquo; Kembali', 'qr-code-validator' ),
							'next_text' => __( 'Lanjut &raquo;', 'qr-code-validator' ),
							'total'     => $total_pages,
							'current'   => $paged,
						) );
						?>
					</span>
				</div>
			</div>
		<?php endif; ?>
		<?php
	}

	/**
	 * Merender form Tambah/Edit QR Code (Tab Add/Edit).
	 *
	 * @param int $id ID dokumen jika edit mode, 0 jika tambah baru.
	 */
	private function render_form_tab( $id = 0 ) {
		$db   = QR_Code_DB::get_instance();
		$item = null;

		if ( $id > 0 ) {
			$item = $db->get_qr_code_by_id( $id );
		}

		$title       = $item ? $item['title'] : '';
		$description = $item ? $item['description'] : '';
		$status      = $item ? $item['status'] : 'valid';
		
		$document_url = '';
		$metadata     = array();
		if ( $item && ! empty( $item['metadata'] ) ) {
			$raw_metadata = maybe_unserialize( $item['metadata'] );
			if ( ! is_array( $raw_metadata ) ) {
				$raw_metadata = json_decode( $item['metadata'], true );
			}
			
			if ( is_array( $raw_metadata ) ) {
				foreach ( $raw_metadata as $meta ) {
					if ( isset( $meta['key'] ) && $meta['key'] === esc_html__( 'File Dokumen', 'qr-code-validator' ) ) {
						$document_url = $meta['value'];
					} else {
						$metadata[] = $meta;
					}
				}
			}
		}
		?>
		<div class="qrcv-form-container">
			<h2><?php echo $id > 0 ? esc_html__( 'Edit Detail Dokumen QR Code', 'qr-code-validator' ) : esc_html__( 'Buat Dokumen QR Code Baru', 'qr-code-validator' ); ?></h2>
			
			<form method="post" action="">
				<?php wp_nonce_field( 'qrcv_save_qr_action', 'qrcv_save_qr_nonce' ); ?>
				<input type="hidden" name="qr_id" value="<?php echo (int) $id; ?>">

				<table class="form-table qrcv-form-table">
					<tr>
						<th scope="row"><label for="qr_title">Judul / Nama Dokumen <span class="required">*</span></label></th>
						<td>
							<input type="text" name="qr_title" id="qr_title" value="<?php echo esc_attr( $title ); ?>" class="regular-text" required placeholder="Contoh: Sertifikat Kelulusan Rasyiqi">
							<p class="description">Judul atau nama resmi dokumen yang akan ditampilkan saat QR Code di-scan.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="qr_description">Deskripsi / Detail Dokumen <span class="required">*</span></label></th>
						<td>
							<textarea name="qr_description" id="qr_description" rows="5" class="large-text" required placeholder="Ketik rincian isi dokumen, instansi penerbit, tanda tangan, dll..."><?php echo esc_textarea( $description ); ?></textarea>
							<p class="description">Detail lengkap dokumen untuk memperjelas validitas data.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="qr_status">Status Keaslian Dokumen</label></th>
						<td>
							<select name="qr_status" id="qr_status">
								<option value="valid" <?php selected( $status, 'valid' ); ?>>✅ VALID - Dokumen Asli dan Aktif</option>
								<option value="expired" <?php selected( $status, 'expired' ); ?>>⚠ EXPIRED - Dokumen Kadaluarsa</option>
								<option value="revoked" <?php selected( $status, 'revoked' ); ?>>❌ REVOKED - Dokumen Dicabut / Tidak Berlaku</option>
							</select>
							<p class="description">Ubah status ini secara real-time untuk membatalkan atau mengaktifkan kembali validasi QR Code.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="qr_document_url">Sematkan Dokumen (File TTD/PDF)</label></th>
						<td>
							<div style="display: flex; gap: 8px; align-items: center;">
								<input type="text" name="qr_document_url" id="qr_document_url" value="<?php echo esc_url( $document_url ); ?>" class="regular-text" placeholder="Pilih atau tempel URL file...">
								<button type="button" id="qrcv-upload-btn" class="button button-secondary">📁 Pilih Berkas Media</button>
							</div>
							<p class="description">Pilih berkas tanda tangan digital (PNG transparan) atau dokumen sertifikat (PDF) dari Media Library WordPress Anda.</p>
						</td>
					</tr>

					<!-- Dynamic Meta Fields (Key-Value) -->
					<tr>
						<th scope="row"><label>Metadata Tambahan (Opsional)</label></th>
						<td>
							<div id="qrcv-meta-fields-container">
								<?php if ( ! empty( $metadata ) ) : ?>
									<?php foreach ( $metadata as $index => $meta ) : ?>
										<div class="qrcv-meta-row">
											<input type="text" name="meta_key[]" value="<?php echo esc_attr( $meta['key'] ); ?>" placeholder="Label (Contoh: Nama Pemilik)" class="meta-input">
											<input type="text" name="meta_value[]" value="<?php echo esc_attr( $meta['value'] ); ?>" placeholder="Nilai (Contoh: Rasyiqi R.)" class="meta-input">
											<button type="button" class="button qrcv-remove-meta-btn">Hapus</button>
										</div>
									<?php endforeach; ?>
								<?php endif; ?>
								<!-- Baris default jika belum ada -->
								<div class="qrcv-meta-row default-row" style="<?php echo ! empty( $metadata ) ? 'display:none;' : ''; ?>">
									<input type="text" name="meta_key[]" placeholder="Label (Contoh: NIK)" class="meta-input">
									<input type="text" name="meta_value[]" placeholder="Nilai (Contoh: 12345678)" class="meta-input">
									<button type="button" class="button qrcv-remove-meta-btn">Hapus</button>
								</div>
							</div>
							<div style="margin-top: 10px;">
								<button type="button" id="qrcv-add-meta-btn" class="button button-secondary">➕ Tambah Baris Data</button>
							</div>
							<p class="description">Gunakan kolom ini untuk data spesifik seperti: NIK, Penerbit, Nomor Registrasi, dll.</p>
						</td>
					</tr>
				</table>

				<p class="submit">
					<input type="submit" name="qrcv_save_submit" id="submit" class="button button-primary button-large" value="<?php echo $id > 0 ? 'Perbarui Dokumen & QR' : 'Simpan & Hasilkan QR Code'; ?>">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=qr-code-generator&tab=list' ) ); ?>" class="button button-large" style="margin-left: 10px;">Batal</a>
				</p>
			</form>
		</div>
		<?php
	}
}
