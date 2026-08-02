<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class SZP_Metaboxes {

	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'boxes' ) );
		add_action( 'save_post_szp_course', array( __CLASS__, 'save_course' ), 10, 2 );
		add_action( 'save_post_szp_session', array( __CLASS__, 'save_session' ), 10, 2 );
	}

	public static function boxes() {
		// Course
		add_meta_box( 'szp_course_info', 'شناسنامه دوره', array( __CLASS__, 'course_info' ), 'szp_course', 'normal', 'high' );
		add_meta_box( 'szp_course_board', 'تابلو اعلانات', array( __CLASS__, 'course_board' ), 'szp_course', 'normal', 'default' );
		add_meta_box( 'szp_course_files', 'فولدر فایل‌ها و منابع', array( __CLASS__, 'course_files' ), 'szp_course', 'normal', 'default' );
		add_meta_box( 'szp_course_survey', 'سوالات نظرسنجی دوره', array( __CLASS__, 'course_survey' ), 'szp_course', 'normal', 'default' );
		add_meta_box( 'szp_course_access', 'دسترسی (کاربران و گروه‌ها)', array( __CLASS__, 'course_access' ), 'szp_course', 'side', 'default' );
		add_meta_box( 'szp_course_woo', 'اتصال محصول ووکامرس', array( __CLASS__, 'course_woo' ), 'szp_course', 'side', 'default' );

		// Session
		add_meta_box( 'szp_session_info', 'شناسنامه و زمان‌بندی جلسه', array( __CLASS__, 'session_info' ), 'szp_session', 'normal', 'high' );
		add_meta_box( 'szp_session_pack', 'توشه دیجیتال جلسه', array( __CLASS__, 'session_pack' ), 'szp_session', 'normal', 'default' );
		add_meta_box( 'szp_session_task', 'تکلیف جلسه', array( __CLASS__, 'session_task' ), 'szp_session', 'normal', 'default' );
		add_meta_box( 'szp_session_check', 'چک‌لیست جلسه', array( __CLASS__, 'session_check' ), 'szp_session', 'normal', 'default' );
		add_meta_box( 'szp_session_survey', 'نظرسنجی ۳ سوالی', array( __CLASS__, 'session_survey' ), 'szp_session', 'normal', 'default' );
	}

	/* ---------------- field renderers ---------------- */

	protected static function nonce( $action ) {
		wp_nonce_field( $action, $action . '_nonce' );
	}

	protected static function text_field( $name, $value, $label, $ph = '' ) {
		printf(
			'<p class="szp-f"><label>%1$s</label><input type="text" name="%2$s" value="%3$s" placeholder="%4$s" class="widefat"></p>',
			esc_html( $label ), esc_attr( $name ), esc_attr( $value ), esc_attr( $ph )
		);
	}

	protected static function textarea_field( $name, $value, $label, $rows = 3 ) {
		printf(
			'<p class="szp-f"><label>%1$s</label><textarea name="%2$s" rows="%4$d" class="widefat">%3$s</textarea></p>',
			esc_html( $label ), esc_attr( $name ), esc_textarea( $value ), (int) $rows
		);
	}

	protected static function media_field( $name, $value, $label ) {
		$fn = $value ? get_the_title( $value ) : '';
		?>
		<p class="szp-f szp-media" data-name="<?php echo esc_attr( $name ); ?>">
			<label><?php echo esc_html( $label ); ?></label>
			<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>">
			<span class="szp-media-name"><?php echo esc_html( $fn ); ?></span>
			<button type="button" class="button szp-media-pick">انتخاب فایل</button>
			<button type="button" class="button szp-media-clear">حذف</button>
		</p>
		<?php
	}

	protected static function media_gallery_field( $name, $value, $label ) {
		$ids = is_array( $value ) ? array_filter( array_map( 'intval', $value ) ) : array();
		?>
		<div class="szp-f szp-gallery" data-name="<?php echo esc_attr( $name ); ?>">
			<label><?php echo esc_html( $label ); ?></label>
			<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( implode( ',', $ids ) ); ?>">
			<div class="szp-gallery-items">
				<?php foreach ( $ids as $id ) : ?>
					<span class="szp-gallery-item" data-id="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( get_the_title( $id ) ); ?> <a href="#" class="szp-gallery-del">×</a></span>
				<?php endforeach; ?>
			</div>
			<button type="button" class="button szp-gallery-pick">+ افزودن فایل</button>
		</div>
		<?php
	}

	protected static function repeater_text( $name, $values, $label, $ph = '' ) {
		$values = is_array( $values ) ? array_filter( $values, 'strlen' ) : array();
		?>
		<div class="szp-rep" data-name="<?php echo esc_attr( $name ); ?>">
			<label><?php echo esc_html( $label ); ?></label>
			<div class="szp-rep-rows">
				<?php foreach ( $values as $v ) : ?>
					<div class="szp-rep-row">
						<input type="text" name="<?php echo esc_attr( $name ); ?>[]" value="<?php echo esc_attr( $v ); ?>" placeholder="<?php echo esc_attr( $ph ); ?>" class="widefat">
						<button type="button" class="button szp-rep-del">×</button>
					</div>
				<?php endforeach; ?>
			</div>
			<button type="button" class="button szp-rep-add">+ افزودن</button>
			<template class="szp-rep-tpl">
				<div class="szp-rep-row">
					<input type="text" name="<?php echo esc_attr( $name ); ?>[]" value="" placeholder="<?php echo esc_attr( $ph ); ?>" class="widefat">
					<button type="button" class="button szp-rep-del">×</button>
				</div>
			</template>
		</div>
		<?php
	}

	protected static function repeater_pair( $name, $values, $label, $ka, $la, $kb, $lb, $b_is_url = false ) {
		$values = is_array( $values ) ? $values : array();
		$btype  = $b_is_url ? 'url' : 'text';
		$a_list = isset( $values[ $ka ] ) ? (array) $values[ $ka ] : array();
		$b_list = isset( $values[ $kb ] ) ? (array) $values[ $kb ] : array();
		$row = function( $a, $b ) use ( $name, $ka, $la, $kb, $lb, $btype ) {
			?>
			<div class="szp-rep-row szp-rep-pair">
				<input type="text" name="<?php echo esc_attr( $name ); ?>[<?php echo esc_attr( $ka ); ?>][]" value="<?php echo esc_attr( $a ); ?>" placeholder="<?php echo esc_attr( $la ); ?>">
				<input type="<?php echo esc_attr( $btype ); ?>" name="<?php echo esc_attr( $name ); ?>[<?php echo esc_attr( $kb ); ?>][]" value="<?php echo esc_attr( $b ); ?>" placeholder="<?php echo esc_attr( $lb ); ?>">
				<button type="button" class="button szp-rep-del">×</button>
			</div>
			<?php
		};
		?>
		<div class="szp-rep szp-rep-pairs" data-name="<?php echo esc_attr( $name ); ?>">
			<label><?php echo esc_html( $label ); ?></label>
			<div class="szp-rep-rows">
				<?php
				$n = max( count( $a_list ), count( $b_list ) );
				for ( $i = 0; $i < $n; $i++ ) {
					$row( $a_list[ $i ] ?? '', $b_list[ $i ] ?? '' );
				}
				?>
			</div>
			<button type="button" class="button szp-rep-add">+ افزودن</button>
			<template class="szp-rep-tpl"><?php $row( '', '' ); ?></template>
		</div>
		<?php
	}

	/* ---------------- course boxes ---------------- */

	public static function course_info( $post ) {
		self::nonce( 'szp_course' );
		self::text_field( '_szp_instructor', get_post_meta( $post->ID, '_szp_instructor', true ), 'نام مدرس' );
		self::syllabus_field( '_szp_syllabus', get_post_meta( $post->ID, '_szp_syllabus', true ) );
		self::textarea_field( '_szp_goals', get_post_meta( $post->ID, '_szp_goals', true ), 'اهداف نهایی دوره', 4 );
		echo '<p class="description">توضیحات کامل دوره را می‌توانید در ویرایشگر اصلی بالا بنویسید.</p>';
	}

	/** Syllabus repeater: each heading has optional sub-items (one per line). */
	public static function syllabus_field( $name, $value ) {
		$items = is_array( $value ) ? $value : array();
		$row   = function ( $title, $subs_text ) use ( $name ) {
			?>
			<div class="szp-rep-row szp-syll-row">
				<input type="text" name="<?php echo esc_attr( $name ); ?>[title][]" value="<?php echo esc_attr( $title ); ?>" placeholder="عنوان سرفصل" class="widefat">
				<textarea name="<?php echo esc_attr( $name ); ?>[subs][]" rows="2" class="widefat" placeholder="زیرمجموعه‌ها — هر مورد در یک خط (اختیاری)"><?php echo esc_textarea( $subs_text ); ?></textarea>
				<button type="button" class="button szp-rep-del">×</button>
			</div>
			<?php
		};
		?>
		<div class="szp-rep" data-name="<?php echo esc_attr( $name ); ?>">
			<label>سرفصل‌ها (هر سرفصل می‌تواند زیرمجموعه داشته باشد)</label>
			<div class="szp-rep-rows">
				<?php foreach ( $items as $it ) {
					if ( is_array( $it ) ) {
						$row( $it['title'] ?? '', implode( "\n", (array) ( $it['subs'] ?? array() ) ) );
					} else {
						$row( (string) $it, '' );
					}
				} ?>
			</div>
			<button type="button" class="button szp-rep-add">+ افزودن سرفصل</button>
			<template class="szp-rep-tpl"><?php $row( '', '' ); ?></template>
		</div>
		<?php
	}

	public static function course_board( $post ) {
		self::repeater_pair( '_szp_announce', get_post_meta( $post->ID, '_szp_announce', true ),
			'اعلانات کلاس', 'text', 'متن اعلان', 'date', 'تاریخ (اختیاری)' );
	}

	public static function course_survey( $post ) {
		self::repeater_text( '_szp_course_survey', get_post_meta( $post->ID, '_szp_course_survey', true ), 'سوالات (هر سوال با امتیاز ۱ تا ۵ پاسخ داده می‌شود)', 'مثلاً: کیفیت کلی دوره را چگونه ارزیابی می‌کنید؟' );
	}

	public static function course_files( $post ) {
		self::media_gallery_field( '_szp_files', get_post_meta( $post->ID, '_szp_files', true ), 'فایل‌ها و جزوات (کتابخانه رسانه)' );
		self::repeater_pair( '_szp_links', get_post_meta( $post->ID, '_szp_links', true ),
			'لینک‌های معرفی‌شده', 'label', 'عنوان لینک', 'url', 'https://', true );
	}

	public static function course_access( $post ) {
		$users  = SZP_Access::manual_users( $post->ID );
		$groups = SZP_Access::manual_groups( $post->ID );
		echo '<div class="szp-access">';
		echo '<label>کاربران مجاز</label>';
		echo '<div class="szp-user-search" data-target="_szp_access_users">';
		echo '<input type="text" class="widefat szp-user-q" placeholder="جستجوی کاربر (نام یا ایمیل)...">';
		echo '<div class="szp-user-results"></div>';
		echo '<div class="szp-user-chips">';
		foreach ( $users as $uid ) {
			$u = get_userdata( $uid );
			if ( ! $u ) {
				continue;
			}
			printf(
				'<span class="szp-chip" data-id="%1$d"><input type="hidden" name="_szp_access_users[]" value="%1$d">%2$s <a href="#" class="szp-chip-del">×</a></span>',
				(int) $uid, esc_html( $u->display_name . ' (' . $u->user_email . ')' )
			);
		}
		echo '</div></div>';

		$all = SZP_Groups::all();
		if ( $all ) {
			echo '<label style="margin-top:12px;display:block">گروه‌های مجاز</label>';
			echo '<div class="szp-group-list">';
			foreach ( $all as $g ) {
				printf(
					'<label class="szp-gl"><input type="checkbox" name="_szp_access_groups[]" value="%1$d" %3$s> %2$s</label>',
					(int) $g->id, esc_html( $g->name ), checked( in_array( (int) $g->id, $groups, true ), true, false )
				);
			}
			echo '</div>';
		} else {
			echo '<p class="description">هنوز گروهی ساخته نشده است.</p>';
		}
		echo '</div>';
	}

	public static function course_woo( $post ) {
		$products = szp_get_wc_products();
		if ( ! $products ) {
			echo '<p class="description">ووکامرس فعال نیست یا محصولی وجود ندارد.</p>';
			return;
		}
		$selected = (array) get_post_meta( $post->ID, '_szp_products', true );
		echo '<label>محصولات مرتبط (با خرید، دسترسی خودکار داده می‌شود)</label>';
		echo '<select name="_szp_products[]" multiple class="widefat" style="height:150px">';
		foreach ( $products as $pid => $title ) {
			printf( '<option value="%1$d" %3$s>%2$s</option>', (int) $pid, esc_html( $title ),
				selected( in_array( (int) $pid, array_map( 'intval', $selected ), true ), true, false ) );
		}
		echo '</select>';
		echo '<p class="description">برای انتخاب چند مورد، کلید Ctrl را نگه دارید.</p>';
	}

	/* ---------------- session boxes ---------------- */

	public static function session_info( $post ) {
		self::nonce( 'szp_session' );
		$cid     = (int) get_post_meta( $post->ID, '_szp_course_id', true );
		$courses = get_posts( array( 'post_type' => 'szp_course', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
		echo '<p class="szp-f"><label>دوره مرتبط</label><select name="_szp_course_id" class="widefat"><option value="">— انتخاب دوره —</option>';
		foreach ( $courses as $c ) {
			printf( '<option value="%1$d" %3$s>%2$s</option>', (int) $c->ID, esc_html( $c->post_title ), selected( $cid, $c->ID, false ) );
		}
		echo '</select></p>';

		$dt = get_post_meta( $post->ID, '_szp_datetime', true );
		echo '<div class="szp-f"><label>تاریخ و ساعت جلسه (تقویم شمسی)</label>';
		echo '<div class="szp-jdt">';
		printf( '<input type="hidden" class="szp-jdt-value" name="_szp_datetime" value="%s">', esc_attr( $dt ) );
		echo '<input type="text" class="szp-jdt-display widefat" readonly placeholder="برای انتخاب کلیک کنید...">';
		echo '</div></div>';

		self::text_field( '_szp_topic', get_post_meta( $post->ID, '_szp_topic', true ), 'موضوع جلسه', 'مثلاً: تکنیک‌های یخ‌شکنی در فروش' );
		self::repeater_text( '_szp_skeys', get_post_meta( $post->ID, '_szp_skeys', true ), 'اهداف کلیدی جلسه', 'مثلاً: تسلط بر شروع مکالمه' );
		echo '<p class="description">توضیح کامل جلسه را می‌توانید در ویرایشگر اصلی بالا بنویسید.</p>';
	}

	public static function session_pack( $post ) {
		self::audio_parts_field( '_szp_audio_parts', get_post_meta( $post->ID, '_szp_audio_parts', true ), (int) get_post_meta( $post->ID, '_szp_audio', true ) );
		self::media_field( '_szp_pdf', get_post_meta( $post->ID, '_szp_pdf', true ), 'فایل PDF اسلایدها (کتابخانه رسانه)' );
		self::text_field( '_szp_pdf_url', get_post_meta( $post->ID, '_szp_pdf_url', true ), 'یا لینک مستقیم PDF (هاست دانلود)', 'https://' );
		self::media_gallery_field( '_szp_sfiles', get_post_meta( $post->ID, '_szp_sfiles', true ), 'فایل‌های تکمیلی (کتابخانه رسانه)' );
		self::repeater_pair( '_szp_sfile_links', get_post_meta( $post->ID, '_szp_sfile_links', true ),
			'فایل‌های تکمیلی از طریق لینک', 'label', 'عنوان فایل', 'url', 'https://', true );
	}

	/** Multipart audio: each row is title + url (media library or direct download-host link). */
	protected static function audio_parts_field( $name, $value, $legacy_id = 0 ) {
		$value  = is_array( $value ) ? $value : array();
		$titles = isset( $value['title'] ) ? (array) $value['title'] : array();
		$urls   = isset( $value['url'] ) ? (array) $value['url'] : array();
		// migrate legacy single audio for display if no parts saved yet
		if ( ! $urls && $legacy_id && ( $lu = wp_get_attachment_url( $legacy_id ) ) ) {
			$titles = array( 'پارت ۱' );
			$urls   = array( $lu );
		}
		$row = function ( $title, $url ) use ( $name ) {
			?>
			<div class="szp-rep-row szp-audio-row">
				<input type="text" name="<?php echo esc_attr( $name ); ?>[title][]" value="<?php echo esc_attr( $title ); ?>" placeholder="عنوان پارت (مثلاً: بخش ۱)">
				<input type="url" class="szp-audio-url" name="<?php echo esc_attr( $name ); ?>[url][]" value="<?php echo esc_attr( $url ); ?>" placeholder="لینک مستقیم فایل صوتی" style="flex:1">
				<button type="button" class="button szp-media-url-pick">رسانه</button>
				<button type="button" class="button szp-rep-del">×</button>
			</div>
			<?php
		};
		?>
		<div class="szp-rep szp-rep-pairs szp-audio-parts" data-name="<?php echo esc_attr( $name ); ?>">
			<label>پارت‌های صوتی جلسه (هر جلسه می‌تواند چند پارت داشته باشد)</label>
			<div class="szp-rep-rows">
				<?php
				$n = max( count( $titles ), count( $urls ) );
				for ( $i = 0; $i < $n; $i++ ) {
					$row( $titles[ $i ] ?? '', $urls[ $i ] ?? '' );
				}
				?>
			</div>
			<button type="button" class="button szp-rep-add">+ افزودن پارت صوتی</button>
			<template class="szp-rep-tpl"><?php $row( '', '' ); ?></template>
			<p class="description">می‌توانید از کتابخانه رسانه انتخاب کنید یا لینک مستقیم فایل روی هاست دانلود را وارد کنید. ترتیب پارت‌ها از بالا به پایین حفظ می‌شود.</p>
		</div>
		<?php
	}

	public static function session_task( $post ) {
		// Link to a sazan_quiz post (exercise / assignment / exam) if that post type exists.
		echo '<p class="szp-f"><label>اتصال به آزمون/تمرین (sazan_quiz)</label>';
		if ( post_type_exists( 'sazan_quiz' ) ) {
			$qid     = (int) get_post_meta( $post->ID, '_szp_quiz_id', true );
			$quizzes = get_posts( array( 'post_type' => 'sazan_quiz', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
			echo '<select name="_szp_quiz_id" class="widefat"><option value="0">— بدون اتصال —</option>';
			foreach ( $quizzes as $q ) {
				printf( '<option value="%1$d" %3$s>%2$s</option>', (int) $q->ID, esc_html( $q->post_title ), selected( $qid, $q->ID, false ) );
			}
			echo '</select>';
			echo '<span class="description">اگر آزمون/تمرینی انتخاب شود، در صفحه‌ی جلسه دکمه‌ی ورود به آن نمایش داده می‌شود.</span>';
		} else {
			echo '<span class="description">پست‌تایپ <code>sazan_quiz</code> فعال نیست. پس از فعال‌سازی افزونه‌ی آزمون، این گزینه ظاهر می‌شود.</span>';
		}
		echo '</p>';

		$val = get_post_meta( $post->ID, '_szp_task', true );
		echo '<p class="szp-f"><label>صورت تمرین (متن دلخواه، اختیاری)</label></p>';
		wp_editor( $val, 'szp_task_editor', array( 'textarea_name' => '_szp_task', 'media_buttons' => false, 'textarea_rows' => 6, 'teeny' => true ) );
	}

	public static function session_check( $post ) {
		self::repeater_text( '_szp_checklist', get_post_meta( $post->ID, '_szp_checklist', true ), 'موارد چک‌لیست (تا قبل از جلسه بعدی)', 'مثلاً: تمرین پرسونا را کامل کن' );
	}

	public static function session_survey( $post ) {
		self::repeater_text( '_szp_survey', get_post_meta( $post->ID, '_szp_survey', true ), 'سوالات نظرسنجی', 'مثلاً: کیفیت تدریس مدرس را چطور ارزیابی می‌کنید؟' );
	}

	/* ---------------- save ---------------- */

	public static function save_course( $post_id, $post ) {
		if ( ! isset( $_POST['szp_course_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['szp_course_nonce'] ) ), 'szp_course' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		update_post_meta( $post_id, '_szp_instructor', sanitize_text_field( wp_unslash( $_POST['_szp_instructor'] ?? '' ) ) );
		update_post_meta( $post_id, '_szp_goals', sanitize_textarea_field( wp_unslash( $_POST['_szp_goals'] ?? '' ) ) );
		update_post_meta( $post_id, '_szp_syllabus', self::clean_syllabus( $_POST['_szp_syllabus'] ?? array() ) );
		update_post_meta( $post_id, '_szp_course_survey', self::clean_list( $_POST['_szp_course_survey'] ?? array() ) );
		update_post_meta( $post_id, '_szp_announce', self::clean_pair( $_POST['_szp_announce'] ?? array(), 'text', 'date' ) );
		update_post_meta( $post_id, '_szp_files', self::clean_ids( $_POST['_szp_files'] ?? '' ) );
		update_post_meta( $post_id, '_szp_links', self::clean_pair( $_POST['_szp_links'] ?? array(), 'label', 'url', true ) );

		$prod = isset( $_POST['_szp_products'] ) ? array_map( 'intval', (array) $_POST['_szp_products'] ) : array();
		update_post_meta( $post_id, '_szp_products', $prod );

		$users  = isset( $_POST['_szp_access_users'] ) ? array_map( 'intval', (array) $_POST['_szp_access_users'] ) : array();
		$groups = isset( $_POST['_szp_access_groups'] ) ? array_map( 'intval', (array) $_POST['_szp_access_groups'] ) : array();
		SZP_Access::sync_manual( $post_id, $users, $groups );
	}

	public static function save_session( $post_id, $post ) {
		if ( ! isset( $_POST['szp_session_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['szp_session_nonce'] ) ), 'szp_session' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		update_post_meta( $post_id, '_szp_course_id', absint( $_POST['_szp_course_id'] ?? 0 ) );
		update_post_meta( $post_id, '_szp_datetime', sanitize_text_field( wp_unslash( $_POST['_szp_datetime'] ?? '' ) ) );
		update_post_meta( $post_id, '_szp_topic', sanitize_text_field( wp_unslash( $_POST['_szp_topic'] ?? '' ) ) );
		update_post_meta( $post_id, '_szp_skeys', self::clean_list( $_POST['_szp_skeys'] ?? array() ) );
		update_post_meta( $post_id, '_szp_pdf', absint( $_POST['_szp_pdf'] ?? 0 ) );
		update_post_meta( $post_id, '_szp_pdf_url', esc_url_raw( wp_unslash( $_POST['_szp_pdf_url'] ?? '' ) ) );
		update_post_meta( $post_id, '_szp_audio_parts', self::clean_pair( $_POST['_szp_audio_parts'] ?? array(), 'title', 'url', true ) );
		update_post_meta( $post_id, '_szp_quiz_id', absint( $_POST['_szp_quiz_id'] ?? 0 ) );
		update_post_meta( $post_id, '_szp_sfiles', self::clean_ids( $_POST['_szp_sfiles'] ?? '' ) );
		update_post_meta( $post_id, '_szp_sfile_links', self::clean_pair( $_POST['_szp_sfile_links'] ?? array(), 'label', 'url', true ) );
		update_post_meta( $post_id, '_szp_task', wp_kses_post( wp_unslash( $_POST['_szp_task'] ?? '' ) ) );
		update_post_meta( $post_id, '_szp_checklist', self::clean_list( $_POST['_szp_checklist'] ?? array() ) );
		update_post_meta( $post_id, '_szp_survey', self::clean_list( $_POST['_szp_survey'] ?? array() ) );
	}

	/* ---------------- sanitizers ---------------- */

	protected static function clean_list( $arr ) {
		$arr = (array) wp_unslash( $arr );
		$out = array();
		foreach ( $arr as $v ) {
			$v = sanitize_text_field( $v );
			if ( $v !== '' ) {
				$out[] = $v;
			}
		}
		return $out;
	}

	protected static function clean_pair( $data, $ka, $kb, $b_url = false ) {
		$data = (array) wp_unslash( $data );
		$a = isset( $data[ $ka ] ) ? (array) $data[ $ka ] : array();
		$b = isset( $data[ $kb ] ) ? (array) $data[ $kb ] : array();
		$n = max( count( $a ), count( $b ) );
		$out = array( $ka => array(), $kb => array() );
		for ( $i = 0; $i < $n; $i++ ) {
			$av = sanitize_text_field( $a[ $i ] ?? '' );
			$bv = $b_url ? esc_url_raw( $b[ $i ] ?? '' ) : sanitize_text_field( $b[ $i ] ?? '' );
			if ( $av === '' && $bv === '' ) {
				continue;
			}
			$out[ $ka ][] = $av;
			$out[ $kb ][] = $bv;
		}
		return $out;
	}

	protected static function clean_ids( $csv ) {
		$csv = wp_unslash( $csv );
		$ids = array_filter( array_map( 'absint', explode( ',', (string) $csv ) ) );
		return array_values( array_unique( $ids ) );
	}

	/** Nested syllabus: [ ['title'=>..,'subs'=>[..]], ... ]. */
	protected static function clean_syllabus( $data ) {
		$data    = (array) wp_unslash( $data );
		$titles  = isset( $data['title'] ) ? (array) $data['title'] : array();
		$subsraw = isset( $data['subs'] ) ? (array) $data['subs'] : array();
		$out     = array();
		foreach ( $titles as $i => $t ) {
			$t     = sanitize_text_field( $t );
			$lines = preg_split( '/\r\n|\r|\n/', (string) ( $subsraw[ $i ] ?? '' ) );
			$subs  = array();
			foreach ( $lines as $ln ) {
				$ln = sanitize_text_field( $ln );
				if ( $ln !== '' ) {
					$subs[] = $ln;
				}
			}
			if ( $t === '' && ! $subs ) {
				continue;
			}
			$out[] = array( 'title' => $t, 'subs' => $subs );
		}
		return $out;
	}
}
