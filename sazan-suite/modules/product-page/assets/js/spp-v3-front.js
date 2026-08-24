(()=>{
'use strict';

const $=(s,p=document)=>p.querySelector(s), $$=(s,p=document)=>[...p.querySelectorAll(s)];
const reveals=$$('.reveal');
if('IntersectionObserver' in window){const io=new IntersectionObserver(es=>es.forEach(e=>{if(e.isIntersecting)e.target.classList.add('in')}),{threshold:.12});reveals.forEach(el=>io.observe(el))}else{reveals.forEach(el=>el.classList.add('in'))}
const reduceMotion=matchMedia('(prefers-reduced-motion:reduce)').matches;let panelSwitching=false;
$$('.path-btn').forEach(btn=>btn.addEventListener('click',async()=>{
  if(btn.classList.contains('active')||panelSwitching)return;
  const current=$('.panel.active'),next=$('#'+btn.dataset.panel);
  if(!next)return;
  $$('.panel').forEach(panel=>panel.getAnimations().forEach(animation=>animation.cancel()));
  $$('.path-btn').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  if(reduceMotion||!current||!next.animate){
    $$('.panel').forEach(p=>p.classList.remove('active'));
    next.classList.add('active');
    return;
  }
  panelSwitching=true;
  let exitAnimation,enterAnimation;
  try{
    exitAnimation=current.animate([
      {opacity:1,transform:'translateX(0)',filter:'blur(0)'},
      {opacity:0,transform:'translateX(-14px)',filter:'blur(3px)'}
    ],{duration:160,easing:'cubic-bezier(.4,0,1,1)',fill:'both'});
    try{await exitAnimation.finished}catch(e){}
    exitAnimation.cancel();
    current.classList.remove('active');
    next.classList.add('active');
    enterAnimation=next.animate([
      {opacity:0,transform:'translateX(18px)',filter:'blur(3px)'},
      {opacity:1,transform:'translateX(0)',filter:'blur(0)'}
    ],{duration:290,easing:'cubic-bezier(.22,1,.36,1)',fill:'both'});
    try{await enterAnimation.finished}catch(e){}
  }finally{
    if(exitAnimation)exitAnimation.cancel();
    if(enterAnimation)enterAnimation.cancel();
    $$('.panel').forEach(p=>p.classList.toggle('active',p===next));
    panelSwitching=false;
  }
}));
$$('.chapter-toggle').forEach(btn=>{const chapter=btn.closest('.chapter');btn.setAttribute('aria-expanded',chapter.classList.contains('open')?'true':'false');btn.addEventListener('click',()=>{const willOpen=!chapter.classList.contains('open');chapter.classList.toggle('open',willOpen);btn.setAttribute('aria-expanded',willOpen?'true':'false')})});
$$('.faq-item button').forEach(btn=>btn.addEventListener('click',()=>{const item=btn.closest('.faq-item'),open=!item.classList.contains('open');item.classList.toggle('open',open);btn.setAttribute('aria-expanded',open?'true':'false')}));
$$('.enroll-tab').forEach(btn=>btn.addEventListener('click',()=>{$$('.enroll-tab').forEach(tab=>{tab.classList.remove('active');tab.setAttribute('aria-selected','false')});btn.classList.add('active');btn.setAttribute('aria-selected','true');$$('.enroll-panel').forEach(panel=>panel.classList.remove('active'));$('#'+btn.dataset.enrollTarget).classList.add('active')}));
$$('[data-spp-v3-video]').forEach(trigger=>trigger.addEventListener('click',event=>{event.preventDefault();const url=trigger.dataset.sppV3Video;if(!url)return;const modal=document.createElement('div');modal.className='spp-v3-video-modal';modal.setAttribute('role','dialog');modal.setAttribute('aria-modal','true');modal.setAttribute('aria-label','ویدیوی دوره');const media=/\.(mp4|webm|ogg)(\?|$)/i.test(url)?`<video src="${url}" controls autoplay playsinline></video>`:`<iframe src="${url}" title="ویدیوی دوره" allow="autoplay; fullscreen" allowfullscreen></iframe>`;modal.innerHTML=`<div class="spp-v3-video-dialog"><button type="button" aria-label="بستن ویدیو">×</button>${media}</div>`;document.body.appendChild(modal);document.body.style.overflow='hidden';const close=()=>{modal.remove();document.body.style.overflow=''};modal.addEventListener('click',e=>{if(e.target===modal||e.target.closest('button'))close()});document.addEventListener('keydown',function esc(e){if(e.key==='Escape'){document.removeEventListener('keydown',esc);close()}},{once:true});modal.querySelector('button').focus()}));
const reviewConfig=window.SPPV3||{},reviewModal=$('[data-spp-v3-review-modal]'),reviewDialog=reviewModal?$('.review-dialog',reviewModal):null,reviewForm=reviewModal?$('[data-spp-v3-review-form]',reviewModal):null;let reviewLastFocus=null,reviewBodyOverflow='';
const reviewFocusable=()=>reviewDialog?$$('button:not([disabled]),input:not([disabled]):not([type="hidden"]):not([tabindex="-1"]),textarea:not([disabled]),select:not([disabled]),a[href]',reviewDialog).filter(el=>el.offsetParent!==null):[];
const openReview=()=>{if(!reviewModal||!reviewDialog)return;reviewLastFocus=document.activeElement;reviewBodyOverflow=document.body.style.overflow;reviewModal.hidden=false;requestAnimationFrame(()=>reviewModal.classList.add('is-open'));document.body.style.overflow='hidden';const first=$('input[name="name"]',reviewDialog)||reviewDialog;first.focus()};
const closeReview=()=>{if(!reviewModal)return;reviewModal.classList.remove('is-open');const finish=()=>{reviewModal.hidden=true;reviewModal.removeEventListener('transitionend',finish);document.body.style.overflow=reviewBodyOverflow;if(reviewLastFocus&&document.contains(reviewLastFocus))reviewLastFocus.focus()};if(reduceMotion)finish();else{reviewModal.addEventListener('transitionend',finish,{once:true});setTimeout(()=>{if(!reviewModal.hidden)finish()},260)}};
$$('[data-spp-v3-review-open]').forEach(trigger=>trigger.addEventListener('click',openReview));
if(reviewModal){reviewModal.addEventListener('click',event=>{if(event.target===reviewModal||event.target.closest('[data-spp-v3-review-close]'))closeReview()});document.addEventListener('keydown',event=>{if(reviewModal.hidden)return;if(event.key==='Escape'){event.preventDefault();closeReview();return}if(event.key!=='Tab')return;const focusable=reviewFocusable();if(!focusable.length){event.preventDefault();reviewDialog.focus();return}const first=focusable[0],last=focusable[focusable.length-1];if(event.shiftKey&&document.activeElement===first){event.preventDefault();last.focus()}else if(!event.shiftKey&&document.activeElement===last){event.preventDefault();first.focus()}})}
const syncReviewStars=()=>{if(!reviewForm)return;const checked=$('input[name="rating"]:checked',reviewForm),rating=checked?Number(checked.value):0;$$('.review-rating label',reviewForm).forEach(label=>label.classList.toggle('is-selected',Number($('input',label).value)<=rating))};
if(reviewForm){$$('input[name="rating"]',reviewForm).forEach(input=>input.addEventListener('change',syncReviewStars));reviewForm.addEventListener('submit',async event=>{event.preventDefault();const status=$('[data-spp-v3-review-status]',reviewForm),submit=$('.review-form__submit',reviewForm),i18n=reviewConfig.i18n||{};status.className='review-form__status';if(!reviewForm.checkValidity()){reviewForm.reportValidity();status.textContent='لطفاً فیلدهای ضروری را کامل کنید.';status.classList.add('is-error');return}status.textContent=i18n.sending||'در حال ارسال نظر…';submit.disabled=true;reviewForm.setAttribute('aria-busy','true');const payload=new FormData(reviewForm);payload.append('action','spp_submit_review');payload.append('nonce',reviewConfig.reviewNonce||'');try{const response=await fetch(reviewConfig.ajax||'/wp-admin/admin-ajax.php',{method:'POST',body:payload,credentials:'same-origin'}),result=await response.json();if(!response.ok||!result.success)throw new Error(result&&result.data&&result.data.message?result.data.message:(i18n.error||'ارسال نظر انجام نشد.'));status.textContent=result.data&&result.data.message?result.data.message:(i18n.success||'نظر شما ثبت شد و پس از تأیید نمایش داده می‌شود.');status.classList.add('is-success');reviewForm.classList.add('is-success');reviewForm.reset();syncReviewStars();submit.textContent='نظر شما ثبت شد';submit.disabled=true}catch(error){status.textContent=error.message||i18n.error||'ارسال نظر انجام نشد. دوباره تلاش کنید.';status.classList.add('is-error');submit.disabled=false}finally{reviewForm.removeAttribute('aria-busy')}})}
const enrollModal=$('[data-spp-v3-enroll-modal]'),enrollDialog=enrollModal?$('.enroll-dialog',enrollModal):null,enrollForm=enrollModal?$('[data-spp-v3-enroll-form]',enrollModal):null;let enrollLastFocus=null,enrollBodyOverflow='';
const enrollFocusable=()=>enrollDialog?$$('button:not([disabled]),input:not([disabled]):not([type="hidden"]):not([tabindex="-1"]),textarea:not([disabled]),select:not([disabled]),a[href]',enrollDialog).filter(el=>el.offsetParent!==null):[];
const openEnroll=()=>{if(!enrollModal||!enrollDialog)return;enrollLastFocus=document.activeElement;enrollBodyOverflow=document.body.style.overflow;enrollModal.hidden=false;requestAnimationFrame(()=>enrollModal.classList.add('is-open'));document.body.style.overflow='hidden';const first=$('input[name="first_name"]',enrollDialog)||enrollDialog;first.focus()};
const closeEnroll=()=>{if(!enrollModal)return;enrollModal.classList.remove('is-open');const finish=()=>{enrollModal.hidden=true;enrollModal.removeEventListener('transitionend',finish);document.body.style.overflow=enrollBodyOverflow;if(enrollLastFocus&&document.contains(enrollLastFocus))enrollLastFocus.focus()};if(reduceMotion)finish();else{enrollModal.addEventListener('transitionend',finish,{once:true});setTimeout(()=>{if(!enrollModal.hidden)finish()},260)}};
$$('[data-spp-v3-enroll-open]').forEach(trigger=>trigger.addEventListener('click',event=>{event.preventDefault();openEnroll()}));
if(enrollModal){enrollModal.addEventListener('click',event=>{if(event.target===enrollModal||event.target.closest('[data-spp-v3-enroll-close]'))closeEnroll()});document.addEventListener('keydown',event=>{if(enrollModal.hidden)return;if(event.key==='Escape'){event.preventDefault();closeEnroll();return}if(event.key!=='Tab')return;const focusable=enrollFocusable();if(!focusable.length){event.preventDefault();enrollDialog.focus();return}const first=focusable[0],last=focusable[focusable.length-1];if(event.shiftKey&&document.activeElement===first){event.preventDefault();last.focus()}else if(!event.shiftKey&&document.activeElement===last){event.preventDefault();first.focus()}})}
const copyValue=async text=>{if(navigator.clipboard&&window.isSecureContext){await navigator.clipboard.writeText(text);return}const field=document.createElement('textarea');field.value=text;field.setAttribute('readonly','');field.style.position='absolute';field.style.left='-9999px';document.body.appendChild(field);field.select();const ok=document.execCommand('copy');field.remove();if(!ok)throw new Error('copy failed')};
$$('[data-spp-v3-copy]').forEach(btn=>btn.addEventListener('click',async()=>{const value=btn.dataset.sppV3Copy;if(!value)return;try{await copyValue(value);btn.textContent='کپی شد';btn.classList.add('is-copied')}catch(e){btn.textContent='کپی نشد'}setTimeout(()=>{btn.textContent='کپی';btn.classList.remove('is-copied')},2200)}));
const enrollFile=enrollForm?$('[data-spp-v3-enroll-file]',enrollForm):null,enrollFileName=enrollForm?$('[data-spp-v3-enroll-filename]',enrollForm):null;
if(enrollFile&&enrollFileName){enrollFile.addEventListener('change',()=>{const file=enrollFile.files&&enrollFile.files[0];enrollFileName.textContent=file?file.name:'فایل رسید را انتخاب کنید';enrollFileName.classList.toggle('is-filled',!!file)})}
if(enrollForm){enrollForm.addEventListener('submit',async event=>{event.preventDefault();const status=$('[data-spp-v3-enroll-status]',enrollForm),submit=$('.review-form__submit',enrollForm),maxSize=Number(reviewConfig.enrollMaxSize)||8388608;status.className='review-form__status';
  if(!enrollForm.checkValidity()){enrollForm.reportValidity();status.textContent='لطفاً نام، نام خانوادگی، شماره موبایل و تصویر رسید را کامل کنید.';status.classList.add('is-error');return}
  const file=enrollFile&&enrollFile.files?enrollFile.files[0]:null;
  if(file&&file.size>maxSize){status.textContent='حجم رسید بیش از حد مجاز است. فایل کوچک‌تری انتخاب کنید.';status.classList.add('is-error');return}
  status.textContent='در حال ثبت‌نام…';submit.disabled=true;enrollForm.setAttribute('aria-busy','true');
  const payload=new FormData(enrollForm);payload.append('action','spp_v3_enroll');payload.append('nonce',reviewConfig.enrollNonce||'');
  try{const response=await fetch(reviewConfig.ajax||'/wp-admin/admin-ajax.php',{method:'POST',body:payload,credentials:'same-origin'}),result=await response.json();
    if(!response.ok||!result.success)throw new Error(result&&result.data&&result.data.message?result.data.message:'ثبت‌نام انجام نشد. دوباره تلاش کنید.');
    status.textContent=result.data&&result.data.message?result.data.message:'ثبت‌نام شما ثبت شد.';status.classList.add('is-success');enrollForm.classList.add('is-success');enrollForm.reset();if(enrollFileName){enrollFileName.textContent='فایل رسید را انتخاب کنید';enrollFileName.classList.remove('is-filled')}submit.textContent='ثبت‌نام شما ثبت شد';submit.disabled=true}
  catch(error){status.textContent=error.message||'ثبت‌نام انجام نشد. دوباره تلاش کنید.';status.classList.add('is-error');submit.disabled=false}
  finally{enrollForm.removeAttribute('aria-busy')}})}
const navLinks=$$('.course-nav a[href^="#"]');const sections=navLinks.map(a=>document.querySelector(a.getAttribute('href'))).filter(Boolean);const courseNav=$('.course-nav');let activeNavHref='';let navTicking=false;
const syncCourseNav=()=>{const mobile=matchMedia('(max-width:720px)').matches,offset=mobile?84:220,maxScroll=Math.max(1,document.documentElement.scrollHeight-innerHeight),progress=Math.min(1,Math.max(0,scrollY/maxScroll));if(courseNav)courseNav.style.setProperty('--page-progress',progress.toFixed(4));let current='';sections.forEach(s=>{if(scrollY>=s.offsetTop-offset)current='#'+s.id});if(!current&&sections[0])current='#'+sections[0].id;if(mobile&&current==='#enroll'&&navLinks.some(a=>a.getAttribute('href')==='#reviews'))current='#reviews';navLinks.forEach(a=>{const active=a.getAttribute('href')===current;a.classList.toggle('active',active);if(active)a.setAttribute('aria-current','location');else a.removeAttribute('aria-current')});if(current!==activeNavHref){activeNavHref=current;const activeLink=navLinks.find(a=>a.getAttribute('href')===current),navStrip=$('.course-nav-inner');if(mobile&&activeLink&&navStrip&&navStrip.scrollWidth>navStrip.clientWidth&&!activeLink.classList.contains('nav-cta'))activeLink.scrollIntoView({behavior:reduceMotion?'auto':'smooth',block:'nearest',inline:'center'})}navTicking=false};
window.addEventListener('scroll',()=>{if(!navTicking){navTicking=true;requestAnimationFrame(syncCourseNav)}},{passive:true});syncCourseNav();
if(matchMedia('(pointer:fine)').matches && $('.command-stage')){const stage=$('.command-stage');stage.addEventListener('mousemove',e=>{const r=stage.getBoundingClientRect(),x=(e.clientX-r.left)/r.width-.5,y=(e.clientY-r.top)/r.height-.5;$('.command-core').style.transform=`translateY(-8px) rotateY(${x*8}deg) rotateX(${-y*6}deg)`});stage.addEventListener('mouseleave',()=>$('.command-core').style.transform='translateY(-8px)')}

})();
