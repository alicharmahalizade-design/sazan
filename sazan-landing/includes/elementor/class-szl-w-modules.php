<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

use Elementor\Controls_Manager;
use Elementor\Repeater;

/** سرفصل‌های آموزشی: کارت‌های شماره‌دار با دسته، عنوان، متن، لیست و جمله تأکیدی. */
class SZL_W_Modules extends SZL_Widget_Base {

	public function get_name() { return 'szl_modules'; }
	public function get_title() { return 'لندینگ: سرفصل‌های دوره'; }
	public function get_icon() { return 'eicon-post-list'; }

	protected function content_controls() {
		$this->start_controls_section( 'c_head', array( 'label' => 'سرتیتر' ) );
		$this->ctl_heading( array(
			'eyebrow'  => 'WHAT YOU LEARN',
			'title'    => 'در این دوره چه خواهید آموخت؟',
			'subtitle' => 'هفت محور اصلی برای دیدن دقیق‌تر بازار، مشتری، فروش و سازمان',
		) );
		$this->end_controls_section();

		$this->start_controls_section( 'c_items', array( 'label' => 'سرفصل‌ها' ) );

		$r = new Repeater();
		$r->add_control( 'cat', array(
			'label'       => 'دسته',
			'type'        => Controls_Manager::TEXT,
			'label_block' => true,
		) );
		$r->add_control( 'title', array(
			'label'       => 'عنوان سرفصل',
			'type'        => Controls_Manager::TEXT,
			'label_block' => true,
		) );
		$r->add_control( 'body', array(
			'label'       => 'متن (هر پاراگراف در یک خط)',
			'type'        => Controls_Manager::TEXTAREA,
			'rows'        => 6,
			'label_block' => true,
		) );
		$r->add_control( 'list', array(
			'label'       => 'لیست (هر مورد در یک خط)',
			'type'        => Controls_Manager::TEXTAREA,
			'rows'        => 5,
			'label_block' => true,
		) );
		$r->add_control( 'marker', array(
			'label'   => 'نشانه لیست',
			'type'    => Controls_Manager::SELECT,
			'default' => 'dot',
			'options' => array( 'dot' => 'نقطه •', 'check' => 'تیک ✓', 'cross' => 'ضربدر ✕', 'dash' => 'خط تیره —', 'none' => 'بدون نشانه' ),
		) );
		$r->add_control( 'body2', array(
			'label'       => 'متن بعد از لیست',
			'type'        => Controls_Manager::TEXTAREA,
			'rows'        => 3,
			'label_block' => true,
		) );
		$r->add_control( 'punch', array(
			'label'       => 'جمله تأکیدی پایانی',
			'type'        => Controls_Manager::TEXTAREA,
			'rows'        => 2,
			'label_block' => true,
		) );

		$this->add_control( 'items', array(
			'label'       => 'سرفصل‌ها',
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $r->get_controls(),
			'title_field' => '{{{ cat }}} — {{{ title }}}',
			'default'     => $this->seed(),
		) );

		$this->add_control( 'numbers', array(
			'label'        => 'نمایش شماره سرفصل',
			'type'         => Controls_Manager::SWITCHER,
			'default'      => 'yes',
			'return_value' => 'yes',
		) );
		$this->add_control( 'digits', array(
			'label'     => 'نوع ارقام',
			'type'      => Controls_Manager::SELECT,
			'default'   => 'fa',
			'options'   => array( 'fa' => 'فارسی (۰۱)', 'en' => 'لاتین (01)' ),
			'condition' => array( 'numbers' => 'yes' ),
		) );

		$this->end_controls_section();
	}

