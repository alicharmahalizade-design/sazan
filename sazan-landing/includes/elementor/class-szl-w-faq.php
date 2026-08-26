<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

use Elementor\Controls_Manager;
use Elementor\Repeater;

/** آکاردئون سوالات متداول، به‌همراه اسکیمای FAQPage. */
class SZL_W_Faq extends SZL_Widget_Base {

	public function get_name() { return 'szl_faq'; }
	public function get_title() { return 'لندینگ: سوالات متداول'; }
	public function get_icon() { return 'eicon-help'; }

	protected function content_controls() {
		$this->start_controls_section( 'c_head', array( 'label' => 'سرتیتر' ) );
		$this->ctl_heading( array(
			'eyebrow' => 'FREQUENTLY ASKED QUESTIONS',
			'title'   => 'سوالات متداول',
		) );
		$this->end_controls_section();

		$this->start_controls_section( 'c_items', array( 'label' => 'سوال‌ها' ) );

		$r = new Repeater();
		$r->add_control( 'q', array( 'label' => 'سوال', 'type' => Controls_Manager::TEXTAREA, 'rows' => 2, 'label_block' => true ) );
		$r->add_control( 'a', array( 'label' => 'پاسخ', 'type' => Controls_Manager::WYSIWYG, 'label_block' => true ) );
		$r->add_control( 'open', array(
			'label'        => 'به‌صورت پیش‌فرض باز باشد',
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
		) );

		$this->add_control( 'items', array(
			'label'       => 'سوال‌ها',
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $r->get_controls(),
			'title_field' => '{{{ q }}}',
			'default'     => $this->seed(),
		) );

		$this->add_control( 'single', array(
			'label'        => 'هم‌زمان فقط یک سوال باز بماند',
			'type'         => Controls_Manager::SWITCHER,
			'default'      => 'yes',
			'return_value' => 'yes',
		) );
		$this->add_control( 'schema', array(
			'label'        => 'افزودن اسکیمای سئو (FAQPage)',
			'type'         => Controls_Manager::SWITCHER,
			'default'      => 'yes',
			'return_value' => 'yes',
			'description'  => 'فقط اگر این تنها بخش سوالات متداول صفحه است روشن بماند.',
		) );

		$this->end_controls_section();
	}

