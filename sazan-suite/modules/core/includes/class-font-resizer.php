<?php
/**
 * دکمه‌ی شناور تغییر سایز فونت (دسترسی‌پذیری) — اعمال روی کل سایت.
 * استایل دارک/شیشه/بلر هماهنگ با سایت.
 *
 * @package Sazan\Core
 */

namespace Sazan;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Font_Resizer {

	private static $instance = null;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'wp_footer', array( $this, 'render' ), 99 );
	}

	private function enabled() {
		// در ویرایشگر/پیش‌نمایش المنتور نمایش داده نشود.
		if ( class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			return false;
		}
		return (int) Settings::get( 'font_resizer' ) === 1;
	}

	public function render() {
		if ( ! $this->enabled() ) {
			return;
		}
		?>
<style id="sz-fr-css">
.sz-fr{position:fixed;top:50%;left:0;transform:translateY(-50%);z-index:99990;font-family:inherit;direction:rtl}
.sz-fr *{box-sizing:border-box}
.sz-fr__toggle{display:flex;align-items:center;justify-content:center;width:46px;height:46px;border:1px solid rgba(255,255,255,.14);border-right:none;border-radius:0 14px 14px 0;background:rgba(15,39,53,.55);-webkit-backdrop-filter:blur(16px) saturate(150%);backdrop-filter:blur(16px) saturate(150%);color:#fff;cursor:pointer;box-shadow:0 18px 44px -16px rgba(0,0,0,.7);transition:background .2s,transform .2s}
.sz-fr__toggle:hover{background:rgba(15,39,53,.75);transform:translateX(2px)}
.sz-fr__toggle svg{width:22px;height:22px;display:block}
.sz-fr__panel{position:absolute;top:50%;left:54px;transform:translateY(-50%) scale(.96);transform-origin:left center;min-width:200px;padding:14px;border:1px solid rgba(255,255,255,.14);border-radius:16px;background:rgba(15,39,53,.6);-webkit-backdrop-filter:blur(20px) saturate(160%);backdrop-filter:blur(20px) saturate(160%);box-shadow:0 24px 60px -28px rgba(0,0,0,.8),inset 0 1px 0 rgba(255,255,255,.12);color:#fff;opacity:0;visibility:hidden;pointer-events:none;transition:opacity .2s,transform .2s,visibility .2s}
.sz-fr.is-open .sz-fr__panel{opacity:1;visibility:visible;pointer-events:auto;transform:translateY(-50%) scale(1)}
.sz-fr__title{font-size:13px;margin:0 0 10px;text-align:center;opacity:.85}
.sz-fr__row{display:flex;align-items:center;gap:8px}
.sz-fr__btn{flex:1;display:flex;align-items:center;justify-content:center;height:40px;border:1px solid rgba(255,255,255,.14);border-radius:10px;background:rgba(255,255,255,.06);color:#fff;cursor:pointer;font-size:18px;line-height:1;transition:background .15s,transform .1s}
.sz-fr__btn:hover{background:rgba(255,255,255,.16)}
.sz-fr__btn:active{transform:scale(.95)}
.sz-fr__btn--minus{font-size:14px}
.sz-fr__btn--plus{font-size:22px}
.sz-fr__level{flex:0 0 auto;min-width:46px;text-align:center;font-size:13px;font-variant-numeric:tabular-nums;direction:ltr}
.sz-fr__reset{display:block;width:100%;margin-top:10px;height:34px;border:1px solid rgba(255,255,255,.12);border-radius:10px;background:transparent;color:#fff;cursor:pointer;font-size:12px;opacity:.85;transition:background .15s,opacity .15s}
.sz-fr__reset:hover{background:rgba(255,255,255,.1);opacity:1}
@media (max-width:782px){
.sz-fr__toggle{width:42px;height:42px}
.sz-fr__panel{left:50px;min-width:180px}
}
</style>
<div class="sz-fr" id="szFontResizer" aria-hidden="false">
	<button class="sz-fr__toggle" type="button" aria-label="<?php esc_attr_e( 'تغییر سایز فونت', 'sazan-core' ); ?>" aria-expanded="false">
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20l5-12 5 12M6 15h6"/><path d="M15 20l3.5-8 3.5 8M16.5 16.5h4"/></svg>
	</button>
	<div class="sz-fr__panel" role="group" aria-label="<?php esc_attr_e( 'تنظیم اندازه فونت', 'sazan-core' ); ?>">
		<p class="sz-fr__title"><?php esc_html_e( 'اندازه فونت', 'sazan-core' ); ?></p>
		<div class="sz-fr__row">
			<button class="sz-fr__btn sz-fr__btn--minus" type="button" data-sz-fr="dec" aria-label="<?php esc_attr_e( 'کوچک‌تر', 'sazan-core' ); ?>">A−</button>
			<span class="sz-fr__level" data-sz-fr-level>100%</span>
			<button class="sz-fr__btn sz-fr__btn--plus" type="button" data-sz-fr="inc" aria-label="<?php esc_attr_e( 'بزرگ‌تر', 'sazan-core' ); ?>">A+</button>
		</div>
		<button class="sz-fr__reset" type="button" data-sz-fr="reset"><?php esc_html_e( 'بازنشانی به حالت اول', 'sazan-core' ); ?></button>
	</div>
</div>
<script id="sz-fr-js">
(function(){
	var KEY='szFontScale',MIN=0.85,MAX=1.5,STEP=0.05,base=null;
	var root=document.documentElement;
	function baseSize(){
		if(base===null){base=parseFloat(getComputedStyle(root).fontSize)||16;}
		return base;
	}
	function clamp(v){return Math.min(MAX,Math.max(MIN,Math.round(v*100)/100));}
	function apply(scale){
		// مقیاس روی font-size ریشه؛ واحدهای rem/em کل سایت متناسب می‌شوند بدون شکستن چیدمان.
		root.style.fontSize=(baseSize()*scale)+'px';
		var lvl=document.querySelector('[data-sz-fr-level]');
		if(lvl){lvl.textContent=Math.round(scale*100)+'%';}
	}
	function load(){var s=parseFloat(localStorage.getItem(KEY));return (s&&!isNaN(s))?clamp(s):1;}
	function save(s){try{localStorage.setItem(KEY,s);}catch(e){}}
	var cur=load();
	apply(cur);
	function ready(fn){if(document.readyState!='loading'){fn();}else{document.addEventListener('DOMContentLoaded',fn);}}
	ready(function(){
		apply(cur); // اطمینان از به‌روزرسانی نمایشگر درصد
		var wrap=document.getElementById('szFontResizer');
		if(!wrap)return;
		var toggle=wrap.querySelector('.sz-fr__toggle');
		toggle.addEventListener('click',function(e){
			e.stopPropagation();
			var open=wrap.classList.toggle('is-open');
			toggle.setAttribute('aria-expanded',open?'true':'false');
		});
		document.addEventListener('click',function(e){
			if(!wrap.contains(e.target)){wrap.classList.remove('is-open');toggle.setAttribute('aria-expanded','false');}
		});
		wrap.addEventListener('click',function(e){
			var b=e.target.closest('[data-sz-fr]');if(!b)return;
			var a=b.getAttribute('data-sz-fr');
			if(a==='inc')cur=clamp(cur+STEP);
			else if(a==='dec')cur=clamp(cur-STEP);
			else if(a==='reset')cur=1;
			save(cur);apply(cur);
		});
	});
})();
</script>
		<?php
	}
}