	private function seed() {
		return array(
			array(
				'cat' => 'نگرش مدیریتی', 'title' => 'نگرش ثروت‌زا',
				'body' => "چرا دو مدیر در یک بازار مشابه، با شرایط مشابه و حتی سرمایه مشابه می‌توانند نتایج کاملاً متفاوتی بسازند؟\nچون قبل از استراتژی یک چیز وجود دارد: <b>نوع نگاه مدیر</b>.\nدر این بخش روی نقطه‌ای کار می‌کنیم که بسیاری از تصمیم‌های بزرگ کسب‌وکار از آنجا آغاز می‌شوند: ذهن و نگرش مدیر.",
				'punch' => 'چون مدیری که فرصت را نمی‌بیند، نمی‌تواند آن را خلق کند.',
			),
			array(
				'cat' => 'استراتژی و رقابت', 'title' => 'خلق مزیت رقابتی',
				'body' => 'اگر مشتری از شما بپرسد «چرا باید از شما بخرم؟» چه پاسخی دارید؟',
				'list' => "«کیفیت ما خوب است»\n«سابقه زیادی داریم»\n«قیمت ما مناسب است»",
				'marker' => 'cross',
				'body2' => 'احتمالاً رقیب شما هم همین جملات را می‌گوید. سؤال اصلی این است: چه دلیلی به مشتری داده‌اید که از میان شما و رقبا، شما را انتخاب کند؟',
				'punch' => 'تا کسب‌وکار شما فقط یکی از گزینه‌های بازار نباشد؛ دلیلی برای انتخاب‌شدن داشته باشد.',
			),
			array(
				'cat' => 'فروش', 'title' => 'هدف‌گذاری حرفه‌ای برای افزایش فروش',
				'body' => "«امسال باید بیشتر بفروشیم» هدف نیست.\nخیلی از کسب‌وکارها عدد فروش دارند اما مسیر رسیدن به آن را ندارند: عدد تعیین می‌شود، به تیم اعلام می‌شود، فشار افزایش پیدا می‌کند، جلسات بیشتر می‌شود؛ اما الزاماً فروش بیشتر نمی‌شود.\nدر این بخش یاد می‌گیرید هدف فروش را از یک آرزو و عدد روی کاغذ به یک مسیر مشخص برای اقدام تبدیل کنید.",
				'punch' => 'چون هدفی که نتوان آن را به اقدام تبدیل کرد، فقط یک عدد است.',
			),
			array(
				'cat' => 'ارتباط با مشتری', 'title' => 'چگونه در ۳ ثانیه اول ارتباط مؤثر برقرار کنیم؟',
				'body' => 'مشتری قبل از اینکه محصولتان را بخرد، شما را ارزیابی می‌کند. گاهی فقط چند ثانیه برای ساختن اولین تصویر ذهنی فرصت دارید.',
				'list' => "نحوه ورود به ارتباط\nلحن\nرفتار\nکلام\nو تصویری که از خودتان می‌سازید",
				'body2' => 'در این بخش یاد می‌گیرید چگونه از همان لحظات ابتدایی، ارتباط حرفه‌ای‌تر و اثرگذارتر ظاهر شوید.',
				'punch' => 'چون گاهی قبل از اینکه فرصت کنید از کیفیت محصولتان بگویید، مشتری درباره خودِ شما تصمیم گرفته است.',
			),
			array(
				'cat' => 'مشتری‌شناسی', 'title' => 'انواع مشتریان و کشف نیاز واقعی مشتری',
				'body' => 'همه مشتری‌ها را نمی‌شود با یک نسخه فروخت:',
				'list' => "یک مشتری دنبال اطمینان است\nیکی سرعت می‌خواهد\nیکی روی قیمت حساس است\nیکی جزئیات می‌خواهد\nو دیگری قبل از هر تصمیمی باید اعتماد کند",
				'body2' => 'پس چرا باید با همه یک شکل رفتار کنیم؟ در این بخش یاد می‌گیرید مشتری را بهتر بشناسید و مهم‌تر از آن کشف کنید: پشت چیزی که مشتری «می‌گوید»، واقعاً چه چیزی «می‌خواهد»؟',
				'punch' => 'چون وقتی نیاز را اشتباه تشخیص دهید، حتی بهترین پیشنهاد هم ممکن است برای مشتری بی‌معنا باشد.',
			),
			array(
				'cat' => 'مذاکره', 'title' => 'اصول و فنون مذاکره',
				'body' => "مذاکره جنگ بر سر قیمت نیست. بسیاری از مذاکرات زمانی به تخفیف می‌رسند که فروشنده ابزار دیگری برای پیش‌برد گفت‌وگو ندارد.\nاما در یک مذاکره حرفه‌ای باید بدانید:",
				'list' => "چه زمانی صحبت کنید\nچه زمانی سؤال بپرسید\nکجا گوش کنید\nچگونه ارزش پیشنهادتان را حفظ کنید\nو چطور مذاکره را به سمت یک نتیجه مطلوب هدایت کنید",
				'punch' => 'تا مذاکره برای شما مسابقه‌ای برای «ارزان‌تر فروختن» نباشد.',
			),
			array(
				'cat' => 'خدمات مشتری', 'title' => 'بوم طراحی خدمات به مشتری',
				'body' => "فروش پایان رابطه نیست. مشتری خرید کرده است؛ بعدش چه؟\nتجربه‌ای که مشتری پس از خرید از شما دریافت می‌کند می‌تواند مشخص کند:",
				'list' => "دوباره برگردد\nشما را به دیگران معرفی کند\nیا برای همیشه از برندتان فاصله بگیرد",
				'punch' => 'در این بخش خدمات مشتری را از یک اتفاق تصادفی به یک فرآیند طراحی‌شده تبدیل می‌کنیم.',
			),
		);
	}