	private function seed() {
		$qa = array(
			array(
				'چه تفاوتی بین «حکمرانی بر بازار» و دوره‌های مدیریتی معمولی وجود دارد؟',
				'<p>در بیشتر دوره‌های مدیریتی، شما دانش دریافت می‌کنید و اجرای آن به‌عهده خودتان است. در «حکمرانی بر بازار» آموزش تنها لایه اول است؛ لایه دوم ۱۴ جلسه خصوصی کوچینگ و منتورینگ است که روی کسب‌وکار خودِ شما کار می‌کند.</p><p>یعنی هم‌زمان با یادگیری، این سؤال دنبال می‌شود: «این را در کسب‌وکار من چطور اجرا کنیم؟»</p>',
			),
			array(
				'چرا در کنار ۱۴ جلسه آموزشی، ۱۴ جلسه خصوصی کوچینگ و منتورینگ برگزار می‌شود؟',
				'<p>چون بزرگ‌ترین فاصله در آموزش مدیریت، فاصله میان «فهمیدم باید چه کار کنم» و «انجامش دادم» است. جلسات خصوصی برای همین فاصله طراحی شده‌اند تا آموخته‌ها با مسائل واقعی کسب‌وکار شما روبه‌رو شوند و به اقدام تبدیل شوند.</p>',
			),
			array(
				'اسکن کسب‌وکار در جلسات خصوصی چگونه انجام می‌شود؟',
				'<p>در جلسات خصوصی، وضعیت کسب‌وکار بررسی می‌شود تا مشخص شود مسئله دقیقاً کجاست: فروش، فروشندگان، ساختار، مدیریت، فرآیندها یا حتی خود مدیر.</p><p>سپس گلوگاه‌ها و باگ‌های سازمانی و مدیریتی شناسایی می‌شوند و متناسب با اهداف دوره و اهداف اختصاصی مدیر، مسیر اصلاح و اقدام تعیین می‌شود.</p>',
			),
			array(
				'جلسات کوچینگ و منتورینگ روی چه بخش‌هایی از کسب‌وکار من متمرکز خواهند بود؟',
				'<ul><li>اصلاح زیرساخت‌های سازمانی</li><li>شناسایی و اصلاح باگ‌های مدیریتی</li><li>شناسایی باگ‌های فروش</li><li>بررسی مسائل فروشندگان</li><li>هم‌راستا کردن اقدامات با اهداف مدیر</li><li>همراهی در مسیر اجرا و پیاده‌سازی</li></ul>',
			),
			array(
				'آیا مسائل اختصاصی فروش و فروشندگان مجموعه من هم بررسی می‌شود؟',
				'<p>بله. بخشی از جلسات خصوصی به بررسی نقاط ضعف مسیر فروش و موانعی اختصاص دارد که اجازه نمی‌دهند ظرفیت واقعی فروش آزاد شود؛ همچنین مشکلات عملکردی تیم فروش و مسیرهای اصلاح آن بررسی می‌شود.</p>',
			),
			array(
				'این دوره برای چه صاحبان کسب‌وکاری مناسب است؟',
				'<p>برای مدیران و صاحبان کسب‌وکاری که احساس می‌کنند ظرفیت مجموعه‌شان بیشتر از نتایج امروز است؛ می‌خواهند بازار و مشتری را عمیق‌تر بشناسند، مزیت رقابتی قابل دفاع بسازند، فروش را هدفمندتر هدایت کنند و باگ‌های پنهان سازمان و فروش را پیدا کنند.</p>',
			),
			array(
				'اگر کسب‌وکار من در حال حاضر فروش دارد، باز هم این دوره برای من کاربرد دارد؟',
				'<p>بله. داشتن فروش با هدایت‌شده بودن فروش یکی نیست. بسیاری از کسب‌وکارها عدد فروش دارند اما مسیر مشخصی برای رسیدن به آن ندارند. این دوره کمک می‌کند فروش موجود هدفمندتر، حرفه‌ای‌تر و کمتر وابسته به تخفیف هدایت شود.</p>',
			),
			array(
				'منتور در مسیر اجرای آموخته‌های دوره چه نقشی دارد؟',
				'<p>منتور در مسیر اقدامات و پیاده‌سازی آموخته‌ها همراه شماست؛ کمک می‌کند اولویت‌ها مشخص شود، اقدامات با اهداف مدیر هم‌راستا بماند و آموخته‌های دوره به تغییر واقعی در کسب‌وکار تبدیل شود.</p>',
			),
			array(
				'نحوه ثبت درخواست و بررسی شرایط حضور در دوره چگونه است؟',
				'<p>کافی است فرم «ثبت درخواست حضور در دوره» را در همین صفحه تکمیل کنید. پس از ثبت درخواست، برای بررسی شرایط حضور در دوره و ارائه مشاوره با شما تماس گرفته می‌شود.</p>',
			),
		);
		return array_map( function ( $x ) { return array( 'q' => $x[0], 'a' => $x[1] ); }, $qa );
	}

