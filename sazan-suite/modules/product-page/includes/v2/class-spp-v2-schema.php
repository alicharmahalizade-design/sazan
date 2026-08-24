<?php
/**
 * SEO, social metadata and structured data for Product Page v2.
 *
 * WooCommerce/Rank Math remain the owners of Product schema. This class only
 * adds course-specific entities and a no-SEO-plugin metadata fallback.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SPP_V2_Schema {

	public static function init() {
		add_action( 'wp_head', array( __CLASS__, 'technical_meta' ), 1 );
		add_action( 'wp_head', array( __CLASS__, 'fallback_meta' ), 5 );
		add_action( 'wp_head', array( __CLASS__, 'fallback_schema' ), 40 );
		add_filter( 'wp_robots', array( __CLASS__, 'robots' ), 20 );
		add_filter( 'document_title_parts', array( __CLASS__, 'fallback_title' ), 20 );

		// Rank Math remains the single JSON-LD emitter when it is active.
		add_filter( 'rank_math/json_ld', array( __CLASS__, 'rank_math_graph' ), 99, 2 );
		add_action( 'rank_math/vars/register_extra_replacements', array( __CLASS__, 'register_rank_math_variables' ) );
	}

	public static function has_rank_math() {
		return defined( 'RANK_MATH_VERSION' ) || class_exists( 'RankMath' );
	}

	public static function has_schema_provider() {
		$active = defined( 'WPSEO_VERSION' )
			|| self::has_rank_math()
			|| defined( 'AIOSEO_VERSION' )
			|| defined( 'SEOPRESS_VERSION' )
			|| class_exists( 'WPSEO_Options' )
			|| class_exists( 'AIOSEO\Plugin\AIOSEO' )
			|| function_exists( 'seopress_init' );
		return (bool) apply_filters( 'spp_v2_has_schema_provider', $active );
	}

	private static function context() {
		if ( ! SPP_V2_Template::is_active_request() ) {
			return array();
		}
		$product_id = SPP_V2_Template::current_product_id();
		$product = $product_id && function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : false;
		if ( ! $product ) {
			return array();
		}
		return array( $product_id, $product, SPP_V2_Data::get( $product_id ) );
	}

	private static function description( $product_id, $data ) {
		$description = isset( $data['seo']['fallback_description'] ) ? $data['seo']['fallback_description'] : '';
		if ( ! $description && ! empty( $data['intro']['short_description'] ) ) {
			$description = $data['intro']['short_description'];
		}
		if ( ! $description ) {
			$description = get_post_field( 'post_excerpt', $product_id );
		}
		if ( ! $description && ! empty( $data['about']['lead'] ) ) {
			$description = $data['about']['lead'];
		}
		if ( ! $description ) {
			$description = get_post_field( 'post_content', $product_id );
		}
		if ( ! $description ) {
			$description = get_the_title( $product_id );
		}
		$description = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( strip_shortcodes( (string) $description ) ) ) );
		return wp_html_excerpt( $description, 160, '…' );
	}

	private static function social_image( $product, $data ) {
		$image_id = ! empty( $data['seo']['social_image'] ) ? absint( $data['seo']['social_image'] ) : 0;
		if ( ! $image_id && ! empty( $data['intro']['hero_image'] ) ) {
			$image_id = absint( $data['intro']['hero_image'] );
		}
		if ( ! $image_id ) {
			$image_id = absint( $product->get_image_id() );
		}
		$url = $image_id ? wp_get_attachment_image_url( $image_id, 'full' ) : '';
		return array( $image_id, $url ? $url : '' );
	}

	private static function faq_entity( $product_id, $data ) {
		$items = isset( $data['faq']['items'] ) ? $data['faq']['items'] : array();
		$entities = array();
		foreach ( $items as $item ) {
			if ( empty( $item['question'] ) || empty( $item['answer'] ) ) {
				continue;
			}
			$entities[] = array(
				'@type' => 'Question',
				'name' => wp_strip_all_tags( $item['question'] ),
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text' => wp_strip_all_tags( $item['answer'] ),
				),
			);
		}
		if ( ! $entities ) {
			return array();
		}
		return array(
			'@type' => 'FAQPage',
			'@id' => get_permalink( $product_id ) . '#faq',
			'mainEntity' => $entities,
		);
	}

	private static function split_lines( $value ) {
		$items = preg_split( '/[\r\n]+/u', (string) $value );
		return array_values( array_filter( array_map( 'trim', $items ) ) );
	}

	private static function instructors( $data ) {
		$people = array();
		$items = isset( $data['instructors']['items'] ) ? $data['instructors']['items'] : array();
		foreach ( $items as $item ) {
			if ( empty( $item['name'] ) ) {
				continue;
			}
			$person = array( '@type' => 'Person', 'name' => wp_strip_all_tags( $item['name'] ) );
			if ( ! empty( $item['role'] ) ) {
				$person['jobTitle'] = wp_strip_all_tags( $item['role'] );
			}
			if ( ! empty( $item['image'] ) ) {
				$image = wp_get_attachment_image_url( absint( $item['image'] ), 'full' );
				if ( $image ) {
					$person['image'] = $image;
				}
			}
			if ( ! empty( $item['website'] ) ) {
				$person['url'] = esc_url_raw( $item['website'] );
			}
			$people[] = $person;
		}
		return $people;
	}

	private static function course_entity( $product_id, $product, $data ) {
		$seo = isset( $data['seo'] ) ? $data['seo'] : array();
		$permalink = get_permalink( $product_id );
		$provider_name = ! empty( $seo['provider_name'] ) ? $seo['provider_name'] : get_bloginfo( 'name' );
		$provider_url = ! empty( $seo['provider_url'] ) ? $seo['provider_url'] : home_url( '/' );
		list( , $image_url ) = self::social_image( $product, $data );
		$entity = array(
			'@type' => 'Course',
			'@id' => $permalink . '#course',
			'url' => $permalink,
			'name' => wp_strip_all_tags( $product->get_name() ),
			'description' => self::description( $product_id, $data ),
			'provider' => array(
				'@type' => 'Organization',
				'@id' => trailingslashit( home_url( '/' ) ) . '#organization',
				'name' => wp_strip_all_tags( $provider_name ),
				'sameAs' => esc_url_raw( $provider_url ),
			),
		);
		if ( $image_url ) {
			$entity['image'] = $image_url;
		}
		if ( ! empty( $seo['course_level'] ) ) {
			$entity['educationalLevel'] = wp_strip_all_tags( $seo['course_level'] );
		}
		if ( ! empty( $seo['prerequisites'] ) ) {
			$entity['coursePrerequisites'] = wp_strip_all_tags( $seo['prerequisites'] );
		}
		$teaches = self::split_lines( isset( $seo['teaches'] ) ? $seo['teaches'] : '' );
		if ( $teaches ) {
			$entity['teaches'] = $teaches;
		}
		if ( ! empty( $seo['credential'] ) ) {
			$entity['educationalCredentialAwarded'] = wp_strip_all_tags( $seo['credential'] );
		}

		$instance = array( '@type' => 'CourseInstance' );
		$modes = array( 'online' => 'Online', 'onsite' => 'Onsite', 'blended' => 'Blended' );
		if ( ! empty( $seo['course_mode'] ) && isset( $modes[ $seo['course_mode'] ] ) ) {
			$instance['courseMode'] = $modes[ $seo['course_mode'] ];
		}
		if ( ! empty( $seo['duration_iso'] ) && preg_match( '/^P(?!$)[0-9YMWDTHMS.]+$/i', $seo['duration_iso'] ) ) {
			$instance['courseWorkload'] = strtoupper( $seo['duration_iso'] );
		}
		if ( ! empty( $seo['start_date'] ) ) {
			$instance['startDate'] = $seo['start_date'];
		}
		if ( ! empty( $seo['end_date'] ) ) {
			$instance['endDate'] = $seo['end_date'];
		}
		$instructors = self::instructors( $data );
		if ( $instructors ) {
			$instance['instructor'] = 1 === count( $instructors ) ? $instructors[0] : $instructors;
		}
		if ( count( $instance ) > 1 ) {
			$entity['hasCourseInstance'] = $instance;
		}
		return $entity;
	}

	private static function graph_has_type( $data, $type ) {
		foreach ( (array) $data as $entity ) {
			if ( ! is_array( $entity ) || empty( $entity['@type'] ) ) {
				continue;
			}
			$types = (array) $entity['@type'];
			if ( in_array( $type, $types, true ) ) {
				return true;
			}
		}
		return false;
	}

	public static function rank_math_graph( $data, $jsonld ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$context = self::context();
		if ( ! $context ) {
			return $data;
		}
		list( $product_id, $product, $course_data ) = $context;
		if ( ! self::graph_has_type( $data, 'Course' ) ) {
			$data['spp-course'] = self::course_entity( $product_id, $product, $course_data );
		}
		if ( ! self::graph_has_type( $data, 'FAQPage' ) ) {
			$faq = self::faq_entity( $product_id, $course_data );
			if ( $faq ) {
				$data['spp-faq'] = $faq;
			}
		}
		return $data;
	}

	public static function fallback_schema() {
		if ( self::has_schema_provider() ) {
			return;
		}
		$context = self::context();
		if ( ! $context ) {
			return;
		}
		list( $product_id, $product, $data ) = $context;
		$graph = array( self::course_entity( $product_id, $product, $data ) );
		$faq = self::faq_entity( $product_id, $data );
		if ( $faq ) {
			$graph[] = $faq;
		}
		echo '<script type="application/ld+json">' . wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => $graph ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public static function technical_meta() {
		if ( ! SPP_V2_Template::is_active_request() ) {
			return;
		}
		echo '<meta name="theme-color" content="#000014">' . "\n";
		echo '<meta name="color-scheme" content="dark">' . "\n";
		if ( defined( 'SAZAN_SUITE_URL' ) ) {
			echo '<link rel="preload" href="' . esc_url( SAZAN_SUITE_URL . 'modules/core/assets/fonts/YekanBakhFaNumVF.ttf' ) . '" as="font" type="font/ttf" crossorigin>' . "\n";
		}
	}

	public static function fallback_meta() {
		if ( self::has_schema_provider() ) {
			return;
		}
		$context = self::context();
		if ( ! $context ) {
			return;
		}
		list( $product_id, $product, $data ) = $context;
		$title = ! empty( $data['seo']['fallback_title'] ) ? $data['seo']['fallback_title'] : wp_get_document_title();
		$description = self::description( $product_id, $data );
		list( $image_id, $image_url ) = self::social_image( $product, $data );
		$permalink = get_permalink( $product_id );
		echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
		echo '<meta property="og:locale" content="' . esc_attr( get_locale() ) . '">' . "\n";
		echo '<meta property="og:type" content="product">' . "\n";
		echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
		echo '<meta property="og:description" content="' . esc_attr( $description ) . '">' . "\n";
		echo '<meta property="og:url" content="' . esc_url( $permalink ) . '">' . "\n";
		echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '">' . "\n";
		if ( $image_url ) {
			echo '<meta property="og:image" content="' . esc_url( $image_url ) . '">' . "\n";
			echo '<meta property="og:image:alt" content="' . esc_attr( $product->get_name() ) . '">' . "\n";
			$meta = wp_get_attachment_metadata( $image_id );
			if ( ! empty( $meta['width'] ) && ! empty( $meta['height'] ) ) {
				echo '<meta property="og:image:width" content="' . esc_attr( $meta['width'] ) . '">' . "\n";
				echo '<meta property="og:image:height" content="' . esc_attr( $meta['height'] ) . '">' . "\n";
			}
		}
		if ( '' !== (string) $product->get_price() ) {
			echo '<meta property="product:price:amount" content="' . esc_attr( wc_format_decimal( $product->get_price(), wc_get_price_decimals() ) ) . '">' . "\n";
			echo '<meta property="product:price:currency" content="' . esc_attr( get_woocommerce_currency() ) . '">' . "\n";
		}
		echo '<meta property="product:availability" content="' . esc_attr( $product->is_in_stock() ? 'in stock' : 'out of stock' ) . '">' . "\n";
		echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
		echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '">' . "\n";
		echo '<meta name="twitter:description" content="' . esc_attr( $description ) . '">' . "\n";
		if ( $image_url ) {
			echo '<meta name="twitter:image" content="' . esc_url( $image_url ) . '">' . "\n";
			echo '<meta name="twitter:image:alt" content="' . esc_attr( $product->get_name() ) . '">' . "\n";
		}
	}

	public static function robots( $robots ) {
		if ( ! SPP_V2_Template::is_active_request() ) {
			return $robots;
		}
		$robots['max-image-preview'] = 'large';
		$robots['max-snippet'] = '-1';
		$robots['max-video-preview'] = '-1';
		return $robots;
	}

	public static function fallback_title( $parts ) {
		if ( self::has_schema_provider() ) {
			return $parts;
		}
		$context = self::context();
		if ( $context && ! empty( $context[2]['seo']['fallback_title'] ) ) {
			$parts['title'] = wp_strip_all_tags( $context[2]['seo']['fallback_title'] );
		}
		return $parts;
	}

	public static function register_rank_math_variables() {
		if ( ! function_exists( 'rank_math_register_var_replacement' ) ) {
			return;
		}
		$variables = array(
			'spp_course_duration' => array( 'مدت دوره سازان', 'مدت ISO 8601 ثبت‌شده برای دوره', array( __CLASS__, 'replacement_duration' ) ),
			'spp_course_level' => array( 'سطح دوره سازان', 'سطح آموزشی دوره', array( __CLASS__, 'replacement_level' ) ),
			'spp_course_instructor' => array( 'مدرس دوره سازان', 'نام مدرس اصلی دوره', array( __CLASS__, 'replacement_instructor' ) ),
			'spp_student_count' => array( 'تعداد دانشجویان سازان', 'تعداد دانشجویان نمایش‌داده‌شده در صفحه', array( __CLASS__, 'replacement_students' ) ),
		);
		foreach ( $variables as $slug => $config ) {
			rank_math_register_var_replacement( $slug, array(
				'name' => $config[0],
				'description' => $config[1],
				'variable' => $slug,
				'example' => call_user_func( $config[2] ),
			), $config[2] );
		}
	}

	private static function replacement_data() {
		$product_id = SPP_V2_Template::current_product_id();
		return $product_id ? SPP_V2_Data::get( $product_id ) : array();
	}

	public static function replacement_duration() {
		$data = self::replacement_data();
		return isset( $data['seo']['duration_iso'] ) ? $data['seo']['duration_iso'] : '';
	}

	public static function replacement_level() {
		$data = self::replacement_data();
		return isset( $data['seo']['course_level'] ) ? $data['seo']['course_level'] : '';
	}

	public static function replacement_instructor() {
		$data = self::replacement_data();
		return ! empty( $data['instructors']['items'][0]['name'] ) ? $data['instructors']['items'][0]['name'] : '';
	}

	public static function replacement_students() {
		$data = self::replacement_data();
		return isset( $data['intro']['student_count'] ) ? $data['intro']['student_count'] : '';
	}
}
