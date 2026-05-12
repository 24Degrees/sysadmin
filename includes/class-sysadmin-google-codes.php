<?php
/**
 * Google code generation logic based on source Excel files.
 *
 * @package SysAdminToolbox
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

/**
 * Handles Google code generation from LeerID exports.
 */
class SysAdmin_Google_Codes {

	/**
	 * Header label for generated Google password column.
	 *
	 * @var string
	 */
	private $google_password_header = 'Google wachtwoord';

	/**
	 * Whether to capitalize the extracted word.
	 *
	 * @var bool
	 */
	private $should_capitalize_word = true;

	/**
	 * Locate and load PhpSpreadsheet when available.
	 *
	 * @return bool
	 */
	private function bootstrap_spreadsheet_library() {
		if ( class_exists( '\\PhpOffice\\PhpSpreadsheet\\IOFactory' ) ) {
			return true;
		}

		$autoload_file = SYSADMIN_TOOLBOX_PLUGIN_DIR . 'vendor/autoload.php';
		if ( file_exists( $autoload_file ) ) {
			require_once $autoload_file;
		}

		return class_exists( '\\PhpOffice\\PhpSpreadsheet\\IOFactory' );
	}

	/**
	 * Build new password with chosen suffix placement.
	 *
	 * @param string $base_word Letters-only source word.
	 * @param string $suffix User suffix.
	 * @param string $placement prefix|suffix.
	 * @return string
	 */
	private function build_new_password( $base_word, $suffix, $placement ) {
		if ( $this->should_capitalize_word ) {
			$base_word = $this->capitalize_word( $base_word );
		}

		if ( 'prefix' === $placement ) {
			return $suffix . $base_word;
		}

		return $base_word . $suffix;
	}

	/**
	 * Convert first character to uppercase in a multibyte-safe way.
	 *
	 * @param string $word Input word.
	 * @return string
	 */
	private function capitalize_word( $word ) {
		if ( '' === $word ) {
			return $word;
		}

		if ( function_exists( 'mb_substr' ) && function_exists( 'mb_strtoupper' ) ) {
			$first = mb_substr( $word, 0, 1, 'UTF-8' );
			$rest  = mb_substr( $word, 1, null, 'UTF-8' );

			return mb_strtoupper( $first, 'UTF-8' ) . $rest;
		}

		return ucfirst( $word );
	}

	/**
	 * Convert arbitrary password into letters-only stem.
	 *
	 * @param string $password Original password.
	 * @return string
	 */
	private function extract_word_from_password( $password ) {
		return (string) preg_replace( '/[^[:alpha:]]/u', '', (string) $password );
	}

	/**
	 * Find the column index for LeerID username.
	 *
	 * @param array<int, mixed> $header_row First spreadsheet row.
	 * @return int
	 */
	private function find_username_column_index( $header_row ) {
		foreach ( $header_row as $index => $header_value ) {
			$normalized = strtolower( trim( (string) $header_value ) );
			if ( 'leerid gebruikersnaam' === $normalized ) {
				return (int) $index;
			}
		}

		return -1;
	}

	/**
	 * Find the column index for LeerID password.
	 *
	 * @param array<int, mixed> $header_row First spreadsheet row.
	 * @return int
	 */
	private function find_password_column_index( $header_row ) {
		foreach ( $header_row as $index => $header_value ) {
			$normalized = strtolower( trim( (string) $header_value ) );
			if ( 'leerid wachtwoord' === $normalized ) {
				return (int) $index;
			}
		}

		return -1;
	}

