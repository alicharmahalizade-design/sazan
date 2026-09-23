<?php
/**
 * Landing canvas: a blank document (no theme header/footer/styles) that renders the
 * Elementor content exactly like the original static landing page.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?><!doctype html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="<?php bloginfo( 'charset' ); ?>">
  <meta name="viewport" content="width=device-width,initial-scale=1">
<?php if ( ! current_theme_supports( 'title-tag' ) ) : ?>
  <title><?php echo esc_html( wp_get_document_title() ); ?></title>
<?php endif; ?>
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?> data-design-version="2">
<?php wp_body_open(); ?>
  <a class="skip-link" href="#main-content">رفتن به محتوای اصلی</a>
  <div class="scroll-progress" aria-hidden="true"><span></span></div>
<?php
HKL_Plugin::print_sprite();

while ( have_posts() ) :
	the_post();
	the_content();
endwhile;

wp_footer();
?>
</body>
</html>
