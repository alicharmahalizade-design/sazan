<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

use Elementor\Controls_Manager;
use Elementor\Repeater;

/** نتایج دوره: کارت‌های شماره‌دار. */
class SZL_W_Results extends SZL_Widget_Base {

	public function get_name() { return 'szl_results'; }
	public function get_title() { return 'لندینگ: نتایج دوره'; }
	public function get_icon() { return 'eicon-number-field'; }

	protected function content_controls() {
		$this->start_controls_section( 'c_head', array( 'label' => 'سرتیتر' ) );
		$this->ctl_heading( array(
			'eyebrow' => 'RESULTS OF COURSE',
			'title'   => 'نتایج دوره چیست؟',
		) );
		$this->end_controls_section();

		$this->start_controls_section( 'c_items', array( 'label' => 'نتایج' ) );

		$r = new Repeater();
		$r->add_control( 'title', array( 'label' => 'عنوان', 'type' => Controls_Manager::TEXT, 'label_block' => true ) );
		$r->add_control( 'text', array( 'label' => 'توضیح', 'type' => Controls_Manager::TEXTAREA, 'rows' => 3, 'label_block' => true ) );

		$seed = array(
			array( 'نگاه دقیق‌تر به بازار و کسب‌وکار', 'به‌جای تصمیم‌گیری صرفاً بر اساس اتفاقات روزمره، تصویر روشن‌تری از شرایط کسب‌وکار داشته باشید.' ),
			array( 'ساخت مزیت رقابتی واقعی', 'برای مشتری پاسخ روشن‌تری به سؤال مهم «چرا شما؟» ایجاد کنید.' ),
			array( 'هدفمندتر شدن فروش', 'اهداف فروش را از اعداد روی کاغذ به اقدامات مشخص نزدیک‌تر کنید.' ),
			array( 'شناخت بهتر مشتری', 'نیاز واقعی مشتری را بهتر کشف کنید و متناسب با آن پیشنهاد ارائه دهید.' ),
			array( 'مذاکره حرفه‌ای‌تر', 'ارزش پیشنهاد خود را بهتر حفظ کنید و مذاکره را فقط به تخفیف وابسته نکنید.' ),
			array( 'طراحی بهتر تجربه مشتری', 'فرآیند ارتباط با مشتری را قبل و بعد از خرید آگاهانه‌تر طراحی کنید.' ),
			array( 'شناسایی باگ‌های کسب‌وکار', 'نقاط ضعف پنهان در مدیریت، سازمان، فروش و عملکرد فروشندگان را بهتر شناسایی کنید.' ),
			array( 'تبدیل آموزش به اقدام', 'دانسته‌ها را به مسائل واقعی کسب‌وکار خود متصل کنید و برای اجرای آن‌ها مسیر مشخص‌تری داشته باشید.' ),
		);
		$this->add_control( 'items', array(
			'label'       => 'نتایج',
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $r->get_controls(),
			'title_field' => '{{{ title }}}',
			'default'     => array_map( function ( $x ) { return array( 'title' => $x[0], 'text' => $x[1] ); }, $seed ),
		) );

		$this->add_control( 'numbers', array(
			'label'        => 'نمایش شماره',
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

	protected function style_controls() {
		$this->sty_heading();

		$this->start_controls_section( 's_items', array(
			'label' => 'استایل کارت نتیجه',
			'tab'   => Controls_Manager::TAB_STYLE,
		) );
		$this->sty_columns( 'res', 'تعداد ستون', '.szl-results', 4, 6 );
		$this->sty_box( 'res', 'کارت', '.szl-result' );
		$this->add_control( 'res_hover', array(
			'label'     => 'رنگ حاشیه در هاور',
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .szl-result:hover' => 'border-color: {{VALUE}};' ),
		) );
		$this->add_control( 'hr1', array( 'type' => Controls_Manager::DIVIDER ) );
		$this->sty_text( 'resn', 'شماره', '.szl-res-n', array( 'margin' => true ) );
		$this->sty_text( 'restitle', 'عنوان', '.szl-result h4', array( 'margin' => true ) );
		$this->sty_text( 'restext', 'توضیح', '.szl-result p' );
		$this->end_controls_section();
	}

	protected function output( $s ) {
		$this->render_heading( $s );

		if ( empty( $s['items'] ) ) {
			return;
		}
		$nums = 'yes' === ( $s['numbers'] ?? 'yes' );

		echo '<div class="szl-results">';
		$i = 1;
		foreach ( $s['items'] as $it ) {
			echo '<article class="szl-card szl-topline szl-result">';
			if ( $nums ) {
				$n = str_pad( (string) $i, 2, '0', STR_PAD_LEFT );
				if ( 'en' !== ( $s['digits'] ?? 'fa' ) ) {
					$n = str_replace( array( '0','1','2','3','4','5','6','7','8','9' ), array( '۰','۱','۲','۳','۴','۵','۶','۷','۸','۹' ), $n );
				}
				echo '<span class="szl-res-n">' . esc_html( $n ) . '</span>';
			}
			if ( ! empty( $it['title'] ) ) {
				echo '<h4>' . self::kses( $it['title'] ) . '</h4>'; // phpcs:ignore WordPress.Security.EscapeOutput
			}
			if ( ! empty( $it['text'] ) ) {
				echo '<p>' . self::kses( $it['text'] ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
			}
			echo '</article>';
			$i++;
		}
		echo '</div>';
	}
}
