<?php
/**
 * Admin functionality.
 *
 * @package SysAdminToolbox
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin-specific functionality.
 */
class SysAdmin_Admin {

	/**
	 * Plugin name.
	 *
	 * @var string
	 */
	private $plugin_name;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Google code generator service.
	 *
	 * @var SysAdmin_Google_Codes
	 */
	private $google_codes;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name Plugin slug.
	 * @param string $version Plugin version.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->google_codes = new SysAdmin_Google_Codes();
	}

	/**
	 * Register admin stylesheet.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 * @return void
	 */
	public function enqueue_styles( $hook_suffix ) {
		if ( 'tools_page_sysadmin' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			$this->plugin_name,
			SYSADMIN_TOOLBOX_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			$this->version,
			'all'
		);
	}

	/**
	 * Register admin JavaScript.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 * @return void
	 */
	public function enqueue_scripts( $hook_suffix ) {
		if ( 'tools_page_sysadmin' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_script(
			$this->plugin_name,
			SYSADMIN_TOOLBOX_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery', 'jquery-ui-sortable' ),
			$this->version,
			true
		);
	}

	/**
	 * Register admin-post handler.
	 *
	 * @return void
	 */
	public function register_post_actions() {
		add_action( 'admin_post_sysadmin_generate_google_codes', array( $this, 'handle_generate_google_codes' ) );
	}

	/**
	 * Register toolbox page under Tools.
	 *
	 * @return void
	 */
	public function register_admin_menu() {
		add_management_page(
			esc_html__( 'SysAdmin Toolbox', 'sysadmin' ),
			esc_html__( 'SysAdmin Toolbox', 'sysadmin' ),
			'manage_options',
			'sysadmin',
			array( $this, 'render_tools_page' )
		);
	}

	/**
	 * Render toolbox page markup.
	 *
	 * @return void
	 */
	public function render_tools_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$notice_data     = $this->get_validated_notice_query_args();
		$status          = $notice_data['status'];
		$msg             = $notice_data['message'];
		$preview_payload = $this->get_preview_payload( $notice_data['is_valid_nonce'] );
		?>
		<div class="wrap sysadmin">
			<h1><?php echo esc_html__( 'SysAdmin Toolbox voor Scholen', 'sysadmin' ); ?></h1>
			<p class="sysadmin-lead"><?php echo esc_html__( 'Beheer je leerlingaccounts sneller met praktische tools voor schoolomgevingen.', 'sysadmin' ); ?></p>

			<?php if ( '' !== $status ) : ?>
				<div class="notice <?php echo esc_attr( 'success' === $status ? 'notice-success' : 'notice-error' ); ?> is-dismissible">
					<p><?php echo esc_html( $msg ); ?></p>
				</div>
			<?php endif; ?>

			<div class="sysadmin-drag-grid" id="sysadmin-drag-grid">
				<section class="sysadmin-card" data-card-id="google-codes">
					<header class="sysadmin-card-header">
						<h2><?php echo esc_html__( 'Genereer Google Codes', 'sysadmin' ); ?></h2>
						<span class="sysadmin-drag-handle" aria-hidden="true">:::</span>
					</header>
					<p><?php echo esc_html__( 'Upload een Excel-bronbestand, bekijk eerst een preview en download daarna het aangepaste bestand.', 'sysadmin' ); ?></p>

					<div class="sysadmin-how-it-works">
						<h3><?php echo esc_html__( 'Werking', 'sysadmin' ); ?></h3>
						<ol>
							<li><?php echo esc_html__( 'Het bestand wordt ingelezen op basis van de kolom "LeerID Wachtwoord".', 'sysadmin' ); ?></li>
							<li><?php echo esc_html__( 'Uit elk wachtwoord worden cijfers verwijderd zodat enkel het woord overblijft.', 'sysadmin' ); ?></li>
							<li><?php echo esc_html__( 'Het nieuwe wachtwoord wordt opgebouwd met prefix of suffix volgens jouw keuze.', 'sysadmin' ); ?></li>
							<li><?php echo esc_html__( 'In de preview kies je live welke kolommen geëxporteerd worden.', 'sysadmin' ); ?></li>
						</ol>
					</div>

					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" class="sysadmin-form">
						<?php wp_nonce_field( 'sysadmin_generate_google_codes', 'sysadmin_google_codes_nonce' ); ?>
						<input type="hidden" name="action" value="sysadmin_generate_google_codes" />
						<input type="hidden" name="sysadmin_mode" value="preview" />

						<label for="sysadmin_source_file"><?php echo esc_html__( 'Bronbestand (xlsx, xls of csv)', 'sysadmin' ); ?></label>
						<input type="file" id="sysadmin_source_file" name="sysadmin_source_file" accept=".xlsx,.xls,.csv" required />

						<label for="sysadmin_suffix"><?php echo esc_html__( 'Suffix (minimaal 4 karakters)', 'sysadmin' ); ?></label>
						<input type="text" id="sysadmin_suffix" name="sysadmin_suffix" minlength="4" required />

						<label for="sysadmin_suffix_position"><?php echo esc_html__( 'Type toevoeging', 'sysadmin' ); ?></label>
						<select id="sysadmin_suffix_position" name="sysadmin_suffix_position" required>
							<option value="suffix"><?php echo esc_html__( 'Suffix (achteraan: woord + suffix)', 'sysadmin' ); ?></option>
							<option value="prefix"><?php echo esc_html__( 'Prefix (vooraan: suffix + woord)', 'sysadmin' ); ?></option>
						</select>

						<label class="sysadmin-inline-check">
							<input type="checkbox" name="sysadmin_capitalize_word" value="1" checked />
							<span><?php echo esc_html__( 'Geef het woord een hoofdletter (eerste letter)', 'sysadmin' ); ?></span>
						</label>

						<button type="submit" class="button button-primary button-hero">
							<?php echo esc_html__( 'Toon Preview (eerste 20)', 'sysadmin' ); ?>
						</button>
					</form>

					<?php if ( ! empty( $preview_payload ) ) : ?>
						<div class="sysadmin-preview" data-password-column="<?php echo esc_attr( (string) $preview_payload['preview']['password_column_index'] ); ?>" data-suffix="<?php echo esc_attr( (string) $preview_payload['suffix'] ); ?>" data-position="<?php echo esc_attr( (string) $preview_payload['position'] ); ?>">
							<?php $headers = isset( $preview_payload['preview']['headers'] ) && is_array( $preview_payload['preview']['headers'] ) ? $preview_payload['preview']['headers'] : array(); ?>
							<h3><?php echo esc_html__( 'Preview', 'sysadmin' ); ?></h3>
							<p>
								<?php
								echo esc_html(
									sprintf(
										/* translators: 1: valid rows, 2: invalid rows */
										__( '%1$d geldige rijen, %2$d ongeldige rijen.', 'sysadmin' ),
										(int) $preview_payload['preview']['valid_count'],
										(int) $preview_payload['preview']['invalid_count']
									)
								);
								?>
							</p>

							<div class="sysadmin-table-wrap">
								<table class="widefat striped">
									<thead>
										<tr>
											<th><?php echo esc_html__( 'Rij', 'sysadmin' ); ?></th>
											<?php foreach ( $headers as $header_index => $header_name ) : ?>
												<th data-source-col="<?php echo esc_attr( (string) $header_index ); ?>"><?php echo esc_html( '' !== trim( (string) $header_name ) ? (string) $header_name : sprintf( __( 'Kolom %d', 'sysadmin' ), $header_index + 1 ) ); ?></th>
											<?php endforeach; ?>
										</tr>
									</thead>
									<tbody>
										<?php foreach ( $preview_payload['preview']['preview_rows'] as $row ) : ?>
											<tr>
												<td><?php echo esc_html( (string) $row['row'] ); ?></td>
												<?php foreach ( $headers as $header_index => $header_name ) : ?>
													<?php if ( $header_index === (int) $preview_payload['preview']['password_column_index'] ) : ?>
														<td class="sysadmin-password-cell" data-source-col="<?php echo esc_attr( (string) $header_index ); ?>" data-base-word="<?php echo esc_attr( isset( $row['base_word'] ) ? (string) $row['base_word'] : '' ); ?>"><?php echo esc_html( isset( $row['values'][ $header_index ] ) ? (string) $row['values'][ $header_index ] : '' ); ?></td>
													<?php else : ?>
														<td data-source-col="<?php echo esc_attr( (string) $header_index ); ?>"><?php echo esc_html( isset( $row['values'][ $header_index ] ) ? (string) $row['values'][ $header_index ] : '' ); ?></td>
													<?php endif; ?>
												<?php endforeach; ?>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>

							<?php if ( ! empty( $preview_payload['preview']['invalid_rows'] ) ) : ?>
								<div class="sysadmin-invalid-list">
									<h4><?php echo esc_html__( 'Ongeldige of ontbrekende wachtwoorden (eerste 20)', 'sysadmin' ); ?></h4>
									<ul>
										<?php foreach ( $preview_payload['preview']['invalid_rows'] as $invalid_row ) : ?>
											<li>
												<?php
												echo esc_html(
													sprintf(
														/* translators: 1: row number, 2: username, 3: reason */
														__( 'Rij %1$d (%2$s): %3$s', 'sysadmin' ),
														(int) $invalid_row['row'],
														'' !== (string) $invalid_row['username'] ? (string) $invalid_row['username'] : __( 'onbekende gebruiker', 'sysadmin' ),
														(string) $invalid_row['reason']
													)
												);
												?>
											</li>
										<?php endforeach; ?>
									</ul>
								</div>
							<?php endif; ?>

							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sysadmin-form-inline">
								<?php wp_nonce_field( 'sysadmin_generate_google_codes', 'sysadmin_google_codes_nonce' ); ?>
								<input type="hidden" name="action" value="sysadmin_generate_google_codes" />
								<input type="hidden" name="sysadmin_mode" value="download_preview" />
								<input type="hidden" name="sysadmin_preview_token" value="<?php echo esc_attr( (string) $preview_payload['token'] ); ?>" />

								<div class="sysadmin-column-picker">
									<h4><?php echo esc_html__( 'Selecteer kolommen voor export', 'sysadmin' ); ?></h4>
									<p><?php echo esc_html__( 'Standaard zijn alle bronkolommen geselecteerd. Wijzigingen zie je meteen in de preview.', 'sysadmin' ); ?></p>
									<div class="sysadmin-column-grid">
										<?php foreach ( $headers as $header_index => $header_name ) : ?>
											<label>
												<input type="checkbox" name="sysadmin_export_columns[]" value="<?php echo esc_attr( (string) $header_index ); ?>" checked />
												<span><?php echo esc_html( '' !== trim( (string) $header_name ) ? (string) $header_name : sprintf( __( 'Kolom %d', 'sysadmin' ), $header_index + 1 ) ); ?></span>
											</label>
										<?php endforeach; ?>
									</div>
								</div>

								<label class="sysadmin-inline-check sysadmin-preview-capitalize-toggle">
									<input type="checkbox" name="sysadmin_capitalize_word" value="1" <?php checked( ! empty( $preview_payload['capitalize_word'] ) ); ?> />
									<span><?php echo esc_html__( 'Hoofdletter live tonen in preview en export', 'sysadmin' ); ?></span>
								</label>

								<button type="submit" class="button button-primary">
									<?php echo esc_html__( 'Download gegenereerd bestand', 'sysadmin' ); ?>
								</button>
							</form>

							<?php if ( ! empty( $preview_payload['preview']['invalid_rows_all'] ) ) : ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sysadmin-form-inline">
									<?php wp_nonce_field( 'sysadmin_generate_google_codes', 'sysadmin_google_codes_nonce' ); ?>
									<input type="hidden" name="action" value="sysadmin_generate_google_codes" />
									<input type="hidden" name="sysadmin_mode" value="download_invalid" />
									<input type="hidden" name="sysadmin_preview_token" value="<?php echo esc_attr( (string) $preview_payload['token'] ); ?>" />
									<button type="submit" class="button button-secondary">
										<?php echo esc_html__( 'Download foutbestand (ongeldige rijen)', 'sysadmin' ); ?>
									</button>
								</form>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</section>
			</div>
		</div>
		<?php
	}

	/**
	 * Handle form submission and generated file download.
	 *
	 * @return void
	 */
	public function handle_generate_google_codes() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Je hebt geen rechten om deze actie uit te voeren.', 'sysadmin' ) );
		}

		check_admin_referer( 'sysadmin_generate_google_codes', 'sysadmin_google_codes_nonce' );

		$mode = isset( $_POST['sysadmin_mode'] ) ? sanitize_key( wp_unslash( $_POST['sysadmin_mode'] ) ) : 'preview';
		if ( 'download_preview' === $mode ) {
			$this->handle_download_from_preview();
		}
		if ( 'download_invalid' === $mode ) {
			$this->handle_download_invalid_from_preview();
		}

		$suffix = isset( $_POST['sysadmin_suffix'] ) ? sanitize_text_field( wp_unslash( $_POST['sysadmin_suffix'] ) ) : '';
		if ( mb_strlen( $suffix ) < 4 ) {
			$this->redirect_with_notice( 'error', __( 'De suffix moet minstens 4 karakters bevatten.', 'sysadmin' ) );
		}

		$position = isset( $_POST['sysadmin_suffix_position'] ) ? sanitize_key( wp_unslash( $_POST['sysadmin_suffix_position'] ) ) : 'suffix';
		if ( ! in_array( $position, array( 'prefix', 'suffix' ), true ) ) {
			$this->redirect_with_notice( 'error', __( 'Ongeldige suffix-positie gekozen.', 'sysadmin' ) );
		}

		$capitalize_word = isset( $_POST['sysadmin_capitalize_word'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['sysadmin_capitalize_word'] ) );

		if ( empty( $_FILES['sysadmin_source_file']['name'] ) || empty( $_FILES['sysadmin_source_file']['tmp_name'] ) ) {
			$this->redirect_with_notice( 'error', __( 'Selecteer een bronbestand om te uploaden.', 'sysadmin' ) );
		}

		$file = array(
			'name'     => isset( $_FILES['sysadmin_source_file']['name'] ) ? sanitize_file_name( wp_unslash( $_FILES['sysadmin_source_file']['name'] ) ) : '',
			'type'     => isset( $_FILES['sysadmin_source_file']['type'] ) ? sanitize_mime_type( wp_unslash( $_FILES['sysadmin_source_file']['type'] ) ) : '',
			'tmp_name' => isset( $_FILES['sysadmin_source_file']['tmp_name'] ) ? sanitize_text_field( wp_unslash( $_FILES['sysadmin_source_file']['tmp_name'] ) ) : '',
			'error'    => isset( $_FILES['sysadmin_source_file']['error'] ) ? absint( $_FILES['sysadmin_source_file']['error'] ) : UPLOAD_ERR_NO_FILE,
			'size'     => isset( $_FILES['sysadmin_source_file']['size'] ) ? absint( $_FILES['sysadmin_source_file']['size'] ) : 0,
		);

		if ( ! empty( $file['error'] ) ) {
			$this->redirect_with_notice( 'error', __( 'Upload mislukt. Probeer opnieuw.', 'sysadmin' ) );
		}

		$extension = strtolower( pathinfo( (string) $file['name'], PATHINFO_EXTENSION ) );
		if ( ! in_array( $extension, array( 'xlsx', 'xls', 'csv' ), true ) ) {
			$this->redirect_with_notice( 'error', __( 'Alleen xlsx, xls en csv bestanden worden ondersteund.', 'sysadmin' ) );
		}

		$temp_file_path = $this->move_upload_to_temp_storage( $file );
		if ( is_wp_error( $temp_file_path ) ) {
			$this->redirect_with_notice( 'error', $temp_file_path->get_error_message() );
		}

		$preview = $this->google_codes->build_preview_data( $temp_file_path, $suffix, $position, $capitalize_word );
		if ( is_wp_error( $preview ) ) {
			$this->redirect_with_notice( 'error', $preview->get_error_message() );
		}

		$token = wp_generate_password( 20, false, false );
		set_transient(
			$this->get_preview_transient_key( $token ),
			array(
				'temp_file_path' => $temp_file_path,
				'input_filename' => (string) $file['name'],
				'suffix'         => $suffix,
				'position'       => $position,
				'capitalize_word' => $capitalize_word,
				'preview'        => $preview,
			),
			30 * MINUTE_IN_SECONDS
		);

		$redirect_url = add_query_arg(
			array(
				'page'                  => 'sysadmin',
				'sysadmin_preview_token' => $token,
				'sysadmin_status'       => 'success',
				'sysadmin_msg'          => __( 'Preview succesvol opgebouwd. Controleer de rijen en download daarna het bestand.', 'sysadmin' ),
				'sysadmin_notice_nonce' => wp_create_nonce( 'sysadmin_notice' ),
			),
			admin_url( 'tools.php' )
		);

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Handle download action based on prepared preview payload.
	 *
	 * @return void
	 */
	private function handle_download_from_preview() {
		check_admin_referer( 'sysadmin_generate_google_codes', 'sysadmin_google_codes_nonce' );

		$token = isset( $_POST['sysadmin_preview_token'] ) ? sanitize_text_field( wp_unslash( $_POST['sysadmin_preview_token'] ) ) : '';
		if ( '' === $token ) {
			$this->redirect_with_notice( 'error', __( 'Preview token ontbreekt. Bouw eerst een preview op.', 'sysadmin' ) );
		}

		$payload = get_transient( $this->get_preview_transient_key( $token ) );
		if ( ! is_array( $payload ) || empty( $payload['temp_file_path'] ) || ! file_exists( (string) $payload['temp_file_path'] ) ) {
			$this->redirect_with_notice( 'error', __( 'De preview is verlopen. Upload het bestand opnieuw.', 'sysadmin' ) );
		}

		$header_count      = isset( $payload['preview']['headers'] ) && is_array( $payload['preview']['headers'] ) ? count( $payload['preview']['headers'] ) : 0;
		$raw_columns       = isset( $_POST['sysadmin_export_columns'] ) && is_array( $_POST['sysadmin_export_columns'] ) ? wp_unslash( $_POST['sysadmin_export_columns'] ) : array();
		$selected_columns  = array();

		foreach ( $raw_columns as $raw_column_index ) {
			$column_index = absint( $raw_column_index );
			if ( $column_index < $header_count ) {
				$selected_columns[] = $column_index;
			}
		}

		$selected_columns = array_values( array_unique( $selected_columns ) );
		if ( empty( $selected_columns ) ) {
			$this->redirect_with_notice( 'error', __( 'Selecteer minstens één kolom om te exporteren.', 'sysadmin' ) );
		}

		$capitalize_word = isset( $_POST['sysadmin_capitalize_word'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['sysadmin_capitalize_word'] ) );

		$result = $this->google_codes->process_and_output_file(
			(string) $payload['temp_file_path'],
			(string) $payload['input_filename'],
			(string) $payload['suffix'],
			(string) $payload['position'],
			$selected_columns,
			$capitalize_word
		);

		if ( is_wp_error( $result ) ) {
			$this->redirect_with_notice( 'error', $result->get_error_message() );
		}
	}

	/**
	 * Handle invalid rows CSV export based on prepared preview payload.
	 *
	 * @return void
	 */
	private function handle_download_invalid_from_preview() {
		check_admin_referer( 'sysadmin_generate_google_codes', 'sysadmin_google_codes_nonce' );

		$token = isset( $_POST['sysadmin_preview_token'] ) ? sanitize_text_field( wp_unslash( $_POST['sysadmin_preview_token'] ) ) : '';
		if ( '' === $token ) {
			$this->redirect_with_notice( 'error', __( 'Preview token ontbreekt. Bouw eerst een preview op.', 'sysadmin' ) );
		}

		$payload = get_transient( $this->get_preview_transient_key( $token ) );
		if ( ! is_array( $payload ) || empty( $payload['preview']['invalid_rows_all'] ) ) {
			$this->redirect_with_notice( 'error', __( 'Geen ongeldige rijen beschikbaar in de huidige preview.', 'sysadmin' ) );
		}

		$this->output_invalid_rows_csv( $payload['preview']['invalid_rows_all'] );
	}

	/**
	 * Return preview payload when token is present.
	 *
	 * @return array<string, mixed>|null
	 */
	private function get_preview_payload( $is_valid_nonce ) {
		if ( ! $is_valid_nonce ) {
			return null;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Notice nonce was validated before this read.
		$token = isset( $_GET['sysadmin_preview_token'] ) ? sanitize_text_field( wp_unslash( $_GET['sysadmin_preview_token'] ) ) : '';
		if ( '' === $token ) {
			return null;
		}

		$payload = get_transient( $this->get_preview_transient_key( $token ) );
		if ( ! is_array( $payload ) || empty( $payload['preview'] ) ) {
			return null;
		}

		$payload['token'] = $token;

		return $payload;
	}

	/**
	 * Build user-specific preview transient key.
	 *
	 * @param string $token Preview token.
	 * @return string
	 */
	private function get_preview_transient_key( $token ) {
		return 'sysadmin_preview_' . get_current_user_id() . '_' . $token;
	}

	/**
	 * Persist uploaded file into wp-content/uploads temp directory.
	 *
	 * @param array<string, mixed> $file Uploaded file entry.
	 * @return string|WP_Error
	 */
	private function move_upload_to_temp_storage( $file ) {
		$upload_overrides = array(
			'test_form' => false,
			'mimes'     => array(
				'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
				'xls'  => 'application/vnd.ms-excel',
				'csv'  => 'text/csv',
			),
		);

		$uploaded = wp_handle_upload( $file, $upload_overrides );
		if ( ! is_array( $uploaded ) || ! empty( $uploaded['error'] ) || empty( $uploaded['file'] ) ) {
			return new WP_Error( 'sysadmin_upload_failed', __( 'Uploadbestand kon niet veilig worden opgeslagen.', 'sysadmin' ) );
		}

		return (string) $uploaded['file'];
	}

	/**
	 * Validate notice-related query args using a dedicated nonce.
	 *
	 * @return array<string, mixed>
	 */
	private function get_validated_notice_query_args() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce is being read specifically to validate it.
		$notice_nonce = isset( $_GET['sysadmin_notice_nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['sysadmin_notice_nonce'] ) ) : '';
		$is_valid     = '' !== $notice_nonce && wp_verify_nonce( $notice_nonce, 'sysadmin_notice' );

		if ( ! $is_valid ) {
			return array(
				'status'         => '',
				'message'        => '',
				'is_valid_nonce' => false,
			);
		}

		$status = isset( $_GET['sysadmin_status'] ) ? sanitize_key( wp_unslash( $_GET['sysadmin_status'] ) ) : '';
		if ( ! in_array( $status, array( 'success', 'error' ), true ) ) {
			$status = '';
		}

		return array(
			'status'         => $status,
			'message'        => isset( $_GET['sysadmin_msg'] ) ? sanitize_text_field( wp_unslash( $_GET['sysadmin_msg'] ) ) : '',
			'is_valid_nonce' => true,
		);
	}

	/**
	 * Stream invalid rows as CSV file.
	 *
	 * @param array<int, array<string, mixed>> $invalid_rows Invalid row payload.
	 * @return void
	 */
	private function output_invalid_rows_csv( $invalid_rows ) {
		nocache_headers();
		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="google-codes-invalid-' . gmdate( 'Ymd-His' ) . '.csv"' );

		$output = fopen( 'php://output', 'w' );
		if ( false === $output ) {
			exit;
		}

		fputcsv( $output, array( 'row', 'username', 'reason' ) );
		foreach ( $invalid_rows as $invalid_row ) {
			fputcsv(
				$output,
				array(
					isset( $invalid_row['row'] ) ? (string) $invalid_row['row'] : '',
					isset( $invalid_row['username'] ) ? (string) $invalid_row['username'] : '',
					isset( $invalid_row['reason'] ) ? (string) $invalid_row['reason'] : '',
				)
			);
		}

		fflush( $output );
		exit;
	}

	/**
	 * Redirect to plugin page with feedback notice.
	 *
	 * @param string $status success|error.
	 * @param string $message Message text.
	 * @return void
	 */
	private function redirect_with_notice( $status, $message ) {
		$redirect_url = add_query_arg(
			array(
				'page'            => 'sysadmin',
				'sysadmin_status' => $status,
				'sysadmin_msg'    => $message,
				'sysadmin_notice_nonce' => wp_create_nonce( 'sysadmin_notice' ),
			),
			admin_url( 'tools.php' )
		);

		wp_safe_redirect( $redirect_url );
		exit;
	}
}
