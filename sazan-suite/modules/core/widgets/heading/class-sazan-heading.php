<?php
/**
 * ویجت نمونه: تیتر پیشرفته‌ی سازان.
 * الگوی ساخت ویجت‌های بعدی است.
 *
 * @package Sazan\Widgets
 */

namespace Sazan\Widgets;

use Sazan\Base\Widget_Base;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Heading extends Widget_Base {

	/** نام یکتای ویجت (در دیتابیس ذخیره می‌شود؛ تغییرش ندید). */
	public function get_name() {
		return 'sazan-heading';
	}

	/** عنوان نمایشی در پنل. */
	public function get_title() {
		return esc_html__( 'تیتر سازان', 'sazan-core' );
	}

	/** آیکن. */
	public function get_icon() {
		return 'eicon-t-letter';
	}

	public function get_keywords() {
		return array( 'sazan', 'سازان', 'heading', 'تیتر', 'عنوان', 'title' );
	}

	/* ====================================================================
	 * ثبت کنترل‌ها
	 * ================================================================== */
	protected function register_controls() {

		/* ---------- تب محتوا ---------- */
		$this->start_controls_section(
			'section_content',
			array(
				'label' => esc_html__( 'محتوا', 'sazan-core' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'title',
			array(
				'label'       => esc_html__( 'متن تیتر', 'sazan-core' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 3,
				'default'     => esc_html__( 'این یک تیتر نمونه است', 'sazan-core' ),
				'placeholder' => esc_html__( 'متن خود را وارد کنید', 'sazan-core' ),
			)
		);

		$this->add_control(
			'link',
			array(
				'label'       => esc_html__( 'لینک', 'sazan-core' ),
				'type'        => Controls_Manager::URL,
				'placeholder' => esc_html__( 'https://example.com', 'sazan-core' ),
				'default'     => array( 'url' => '' ),
			)
		);

		$this->add_control(
			'html_tag',
			array(
				'label'   => esc_html__( 'تگ HTML', 'sazan-core' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'h2',
				'options' => array(
					'h1'   => 'H1',
					'h2'   => 'H2',
					'h3'   => 'H3',
					'h4'   => 'H4',
					'h5'   => 'H5',
					'h6'   => 'H6',
					'div'  => 'div',
					'span' => 'span',
					'p'    => 'p',
				),
			)
		);

		$this->add_responsive_control(
			'align',
			array(
				'label'     => esc_html__( 'چینش', 'sazan-core' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'right'   => array( 'title' => esc_html__( 'راست', 'sazan-core' ), 'icon' => 'eicon-text-align-right' ),
					'center'  => array( 'title' => esc_html__( 'وسط', 'sazan-core' ), 'icon' => 'eicon-text-align-center' ),
					'left'    => array( 'title' => esc_html__( 'چپ', 'sazan-core' ), 'icon' => 'eicon-text-align-left' ),
					'justify' => array( 'title' => esc_html__( 'هم‌تراز', 'sazan-core' ), 'icon' => 'eicon-text-align-justify' ),
				),
				'default'   => 'right',
				'selectors' => array(
					'{{WRAPPER}} .sazan-heading' => 'text-align: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		/* ---------- تب استایل ---------- */
		$this->start_controls_section(
			'section_style',
			array(
				'label' => esc_html__( 'استایل تیتر', 'sazan-core' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		// تب‌های حالت عادی / هاور
		$this->start_controls_tabs( 'title_tabs' );

		/* --- حالت عادی --- */
		$this->start_controls_tab(
			'title_tab_normal',
			array( 'label' => esc_html__( 'عادی', 'sazan-core' ) )
		);

		$this->sazan_add_color( 'title_normal', '.sazan-heading__title', esc_html__( 'رنگ متن', 'sazan-core' ) );

		$this->end_controls_tab();

		/* --- حالت هاور --- */
		$this->start_controls_tab(
			'title_tab_hover',
			array( 'label' => esc_html__( 'هاور', 'sazan-core' ) )
		);

		$this->add_control(
			'title_hover_color',
			array(
				'label'     => esc_html__( 'رنگ متن (هاور)', 'sazan-core' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .sazan-heading:hover .sazan-heading__title' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'title_hover_transition',
			array(
				'label'      => esc_html__( 'مدت ترنزیشن (ثانیه)', 'sazan-core' ),
				'type'       => Controls_Manager::SLIDER,
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 3, 'step' => 0.1 ) ),
				'default'    => array( 'size' => 0.3 ),
				'selectors'  => array(
					'{{WRAPPER}} .sazan-heading__title' => 'transition: color {{SIZE}}s ease, background {{SIZE}}s ease;',
				),
			)
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		// جداکننده
		$this->add_control(
			'style_divider',
			array( 'type' => Controls_Manager::DIVIDER )
		);

		// تایپوگرافی، پس‌زمینه، حاشیه، سایه، فاصله — همه از کلاس پایه
		$this->sazan_add_typography( 'title', '.sazan-heading__title' );
		$this->sazan_add_background( 'title_bg', '.sazan-heading__title' );
		$this->sazan_add_border( 'title', '.sazan-heading__title' );
		$this->sazan_add_box_shadow( 'title', '.sazan-heading__title' );
		$this->sazan_add_padding( 'title', '.sazan-heading__title' );
		$this->sazan_add_margin( 'title', '.sazan-heading__title' );

		$this->end_controls_section();
	}

	/* ====================================================================
	 * خروجی فرانت
	 * ================================================================== */
	protected function render() {
		$settings = $this->get_settings_for_display();

		$title = $settings['title'];
		if ( '' === trim( $title ) ) {
			return;
		}

		$tag = in_array( $settings['html_tag'], array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'span', 'p' ), true )
			? $settings['html_tag']
			: 'h2';

		// محتوای تیتر
		$title_html = sprintf( '<span class="sazan-heading__title">%s</span>', esc_html( $title ) );

		// اگر لینک دارد
		if ( ! empty( $settings['link']['url'] ) ) {
			$this->add_link_attributes( 'link', $settings['link'] );
			$title_html = sprintf(
				'<a %1$s>%2$s</a>',
				$this->get_render_attribute_string( 'link' ),
				$title_html
			);
		}

		printf(
			'<div class="sazan-heading"><%1$s class="sazan-heading__tag">%2$s</%1$s></div>',
			esc_attr( $tag ),
			$title_html // محتوای از قبل امن‌سازی‌شده
		);
	}

	/**
	 * خروجی ادیتور (Live preview) — اختیاری ولی توصیه‌شده.
	 */
	protected function content_template() {
		?>
		<#
		var tag = settings.html_tag || 'h2';
		var titleHtml = '<span class="sazan-heading__title">' + settings.title + '</span>';
		if ( settings.link && settings.link.url ) {
			titleHtml = '<a href="' + settings.link.url + '">' + titleHtml + '</a>';
		}
		#>
		<div class="sazan-heading">
			<{{{ tag }}} class="sazan-heading__tag">{{{ titleHtml }}}</{{{ tag }}}>
		</div>
		<?php
	}
}
