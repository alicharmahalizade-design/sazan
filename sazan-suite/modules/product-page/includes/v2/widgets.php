<?php
/** Product Page v2 Elementor widgets. */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class SPP_V2_Simple_Widget extends SPP_V2_Widget_Base {
	protected $spp_v2_id = '';
	protected $spp_v2_title = '';
	protected function section_id() { return $this->spp_v2_id; }
	protected function section_title() { return $this->spp_v2_title; }
}

class SPP_V2_Widget_Course_Hero extends SPP_V2_Simple_Widget { protected $spp_v2_id = 'course-hero'; protected $spp_v2_title = 'هیرو دوره'; }
class SPP_V2_Widget_Course_Nav extends SPP_V2_Simple_Widget { protected $spp_v2_id = 'course-nav'; protected $spp_v2_title = 'ناوبری چسبان'; }
class SPP_V2_Widget_Prerequisite extends SPP_V2_Simple_Widget { protected $spp_v2_id = 'prerequisite'; protected $spp_v2_title = 'پیش‌نیاز'; }
class SPP_V2_Widget_Benefits extends SPP_V2_Simple_Widget { protected $spp_v2_id = 'benefits'; protected $spp_v2_title = 'مزیت‌های دوره'; }
class SPP_V2_Widget_Course_Introduction extends SPP_V2_Simple_Widget { protected $spp_v2_id = 'course-introduction'; protected $spp_v2_title = 'معرفی کامل دوره'; }
class SPP_V2_Widget_Curriculum_Overview extends SPP_V2_Simple_Widget { protected $spp_v2_id = 'curriculum-overview'; protected $spp_v2_title = 'نمای کلی سرفصل‌ها'; }
class SPP_V2_Widget_Full_Curriculum extends SPP_V2_Simple_Widget { protected $spp_v2_id = 'full-curriculum'; protected $spp_v2_title = 'سرفصل‌های کامل'; }
class SPP_V2_Widget_Instructors extends SPP_V2_Simple_Widget { protected $spp_v2_id = 'instructors'; protected $spp_v2_title = 'مدرس‌ها'; }
class SPP_V2_Widget_Certificate extends SPP_V2_Simple_Widget { protected $spp_v2_id = 'certificate'; protected $spp_v2_title = 'گواهینامه'; }
class SPP_V2_Widget_Reviews extends SPP_V2_Simple_Widget { protected $spp_v2_id = 'reviews'; protected $spp_v2_title = 'نظرات دانشجویان'; }
class SPP_V2_Widget_Enrollment extends SPP_V2_Simple_Widget { protected $spp_v2_id = 'enrollment'; protected $spp_v2_title = 'ثبت‌نام و پرداخت'; }
class SPP_V2_Widget_Guarantee extends SPP_V2_Simple_Widget { protected $spp_v2_id = 'guarantee'; protected $spp_v2_title = 'ضمانت'; }
class SPP_V2_Widget_FAQ extends SPP_V2_Simple_Widget { protected $spp_v2_id = 'faq'; protected $spp_v2_title = 'سوالات متداول'; }
class SPP_V2_Widget_Final_CTA extends SPP_V2_Simple_Widget { protected $spp_v2_id = 'final-cta'; protected $spp_v2_title = 'دعوت نهایی'; }

function spp_v2_widget_map() {
	return array(
		'SPP_V2_Widget_Course_Hero', 'SPP_V2_Widget_Course_Nav', 'SPP_V2_Widget_Prerequisite', 'SPP_V2_Widget_Benefits',
		'SPP_V2_Widget_Course_Introduction', 'SPP_V2_Widget_Curriculum_Overview', 'SPP_V2_Widget_Full_Curriculum',
		'SPP_V2_Widget_Instructors', 'SPP_V2_Widget_Certificate', 'SPP_V2_Widget_Reviews', 'SPP_V2_Widget_Enrollment',
		'SPP_V2_Widget_Guarantee', 'SPP_V2_Widget_FAQ', 'SPP_V2_Widget_Final_CTA',
	);
}