	/**
	 * Analyze a source file and return preview/validation data.
	 *
	 * @param string $input_file_path Absolute file path.
	 * @param string $suffix Password suffix.
	 * @param string $placement prefix|suffix.
	 * @return array<string, mixed>|WP_Error
	 */
	public function build_preview_data( $input_file_path, $suffix, $placement, $capitalize_word = true ) {
		$this->should_capitalize_word = (bool) $capitalize_word;

		if ( ! $this->bootstrap_spreadsheet_library() ) {
			return new WP_Error(
				'sysadmin_missing_spreadsheet_lib',
				__( 'PhpSpreadsheet ontbreekt. Voer in deze pluginmap eerst composer install uit.', 'sysadmin' )
			);
		}

		try {
			$spreadsheet = IOFactory::load( $input_file_path );
		} catch ( Exception $exception ) {
			return new WP_Error(
				'sysadmin_invalid_excel',
				__( 'Het bronbestand kon niet worden gelezen als Excel-bestand.', 'sysadmin' )
			);
		}

		$sheet               = $spreadsheet->getActiveSheet();
		$rows                = $sheet->toArray( null, true, true, false );
		$headers             = isset( $rows[0] ) && is_array( $rows[0] ) ? $rows[0] : array();
		$password_column_idx = isset( $rows[0] ) ? $this->find_password_column_index( $rows[0] ) : -1;
		$username_column_idx = isset( $rows[0] ) ? $this->find_username_column_index( $rows[0] ) : -1;
		$google_column_idx   = count( $headers );

		$headers[] = $this->google_password_header;

		if ( -1 === $password_column_idx ) {
			return new WP_Error(
				'sysadmin_missing_password_column',
				__( 'Kolom "LeerID Wachtwoord" niet gevonden in het bestand.', 'sysadmin' )
			);
		}

		$password_column = Coordinate::stringFromColumnIndex( $password_column_idx + 1 );
		$username_column = -1 === $username_column_idx ? '' : Coordinate::stringFromColumnIndex( $username_column_idx + 1 );

		$preview_rows = array();
		$invalid_rows = array();
		$valid_count  = 0;

		$row_count = count( $rows );
		for ( $row = 2; $row <= $row_count; $row++ ) {
			$password_coordinate = $password_column . $row;
			$original_password   = (string) $sheet->getCell( $password_coordinate )->getValue();
			$username            = '';

			if ( '' !== $username_column ) {
				$username = (string) $sheet->getCell( $username_column . $row )->getValue();
			}

			if ( '' === trim( $original_password ) ) {
				$invalid_rows[] = array(
					'row'      => $row,
					'username' => $username,
					'reason'   => __( 'Ontbrekend wachtwoord.', 'sysadmin' ),
				);
				continue;
			}

			$base_word = $this->extract_word_from_password( $original_password );
			if ( '' === $base_word ) {
				$invalid_rows[] = array(
					'row'      => $row,
					'username' => $username,
					'reason'   => __( 'Wachtwoord bevat geen letters.', 'sysadmin' ),
				);
				continue;
			}

			$valid_count++;
			if ( count( $preview_rows ) < 20 ) {
				$row_values                       = isset( $rows[ $row - 1 ] ) && is_array( $rows[ $row - 1 ] ) ? $rows[ $row - 1 ] : array();
				$row_values[ $google_column_idx ]   = $this->build_new_password( $base_word, $suffix, $placement );

				$preview_rows[] = array(
					'row'    => $row,
					'base_word' => $base_word,
					'values' => $row_values,
				);
			}
		}

		return array(
			'valid_count'   => $valid_count,
			'invalid_count' => count( $invalid_rows ),
			'headers'       => $headers,
			'password_column_index' => $google_column_idx,
			'preview_rows'  => $preview_rows,
			'invalid_rows'  => array_slice( $invalid_rows, 0, 20 ),
			'invalid_rows_all' => $invalid_rows,
		);
	}

	/**
	 * Keep only selected columns in a worksheet.
	 *
	 * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet Worksheet instance.
	 * @param array<int, int>                                  $selected_columns Selected zero-based column indexes.
	 * @param int                                               $highest_column_index Highest 1-based column index.
	 * @return void
	 */
	private function filter_sheet_columns( $sheet, $selected_columns, $highest_column_index ) {
		if ( empty( $selected_columns ) ) {
			return;
		}

		for ( $column_index = $highest_column_index; $column_index >= 1; $column_index-- ) {
			if ( ! in_array( $column_index - 1, $selected_columns, true ) ) {
				$sheet->removeColumn( $column_index, 1 );
			}
		}
	}

