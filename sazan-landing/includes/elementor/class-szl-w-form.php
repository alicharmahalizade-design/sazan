<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

use Elementor\Controls_Manager;
use Elementor\Repeater;

/** بلوک CTA به‌همراه فرم ثبت درخواست حضور در دوره. */
class SZL_W_Form extends SZL_Widget_Base {

	protected $default_head_align = 'right';

	public function get_name() { return 'szl_form'; }
	public function get_title() { return 'لندینگ: فرم ثبت درخواست'; }
	public function get_icon() { return 'eicon-form-horizontal'; }

	protected function content_controls() {
		$this->start_controls_section( 'c_copy', array( 'label' => 'متن معرفی' ) );
		$this->add_control( 'show_copy', array(
			'label'        => 'نمایش ستون متن',
			'type'         => Controls_Manager::SWITCHER,
			'default'      => 'yes',
			'return_value' => 'yes',
		) );
		$this->ctl_heading( array(
			'eyebrow' => 'MARKET GOVERNANCE',
			'title'   => 'دوره جامع <span>حکمرانی بر بازار</span>',
		) );
		$this->add_control( 'lead', array(
			'label'       => 'شعار',
			'type'        => Controls_Manager::TEXTAREA,
			'rows'        => 3,
			'label_block' => true,
			'default'     => 'بازار را نمی‌توان کنترل کرد، اما می‌توان کسب‌وکاری ساخت که بلد باشد در بازار چگونه بازی کند.',
		) );
		$this->add_control( 'meta', array(
			'label'       => 'تراشه‌های اطلاعات (با کاما جدا کنید)',
			'type'        => Controls_Manager::TEXTAREA,
			'rows'        => 2,
			'label_block' => true,
			'default'     => 'هر هفته سه‌شنبه‌ها, ۱۴ جلسه آموزش و راهبری, ۱۴ جلسه خصوصی کوچینگ و منتورینگ',
		) );
		$this->add_control( 'body', array(
			'label'       => 'متن (هر پاراگراف در یک خط)',
			'type'        => Controls_Manager::TEXTAREA,
			'rows'        => 5,
			'label_block' => true,
			'default'     => "اگر احساس می‌کنید کسب‌وکارتان ظرفیت بیشتری از نتیجه‌ای که امروز می‌گیرد دارد، شاید زمان آن رسیده باشد که به‌جای اضافه‌کردن چند تکنیک دیگر، خودِ کسب‌وکار را دقیق‌تر ببینید.\nبرای بررسی شرایط حضور در دوره «حکمرانی بر بازار» و دریافت مشاوره، درخواست خود را ثبت کنید.",
		) );
		$this->end_controls_section();

		/* ── form fields ── */
		$this->start_controls_section( 'c_form', array( 'label' => 'فرم' ) );
		$this->add_control( 'form_title', array(
			'label'   => 'عنوان فرم',
			'type'    => Controls_Manager::TEXT,
			'default' => 'ثبت درخواست حضور در دوره',
			'label_block' => true,
		) );

		$fields = array(
			'name'  => array( 'نام و نام خانوادگی', 'مثلاً علی محمدی' ),
			'phone' => array( 'شماره موبایل', '۰۹۱۲۳۴۵۶۷۸۹' ),
			'biz'   => array( 'نام و حوزه کسب‌وکار', 'مثلاً پخش مواد غذایی / تولیدی پوشاک' ),
			'note'  => array( 'مهم‌ترین چالش امروز کسب‌وکارتان', 'کوتاه بنویسید تا در مشاوره دقیق‌تر بررسی شود' ),
		);
		foreach ( $fields as $k => $f ) {
			$this->add_control( 'lbl_' . $k, array(
				'label'       => 'برچسب: ' . $f[0],
				'type'        => Controls_Manager::TEXT,
				'default'     => $f[0],
				'label_block' => true,
			) );
			$this->add_control( 'ph_' . $k, array(
				'label'       => 'راهنمای داخل کادر',
				'type'        => Controls_Manager::TEXT,
				'default'     => $f[1],
				'label_block' => true,
			) );
			if ( in_array( $k, array( 'biz', 'note' ), true ) ) {
				$this->add_control( 'on_' . $k, array(
					'label'        => 'نمایش این فیلد',
					'type'         => Controls_Manager::SWITCHER,
					'default'      => 'yes',
					'return_value' => 'yes',
					'separator'    => 'after',
				) );
			} else {
				$this->add_control( 'sep_' . $k, array( 'type' => Controls_Manager::DIVIDER ) );
			}
		}

		$this->add_control( 'submit', array(
			'label'       => 'متن دکمه ارسال',
			'type'        => Controls_Manager::TEXT,
			'default'     => 'ثبت درخواست حضور در دوره',
			'label_block' => true,
		) );
		$this->add_control( 'hint', array(
			'label'       => 'متن راهنمای زیر دکمه',
			'type'        => Controls_Manager::TEXTAREA,
			'rows'        => 2,
			'label_block' => true,
			'default'     => 'پس از ثبت درخواست، برای بررسی شرایط حضور در دوره با شما تماس گرفته می‌شود.',
		) );
		$this->add_control( 'success', array(
			'label'       => 'پیام موفقیت',
			'type'        => Controls_Manager::TEXT,
			'default'     => 'درخواست شما ثبت شد. به‌زودی با شما تماس می‌گیریم.',
			'label_block' => true,
		) );
		$this->add_control( 'redirect', array(
			'label'       => 'انتقال پس از ثبت (اختیاری)',
			'type'        => Controls_Manager::URL,
			'label_block' => true,
			'description' => 'اگر پر شود، کاربر پس از ثبت موفق به این آدرس منتقل می‌شود.',
		) );
		$this->end_controls_section();
	}

