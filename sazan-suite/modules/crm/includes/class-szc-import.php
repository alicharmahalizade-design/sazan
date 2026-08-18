<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** ایمپورت انبوه مخاطبین از فایل CSV با نگاشت ستون‌ها و حذف تکراری بر اساس موبایل. */
class SZC_Import {

	const MAX_ROWS = 20000;

	/** فیلدهای قابل ایمپورت: key => label. */
	public static function fields() {
		return array(
			'mobile'     => 'موبایل (الزامی)',
			'first_name' => 'نام',
			'last_name'  => 'نام خانوادگی',
			'job'        => 'شغل',
			'company'    => 'شرکت',
			'city'       => 'شهر',
			'email'      => 'ایمیل',
			'source'     => 'منبع',
			'campaign'   => 'کمپین',
			'expected_value' => 'ارزش احتمالی',
			'tags'       => 'برچسب‌ها',
		);
	}

	protected static function dir() {
		$up  = wp_upload_dir();
		$dir = trailingslashit( $up['basedir'] ) . 'szc-imports';
		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
			@file_put_contents( $dir . '/.htaccess', "Deny from all\n" );
			@file_put_contents( $dir . '/index.php', "<?php\n// Silence is golden.\n" );
		}
		return $dir;
	}

	/** جابه‌جایی فایل آپلودشده به پوشه‌ی امن. خروجی: array( ok, file|msg ). */
	public static function store_upload( $file ) {
		if ( empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
			return array( 'ok' => false, 'msg' => 'فایلی آپلود نشد.' );
		}
		$ext = strtolower( pathinfo( (string) $file['name'], PATHINFO_EXTENSION ) );
		if ( ! in_array( $ext, array( 'csv', 'txt' ), true ) ) {
			return array( 'ok' => false, 'msg' => 'فقط فایل CSV پذیرفته می‌شود. (اکسل را با فرمت CSV ذخیره کنید)' );
		}
		$name = 'imp-' . wp_generate_password( 12, false ) . '.csv';
		$path = self::dir() . '/' . $name;
		if ( ! @move_uploaded_file( $file['tmp_name'], $path ) ) {
			return array( 'ok' => false, 'msg' => 'انتقال فایل ناموفق بود.' );
		}
		return array( 'ok' => true, 'file' => $name );
	}

	protected static function path( $file ) {
		$file = basename( (string) $file ); // جلوگیری از پیمایش مسیر
		$path = self::dir() . '/' . $file;
		return ( $file !== '' && file_exists( $path ) ) ? $path : '';
	}

	protected static function fix_encoding( $s ) {
		if ( $s === '' || function_exists( 'mb_check_encoding' ) === false ) {
			return $s;
		}
		if ( ! mb_check_encoding( $s, 'UTF-8' ) ) {
			$conv = @mb_convert_encoding( $s, 'UTF-8', 'Windows-1256' );
			if ( $conv !== false ) {
				return $conv;
			}
		}
		return $s;
	}

	/** خواندن سرستون‌ها + چند ردیف اول برای پیش‌نمایش. */
	public static function preview( $file, $delimiter = ',', $rows = 5 ) {
		$path = self::path( $file );
		if ( $path === '' ) {
			return array( 'ok' => false, 'msg' => 'فایل یافت نشد.' );
		}
		$fh = fopen( $path, 'r' );
		if ( ! $fh ) {
			return array( 'ok' => false, 'msg' => 'باز کردن فایل ناموفق بود.' );
		}
		$header = fgetcsv( $fh, 0, $delimiter );
		if ( ! $header ) {
			fclose( $fh );
			return array( 'ok' => false, 'msg' => 'فایل خالی است.' );
		}
		$header = array_map( array( __CLASS__, 'fix_encoding' ), $header );
		// حذف BOM از اولین سلول.
		if ( isset( $header[0] ) ) {
			$header[0] = preg_replace( '/^\xEF\xBB\xBF/', '', $header[0] );
		}
		$sample = array();
		$i = 0;
		while ( $i < $rows && ( $r = fgetcsv( $fh, 0, $delimiter ) ) !== false ) {
			$sample[] = array_map( array( __CLASS__, 'fix_encoding' ), $r );
			$i++;
		}
		fclose( $fh );
		return array( 'ok' => true, 'header' => $header, 'sample' => $sample );
	}

	/**
	 * اجرای ایمپورت.
	 * $mapping: field_key => column_index (یا -1 برای نادیده‌گرفتن).
	 * $opts: has_header(bool)، delimiter، source، tags، priority، update_existing(bool).
	 */
	public static function run( $file, $mapping, $opts = array() ) {
		$path = self::path( $file );
		if ( $path === '' ) {
			return array( 'ok' => false, 'msg' => 'فایل یافت نشد.' );
		}
		$o = wp_parse_args( $opts, array(
			'has_header'      => true,
			'delimiter'       => ',',
			'source'          => '',
			'tags'            => '',
			'priority'        => 'warm',
			'update_existing' => true,
		) );
		if ( ! isset( $mapping['mobile'] ) || (int) $mapping['mobile'] < 0 ) {
			return array( 'ok' => false, 'msg' => 'ستون موبایل را مشخص کنید.' );
		}
		@set_time_limit( 0 );

		$fh = fopen( $path, 'r' );
		if ( ! $fh ) {
			return array( 'ok' => false, 'msg' => 'باز کردن فایل ناموفق بود.' );
		}
		$stats = array( 'created' => 0, 'updated' => 0, 'invalid' => 0, 'skipped' => 0, 'total' => 0 );
		if ( $o['has_header'] ) {
			fgetcsv( $fh, 0, $o['delimiter'] ); // رد کردن سرستون
		}
		$fields = array_keys( self::fields() );

		while ( ( $row = fgetcsv( $fh, 0, $o['delimiter'] ) ) !== false ) {
			if ( $stats['total'] >= self::MAX_ROWS ) {
				$stats['capped'] = true;
				break;
			}
			$stats['total']++;
			$in = array();
			foreach ( $fields as $f ) {
				$idx = isset( $mapping[ $f ] ) ? (int) $mapping[ $f ] : -1;
				$in[ $f ] = ( $idx >= 0 && isset( $row[ $idx ] ) ) ? self::fix_encoding( trim( (string) $row[ $idx ] ) ) : '';
			}
			$mobile = szc_normalize_mobile( $in['mobile'] );
			if ( ! szc_is_valid_mobile( $mobile ) ) {
				$stats['invalid']++;
				continue;
			}
			$in['mobile']   = $mobile;
			$in['priority'] = $o['priority'];
			$in['stage']    = 'new';
			if ( $in['source'] === '' ) { $in['source'] = $o['source']; }
			if ( $in['tags'] === '' )   { $in['tags'] = $o['tags']; }

			$res = SZC_Contacts::upsert_by_mobile( $in, (bool) $o['update_existing'] );
			$stats[ $res ] = ( $stats[ $res ] ?? 0 ) + 1;
		}
		fclose( $fh );
		@unlink( $path );

		return array( 'ok' => true, 'stats' => $stats );
	}
}
