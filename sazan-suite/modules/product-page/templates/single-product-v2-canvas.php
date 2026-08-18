<?php
/** Full-width canvas for the generated Sazan single-product template. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$template_id = SPP_V2_Template::template_id();
$product_id  = SPP_V2_Template::current_product_id();
if ( $product_id && function_exists( 'wc_get_product' ) ) {
	$GLOBALS['product'] = wc_get_product( $product_id );
	if ( function_exists( 'WC' ) && WC() && isset( WC()->structured_data ) && is_callable( array( WC()->structured_data, 'generate_product_data' ) ) ) {
		WC()->structured_data->generate_product_data( $GLOBALS['product'] );
	}
}
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<?php if ( ! current_theme_supports( 'title-tag' ) ) : ?><title><?php echo esc_html( wp_get_document_title() ); ?></title><?php endif; ?>
	<?php wp_head(); ?>
	<style id="spp-v3-canvas-isolation">
		html{width:100%!important;max-width:100%!important;margin:0!important;padding:0!important;overflow-x:clip!important;background:#030316!important;scroll-behavior:smooth!important}
		body.spp-v3-premium{position:relative!important;width:100%!important;max-width:100%!important;min-width:0!important;margin:0!important;padding-right:0!important;padding-left:0!important;overflow-x:clip!important;background:radial-gradient(circle at 80% -10%,rgba(2,198,254,.11),transparent 25%),radial-gradient(circle at 12% 16%,rgba(243,180,1,.05),transparent 22%),#030316!important;color:#f5fbff!important}
		body.spp-v3-premium #spp-v2-main{position:relative!important;inset-inline:0!important;display:block!important;width:100%!important;max-width:100%!important;min-width:0!important;min-height:100vh!important;margin:0!important;padding:0!important;overflow-x:clip!important;overflow-y:visible!important;direction:rtl!important;color:#f5fbff!important;background:transparent!important;font-family:"YekanBakhFaNum","Yekan Bakh","IRANYekan",Tahoma,sans-serif!important;font-size:16px!important;font-style:normal!important;line-height:1.5!important;text-align:right!important;text-transform:none!important}
		body.spp-v3-premium #spp-v2-main,body.spp-v3-premium #spp-v2-main *{box-sizing:border-box}
		body.spp-v3-premium #spp-v2-main .spp-v3-app{position:relative!important;inset-inline:0!important;display:block!important;width:100%!important;max-width:100%!important;min-width:0!important;margin:0!important;padding:0!important;overflow-x:clip!important;isolation:isolate!important;color:#f5fbff!important;font-family:inherit!important}
		body.spp-v3-premium #spp-v2-main .container{width:min(calc(100% - 48px),1280px)!important;max-width:1280px!important;margin-inline:auto!important;padding-right:0!important;padding-left:0!important}
		body.spp-v3-premium #spp-v2-main .site-header .header-shell,body.spp-v3-premium #spp-v2-main .course-nav .course-nav-inner{width:min(calc(100% - 80px),1280px)!important;max-width:1280px!important;margin-inline:auto!important;padding-right:20px!important;padding-left:20px!important}
		body.spp-v3-premium #spp-v2-main .site-header .logo,body.spp-v3-premium #spp-v2-main .site-header .account,body.spp-v3-premium #spp-v2-main .course-nav .nav-cta{flex-shrink:0!important;max-width:calc(100% - 40px)!important}
		body.spp-v3-premium #spp-v2-main :where(h1,h2,h3,h4,h5,h6,p,a,button,input,output,small,strong,b,span,li,blockquote){font-family:inherit;text-transform:none}
		body.spp-v3-premium #spp-v2-main :where(a){text-decoration:none}
		body.spp-v3-premium #spp-v2-main :where(button,input){letter-spacing:normal}
		body.spp-v3-premium #spp-v2-main :where(img,svg,video,iframe){max-width:100%}
		body.spp-v3-premium.admin-bar #spp-v2-main .site-header{top:46px}
		body.spp-v3-premium.admin-bar #spp-v2-main .course-nav{top:auto!important;bottom:18px!important}
		@media(max-width:782px) and (min-width:721px){body.spp-v3-premium.admin-bar #spp-v2-main .site-header{top:60px}body.spp-v3-premium.admin-bar #spp-v2-main .course-nav{top:auto!important;bottom:18px!important}}
		@media(max-width:720px){body.spp-v3-premium{padding-top:calc(78px + env(safe-area-inset-top))!important;padding-bottom:calc(88px + env(safe-area-inset-bottom))!important;background:radial-gradient(circle at 50% -5%,rgba(2,198,254,.13),transparent 25%),linear-gradient(180deg,#030817,#030316 44%,#020513)!important}body.spp-v3-premium #spp-v2-main .container{width:min(calc(100% - 20px),1280px)!important}body.spp-v3-premium #spp-v2-main .course-nav .course-nav-inner{width:100%!important}body.spp-v3-premium.admin-bar #spp-v2-main .course-nav{top:46px!important}}
	</style>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="spp-v2-skip" href="#spp-v2-main">رفتن به محتوای دوره</a>
<main id="spp-v2-main" class="spp-v2-page" dir="rtl">
	<?php if ( function_exists( 'wc_print_notices' ) ) : ?><div class="spp-v2 spp-v2-woo-notices"><div class="spp-v2-container"><?php wc_print_notices(); ?></div></div><?php endif; ?>
	<?php echo SPP_V3_Renderer::render( $product_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</main>
<?php wp_footer(); ?>
</body>
</html>