	protected function style_controls() {
		$this->sty_heading();

		$this->start_controls_section( 's_copy', array(
			'label'     => 'استایل ستون متن',
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'show_copy' => 'yes' ),
		) );
		$this->add_responsive_control( 'split', array(
			'label'      => 'نسبت عرض ستون متن',
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( '%' ),
			'range'      => array( '%' => array( 'min' => 25, 'max' => 75 ) ),
			'default'    => array( 'unit' => '%', 'size' => 52 ),
			'selectors'  => array(
				'{{WRAPPER}} .szl-cta-inner' => 'grid-template-columns: {{SIZE}}% minmax(0, 1fr);',
			),
		) );
		$this->sty_text( 'lead', 'شعار', '.szl-cta-lead', array( 'margin' => true ) );
		$this->sty_text( 'body', 'متن', '.szl-cta-copy > p' );
		$this->add_control( 'hr1', array( 'type' => Controls_Manager::DIVIDER ) );
		$this->sty_text( 'meta', 'تراشه اطلاعات', '.szl-cta-meta li' );
		$this->sty_box( 'meta', 'تراشه اطلاعات', '.szl-cta-meta li', array( 'no_shadow' => true ) );
		$this->end_controls_section();

		$this->start_controls_section( 's_form', array(
			'label' => 'استایل فرم',
			'tab'   => Controls_Manager::TAB_STYLE,
		) );
		$this->sty_box( 'form', 'کادر فرم', '.szl-form' );
		$this->add_control( 'sticky', array(
			'label'        => 'چسبیدن فرم هنگام اسکرول',
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'selectors'    => array( '{{WRAPPER}} .szl-form' => 'position:sticky;top:100px;' ),
		) );
		$this->add_control( 'hr2', array( 'type' => Controls_Manager::DIVIDER ) );
		$this->sty_text( 'formtitle', 'عنوان فرم', '.szl-form-title', array( 'margin' => true ) );
		$this->sty_text( 'label', 'برچسب فیلدها', '.szl-field label' );
		$this->add_control( 'hr3', array( 'type' => Controls_Manager::DIVIDER ) );
		$this->sty_text( 'input', 'متن داخل فیلد', '.szl-field input, {{WRAPPER}} .szl-field textarea' );
		$this->add_control( 'input_ph', array(
			'label'     => 'رنگ راهنمای داخل کادر',
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .szl-field input::placeholder, {{WRAPPER}} .szl-field textarea::placeholder' => 'color: {{VALUE}};',
			),
		) );
		$this->sty_box( 'input', 'کادر فیلد', '.szl-field input, {{WRAPPER}} .szl-field textarea', array( 'no_shadow' => true ) );
		$this->add_control( 'input_focus', array(
			'label'     => 'رنگ حاشیه هنگام فوکوس',
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .szl-field input:focus, {{WRAPPER}} .szl-field textarea:focus' => 'border-color: {{VALUE}};',
			),
		) );
		$this->add_control( 'err_color', array(
			'label'     => 'رنگ پیام خطا',
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .szl-err' => 'color: {{VALUE}};' ),
		) );
		$this->end_controls_section();

		$this->start_controls_section( 's_submit', array(
			'label' => 'استایل دکمه و پیام‌ها',
			'tab'   => Controls_Manager::TAB_STYLE,
		) );
		$this->sty_button( 'sub', 'دکمه ارسال', '.szl-submit' );
		$this->add_control( 'hr4', array( 'type' => Controls_Manager::DIVIDER ) );
		$this->sty_text( 'hint', 'متن راهنما', '.szl-form-hint' );
		$this->sty_text( 'ok', 'پیام موفقیت', '.szl-form-ok' );
		$this->sty_box( 'ok', 'پیام موفقیت', '.szl-form-ok', array( 'no_shadow' => true ) );
		$this->end_controls_section();
	}

	protected function output( $s ) {
		$copy = 'yes' === ( $s['show_copy'] ?? 'yes' );

		echo '<div class="szl-cta-inner' . ( $copy ? '' : ' szl-no-copy' ) . '" id="szl-form">';

		if ( $copy ) {
			echo '<div class="szl-cta-copy">';
			$this->render_heading( $s );
			if ( ! empty( $s['lead'] ) ) {
				echo '<p class="szl-cta-lead">' . self::kses( $s['lead'] ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
			}
			if ( ! empty( $s['meta'] ) ) {
				echo '<ul class="szl-cta-meta">';
				foreach ( array_filter( array_map( 'trim', explode( ',', $s['meta'] ) ) ) as $m ) {
					echo '<li>' . esc_html( $m ) . '</li>';
				}
				echo '</ul>';
			}
			if ( ! empty( $s['body'] ) ) {
				foreach ( preg_split( '/\R/u', $s['body'] ) as $p ) {
					$p = trim( $p );
					if ( '' !== $p ) {
						echo '<p>' . self::kses( $p ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
					}
				}
			}
			echo '</div>';
		}

		$redirect = ! empty( $s['redirect']['url'] ) ? esc_url( $s['redirect']['url'] ) : '';

		printf(
			'<form class="szl-card szl-topline szl-form" data-szl-form data-success="%s" data-redirect="%s" novalidate>',
			esc_attr( $s['success'] ?? '' ),
			esc_attr( $redirect )
		);

		if ( ! empty( $s['form_title'] ) ) {
			echo '<h3 class="szl-form-title">' . esc_html( $s['form_title'] ) . '</h3>';
		}

		$this->field( 'name', 'text', $s, true );
		$this->field( 'phone', 'tel', $s, true );
		if ( 'yes' === ( $s['on_biz'] ?? 'yes' ) ) {
			$this->field( 'biz', 'text', $s, false );
		}
		if ( 'yes' === ( $s['on_note'] ?? 'yes' ) ) {
			$this->field( 'note', 'textarea', $s, false );
		}

		// Honeypot — hidden from people, tempting to bots.
		echo '<div class="szl-hp" aria-hidden="true"><label>این فیلد را خالی بگذارید<input type="text" name="szl_hp" tabindex="-1" autocomplete="off"></label></div>';

		printf(
			'<button type="submit" class="szl-btn szl-btn-primary szl-submit">%s</button>',
			esc_html( $s['submit'] ?: 'ثبت درخواست' )
		);

		if ( ! empty( $s['hint'] ) ) {
			echo '<p class="szl-form-hint">' . self::kses( $s['hint'] ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		echo '<p class="szl-form-ok" role="status" hidden></p>';

		echo '</form></div>';
	}

	private function field( $key, $type, $s, $required ) {
		$id    = 'szl-' . $key . '-' . $this->get_id();
		$label = $s[ 'lbl_' . $key ] ?? '';
		$ph    = $s[ 'ph_' . $key ] ?? '';
		$name  = array( 'name' => 'name', 'phone' => 'phone', 'biz' => 'business', 'note' => 'note' )[ $key ];

		echo '<div class="szl-field">';
		printf(
			'<label for="%s">%s%s</label>',
			esc_attr( $id ),
			esc_html( $label ),
			$required ? ' <span class="szl-req" aria-hidden="true">*</span>' : ''
		);

		if ( 'textarea' === $type ) {
			printf(
				'<textarea id="%s" name="%s" rows="3" placeholder="%s"></textarea>',
				esc_attr( $id ),
				esc_attr( $name ),
				esc_attr( $ph )
			);
		} else {
			printf(
				'<input type="%s" id="%s" name="%s" placeholder="%s"%s%s>',
				esc_attr( $type ),
				esc_attr( $id ),
				esc_attr( $name ),
				esc_attr( $ph ),
				$required ? ' required' : '',
				'tel' === $type ? ' inputmode="numeric" autocomplete="tel"' : ''
			);
		}

		echo '<small class="szl-err" data-for="' . esc_attr( $name ) . '"></small>';
		echo '</div>';
	}
}
