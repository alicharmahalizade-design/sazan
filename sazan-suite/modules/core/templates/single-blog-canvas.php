<?php
/** Canvas template for the automatically generated single-blog page. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$template_id = \Sazan\Single_Blog::template_id();
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php if ( ! current_theme_supports( 'title-tag' ) ) : ?><title><?php echo esc_html( wp_get_document_title() ); ?></title><?php endif; ?>
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="sazan-sb-skip" href="#sazan-single-blog-content">رفتن به محتوای مقاله</a>
<main id="sazan-single-blog-content" class="sazan-single-blog-page" dir="rtl">
	<?php
	if ( $template_id && class_exists( '\\Elementor\\Plugin' ) ) {
		echo \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $template_id, true ); // phpcs:ignore WordPress.Security.EscapeOutput
	}
	?>
</main>
<?php wp_footer(); ?>
</body>
</html>