	protected function style_controls() {
		$this->sty_heading();

		$this->start_controls_section( 's_faq', array(
			'label' => 'استایل آکاردئون',
			'tab'   => Controls_Manager::TAB_STYLE,
		) );
		$this->add_responsive_control( 'faq_gap', array(
			'label'      => 'فاصله بین سوال‌ها',
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
			'selectors'  => array( '{{WRAPPER}} .szl-faq' => 'gap:{{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'faq_maxw', array(
			'label'      => 'حداکثر عرض',
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px', '%' ),
			'range'      => array( 'px' => array( 'min' => 400, 'max' => 1200 ) ),
			'selectors'  => array( '{{WRAPPER}} .szl-faq' => 'max-width:{{SIZE}}{{UNIT}};margin-inline:auto;' ),
		) );
		$this->sty_box( 'faq', 'کادر سوال', '.szl-faq-item', array( 'no_shadow' => true ) );
		$this->add_control( 'faq_open_border', array(
			'label'     => 'رنگ حاشیه سوال باز',
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .szl-faq-item.szl-open' => 'border-color: {{VALUE}};' ),
		) );
		$this->add_control( 'hr1', array( 'type' => Controls_Manager::DIVIDER ) );
		$this->sty_text( 'faqq', 'متن سوال', '.szl-faq-q' );
		$this->add_responsive_control( 'faqq_pad', array(
			'label'      => 'فاصله داخلی سوال',
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em' ),
			'selectors'  => array(
				'{{WRAPPER}} .szl-faq-q' => 'padding:{{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			),
		) );
		$this->add_control( 'faqq_open_color', array(
			'label'     => 'رنگ سوال باز',
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .szl-faq-item.szl-open .szl-faq-q' => 'color: {{VALUE}};' ),
		) );
		$this->add_control( 'hr2', array( 'type' => Controls_Manager::DIVIDER ) );
		$this->sty_text( 'faqa', 'متن پاسخ', '.szl-faq-a' );
		$this->add_responsive_control( 'faqa_pad', array(
			'label'      => 'فاصله داخلی پاسخ',
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em' ),
			'selectors'  => array(
				'{{WRAPPER}} .szl-faq-a > div' => 'padding:{{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			),
		) );
		$this->add_control( 'hr3', array( 'type' => Controls_Manager::DIVIDER ) );
		$this->add_responsive_control( 'ico_size', array(
			'label'      => 'اندازه آیکون',
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 16, 'max' => 48 ) ),
			'selectors'  => array( '{{WRAPPER}} .szl-faq-ico' => 'width:{{SIZE}}px;height:{{SIZE}}px;' ),
		) );
		$this->add_control( 'ico_color', array(
			'label'     => 'رنگ آیکون',
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .szl-faq-ico::before, {{WRAPPER}} .szl-faq-ico::after' => 'background: {{VALUE}};' ),
		) );
		$this->add_control( 'ico_bg', array(
			'label'     => 'پس‌زمینه آیکون',
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .szl-faq-ico' => 'background: {{VALUE}};' ),
		) );
		$this->add_control( 'ico_border', array(
			'label'     => 'حاشیه آیکون',
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .szl-faq-ico' => 'border-color: {{VALUE}};' ),
		) );
		$this->end_controls_section();
	}

	protected function output( $s ) {
		$this->render_heading( $s );

		if ( empty( $s['items'] ) ) {
			return;
		}
		$single = 'yes' === ( $s['single'] ?? 'yes' ) ? '1' : '0';
		$uid    = 'szl-faq-' . $this->get_id();

		echo '<div class="szl-faq" data-szl-faq data-single="' . esc_attr( $single ) . '">';
		$i = 0;
		foreach ( $s['items'] as $it ) {
			if ( empty( $it['q'] ) ) {
				continue;
			}
			$open = 'yes' === ( $it['open'] ?? '' );
			$pid  = $uid . '-' . $i;
			printf(
				'<div class="szl-faq-item%1$s">
					<button class="szl-faq-q" type="button" aria-expanded="%2$s" aria-controls="%3$s">
						<span>%4$s</span><i class="szl-faq-ico" aria-hidden="true"></i>
					</button>
					<div class="szl-faq-a" id="%3$s" role="region"><div>%5$s</div></div>
				</div>',
				$open ? ' szl-open' : '',
				$open ? 'true' : 'false',
				esc_attr( $pid ),
				self::kses( $it['q'] ), // phpcs:ignore WordPress.Security.EscapeOutput
				wp_kses_post( $it['a'] ) // phpcs:ignore WordPress.Security.EscapeOutput
			);
			$i++;
		}
		echo '</div>';

		if ( 'yes' === ( $s['schema'] ?? '' ) ) {
			$this->schema( $s['items'] );
		}
	}

	private function schema( $items ) {
		$entities = array();
		foreach ( $items as $it ) {
			if ( empty( $it['q'] ) ) {
				continue;
			}
			$entities[] = array(
				'@type'          => 'Question',
				'name'           => wp_strip_all_tags( $it['q'] ),
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => wp_strip_all_tags( $it['a'] ?? '' ),
				),
			);
		}
		if ( ! $entities ) {
			return;
		}
		echo '<script type="application/ld+json">' .
			wp_json_encode( array(
				'@context'   => 'https://schema.org',
				'@type'      => 'FAQPage',
				'mainEntity' => $entities,
			), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) .
			'</script>';
	}
}
