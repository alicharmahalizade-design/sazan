<?php
/**
 * ویجت المنتور: آزمون سازان — انتخاب یک آزمون و نمایش آن.
 *
 * @package Sazan\Widgets
 */

namespace Sazan\Widgets;

use Sazan\Base\Widget_Base;
use Sazan\Quiz_CPT;
use Sazan\Quiz_Engine;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Quiz extends Widget_Base {

	public function get_name() { return 'sazan-quiz'; }
	public function get_title() { return esc_html__( 'آزمون سازان', 'sazan-core' ); }
	public function get_icon() { return 'eicon-form-horizontal'; }
	public function get_keywords() { return array( 'sazan', 'سازان', 'quiz', 'آزمون', 'ارزیابی', 'فرم', 'نیازسنجی' ); }

	protected function register_controls() {
		$this->start_controls_section( 'sec', array( 'label' => esc_html__( 'آزمون', 'sazan-core' ) ) );

		$opts = array( 0 => esc_html__( '— انتخاب آزمون —', 'sazan-core' ) );
		foreach ( get_posts( array( 'post_type' => Quiz_CPT::POST_TYPE, 'numberposts' => -1 ) ) as $q ) {
			$opts[ $q->ID ] = $q->post_title;
		}
		$this->add_control( 'quiz_id', array(
			'label'   => esc_html__( 'انتخاب آزمون', 'sazan-core' ),
			'type'    => Controls_Manager::SELECT2,
			'options' => $opts,
			'default' => 0,
		) );
		$this->end_controls_section();
	}

	protected function render() {
		$id = absint( $this->get_settings_for_display( 'quiz_id' ) );
		echo Quiz_Engine::instance()->render( $id ); // خروجی امن و escape‌شده در رندر موتور
	}
}