	/**
	 * Process uploaded file and stream generated output file.
	 *
	 * @param string $input_file_path Absolute path to uploaded file.
	 * @param string $input_filename Original file name.
	 * @param string $suffix Password suffix.
	 * @param array<int, int> $selected_columns Optional selected zero-based column indexes.
	 * @return true|WP_Error
	 */
	public function process_and_output_file( $input_file_path, $input_filename, $suffix, $placement, $selected_columns = array(), $capitalize_word = true ) {
		$this->should_capitalize_word = (bool) $capitalize_word;

		if ( ! $this->bootstrap_spreadsheet_library() ) {
			return new WP_Error(
			'sysadmin_missing_spreadsheet_lib',
			__( 'PhpSpreadsheet ontbreekt. Voer in deze pluginmap eerst composer install uit.', 'sysadmin' )
			);
		}

		try {
			$spreadsheet = IOFactory::load( $input_file_path );
		} catch ( Exception $exception ) {
			return new WP_Error(
			'sysadmin_invalid_excel',
			__( 'Het bronbestand kon niet worden gelezen als Excel-bestand.', 'sysadmin' )
			);
		}

		$sheet               = $spreadsheet->getActiveSheet();
		$rows                = $sheet->toArray( null, true, true, false );
		$header_count        = isset( $rows[0] ) && is_array( $rows[0] ) ? count( $rows[0] ) : 0;
		$password_column_idx = isset( $rows[0] ) ? $this->find_password_column_index( $rows[0] ) : -1;

		if ( -1 === $password_column_idx ) {
			return new WP_Error(
			'sysadmin_missing_password_column',
			__( 'Kolom "LeerID Wachtwoord" niet gevonden in het bestand.', 'sysadmin' )
			);
		}

		$password_column = Coordinate::stringFromColumnIndex( $password_column_idx + 1 );
		$google_column   = Coordinate::stringFromColumnIndex( $header_count + 1 );

		$sheet->setCellValue( $google_column . '1', $this->google_password_header );

		$row_count = count( $rows );
		for ( $row = 2; $row <= $row_count; $row++ ) {
			$cell_coordinate   = $password_column . $row;
			$original_password = (string) $sheet->getCell( $cell_coordinate )->getValue();
			$base_word         = $this->extract_word_from_password( $original_password );

			if ( '' === $base_word ) {
				continue;
			}

			$sheet->setCellValue( $google_column . $row, $this->build_new_password( $base_word, $suffix, $placement ) );
		}

		if ( ! empty( $selected_columns ) ) {
			$highest_data_column = Coordinate::columnIndexFromString( $sheet->getHighestDataColumn() );
			$highest_column      = max( $highest_data_column, $header_count + 1 );
			$this->filter_sheet_columns( $sheet, $selected_columns, $highest_column );
		}

		$extension        = strtolower( pathinfo( $input_filename, PATHINFO_EXTENSION ) );
		$download_name    = 'google-codes-' . gmdate( 'Ymd-His' ) . '.xlsx';
		$writer_type      = 'Xlsx';
		$allowed_original = array( 'xlsx', 'xls', 'csv' );

		if ( in_array( $extension, $allowed_original, true ) && 'csv' === $extension ) {
			$download_name = 'google-codes-' . gmdate( 'Ymd-His' ) . '.csv';
			$writer_type   = 'Csv';
		}

		$writer = IOFactory::createWriter( $spreadsheet, $writer_type );

		nocache_headers();
		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="' . $download_name . '"' );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate' );
		header( 'Pragma: public' );

		$writer->save( 'php://output' );
		exit;
	}
}
