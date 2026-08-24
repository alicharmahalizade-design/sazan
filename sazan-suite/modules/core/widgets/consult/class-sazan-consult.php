<?php
/**
 * ویجت المنتور: فرم درخواست مشاوره کسب‌وکار سازان.
 *
 * @package Sazan\Widgets
 */

namespace Sazan\Widgets;

use Sazan\Base\Widget_Base;
use Sazan\Consult_Engine;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Consult extends Widget_Base {

	public function get_name() { return 'sazan-consult'; }
	public function get_title() { return esc_html__( 'فرم مشاوره کسب‌وکار سازان', 'sazan-core' ); }
	public function get_icon() { return 'eicon-form-horizontal'; }
	public function get_keywords() { return array( 'sazan', 'سازان', 'مشاوره', 'consult', 'فرم', 'رزرو', 'تقویم', 'کسب و کار' ); }

	protected function register_controls() {
		$this->start_controls_section( 'sec', array( 'label' => esc_html__( 'فرم مشاوره', 'sazan-core' ) ) );

		$this->add_control( 'intro', array(
			'label'   => esc_html__( 'متن معرفی (بالای فرم)', 'sazan-core' ),
			'type'    => Controls_Manager::TEXTAREA, 'rows' => 2,
			'default' => esc_html__( 'برای رزرو جلسه‌ی مشاوره‌ی کسب‌وکار، فرم زیر را تکمیل کنید.', 'sazan-core' ),
		) );
		$this->add_control( 'slots', array(
			'label'       => esc_html__( 'ساعت‌های قابل انتخاب (هر خط یک ساعت)', 'sazan-core' ),
			'type'        => Controls_Manager::TEXTAREA, 'rows' => 6,
			'default'     => "۹:۰۰\n۱۰:۰۰\n۱۱:۰۰\n۱۲:۰۰\n۱۴:۰۰\n۱۵:۰۰\n۱۶:۰۰\n۱۷:۰۰",
			'description' => esc_html__( 'این ساعت‌ها برای انتخاب کاربر و یادآوری پیامکی استفاده می‌شود.', 'sazan-core' ),
		) );
		$this->add_control( 'btn', array(
			'label' => esc_html__( 'متن دکمه‌ی ثبت', 'sazan-core' ),
			'type'  => Controls_Manager::TEXT,
			'default' => esc_html__( 'ثبت درخواست مشاوره', 'sazan-core' ),
		) );
		$this->add_control( 'success', array(
			'label' => esc_html__( 'پیام موفقیت', 'sazan-core' ),
			'type'  => Controls_Manager::TEXTAREA, 'rows' => 2,
			'default' => esc_html__( 'درخواست شما با موفقیت ثبت شد. زمان جلسه برای شما پیامک شد و قبل از جلسه یادآوری می‌کنیم.', 'sazan-core' ),
		) );
		$this->add_control( 'note', array(
			'type' => Controls_Manager::RAW_HTML,
			'raw'  => esc_html__( 'تنظیمات پیامک (کلید API، فرستنده، کدهای پترن، شماره مدیریت) از منوی «مشاوره کسب‌وکار ← تنظیمات پیامک» انجام می‌شود.', 'sazan-core' ),
			'content_classes' => 'elementor-descriptor',
		) );

		$this->end_controls_section();

		$this->add_palette_controls();
	}

	protected function render() {
		$a = $this->get_settings_for_display();
		echo Consult_Engine::instance()->render( array(
			'intro'   => $a['intro'] ?? '',
			'slots'   => $a['slots'] ?? '',
			'btn'     => $a['btn'] ?? '',
			'success' => $a['success'] ?? '',
		) );
	}
}
