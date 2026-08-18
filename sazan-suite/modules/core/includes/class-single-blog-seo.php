<?php
/**
 * SEO fallback for the Sazan single-blog canvas.
 *
 * WordPress core remains responsible for canonical URLs and XML sitemaps. When a
 * dedicated SEO plugin is active, this class intentionally stays silent to avoid
 * duplicate meta tags or structured-data graphs.
 *
 * @package Sazan\Core
 */

namespace Sazan;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Single_Blog_SEO {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'wp_head', array( $this, 'output_meta' ), 5 );
		add_action( 'wp_head', array( $this, 'output_schema' ), 30 );
		add_filter( 'wp_robots', array( $this, 'robots' ) );
	}

	private function should_output() {
		return Single_Blog::is_active_request()
			&& is_singular( 'post' )
			&& ! post_password_required()
			&& ! self::has_seo_plugin();
	}

	/**
	 * Detect common SEO suites. Site owners can override this result.
	 */
	public static function has_seo_plugin() {
		$active = defined( 'WPSEO_VERSION' )
			|| defined( 'RANK_MATH_VERSION' )
			|| defined( 'AIOSEO_VERSION' )
			|| defined( 'SEOPRESS_VERSION' )
			|| defined( 'SLIM_SEO_VERSION' )
			|| defined( 'SLIM_SEO_VER' )
			|| defined( 'THE_SEO_FRAMEWORK_VERSION' )
			|| class_exists( '\\WPSEO_Options' )
			|| class_exists( '\\RankMath' )
			|| class_exists( '\\AIOSEO\\Plugin\\Common\\Main' )
			|| class_exists( '\\SEOPress\\Main' )
			|| function_exists( 'the_seo_framework' )
			|| ( function_exists( 'has_filter' ) && has_filter( 'wp_head', 'jetpack_og_tags' ) );

		return (bool) apply_filters( 'sazan_single_blog_has_seo_plugin', $active );
	}

	public function robots( $robots ) {
		if ( $this->should_output() ) {
			$robots['max-image-preview'] = 'large';
			$robots['max-snippet']       = -1;
			$robots['max-video-preview'] = -1;
		}
		return $robots;
	}

	private static function description( $post_id ) {
		$text = trim( wp_strip_all_tags( (string) get_the_excerpt( $post_id ), true ) );
		if ( '' === $text ) {
			$text = trim( wp_strip_all_tags( strip_shortcodes( (string) get_post_field( 'post_content', $post_id ) ), true ) );
		}
		return html_entity_decode( wp_html_excerpt( preg_replace( '/\s+/u', ' ', $text ), 160, '…' ), ENT_QUOTES, get_bloginfo( 'charset' ) );
	}

	/**
	 * Normalized image shared by social metadata and schema.
	 *
	 * @return array{url:string,width:int,height:int,alt:string}
	 */
	private static function image( $post_id ) {
		$attachment_id = get_post_thumbnail_id( $post_id );
		$source        = $attachment_id ? wp_get_attachment_image_src( $attachment_id, 'full' ) : false;
		$alt           = $attachment_id ? trim( (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) : '';

		if ( $source ) {
			return array(
				'url'    => $source[0],
				'width'  => absint( $source[1] ),
				'height' => absint( $source[2] ),
				'alt'    => $alt ? $alt : get_the_title( $post_id ),
			);
		}

		return array(
			'url'    => SAZAN_CORE_URL . 'assets/img/sazan-blog-fallback.png',
			'width'  => 1881,
			'height' => 836,
			'alt'    => get_the_title( $post_id ),
		);
	}

	public function output_meta() {
		if ( ! $this->should_output() ) {
			return;
		}

		$post_id     = get_queried_object_id();
		$title       = wp_strip_all_tags( get_the_title( $post_id ) );
		$description = self::description( $post_id );
		$url         = get_permalink( $post_id );
		$image       = self::image( $post_id );
		$secure_image = 'https' === wp_parse_url( $image['url'], PHP_URL_SCHEME ) ? $image['url'] : '';
		$author_id   = absint( get_post_field( 'post_author', $post_id ) );
		$categories  = get_the_category( $post_id );
		$tags        = get_the_tags( $post_id );
		?>
		<!-- Sazan Single Blog SEO fallback -->
		<meta name="description" content="<?php echo esc_attr( $description ); ?>">
		<meta property="og:type" content="article">
		<meta property="og:title" content="<?php echo esc_attr( $title ); ?>">
		<meta property="og:description" content="<?php echo esc_attr( $description ); ?>">
		<meta property="og:url" content="<?php echo esc_url( $url ); ?>">
		<meta property="og:site_name" content="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
		<meta property="og:locale" content="<?php echo esc_attr( get_locale() ); ?>">
		<meta property="og:image" content="<?php echo esc_url( $image['url'] ); ?>">
		<?php if ( $secure_image ) : ?><meta property="og:image:secure_url" content="<?php echo esc_url( $secure_image ); ?>"><?php endif; ?>
		<meta property="og:image:width" content="<?php echo esc_attr( $image['width'] ); ?>">
		<meta property="og:image:height" content="<?php echo esc_attr( $image['height'] ); ?>">
		<meta property="og:image:alt" content="<?php echo esc_attr( $image['alt'] ); ?>">
		<meta property="article:published_time" content="<?php echo esc_attr( get_post_time( DATE_W3C, true, $post_id ) ); ?>">
		<meta property="article:modified_time" content="<?php echo esc_attr( get_post_modified_time( DATE_W3C, true, $post_id ) ); ?>">
		<meta property="article:author" content="<?php echo esc_url( get_author_posts_url( $author_id ) ); ?>">
		<?php if ( $categories ) : ?><meta property="article:section" content="<?php echo esc_attr( $categories[0]->name ); ?>"><?php endif; ?>
		<?php if ( $tags ) : foreach ( $tags as $tag ) : ?><meta property="article:tag" content="<?php echo esc_attr( $tag->name ); ?>">
		<?php endforeach; endif; ?>
		<meta name="twitter:card" content="summary_large_image">
		<meta name="twitter:title" content="<?php echo esc_attr( $title ); ?>">
		<meta name="twitter:description" content="<?php echo esc_attr( $description ); ?>">
		<meta name="twitter:image" content="<?php echo esc_url( $image['url'] ); ?>">
		<meta name="twitter:image:alt" content="<?php echo esc_attr( $image['alt'] ); ?>">
		<?php
	}

	private static function publisher() {
		$logo_id  = absint( get_theme_mod( 'custom_logo' ) );
		$logo_src = $logo_id ? wp_get_attachment_image_src( $logo_id, 'full' ) : false;
		$logo_url = $logo_src ? $logo_src[0] : get_site_icon_url( 512 );
		$publisher = array(
			'@type' => 'Organization',
			'@id'   => home_url( '/#organization' ),
			'name'  => get_bloginfo( 'name' ),
			'url'   => home_url( '/' ),
		);
		if ( $logo_url ) {
			$publisher['logo'] = array( '@type' => 'ImageObject', 'url' => $logo_url );
		}
		return $publisher;
	}

	private static function breadcrumbs( $post_id ) {
		$items    = array();
		$position = 1;
		$items[]  = array( '@type' => 'ListItem', 'position' => $position++, 'name' => 'خانه', 'item' => home_url( '/' ) );
		$blog_id  = absint( get_option( 'page_for_posts' ) );
		if ( $blog_id ) {
			$items[] = array( '@type' => 'ListItem', 'position' => $position++, 'name' => get_the_title( $blog_id ), 'item' => get_permalink( $blog_id ) );
		}
		$categories = get_the_category( $post_id );
		if ( $categories ) {
			$items[] = array( '@type' => 'ListItem', 'position' => $position++, 'name' => $categories[0]->name, 'item' => get_category_link( $categories[0] ) );
		}
		$items[] = array( '@type' => 'ListItem', 'position' => $position, 'name' => get_the_title( $post_id ) );
		return $items;
	}

	/**
	 * Build the JSON-LD graph separately so it can be unit-tested and filtered.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function schema_graph( $post_id ) {
		$post_id    = absint( $post_id );
		$url        = get_permalink( $post_id );
		$image      = self::image( $post_id );
		$author_id  = absint( get_post_field( 'post_author', $post_id ) );
		$author_url = get_author_posts_url( $author_id );
		$author_web = esc_url_raw( (string) get_the_author_meta( 'user_url', $author_id ) );
		$categories = get_the_category( $post_id );
		$tags       = get_the_tags( $post_id );
		$content    = trim( wp_strip_all_tags( strip_shortcodes( (string) get_post_field( 'post_content', $post_id ) ) ) );
		$words      = preg_split( '/[\s\x{200c}]+/u', $content, -1, PREG_SPLIT_NO_EMPTY );

		$author = array(
			'@type' => 'Person',
			'@id'   => $author_url . '#author',
			'name'  => get_the_author_meta( 'display_name', $author_id ),
			'url'   => $author_url,
		);
		if ( $author_web && $author_web !== $author_url ) {
			$author['sameAs'] = array( $author_web );
		}

		$article = array(
			'@type'            => 'BlogPosting',
			'@id'              => $url . '#article',
			'url'              => $url,
			'mainEntityOfPage' => array( '@type' => 'WebPage', '@id' => $url ),
			'headline'         => wp_strip_all_tags( get_the_title( $post_id ) ),
			'description'      => self::description( $post_id ),
			'image'            => array(
				'@type'   => 'ImageObject',
				'url'     => $image['url'],
				'width'   => $image['width'],
				'height'  => $image['height'],
				'caption' => $image['alt'],
			),
			'thumbnailUrl'     => $image['url'],
			'datePublished'    => get_post_time( DATE_W3C, true, $post_id ),
			'dateModified'     => get_post_modified_time( DATE_W3C, true, $post_id ),
			'author'           => $author,
			'publisher'        => self::publisher(),
			'inLanguage'       => get_bloginfo( 'language' ),
			'isAccessibleForFree' => true,
			'wordCount'        => count( (array) $words ),
		);
		if ( $categories ) {
			$article['articleSection'] = $categories[0]->name;
		}
		if ( $tags ) {
			$article['keywords'] = implode( ', ', wp_list_pluck( $tags, 'name' ) );
		}

		return array(
			$article,
			array(
				'@type'           => 'BreadcrumbList',
				'@id'             => $url . '#breadcrumb',
				'itemListElement' => self::breadcrumbs( $post_id ),
			),
		);
	}

	public function output_schema() {
		if ( ! $this->should_output() || '0' === (string) get_option( 'blog_public', '1' ) ) {
			return;
		}
		$post_id = get_queried_object_id();
		$graph   = apply_filters( 'sazan_single_blog_schema_graph', self::schema_graph( $post_id ), $post_id );
		$data    = array( '@context' => 'https://schema.org', '@graph' => array_values( (array) $graph ) );
		echo '<script type="application/ld+json" class="sazan-single-blog-schema">' . wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