	protected function style_controls() {
		$this->sty_heading();

		$this->start_controls_section( 's_card', array(
			'label' => 'استایل کارت سرفصل',
			'tab'   => Controls_Manager::TAB_STYLE,
		) );
		$this->sty_columns( 'mod', 'تعداد ستون', '.szl-modules', 2, 4 );
		$this->sty_box( 'mod', 'کارت', '.szl-module' );
		$this->add_control( 'mod_hover_border', array(
			'label'     => 'رنگ حاشیه در هاور',
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .szl-module:hover' => 'border-color: {{VALUE}};' ),
		) );
		$this->end_controls_section();

		$this->start_controls_section( 's_txt', array(
			'label' => 'استایل متن سرفصل',
			'tab'   => Controls_Manager::TAB_STYLE,
		) );
		$this->sty_text( 'modn', 'شماره', '.szl-mod-n' );
		$this->add_control( 'modn_stroke', array(
			'label'        => 'شماره تو‌خالی',
			'type'         => Controls_Manager::SWITCHER,
			'default'      => 'yes',
			'return_value' => 'yes',
		) );
		$this->add_control( 'hr1', array( 'type' => Controls_Manager::DIVIDER ) );
		$this->sty_text( 'modcat', 'دسته', '.szl-mod-cat' );
		$this->sty_box( 'modcat', 'دسته', '.szl-mod-cat', array( 'no_shadow' => true ) );
		$this->add_control( 'hr2', array( 'type' => Controls_Manager::DIVIDER ) );
		$this->sty_text( 'modtitle', 'عنوان', '.szl-mod-title', array( 'margin' => true ) );
		$this->sty_text( 'modbody', 'متن', '.szl-module p:not(.szl-mod-punch)' );
		$this->sty_text( 'modlist', 'لیست', '.szl-module .szl-list li' );
		$this->add_control( 'modlist_marker', array(
			'label'     => 'رنگ نشانه لیست',
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .szl-module .szl-list li::before' => 'color: {{VALUE}};' ),
		) );
		$this->add_control( 'hr3', array( 'type' => Controls_Manager::DIVIDER ) );
		$this->sty_text( 'modpunch', 'جمله تأکیدی', '.szl-mod-punch' );
		$this->add_control( 'punch_line', array(
			'label'     => 'رنگ خط بالای جمله تأکیدی',
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .szl-mod-punch' => 'border-top-color: {{VALUE}};' ),
		) );
		$this->end_controls_section();
	}

	private function num( $i, $mode ) {
		$n = str_pad( (string) $i, 2, '0', STR_PAD_LEFT );
		if ( 'fa' !== $mode ) {
			return $n;
		}
		return str_replace( array( '0','1','2','3','4','5','6','7','8','9' ), array( '۰','۱','۲','۳','۴','۵','۶','۷','۸','۹' ), $n );
	}

	protected function output( $s ) {
		$this->render_heading( $s );

		if ( empty( $s['items'] ) ) {
			return;
		}
		$nums   = 'yes' === ( $s['numbers'] ?? 'yes' );
		$stroke = 'yes' === ( $s['modn_stroke'] ?? 'yes' ) ? ' szl-stroke' : '';

		echo '<div class="szl-modules">';
		$i = 1;
		foreach ( $s['items'] as $m ) {
			echo '<article class="szl-card szl-topline szl-module">';
			if ( $nums ) {
				echo '<span class="szl-mod-n' . esc_attr( $stroke ) . '">' . esc_html( $this->num( $i, $s['digits'] ?? 'fa' ) ) . '</span>';
			}
			if ( ! empty( $m['cat'] ) ) {
				echo '<span class="szl-mod-cat">' . esc_html( $m['cat'] ) . '</span>';
			}
			if ( ! empty( $m['title'] ) ) {
				echo '<h3 class="szl-mod-title">' . self::kses( $m['title'] ) . '</h3>'; // phpcs:ignore WordPress.Security.EscapeOutput
			}
			$this->paras( $m['body'] ?? '' );
			if ( ! empty( $m['list'] ) ) {
				echo '<ul class="szl-list szl-mk-' . esc_attr( $m['marker'] ?: 'dot' ) . '">';
				foreach ( preg_split( '/\R/u', $m['list'] ) as $li ) {
					$li = trim( $li );
					if ( '' !== $li ) {
						echo '<li>' . self::kses( $li ) . '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput
					}
				}
				echo '</ul>';
			}
			$this->paras( $m['body2'] ?? '' );
			if ( ! empty( $m['punch'] ) ) {
				echo '<p class="szl-mod-punch">' . self::kses( $m['punch'] ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
			}
			echo '</article>';
			$i++;
		}
		echo '</div>';
	}

	private function paras( $text ) {
		if ( '' === trim( (string) $text ) ) {
			return;
		}
		foreach ( preg_split( '/\R/u', $text ) as $p ) {
			$p = trim( $p );
			if ( '' !== $p ) {
				echo '<p>' . self::kses( $p ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
			}
		}
	}
}
