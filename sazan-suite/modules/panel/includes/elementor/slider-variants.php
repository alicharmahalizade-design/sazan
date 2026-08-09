<?php
// Auto-generated from the Claude Design canvas «اسلایدر دوره‌ها». Do not edit by hand.
if ( ! defined( 'ABSPATH' ) ) { exit; }
return array(
  '6a' => array(
    'label' => 'انفجار انرژی',
    'dots' => '<button type="button" data-i="{I}" class="szp-cs-dot{DACTIVE}"></button>',
    'dots_v' => false,
    'slides' => array(
      array( 'anim' => 'slidex', 'tpl' => '<div class="szp-cs-slide{ACTIVE}" data-i="{I}">
              <div style="height: 100%; display: flex; flex-direction: column; justify-content: center; gap: 15px; align-items: flex-start; padding-right: 100px; box-sizing: border-box;">
                <div style="display: flex; align-items: center; gap: 9px; color: #001018; font-size: 14px; font-weight: 900; background: linear-gradient(120deg, #4cc3e4, #1893B8); border-radius: 100px; padding: 7px 18px; box-shadow: 0 0 24px rgba(24,147,184,.5);">⚡ در حال ثبت‌نام</div>
                <h2 style="margin: 0; color: #f2f6f9; font-size: 64px; font-weight: 900; line-height: 1.2; text-shadow: 0 0 50px rgba(24,147,184,.4);">{NAME}</h2>
                <p style="margin: 0; color: #8ba3b3; font-size: 17px; font-weight: 300; line-height: 1.9; max-width: 530px;">{DESC}</p>
                <div style="display: flex; align-items: center; gap: 24px; margin-top: 6px;">
                  <a href="{URL}" style="position: relative; overflow: hidden; background: #1893B8; color: #001018; font-size: 18px; font-weight: 900; padding: 15px 48px; border-radius: 14px; box-shadow: 0 0 36px rgba(24,147,184,.6);">
                    <span style="position: absolute; top: 0; bottom: 0; width: 44px; background: rgba(255,255,255,.4); animation: shine 2.4s ease-in-out infinite;"></span>
                    ثبت‌نام در دوره
                  </a>
                  <div style="color: #6d8494; font-size: 15px;">شروع: <span style="color: #d4e2ea; font-weight: 700;">{DATE}</span></div>
                </div>
              </div>
            </div>' ),
    ),
    'chrome' => '<div dir="rtl" class="szp-cs-stage" style="width: 1440px; height: 440px; position: relative;">
      <div style="position: absolute; inset: 0; border-radius: 24px; overflow: hidden; background: #000014;">
        <div style="position: absolute; inset: 0; background: conic-gradient(from 210deg at 22% 75%, rgba(24,147,184,.35) 0deg, transparent 70deg, transparent 290deg, rgba(24,147,184,.18) 360deg);"></div>
        <!-- energy rays behind archer -->
        <div style="position: absolute; left: 90px; bottom: -60px; width: 480px; height: 480px;">
          <div style="position: absolute; inset: 0; border-radius: 50%; border: 2px solid rgba(76,195,228,.55); animation: ringPulse 2.6s ease-out infinite;"></div>
          <div style="position: absolute; inset: 0; border-radius: 50%; border: 2px solid rgba(76,195,228,.4); animation: ringPulse 2.6s ease-out infinite; animation-delay: 1.3s;"></div>
          <div style="position: absolute; inset: 60px; border-radius: 50%; background: radial-gradient(circle, rgba(24,147,184,.5) 0%, transparent 65%);"></div>
        </div>
        <div style="position: absolute; top: -120px; bottom: -120px; left: 250px; width: 2px; background: linear-gradient(180deg, transparent, rgba(76,195,228,.7), transparent); transform: rotate(24deg);"></div>
        <div style="position: absolute; top: -120px; bottom: -120px; left: 420px; width: 1px; background: linear-gradient(180deg, transparent, rgba(76,195,228,.4), transparent); transform: rotate(-18deg);"></div>
        <!-- slides -->
        <div style="position: absolute; top: 0; right: 0; bottom: 0; width: 750px;">
          {SLIDES0}
        </div>
        <!-- controls -->
        <div style="position: absolute; bottom: 28px; right: 100px; display: flex; align-items: center; gap: 14px;">
          <button data-cs="next" style="width: 42px; height: 42px; border-radius: 50%; border: 1.5px solid rgba(76,195,228,.7); background: transparent; color: #4cc3e4; font-size: 18px; cursor: pointer; font-family: inherit; box-shadow: 0 0 16px rgba(24,147,184,.3);">›</button>
          <div style="display: flex; gap: 7px;">
            {DOTS}
          </div>
          <button data-cs="prev" style="width: 42px; height: 42px; border-radius: 50%; border: 1.5px solid rgba(76,195,228,.7); background: transparent; color: #4cc3e4; font-size: 18px; cursor: pointer; font-family: inherit; box-shadow: 0 0 16px rgba(24,147,184,.3);">‹</button>
        </div>
      </div>
      <!-- archer at the heart of the blast, breaking out -->
      <img class="szp-cs-archer" src="{ARCHER}" alt="کماندار" style="position: absolute; left: 70px; bottom: 0; width: 555px; filter: drop-shadow(0 26px 55px rgba(0,0,0,.8)) drop-shadow(0 0 40px rgba(24,147,184,.35)); pointer-events: none;">
    </div>
  </div>

  <!-- ═══════════ 6b — شفق شیشه‌ای ═══════════ -->',
  ),
  '6b' => array(
    'label' => 'شفق شیشه‌ای',
    'dots' => '<button type="button" data-i="{I}" class="szp-cs-dot{DACTIVE}"></button>',
    'dots_v' => false,
    'slides' => array(
      array( 'anim' => 'fade', 'tpl' => '<div class="szp-cs-slide{ACTIVE}" data-i="{I}">
              <div style="height: 100%; display: flex; flex-direction: column; justify-content: center; gap: 15px; align-items: flex-start; padding: 0 54px; box-sizing: border-box;">
                <div style="display: flex; align-items: center; gap: 9px; color: #4cc3e4; font-size: 14px; font-weight: 700; border: 1px solid rgba(76,195,228,.45); background: rgba(76,195,228,.08); border-radius: 100px; padding: 6px 16px;">
                  <span style="width: 7px; height: 7px; border-radius: 50%; background: #4cc3e4; box-shadow: 0 0 10px #4cc3e4;"></span>
                  در حال ثبت‌نام
                </div>
                <h2 style="margin: 0; font-size: 56px; font-weight: 900; line-height: 1.25; background: linear-gradient(120deg, #f2f6f9 30%, #4cc3e4); -webkit-background-clip: text; background-clip: text; color: transparent;">{NAME}</h2>
                <p style="margin: 0; color: #9db4c2; font-size: 17px; font-weight: 300; line-height: 1.9; max-width: 540px;">{DESC}</p>
                <div style="display: flex; align-items: center; gap: 24px; margin-top: 6px;">
                  <a href="{URL}" style="background: rgba(76,195,228,.95); color: #001018; font-size: 17px; font-weight: 900; padding: 13px 44px; border-radius: 100px; box-shadow: 0 14px 36px rgba(24,147,184,.45);">ثبت‌نام در دوره</a>
                  <div style="color: #7d94a4; font-size: 15px;">شروع: <span style="color: #e8f2f7; font-weight: 700;">{DATE}</span></div>
                </div>
              </div>
            </div>' ),
    ),
    'chrome' => '<div dir="rtl" class="szp-cs-stage" style="width: 1440px; height: 440px; position: relative;">
      <div style="position: absolute; inset: 0; border-radius: 24px; overflow: hidden; background: #000014;">
        <!-- aurora blobs -->
        <div style="position: absolute; left: 500px; top: -120px; width: 560px; height: 420px; border-radius: 50%; background: radial-gradient(circle, rgba(24,147,184,.35) 0%, transparent 65%); filter: blur(30px); animation: aurora 11s ease-in-out infinite;"></div>
        <div style="position: absolute; right: -80px; bottom: -160px; width: 520px; height: 400px; border-radius: 50%; background: radial-gradient(circle, rgba(15,29,40,1) 0%, rgba(24,147,184,.2) 50%, transparent 70%); filter: blur(24px); animation: aurora 14s ease-in-out infinite reverse;"></div>
        <div style="position: absolute; left: 80px; bottom: -100px; width: 480px; height: 360px; border-radius: 50%; background: radial-gradient(circle, rgba(76,195,228,.22) 0%, transparent 65%); filter: blur(26px); animation: aurora 12s ease-in-out infinite; animation-delay: 3s;"></div>
        <!-- frosted content sheet -->
        <div style="position: absolute; top: 48px; bottom: 48px; right: 60px; width: 720px; background: rgba(15,29,40,.35); border: 1px solid rgba(76,195,228,.28); border-radius: 24px; backdrop-filter: blur(16px); box-shadow: 0 30px 70px rgba(0,0,20,.5), inset 0 1px 0 rgba(76,195,228,.25);"></div>
        <!-- slides -->
        <div style="position: absolute; top: 48px; bottom: 48px; right: 60px; width: 720px;">
          {SLIDES0}
        </div>
        <!-- controls on the sheet -->
        <div style="position: absolute; bottom: 66px; right: 114px; display: flex; align-items: center; gap: 14px;">
          <button data-cs="next" style="width: 40px; height: 40px; border-radius: 50%; border: 1px solid rgba(76,195,228,.5); background: rgba(76,195,228,.08); color: #4cc3e4; font-size: 17px; cursor: pointer; font-family: inherit;">›</button>
          <div style="display: flex; gap: 7px;">
            {DOTS}
          </div>
          <button data-cs="prev" style="width: 40px; height: 40px; border-radius: 50%; border: 1px solid rgba(76,195,228,.5); background: rgba(76,195,228,.08); color: #4cc3e4; font-size: 17px; cursor: pointer; font-family: inherit;">‹</button>
        </div>
      </div>
      <!-- archer in front of the aurora, breaking out -->
      <img class="szp-cs-archer" src="{ARCHER}" alt="کماندار" style="position: absolute; left: 65px; bottom: 0; width: 550px; filter: drop-shadow(0 26px 55px rgba(0,0,0,.8)); pointer-events: none;">
    </div>
  </div>

  <!-- ═══════════ 5a — هولوگرام فرماندهی ═══════════ -->',
  ),
  '5a' => array(
    'label' => 'هولوگرام فرماندهی',
    'dots' => '<button type="button" data-i="{I}" class="szp-cs-dot{DACTIVE}"></button>',
    'dots_v' => false,
    'slides' => array(
      array( 'anim' => 'fade', 'tpl' => '<div class="szp-cs-slide{ACTIVE}" data-i="{I}">
              <div style="height: 100%; display: flex; flex-direction: column; justify-content: center; gap: 15px; align-items: flex-start; padding-right: 110px; box-sizing: border-box;">
                <div style="display: flex; align-items: center; gap: 9px; color: #4cc3e4; font-size: 13px; font-weight: 700; font-family: monospace; letter-spacing: 2px; border: 1px solid rgba(24,147,184,.6); border-radius: 4px; padding: 6px 14px; background: rgba(24,147,184,.08);">
                  <span style="width: 7px; height: 7px; background: #1893B8; box-shadow: 0 0 8px #1893B8; animation: blink 1.6s ease-in-out infinite;"></span>
                  ● در حال ثبت‌نام
                </div>
                <h2 style="margin: 0; color: #f2f6f9; font-size: 58px; font-weight: 900; line-height: 1.25; text-shadow: 0 0 40px rgba(24,147,184,.35);">{NAME}</h2>
                <div style="display: flex; gap: 0; border: 1px solid rgba(24,147,184,.35); border-radius: 8px; overflow: hidden;">
                  <div style="padding: 10px 22px; color: #6d8494; font-size: 14px; border-left: 1px solid rgba(24,147,184,.35);">شروع <span style="color: #d4e2ea; font-weight: 700;">{DATE}</span></div>
                  <div style="padding: 10px 22px; color: #6d8494; font-size: 14px;">ظرفیت <span style="color: #d4e2ea; font-weight: 700;">محدود</span></div>
                </div>
                <a href="{URL}" style="margin-top: 6px; background: #1893B8; color: #001018; font-size: 17px; font-weight: 700; padding: 13px 44px; border-radius: 8px; box-shadow: 0 0 30px rgba(24,147,184,.5); clip-path: polygon(0 0, calc(100% - 14px) 0, 100% 14px, 100% 100%, 14px 100%, 0 calc(100% - 14px));">ثبت‌نام در دوره</a>
              </div>
            </div>' ),
    ),
    'chrome' => '<div dir="rtl" class="szp-cs-stage" style="width: 1440px; height: 440px; position: relative;">
      <div style="position: absolute; inset: 0; border-radius: 24px; overflow: hidden; background: #000014;">
        <div style="position: absolute; inset: 0; background: radial-gradient(800px 480px at 22% 80%, rgba(15,29,40,1) 0%, transparent 72%);"></div>
        <!-- HUD corner brackets -->
        <div style="position: absolute; top: 26px; right: 26px; width: 54px; height: 54px; border-top: 2px solid rgba(24,147,184,.8); border-right: 2px solid rgba(24,147,184,.8); border-radius: 0 8px 0 0;"></div>
        <div style="position: absolute; bottom: 26px; right: 26px; width: 54px; height: 54px; border-bottom: 2px solid rgba(24,147,184,.8); border-right: 2px solid rgba(24,147,184,.8); border-radius: 0 0 8px 0;"></div>
        <div style="position: absolute; top: 26px; left: 26px; width: 54px; height: 54px; border-top: 2px solid rgba(24,147,184,.8); border-left: 2px solid rgba(24,147,184,.8); border-radius: 8px 0 0 0;"></div>
        <div style="position: absolute; bottom: 26px; left: 26px; width: 54px; height: 54px; border-bottom: 2px solid rgba(24,147,184,.8); border-left: 2px solid rgba(24,147,184,.8); border-radius: 0 0 0 8px;"></div>
        <!-- scan line over archer zone -->
        <div style="position: absolute; left: 60px; width: 540px; height: 2px; background: linear-gradient(90deg, transparent, rgba(76,195,228,.9), transparent); box-shadow: 0 0 18px rgba(24,147,184,.8); animation: scanY 4.5s linear infinite;"></div>
        <!-- telemetry ticks, top -->
        <div dir="ltr" style="position: absolute; top: 40px; left: 120px; display: flex; gap: 6px; align-items: flex-end;">
          <div style="width: 3px; height: 10px; background: rgba(24,147,184,.7);"></div>
          <div style="width: 3px; height: 18px; background: rgba(24,147,184,.45);"></div>
          <div style="width: 3px; height: 8px; background: rgba(24,147,184,.7);"></div>
          <div style="width: 3px; height: 14px; background: rgba(24,147,184,.35);"></div>
          <div style="width: 3px; height: 22px; background: rgba(24,147,184,.6);"></div>
          <div style="width: 3px; height: 9px; background: rgba(24,147,184,.4);"></div>
        </div>
        <div style="position: absolute; top: 44px; left: 200px; color: rgba(76,195,228,.7); font-size: 12px; font-family: monospace; letter-spacing: 3px;">TARGET LOCKED — <span class="szp-cs-counter" data-en="1"></span></div>
        <!-- holo rings under archer -->
        <div style="position: absolute; left: 130px; bottom: 22px; width: 400px; height: 90px; border-radius: 50%; border: 1.5px solid rgba(24,147,184,.65); transform: perspective(300px) rotateX(62deg); box-shadow: 0 0 30px rgba(24,147,184,.35);"></div>
        <div style="position: absolute; left: 170px; bottom: 32px; width: 320px; height: 70px; border-radius: 50%; border: 1px dashed rgba(24,147,184,.45); transform: perspective(300px) rotateX(62deg); animation: glowPulse 3s ease-in-out infinite;"></div>
        <!-- slides -->
        <div style="position: absolute; top: 0; right: 0; bottom: 0; width: 740px;">
          {SLIDES0}
        </div>
        <!-- controls -->
        <div style="position: absolute; bottom: 40px; right: 110px; display: flex; align-items: center; gap: 14px;">
          <button data-cs="next" style="width: 40px; height: 40px; border-radius: 4px; border: 1px solid rgba(24,147,184,.6); background: rgba(24,147,184,.06); color: #4cc3e4; font-size: 17px; cursor: pointer; font-family: inherit;">›</button>
          <div style="display: flex; gap: 7px;">
            {DOTS}
          </div>
          <button data-cs="prev" style="width: 40px; height: 40px; border-radius: 4px; border: 1px solid rgba(24,147,184,.6); background: rgba(24,147,184,.06); color: #4cc3e4; font-size: 17px; cursor: pointer; font-family: inherit;">‹</button>
        </div>
      </div>
      <!-- archer breaking out, holographic -->
      <img class="szp-cs-archer" src="{ARCHER}" alt="کماندار" style="position: absolute; left: 60px; bottom: 14px; width: 545px; filter: drop-shadow(0 26px 55px rgba(0,0,0,.75)) drop-shadow(0 0 26px rgba(24,147,184,.3)); pointer-events: none;">
    </div>
  </div>

  <!-- ═══════════ 5b — پوستر سینمایی ═══════════ -->',
  ),
  '5b' => array(
    'label' => 'پوستر سینمایی',
    'dots' => null,
    'dots_v' => false,
    'slides' => array(
      array( 'anim' => 'slidex', 'tpl' => '<div class="szp-cs-slide{ACTIVE}" data-i="{I}">
              <div style="height: 100%; display: flex; flex-direction: column; justify-content: center; gap: 14px; align-items: flex-start; padding-right: 100px; box-sizing: border-box;">
                <div style="color: #4cc3e4; font-size: 15px; font-weight: 500; letter-spacing: 6px;">— در حال ثبت‌نام —</div>
                <h2 style="margin: 0; color: #f2f6f9; font-size: 72px; font-weight: 900; line-height: 1.15; text-shadow: 0 8px 40px rgba(0,0,20,.8);">{NAME}</h2>
                <!-- movie-credits style metadata row -->
                <div style="display: flex; gap: 26px; color: #6d8494; font-size: 13px; letter-spacing: 1px; margin-top: 4px;">
                  <div style="display: flex; flex-direction: column; gap: 2px;"><span style="font-size: 11px; color: rgba(109,132,148,.7);">شروع دوره</span><span style="color: #d4e2ea; font-weight: 700; font-size: 15px;">{DATE}</span></div>
                  <div style="width: 1px; background: rgba(24,147,184,.3);"></div>
                  <div style="display: flex; flex-direction: column; gap: 2px;"><span style="font-size: 11px; color: rgba(109,132,148,.7);">ظرفیت</span><span style="color: #d4e2ea; font-weight: 700; font-size: 15px;">محدود</span></div>
                  <div style="width: 1px; background: rgba(24,147,184,.3);"></div>
                  <div style="display: flex; flex-direction: column; gap: 2px;"><span style="font-size: 11px; color: rgba(109,132,148,.7);">برگزاری</span><span style="color: #d4e2ea; font-weight: 700; font-size: 15px;">حضوری + آنلاین</span></div>
                </div>
                <a href="{URL}" style="margin-top: 10px; background: linear-gradient(120deg, #1893B8, #4cc3e4); color: #001018; font-size: 17px; font-weight: 900; padding: 14px 48px; border-radius: 100px; box-shadow: 0 14px 36px rgba(24,147,184,.45); letter-spacing: 1px;">همین حالا ثبت‌نام کن</a>
              </div>
            </div>' ),
    ),
    'chrome' => '<div dir="rtl" class="szp-cs-stage" style="width: 1440px; height: 440px; position: relative;">
      <div style="position: absolute; inset: 0; border-radius: 24px; overflow: hidden; background: radial-gradient(1000px 600px at 30% 120%, #16374a 0%, #000014 60%);">
        <!-- letterbox bars -->
        <div style="position: absolute; top: 0; left: 0; right: 0; height: 34px; background: #000014; border-bottom: 1px solid rgba(24,147,184,.25); display: flex; align-items: center; justify-content: space-between; padding: 0 40px; box-sizing: border-box;">
          <div style="color: rgba(76,195,228,.55); font-size: 12px; font-family: monospace; letter-spacing: 4px;">ACADEMY PRESENTS</div>
          <div style="color: rgba(76,195,228,.55); font-size: 12px; font-family: monospace; letter-spacing: 4px;"><span class="szp-cs-counter" data-en="1"></span></div>
        </div>
        <div style="position: absolute; bottom: 0; left: 0; right: 0; height: 34px; background: #000014; border-top: 1px solid rgba(24,147,184,.25);"></div>
        <!-- film grain vignette -->
        <div style="position: absolute; inset: 0; background: radial-gradient(closest-side at 50% 50%, transparent 60%, rgba(0,0,20,.6) 100%);"></div>
        <!-- rim light behind archer -->
        <div style="position: absolute; left: 150px; bottom: 0; width: 360px; height: 400px; background: radial-gradient(50% 55% at 50% 60%, rgba(24,147,184,.4) 0%, transparent 70%);"></div>
        <!-- slides: title like a movie poster -->
        <div style="position: absolute; top: 34px; right: 0; bottom: 34px; width: 780px;">
          {SLIDES0}
        </div>
        <!-- controls on bottom bar -->
        <div style="position: absolute; bottom: 48px; right: 100px; display: flex; align-items: center; gap: 14px;">
          <button data-cs="next" style="width: 40px; height: 40px; border-radius: 50%; border: 1px solid rgba(24,147,184,.5); background: rgba(0,0,20,.5); color: #4cc3e4; font-size: 17px; cursor: pointer; font-family: inherit;">›</button>
          <button data-cs="prev" style="width: 40px; height: 40px; border-radius: 50%; border: 1px solid rgba(24,147,184,.5); background: rgba(0,0,20,.5); color: #4cc3e4; font-size: 17px; cursor: pointer; font-family: inherit;">‹</button>
        </div>
      </div>
      <!-- archer as movie hero, breaking through letterbox -->
      <img class="szp-cs-archer" src="{ARCHER}" alt="کماندار" style="position: absolute; left: 90px; bottom: 0; width: 545px; filter: drop-shadow(0 26px 55px rgba(0,0,0,.8)) contrast(1.05); pointer-events: none;">
    </div>
  </div>

  <!-- ═══════════ 5c — مجله لوکس ═══════════ -->',
  ),
  '5c' => array(
    'label' => 'مجله لوکس',
    'dots' => null,
    'dots_v' => false,
    'slides' => array(
      array( 'anim' => 'slidex', 'tpl' => '<div class="szp-cs-deco" style="position:absolute;right:620px;bottom:30px;font-size:200px;font-weight:900;line-height:1;color:transparent;-webkit-text-stroke:1px rgba(76,195,228,.28);pointer-events:none;user-select:none;">{NUM}</div>' ),
      array( 'anim' => 'fade', 'tpl' => '<div class="szp-cs-slide{ACTIVE}" data-i="{I}">
              <div style="height: 100%; display: flex; flex-direction: column; justify-content: center; gap: 18px; align-items: flex-start; padding-right: 100px; box-sizing: border-box;">
                <div style="display: flex; align-items: baseline; gap: 16px;">
                  <div style="color: #4cc3e4; font-size: 14px; letter-spacing: 5px; font-weight: 500;">در حال ثبت‌نام</div>
                  <div style="flex: 0 0 60px; height: 1px; background: #1893B8; align-self: center;"></div>
                </div>
                <h2 style="margin: 0; color: #f2f6f9; font-size: 62px; font-weight: 300; line-height: 1.3;">{NAMETHIN}<span style="font-weight: 900; color: #4cc3e4;">{NAMEBOLD}</span></h2>
                <p style="margin: 0; color: #8ba3b3; font-size: 16px; font-weight: 300; line-height: 2; max-width: 500px;">{DESC}</p>
                <div style="display: flex; align-items: center; gap: 30px; margin-top: 4px;">
                  <a href="{URL}" style="color: #f2f6f9; font-size: 16px; font-weight: 700; letter-spacing: 2px; border-bottom: 2px solid #1893B8; padding-bottom: 6px;" style-hover="color: #4cc3e4;">ثبت‌نام در دوره ←</a>
                  <div style="color: #6d8494; font-size: 14px; letter-spacing: 1px;">شروع {DATE}</div>
                </div>
              </div>
            </div>' ),
    ),
    'chrome' => '<div dir="rtl" class="szp-cs-stage" style="width: 1440px; height: 440px; position: relative;">
      <div style="position: absolute; inset: 0; border-radius: 24px; overflow: hidden; background: #0F1D28;">
        <!-- editorial hairlines -->
        <div style="position: absolute; top: 52px; left: 60px; right: 60px; height: 1px; background: rgba(76,195,228,.25);"></div>
        <div style="position: absolute; bottom: 52px; left: 60px; right: 60px; height: 1px; background: rgba(76,195,228,.25);"></div>
        <!-- masthead row -->
        <div style="position: absolute; top: 22px; left: 60px; right: 60px; display: flex; justify-content: space-between; align-items: center;">
          <div style="color: rgba(76,195,228,.7); font-size: 13px; letter-spacing: 4px; font-weight: 500;">آکادمی کسب‌وکار</div>
          <div style="color: rgba(76,195,228,.7); font-size: 13px; letter-spacing: 4px; font-weight: 500;">فصل ثبت‌نام ۱۴۰۵</div>
        </div>
        <!-- giant editorial index number -->
        {SLIDES0}
        <!-- slides -->
        <div style="position: absolute; top: 52px; right: 0; bottom: 52px; width: 720px;">
          {SLIDES1}
        </div>
        <!-- controls, editorial style -->
        <div style="position: absolute; bottom: 15px; right: 100px; display: flex; align-items: center; gap: 22px;">
          <button data-cs="next" style="border: none; background: transparent; color: #4cc3e4; font-size: 15px; letter-spacing: 3px; cursor: pointer; font-family: inherit; font-weight: 700;">بعدی ‹</button>
          <div style="color: #6d8494; font-size: 13px; font-family: monospace;"><span class="szp-cs-counter"></span></div>
          <button data-cs="prev" style="border: none; background: transparent; color: #4cc3e4; font-size: 15px; letter-spacing: 3px; cursor: pointer; font-family: inherit; font-weight: 700;">› قبلی</button>
        </div>
      </div>
      <!-- archer bursting past the hairline frame -->
      <img class="szp-cs-archer" src="{ARCHER}" alt="کماندار" style="position: absolute; left: 70px; bottom: 20px; width: 530px; filter: drop-shadow(0 26px 55px rgba(0,0,0,.7)); pointer-events: none;">
    </div>
  </div>

  <!-- ═══════════ 4a — شلیک نور ═══════════ -->',
  ),
  '4a' => array(
    'label' => 'شلیک نور',
    'dots' => '<button type="button" data-i="{I}" class="szp-cs-dot{DACTIVE}"></button>',
    'dots_v' => false,
    'slides' => array(
      array( 'anim' => 'fade', 'tpl' => '<div class="szp-cs-slide{ACTIVE}" data-i="{I}">
              <div style="height: 100%; display: flex; flex-direction: column; justify-content: center; gap: 15px; align-items: flex-start; padding-right: 96px; box-sizing: border-box;">
                <h2 style="margin: 0; color: #f2f6f9; font-size: 60px; font-weight: 900; line-height: 1.25; text-shadow: 0 0 40px rgba(24,147,184,.3);">{NAME}</h2>
                <p style="margin: 0; color: #8ba3b3; font-size: 17px; font-weight: 300; line-height: 1.9; max-width: 540px;">{DESC}</p>
                <div style="display: flex; align-items: center; gap: 24px; margin-top: 6px;">
                  <a href="{URL}" style="position: relative; overflow: hidden; background: #1893B8; color: #001018; font-size: 17px; font-weight: 700; padding: 13px 42px; border-radius: 100px; box-shadow: 0 0 30px rgba(24,147,184,.45);">
                    <span style="position: absolute; top: 0; bottom: 0; width: 40px; background: rgba(255,255,255,.35); animation: shine 2.8s ease-in-out infinite;"></span>
                    ثبت‌نام در دوره
                  </a>
                  <div style="color: #6d8494; font-size: 15px;">شروع: <span style="color: #d4e2ea; font-weight: 500;">{DATE}</span></div>
                </div>
              </div>
            </div>' ),
    ),
    'chrome' => '<div dir="rtl" class="szp-cs-stage" style="width: 1440px; height: 440px; position: relative;">
      <div style="position: absolute; inset: 0; border-radius: 24px; overflow: hidden; background: #000014;">
        <!-- spotlight cone from top -->
        <div style="position: absolute; top: -80px; left: 120px; width: 460px; height: 620px; background: radial-gradient(50% 60% at 50% 30%, rgba(24,147,184,.3) 0%, transparent 70%);"></div>
        <div style="position: absolute; inset: 0; background: radial-gradient(900px 500px at 82% 50%, rgba(15,29,40,.95) 0%, transparent 75%);"></div>
        <!-- arrow of light shooting across the frame -->
        <div style="position: absolute; top: 158px; left: 380px; right: 96px; height: 3px; background: repeating-linear-gradient(90deg, #1893B8 0 46px, rgba(24,147,184,.1) 46px 80px); animation: dashMove 1.1s linear infinite; box-shadow: 0 0 16px rgba(24,147,184,.7); border-radius: 3px;"></div>
        <div style="position: absolute; top: 146px; right: 86px; width: 0; height: 0; border-top: 14px solid transparent; border-bottom: 14px solid transparent; border-left: 24px solid #1893B8; filter: drop-shadow(0 0 12px rgba(24,147,184,.8));"></div>
        <!-- floating embers -->
        <div style="position: absolute; left: 640px; top: 90px; width: 6px; height: 6px; border-radius: 50%; background: #1893B8; box-shadow: 0 0 10px #1893B8; animation: floatY 4s ease-in-out infinite;"></div>
        <div style="position: absolute; left: 780px; top: 300px; width: 4px; height: 4px; border-radius: 50%; background: rgba(76,195,228,.8); box-shadow: 0 0 8px #4cc3e4; animation: floatY 5.5s ease-in-out infinite;"></div>
        <div style="position: absolute; left: 520px; top: 350px; width: 5px; height: 5px; border-radius: 50%; background: rgba(76,195,228,.6); animation: floatY 6s ease-in-out infinite;"></div>
        <!-- slides (below the light arrow) -->
        <div style="position: absolute; top: 120px; right: 0; bottom: 0; width: 760px;">
          {SLIDES0}
        </div>
        <!-- badge above the light arrow, right -->
        <div style="position: absolute; top: 66px; right: 96px; display: flex; align-items: center; gap: 9px; color: #4cc3e4; font-size: 14px; font-weight: 700; background: rgba(24,147,184,.14); border: 1px solid rgba(24,147,184,.6); border-radius: 100px; padding: 6px 16px; box-shadow: 0 0 18px rgba(24,147,184,.25);">
          <span style="width: 7px; height: 7px; border-radius: 50%; background: #1893B8; box-shadow: 0 0 8px #1893B8;"></span>
          در حال ثبت‌نام
        </div>
        <!-- controls -->
        <div style="position: absolute; bottom: 28px; right: 96px; display: flex; align-items: center; gap: 14px;">
          <button data-cs="next" style="width: 40px; height: 40px; border-radius: 50%; border: 1px solid rgba(24,147,184,.6); background: transparent; color: #4cc3e4; font-size: 17px; cursor: pointer; font-family: inherit;">›</button>
          <div style="display: flex; gap: 7px;">
            {DOTS}
          </div>
          <button data-cs="prev" style="width: 40px; height: 40px; border-radius: 50%; border: 1px solid rgba(24,147,184,.6); background: transparent; color: #4cc3e4; font-size: 17px; cursor: pointer; font-family: inherit;">‹</button>
        </div>
      </div>
      <!-- archer under the spotlight, breaking out top -->
      <img class="szp-cs-archer" src="{ARCHER}" alt="کماندار" style="position: absolute; left: 70px; bottom: 0; width: 540px; filter: drop-shadow(0 26px 55px rgba(0,0,0,.75)) drop-shadow(0 0 35px rgba(24,147,184,.2)); pointer-events: none;">
    </div>
  </div>

  <!-- ═══════════ 4b — عنوان خطی غول‌آسا ═══════════ -->',
  ),
  '4b' => array(
    'label' => 'عنوان خطی غول‌آسا',
    'dots' => null,
    'dots_v' => false,
    'slides' => array(
      array( 'anim' => 'rot3d', 'tpl' => '<div class="szp-cs-deco" style="position:absolute;right:40px;top:-30px;font-size:210px;font-weight:900;line-height:1;color:transparent;-webkit-text-stroke:1.5px rgba(24,147,184,.22);white-space:nowrap;pointer-events:none;user-select:none;">{GHOST}</div>' ),
      array( 'anim' => 'rot3d', 'tpl' => '<div class="szp-cs-slide{ACTIVE}" data-i="{I}">
              <div style="height: 100%; display: flex; flex-direction: column; justify-content: center; gap: 16px; align-items: flex-start; padding-right: 96px; box-sizing: border-box;">
                <div style="display: flex; align-items: center; gap: 9px; color: #4cc3e4; font-size: 14px; font-weight: 700; background: rgba(0,0,20,.6); border: 1px solid rgba(24,147,184,.55); border-radius: 100px; padding: 5px 15px;">
                  <span style="width: 7px; height: 7px; border-radius: 50%; background: #1893B8; box-shadow: 0 0 8px #1893B8;"></span>
                  در حال ثبت‌نام
                </div>
                <h2 style="margin: 0; color: #f2f6f9; font-size: 58px; font-weight: 900; line-height: 1.25;">{NAME}</h2>
                <div style="display: flex; align-items: center; gap: 24px; margin-top: 6px;">
                  <a href="{URL}" style="background: #1893B8; color: #001018; font-size: 17px; font-weight: 700; padding: 13px 42px; border-radius: 12px; box-shadow: 0 12px 30px rgba(24,147,184,.4);">ثبت‌نام در دوره</a>
                  <div style="color: #6d8494; font-size: 15px;">شروع: <span style="color: #d4e2ea; font-weight: 500;">{DATE}</span></div>
                </div>
              </div>
            </div>' ),
    ),
    'chrome' => '<div dir="rtl" class="szp-cs-stage" style="width: 1440px; height: 440px; position: relative;">
      <div style="position: absolute; inset: 0; border-radius: 24px; overflow: hidden; background: linear-gradient(115deg, #0F1D28 0%, #000014 60%);">
        <!-- giant outlined course word -->
        {SLIDES0}
        <!-- diagonal light shard -->
        <div style="position: absolute; top: -100px; bottom: -100px; left: 470px; width: 3px; background: linear-gradient(180deg, transparent, #1893B8, transparent); transform: rotate(18deg); box-shadow: 0 0 20px rgba(24,147,184,.5);"></div>
        <!-- slides -->
        <div style="position: absolute; top: 60px; right: 0; bottom: 0; width: 760px;">
          {SLIDES1}
        </div>
        <!-- controls -->
        <div style="position: absolute; bottom: 28px; right: 96px; display: flex; align-items: center; gap: 14px;">
          <button data-cs="next" style="width: 40px; height: 40px; border-radius: 10px; border: 1px solid rgba(24,147,184,.5); background: transparent; color: #4cc3e4; font-size: 17px; cursor: pointer; font-family: inherit;">›</button>
          <button data-cs="prev" style="width: 40px; height: 40px; border-radius: 10px; border: 1px solid rgba(24,147,184,.5); background: transparent; color: #4cc3e4; font-size: 17px; cursor: pointer; font-family: inherit;">‹</button>
          <div style="color: #6d8494; font-size: 14px; font-family: monospace;"><span class="szp-cs-counter"></span></div>
        </div>
      </div>
      <!-- archer floating, breaking out -->
      <img class="szp-cs-archer" src="{ARCHER}" alt="کماندار" style="position: absolute; left: 50px; bottom: -10px; width: 560px; filter: drop-shadow(0 26px 55px rgba(0,0,0,.75)); pointer-events: none; animation: floatY 7s ease-in-out infinite;">
    </div>
  </div>

  <!-- ═══════════ 4c — چرخش سه‌بعدی ═══════════ -->',
  ),
  '4c' => array(
    'label' => 'چرخش سه‌بعدی',
    'dots' => '<button type="button" data-i="{I}" class="szp-cs-dot{DACTIVE}"></button>',
    'dots_v' => false,
    'slides' => array(
      array( 'anim' => 'rot3d', 'tpl' => '<div class="szp-cs-slide{ACTIVE}" data-i="{I}">
              <div style="height: 100%; box-sizing: border-box; background: linear-gradient(135deg, rgba(24,147,184,.12), rgba(15,29,40,.9)); border: 1px solid rgba(24,147,184,.5); border-radius: 20px; padding: 34px 40px; display: flex; flex-direction: column; gap: 14px; align-items: flex-start; box-shadow: 0 30px 70px rgba(0,0,20,.7), inset 0 1px 0 rgba(76,195,228,.25); backdrop-filter: blur(6px);">
                <div style="display: flex; align-items: center; gap: 8px; color: #4cc3e4; font-size: 13px; font-weight: 700; border: 1px solid rgba(24,147,184,.5); border-radius: 100px; padding: 4px 13px;">
                  <span style="width: 6px; height: 6px; border-radius: 50%; background: #1893B8; box-shadow: 0 0 8px #1893B8;"></span>
                  در حال ثبت‌نام
                </div>
                <h3 style="margin: 0; color: #f2f6f9; font-size: 40px; font-weight: 900;">{NAME}</h3>
                <div style="color: #8ba3b3; font-size: 16px;">شروع: <span style="color: #d4e2ea;">{DATE}</span></div>
                <div style="flex: 1;"></div>
                <a href="{URL}" style="background: #1893B8; color: #001018; font-size: 16px; font-weight: 700; padding: 12px 36px; border-radius: 10px; box-shadow: 0 10px 26px rgba(24,147,184,.4);">ثبت‌نام</a>
              </div>
            </div>' ),
    ),
    'chrome' => '<div dir="rtl" class="szp-cs-stage" style="width: 1440px; height: 440px; position: relative;">
      <div style="position: absolute; inset: 0; border-radius: 24px; overflow: hidden; background: #000014; border: 1px solid rgba(24,147,184,.25);">
        <div style="position: absolute; inset: 0; background: radial-gradient(760px 460px at 20% 90%, rgba(15,29,40,1) 0%, transparent 70%);"></div>
        <!-- perspective floor grid -->
        <div style="position: absolute; left: 0; right: 0; bottom: -60px; height: 260px; background-image: linear-gradient(rgba(24,147,184,.16) 1px, transparent 1px), linear-gradient(90deg, rgba(24,147,184,.16) 1px, transparent 1px); background-size: 70px 44px; transform: perspective(500px) rotateX(58deg); transform-origin: bottom;"></div>
        <!-- 3D rotating slide card -->
        <div style="position: absolute; top: 62px; right: 96px; width: 640px; height: 280px;">
          {SLIDES0}
        </div>
        <!-- controls -->
        <div style="position: absolute; bottom: 30px; right: 96px; display: flex; align-items: center; gap: 14px;">
          <button data-cs="next" style="width: 40px; height: 40px; border-radius: 50%; border: 1px solid rgba(24,147,184,.5); background: transparent; color: #4cc3e4; font-size: 17px; cursor: pointer; font-family: inherit;">›</button>
          <div style="display: flex; gap: 7px;">
            {DOTS}
          </div>
          <button data-cs="prev" style="width: 40px; height: 40px; border-radius: 50%; border: 1px solid rgba(24,147,184,.5); background: transparent; color: #4cc3e4; font-size: 17px; cursor: pointer; font-family: inherit;">‹</button>
        </div>
      </div>
      <!-- archer standing on the grid, breaking out -->
      <img class="szp-cs-archer" src="{ARCHER}" alt="کماندار" style="position: absolute; left: 55px; bottom: 6px; width: 545px; filter: drop-shadow(0 26px 55px rgba(0,0,0,.75)); pointer-events: none;">
    </div>
  </div>

  <!-- ═══════════ 3a — مدار قهرمان ═══════════ -->',
  ),
  '3a' => array(
    'label' => 'مدار قهرمان',
    'dots' => '<button type="button" data-i="{I}" class="szp-cs-dot{DACTIVE}"></button>',
    'dots_v' => false,
    'slides' => array(
      array( 'anim' => 'fade', 'tpl' => '<div class="szp-cs-slide{ACTIVE}" data-i="{I}">
              <div style="height: 100%; display: flex; flex-direction: column; justify-content: center; gap: 15px; align-items: flex-start; padding-right: 90px; box-sizing: border-box;">
                <div style="display: flex; align-items: center; gap: 9px; color: #4cc3e4; font-size: 14px; font-weight: 700; background: rgba(24,147,184,.14); border: 1px solid rgba(24,147,184,.5); border-radius: 100px; padding: 5px 15px;">
                  <span style="width: 7px; height: 7px; border-radius: 50%; background: #1893B8; box-shadow: 0 0 8px #1893B8;"></span>
                  در حال ثبت‌نام
                </div>
                <h2 style="margin: 0; color: #f2f6f9; font-size: 54px; font-weight: 900; line-height: 1.25;">{NAME}</h2>
                <div style="display: flex; align-items: center; gap: 24px; margin-top: 4px;">
                  <a href="{URL}" style="background: #1893B8; color: #001018; font-size: 16px; font-weight: 700; padding: 12px 36px; border-radius: 10px; box-shadow: 0 10px 26px rgba(24,147,184,.35);">ثبت‌نام در دوره</a>
                  <div style="color: #6d8494; font-size: 15px;">شروع: <span style="color: #d4e2ea; font-weight: 500;">{DATE}</span></div>
                </div>
              </div>
            </div>' ),
    ),
    'chrome' => '<div dir="rtl" class="szp-cs-stage" style="width: 1440px; height: 430px; position: relative;">
      <!-- clipped background -->
      <div style="position: absolute; inset: 0; border-radius: 24px; overflow: hidden; background: linear-gradient(115deg, #0F1D28 0%, #000014 70%); border-bottom: 3px solid #1893B8;">
        <div style="position: absolute; inset: 0; background: radial-gradient(700px 420px at 18% 100%, rgba(24,147,184,.25) 0%, transparent 62%);"></div>
        <!-- slides -->
        <div style="position: absolute; top: 0; right: 0; bottom: 0; width: 740px;">
          {SLIDES0}
        </div>
        <!-- controls -->
        <div style="position: absolute; bottom: 26px; right: 90px; display: flex; align-items: center; gap: 14px;">
          <button data-cs="next" style="width: 40px; height: 40px; border-radius: 50%; border: 1px solid rgba(24,147,184,.5); background: transparent; color: #4cc3e4; font-size: 17px; cursor: pointer; font-family: inherit;">›</button>
          <div style="display: flex; gap: 7px;">
            {DOTS}
          </div>
          <button data-cs="prev" style="width: 40px; height: 40px; border-radius: 50%; border: 1px solid rgba(24,147,184,.5); background: transparent; color: #4cc3e4; font-size: 17px; cursor: pointer; font-family: inherit;">‹</button>
        </div>
      </div>
      <!-- orbit rings crossing the frame -->
      <div style="position: absolute; left: 55px; bottom: -60px; width: 520px; height: 520px; border-radius: 50%; border: 1px dashed rgba(24,147,184,.45); animation: spinSlow 40s linear infinite; pointer-events: none;"></div>
      <div style="position: absolute; left: 85px; bottom: -30px; width: 460px; height: 460px; border-radius: 50%; border: 1px solid rgba(24,147,184,.3); pointer-events: none;"></div>
      <!-- archer breaking far above the frame -->
      <img class="szp-cs-archer" src="{ARCHER}" alt="کماندار" style="position: absolute; left: 30px; bottom: 0; width: 570px; filter: drop-shadow(0 26px 55px rgba(0,0,0,.7)); pointer-events: none;">
    </div>
  </div>

  <!-- ═══════════ 3b — پنل مورب ═══════════ -->',
  ),
  '3b' => array(
    'label' => 'پنل مورب',
    'dots' => null,
    'dots_v' => false,
    'slides' => array(
      array( 'anim' => 'slidex', 'tpl' => '<div class="szp-cs-deco" style="position:absolute;left:620px;bottom:-40px;font-size:230px;font-weight:900;line-height:1;color:transparent;-webkit-text-stroke:1.5px rgba(24,147,184,.35);pointer-events:none;user-select:none;">{NUM}</div>' ),
      array( 'anim' => 'slidex', 'tpl' => '<div class="szp-cs-slide{ACTIVE}" data-i="{I}">
              <div style="height: 100%; display: flex; flex-direction: column; justify-content: center; gap: 15px; align-items: flex-start; padding-right: 90px; box-sizing: border-box;">
                <div style="display: flex; align-items: center; gap: 12px;">
                  <div style="width: 44px; height: 2px; background: #1893B8;"></div>
                  <div style="color: #4cc3e4; font-size: 15px; font-weight: 500; letter-spacing: 2px;">در حال ثبت‌نام</div>
                </div>
                <h2 style="margin: 0; color: #f2f6f9; font-size: 56px; font-weight: 900; line-height: 1.25;">{NAME}</h2>
                <p style="margin: 0; color: #8ba3b3; font-size: 17px; font-weight: 300; line-height: 1.9; max-width: 520px;">{DESC}</p>
                <div style="display: flex; align-items: center; gap: 24px; margin-top: 4px;">
                  <a href="{URL}" style="background: #1893B8; color: #001018; font-size: 16px; font-weight: 700; padding: 12px 36px; border-radius: 10px;">ثبت‌نام</a>
                  <div style="color: #6d8494; font-size: 15px;">شروع: <span style="color: #d4e2ea; font-weight: 500;">{DATE}</span></div>
                </div>
              </div>
            </div>' ),
    ),
    'chrome' => '<div dir="rtl" class="szp-cs-stage" style="width: 1440px; height: 440px; position: relative;">
      <div style="position: absolute; inset: 0; border-radius: 24px; overflow: hidden; background: #000014;">
        <!-- big skewed panel behind archer -->
        <div style="position: absolute; top: -80px; bottom: -80px; left: -180px; width: 720px; background: linear-gradient(160deg, rgba(24,147,184,.32), rgba(24,147,184,.04)); transform: skewX(-16deg); border-right: 2px solid rgba(24,147,184,.6);"></div>
        <div style="position: absolute; top: -80px; bottom: -80px; left: 560px; width: 30px; background: rgba(24,147,184,.14); transform: skewX(-16deg);"></div>
        <!-- big slide number -->
        {SLIDES0}
        <!-- slides -->
        <div style="position: absolute; top: 0; right: 0; bottom: 0; width: 720px;">
          {SLIDES1}
        </div>
        <!-- controls -->
        <div style="position: absolute; bottom: 26px; right: 90px; display: flex; align-items: center; gap: 14px;">
          <button data-cs="next" style="width: 40px; height: 40px; border-radius: 10px; border: 1px solid rgba(24,147,184,.5); background: transparent; color: #4cc3e4; font-size: 17px; cursor: pointer; font-family: inherit;">›</button>
          <button data-cs="prev" style="width: 40px; height: 40px; border-radius: 10px; border: 1px solid rgba(24,147,184,.5); background: transparent; color: #4cc3e4; font-size: 17px; cursor: pointer; font-family: inherit;">‹</button>
          <div style="color: #6d8494; font-size: 14px; font-family: monospace;"><span class="szp-cs-counter"></span></div>
        </div>
      </div>
      <!-- archer breaking above, standing on the seam -->
      <img class="szp-cs-archer" src="{ARCHER}" alt="کماندار" style="position: absolute; left: 60px; bottom: 0; width: 545px; filter: drop-shadow(0 26px 55px rgba(0,0,0,.7)); pointer-events: none;">
    </div>
  </div>

  <!-- ═══════════ 3c — قاب خطی و تیکر ═══════════ -->',
  ),
  '3c' => array(
    'label' => 'قاب خطی و تیکر',
    'dots' => '<button type="button" data-i="{I}" class="szp-cs-dot{DACTIVE}"></button>',
    'dots_v' => false,
    'slides' => array(
      array( 'anim' => 'fade', 'tpl' => '<div class="szp-cs-slide{ACTIVE}" data-i="{I}">
              <div style="height: 100%; display: flex; flex-direction: column; justify-content: center; gap: 15px; align-items: flex-start; padding-right: 90px; box-sizing: border-box;">
                <div style="display: flex; align-items: center; gap: 9px; color: #4cc3e4; font-size: 14px; font-weight: 700; border: 1px solid rgba(24,147,184,.5); border-radius: 100px; padding: 5px 15px;">
                  <span style="width: 7px; height: 7px; border-radius: 50%; background: #1893B8; box-shadow: 0 0 8px #1893B8;"></span>
                  در حال ثبت‌نام
                </div>
                <h2 style="margin: 0; color: #f2f6f9; font-size: 54px; font-weight: 900; line-height: 1.25;">{NAME}</h2>
                <div style="display: flex; align-items: center; gap: 24px; margin-top: 4px;">
                  <a href="{URL}" style="border: 1.5px solid #1893B8; color: #4cc3e4; font-size: 16px; font-weight: 700; padding: 12px 40px; border-radius: 100px;" style-hover="background: #1893B8; color: #001018;">ثبت‌نام در دوره</a>
                  <div style="color: #6d8494; font-size: 15px;">شروع: <span style="color: #d4e2ea; font-weight: 500;">{DATE}</span></div>
                </div>
              </div>
            </div>' ),
    ),
    'chrome' => '<div dir="rtl" class="szp-cs-stage" style="width: 1440px; height: 440px; position: relative;">
      <div style="position: absolute; inset: 0; border-radius: 24px; overflow: hidden; background: #0F1D28; border: 1.5px solid rgba(24,147,184,.55); box-sizing: border-box;">
        <div style="position: absolute; inset: 0; background: radial-gradient(800px 440px at 16% 90%, rgba(0,0,20,.75) 0%, transparent 60%);"></div>
        <!-- slides -->
        <div style="position: absolute; top: 0; right: 0; bottom: 66px; width: 740px;">
          {SLIDES0}
        </div>
        <!-- controls -->
        <div style="position: absolute; bottom: 92px; right: 90px; display: flex; align-items: center; gap: 14px;">
          <button data-cs="next" style="width: 40px; height: 40px; border-radius: 50%; border: 1px solid rgba(24,147,184,.5); background: transparent; color: #4cc3e4; font-size: 17px; cursor: pointer; font-family: inherit;">›</button>
          <div style="display: flex; gap: 7px;">
            {DOTS}
          </div>
          <button data-cs="prev" style="width: 40px; height: 40px; border-radius: 50%; border: 1px solid rgba(24,147,184,.5); background: transparent; color: #4cc3e4; font-size: 17px; cursor: pointer; font-family: inherit;">‹</button>
        </div>
        <!-- ticker strip -->
        <div dir="ltr" style="position: absolute; left: 0; right: 0; bottom: 0; height: 56px; border-top: 1px solid rgba(24,147,184,.35); overflow: hidden; display: flex; align-items: center; background: rgba(0,0,20,.5);">
          <div style="display: flex; white-space: nowrap; animation: marquee 24s linear infinite; direction: rtl;">
            <span style="color: rgba(76,195,228,.65); font-size: 16px; font-weight: 500; padding: 0 26px;">ثبت‌نام آغاز شد ✦ کارگاه فروش حرفه‌ای ✦ دوره حکمرانی بر بازار ✦ ظرفیت محدود</span>
            <span style="color: rgba(76,195,228,.65); font-size: 16px; font-weight: 500; padding: 0 26px;">ثبت‌نام آغاز شد ✦ کارگاه فروش حرفه‌ای ✦ دوره حکمرانی بر بازار ✦ ظرفیت محدود</span>
            <span style="color: rgba(76,195,228,.65); font-size: 16px; font-weight: 500; padding: 0 26px;">ثبت‌نام آغاز شد ✦ کارگاه فروش حرفه‌ای ✦ دوره حکمرانی بر بازار ✦ ظرفیت محدود</span>
            <span style="color: rgba(76,195,228,.65); font-size: 16px; font-weight: 500; padding: 0 26px;">ثبت‌نام آغاز شد ✦ کارگاه فروش حرفه‌ای ✦ دوره حکمرانی بر بازار ✦ ظرفیت محدود</span>
          </div>
        </div>
      </div>
      <!-- archer breaking through the stroked frame -->
      <img class="szp-cs-archer" src="{ARCHER}" alt="کماندار" style="position: absolute; left: 26px; bottom: 10px; width: 555px; filter: drop-shadow(0 26px 55px rgba(0,0,0,.7)); pointer-events: none;">
    </div>
  </div>

  <!-- ═══════════ 2a — شکستن قاب ═══════════ -->',
  ),
  '2a' => array(
    'label' => 'شکستن قاب',
    'dots' => '<button type="button" data-i="{I}" class="szp-cs-dot{DACTIVE}"></button>',
    'dots_v' => false,
    'slides' => array(
      array( 'anim' => 'slidex', 'tpl' => '<div class="szp-cs-slide{ACTIVE}" data-i="{I}">
              <div style="height: 100%; display: flex; flex-direction: column; justify-content: center; gap: 16px; align-items: flex-start; padding-right: 84px; box-sizing: border-box;">
                <div style="display: flex; align-items: center; gap: 9px; color: #4cc3e4; font-size: 14px; font-weight: 700; background: rgba(24,147,184,.14); border: 1px solid rgba(24,147,184,.5); border-radius: 100px; padding: 5px 15px;">
                  <span style="width: 7px; height: 7px; border-radius: 50%; background: #1893B8; box-shadow: 0 0 8px #1893B8;"></span>
                  در حال ثبت‌نام
                </div>
                <h2 style="margin: 0; color: #f2f6f9; font-size: 56px; font-weight: 900; line-height: 1.25;">{NAME}</h2>
                <div style="display: flex; align-items: center; gap: 26px; margin-top: 6px;">
                  <a href="{URL}" style="background: #1893B8; color: #001018; font-size: 17px; font-weight: 700; padding: 13px 38px; border-radius: 10px; box-shadow: 0 10px 26px rgba(24,147,184,.35);">ثبت‌نام در دوره</a>
                  <div style="color: #6d8494; font-size: 15px;">شروع: <span style="color: #d4e2ea; font-weight: 500;">{DATE}</span></div>
                </div>
              </div>
            </div>' ),
    ),
    'chrome' => '<div dir="rtl" class="szp-cs-stage" style="width: 1440px; height: 460px; position: relative;">
      <!-- clipped background -->
      <div style="position: absolute; inset: 0; border-radius: 20px; overflow: hidden; background: #0F1D28;">
        <div style="position: absolute; inset: 0; background: radial-gradient(900px 500px at 20% 100%, rgba(24,147,184,.22) 0%, transparent 60%);"></div>
        <div style="position: absolute; top: 0; bottom: 0; left: 430px; width: 220px; background: linear-gradient(180deg, rgba(24,147,184,.9), rgba(24,147,184,.25)); transform: skewX(-14deg);"></div>
        <div style="position: absolute; top: 0; bottom: 0; left: 665px; width: 16px; background: rgba(24,147,184,.35); transform: skewX(-14deg);"></div>
        <!-- slides -->
        <div style="position: absolute; top: 0; right: 0; bottom: 0; width: 720px;">
          {SLIDES0}
        </div>
        <!-- controls -->
        <div style="position: absolute; bottom: 30px; right: 84px; display: flex; align-items: center; gap: 14px;">
          <button data-cs="next" style="width: 40px; height: 40px; border-radius: 50%; border: 1px solid rgba(24,147,184,.5); background: transparent; color: #4cc3e4; font-size: 17px; cursor: pointer; font-family: inherit;">›</button>
          <div style="display: flex; gap: 7px;">
            {DOTS}
          </div>
          <button data-cs="prev" style="width: 40px; height: 40px; border-radius: 50%; border: 1px solid rgba(24,147,184,.5); background: transparent; color: #4cc3e4; font-size: 17px; cursor: pointer; font-family: inherit;">‹</button>
        </div>
      </div>
      <!-- archer breaking out of the frame -->
      <img class="szp-cs-archer" src="{ARCHER}" alt="کماندار" style="position: absolute; left: 36px; bottom: 0; width: 530px; filter: drop-shadow(0 24px 50px rgba(0,0,0,.65)); pointer-events: none;">
    </div>
  </div>

  <!-- ═══════════ 2b — خط نشانه ═══════════ -->',
  ),
  '2b' => array(
    'label' => 'خط نشانه',
    'dots' => null,
    'dots_v' => false,
    'slides' => array(
      array( 'anim' => 'fade', 'tpl' => '<div class="szp-cs-slide{ACTIVE}" data-i="{I}">
            <div style="height: 100%; display: flex; flex-direction: column; justify-content: center; gap: 15px; align-items: flex-start; padding-right: 90px; box-sizing: border-box;">
              <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 44px; height: 2px; background: #1893B8;"></div>
                <div style="color: #4cc3e4; font-size: 15px; font-weight: 500; letter-spacing: 2px;">در حال ثبت‌نام</div>
              </div>
              <h2 style="margin: 0; color: #f2f6f9; font-size: 58px; font-weight: 900; line-height: 1.25;">{NAME}</h2>
              <p style="margin: 0; color: #8ba3b3; font-size: 17px; font-weight: 300; line-height: 1.9; max-width: 520px;">{DESC}</p>
              <div style="display: flex; align-items: center; gap: 24px; margin-top: 4px;">
                <a href="{URL}" style="background: #1893B8; color: #001018; font-size: 16px; font-weight: 700; padding: 12px 36px; border-radius: 100px;">ثبت‌نام</a>
                <div style="color: #6d8494; font-size: 15px;">شروع: <span style="color: #d4e2ea; font-weight: 500;">{DATE}</span></div>
              </div>
            </div>
          </div>' ),
    ),
    'chrome' => '<div dir="rtl" class="szp-cs-stage" style="width: 1440px; height: 460px; border-radius: 20px; overflow: hidden; position: relative; background: radial-gradient(800px 460px at 15% 60%, #0F1D28 0%, #000014 65%);">
      <!-- aim line from arrow tip to target -->
      <div style="position: absolute; top: 172px; left: 470px; right: 720px; height: 1.5px; background: linear-gradient(90deg, rgba(24,147,184,.9), rgba(24,147,184,.25));"></div>
      <!-- target rings around card -->
      <div style="position: absolute; top: 62px; right: 500px; width: 220px; height: 220px; border-radius: 50%; border: 1px solid rgba(24,147,184,.25);"></div>
      <div style="position: absolute; top: 92px; right: 530px; width: 160px; height: 160px; border-radius: 50%; border: 1px dashed rgba(24,147,184,.35);"></div>
      <!-- archer fixed left -->
      <img class="szp-cs-archer" src="{ARCHER}" alt="کماندار" style="position: absolute; left: 10px; bottom: -40px; width: 480px; filter: drop-shadow(0 24px 50px rgba(0,0,0,.65));">
      <!-- slides right -->
      <div style="position: absolute; top: 0; right: 0; bottom: 0; width: 700px;">
        {SLIDES0}
      </div>
      <!-- controls bottom-left of content -->
      <div style="position: absolute; bottom: 28px; right: 90px; display: flex; align-items: center; gap: 14px;">
        <button data-cs="next" style="width: 40px; height: 40px; border-radius: 50%; border: 1px solid rgba(24,147,184,.5); background: transparent; color: #4cc3e4; font-size: 17px; cursor: pointer; font-family: inherit;">›</button>
        <button data-cs="prev" style="width: 40px; height: 40px; border-radius: 50%; border: 1px solid rgba(24,147,184,.5); background: transparent; color: #4cc3e4; font-size: 17px; cursor: pointer; font-family: inherit;">‹</button>
        <div style="color: #6d8494; font-size: 14px; font-family: monospace;"><span class="szp-cs-counter"></span></div>
      </div>
    </div>
  </div>

  <!-- ═══════════ 2c — کارت‌های پشت‌سرهم ═══════════ -->',
  ),
  '2c' => array(
    'label' => 'کارت‌های پشت‌سرهم',
    'dots' => '<button type="button" data-i="{I}" class="szp-cs-dot{DACTIVE}"></button>',
    'dots_v' => false,
    'slides' => array(
      array( 'anim' => 'card', 'tpl' => '<div class="szp-cs-slide szp-cs-card{ACTIVE}" data-i="{I}">
            <div style="height: 100%; box-sizing: border-box; background: linear-gradient(135deg, #122635, #0F1D28); border: 1px solid rgba(24,147,184,.4); border-radius: 18px; padding: 28px 34px; display: flex; flex-direction: column; gap: 12px; align-items: flex-start; box-shadow: 0 24px 50px rgba(0,0,20,.55);">
              <div style="display: flex; align-items: center; gap: 8px; color: #4cc3e4; font-size: 13px; font-weight: 700; border: 1px solid rgba(24,147,184,.5); border-radius: 100px; padding: 4px 13px;">
                <span style="width: 6px; height: 6px; border-radius: 50%; background: #1893B8; box-shadow: 0 0 8px #1893B8;"></span>
                در حال ثبت‌نام
              </div>
              <h3 style="margin: 0; color: #f2f6f9; font-size: 30px; font-weight: 900;">{NAME}</h3>
              <div style="display: flex; align-items: center; gap: 22px; margin-top: auto;">
                <a href="{URL}" style="background: #1893B8; color: #001018; font-size: 15px; font-weight: 700; padding: 10px 30px; border-radius: 9px;">ثبت‌نام</a>
                <div style="color: #6d8494; font-size: 14px;">شروع: <span style="color: #d4e2ea;">{DATE}</span></div>
              </div>
            </div>
          </div>' ),
    ),
    'chrome' => '<div dir="rtl" class="szp-cs-stage" style="width: 1440px; height: 460px; border-radius: 20px; overflow: hidden; position: relative; background: #000014;">
      <div style="position: absolute; inset: 0; background: linear-gradient(100deg, #0F1D28 0%, #0F1D28 40%, transparent 40%);"></div>
      <div style="position: absolute; left: -80px; bottom: -220px; width: 620px; height: 620px; border-radius: 50%; background: radial-gradient(circle, rgba(24,147,184,.28) 0%, transparent 65%);"></div>
      <!-- archer fixed left -->
      <img class="szp-cs-archer" src="{ARCHER}" alt="کماندار" style="position: absolute; left: 20px; bottom: -30px; width: 470px; filter: drop-shadow(20px 20px 45px rgba(0,0,0,.65));">
      <!-- fixed heading -->
      <div style="position: absolute; top: 66px; right: 90px; display: flex; flex-direction: column; gap: 6px;">
        <div style="color: #1893B8; font-size: 15px; font-weight: 500; letter-spacing: 2px;">آموزش و مشاوره کسب‌وکار</div>
        <h2 style="margin: 0; color: #f2f6f9; font-size: 34px; font-weight: 900;">دوره‌های در حال ثبت‌نام</h2>
      </div>
      <!-- stacked cards -->
      <div style="position: absolute; top: 170px; right: 90px; width: 620px; height: 220px;">
        {SLIDES0}
      </div>
      <!-- controls -->
      <div style="position: absolute; bottom: 26px; right: 90px; display: flex; align-items: center; gap: 14px;">
        <button data-cs="next" style="width: 40px; height: 40px; border-radius: 10px; border: 1px solid rgba(24,147,184,.5); background: transparent; color: #4cc3e4; font-size: 17px; cursor: pointer; font-family: inherit;">›</button>
        <button data-cs="prev" style="width: 40px; height: 40px; border-radius: 10px; border: 1px solid rgba(24,147,184,.5); background: transparent; color: #4cc3e4; font-size: 17px; cursor: pointer; font-family: inherit;">‹</button>
        <div style="display: flex; gap: 7px;">
          {DOTS}
        </div>
      </div>
    </div>
  </div>

  <!-- ═══════════ 1a — هاله و شکوه ═══════════ -->',
  ),
  '1a' => array(
    'label' => 'هاله و شکوه',
    'dots' => '<button type="button" data-i="{I}" class="szp-cs-dot{DACTIVE}"></button>',
    'dots_v' => false,
    'slides' => array(
      array( 'anim' => 'fade', 'tpl' => '<div class="szp-cs-slide{ACTIVE}" data-i="{I}">
            <div style="display: flex; flex-direction: column; gap: 22px; align-items: flex-start; height: 100%; justify-content: center;">
              <div style="display: flex; align-items: center; gap: 10px; background: rgba(24,147,184,.14); border: 1px solid rgba(24,147,184,.5); color: #4cc3e4; border-radius: 100px; padding: 7px 18px; font-size: 15px; font-weight: 500;">
                <span style="width: 8px; height: 8px; border-radius: 50%; background: #1893B8; box-shadow: 0 0 10px #1893B8;"></span>
                در حال ثبت‌نام
              </div>
              <h2 style="margin: 0; color: #f2f6f9; font-size: 64px; font-weight: 900; line-height: 1.25;">{NAME}</h2>
              <p style="margin: 0; color: #8ba3b3; font-size: 20px; font-weight: 300; line-height: 1.9; max-width: 560px;">{DESC}</p>
              <div style="display: flex; align-items: center; gap: 28px; margin-top: 8px;">
                <a href="{URL}" style="background: #1893B8; color: #001018; font-size: 18px; font-weight: 700; padding: 15px 42px; border-radius: 12px; box-shadow: 0 10px 30px rgba(24,147,184,.35);">ثبت‌نام در دوره</a>
                <div style="color: #6d8494; font-size: 16px;">شروع دوره: <span style="color: #d4e2ea; font-weight: 500;">{DATE}</span></div>
              </div>
            </div>
          </div>' ),
    ),
    'chrome' => '<div dir="rtl" class="szp-cs-stage" style="width: 1440px; height: 640px; background: radial-gradient(1100px 700px at 82% 30%, #0F1D28 0%, #000014 62%); border-radius: 20px; overflow: hidden; position: relative; display: flex;">
      <!-- character — fixed, left side -->
      <div style="position: absolute; left: 0; bottom: 0; width: 620px; height: 640px; pointer-events: none;">
        <div style="position: absolute; left: 40px; bottom: -180px; width: 560px; height: 560px; border-radius: 50%; background: radial-gradient(circle, rgba(24,147,184,.35) 0%, rgba(24,147,184,0) 68%); animation: glowPulse 5s ease-in-out infinite;"></div>
        <div style="position: absolute; left: 90px; bottom: -60px; width: 470px; height: 470px; border-radius: 50%; border: 1px solid rgba(24,147,184,.35);"></div>
        <div style="position: absolute; left: 60px; bottom: -90px; width: 530px; height: 530px; border-radius: 50%; border: 1px dashed rgba(24,147,184,.18);"></div>
        <img class="szp-cs-archer" src="{ARCHER}" alt="کماندار" style="position: absolute; left: 30px; bottom: 0; width: 560px; filter: drop-shadow(0 30px 60px rgba(0,0,0,.6));">
      </div>
      <!-- slides — right side -->
      <div style="position: absolute; top: 0; right: 0; bottom: 0; width: 780px; padding: 90px 90px 90px 0; box-sizing: border-box;">
        {SLIDES0}
      </div>
      <!-- controls -->
      <div style="position: absolute; bottom: 44px; right: 90px; display: flex; align-items: center; gap: 18px;">
        <button data-cs="next" style="width: 46px; height: 46px; border-radius: 50%; border: 1px solid rgba(24,147,184,.5); background: transparent; color: #4cc3e4; font-size: 20px; cursor: pointer; font-family: inherit;">›</button>
        <div style="display: flex; gap: 8px;">
          {DOTS}
        </div>
        <button data-cs="prev" style="width: 46px; height: 46px; border-radius: 50%; border: 1px solid rgba(24,147,184,.5); background: transparent; color: #4cc3e4; font-size: 20px; cursor: pointer; font-family: inherit;">‹</button>
      </div>
    </div>
  </div>

  <!-- ═══════════ 1b — کارت شیشه‌ای ═══════════ -->',
  ),
  '1b' => array(
    'label' => 'کارت شیشه‌ای',
    'dots' => null,
    'dots_v' => false,
    'slides' => array(
      array( 'anim' => 'fade', 'tpl' => '<div class="szp-cs-slide{ACTIVE}" data-i="{I}">
            <div style="height: 100%; box-sizing: border-box; background: rgba(24,147,184,.07); border: 1px solid rgba(24,147,184,.35); border-radius: 20px; backdrop-filter: blur(8px); padding: 40px 44px; display: flex; flex-direction: column; gap: 18px; align-items: flex-start;">
              <div style="display: flex; align-items: center; gap: 9px; color: #4cc3e4; font-size: 14px; font-weight: 700; border: 1px solid rgba(24,147,184,.5); border-radius: 100px; padding: 5px 14px;">
                <span style="width: 7px; height: 7px; border-radius: 50%; background: #1893B8; box-shadow: 0 0 8px #1893B8;"></span>
                در حال ثبت‌نام
              </div>
              <h3 style="margin: 0; color: #f2f6f9; font-size: 40px; font-weight: 900;">{NAME}</h3>
              <div style="color: #8ba3b3; font-size: 17px;">شروع: <span style="color: #d4e2ea;">{DATE}</span></div>
              <div style="flex: 1;"></div>
              <a href="{URL}" style="background: #1893B8; color: #001018; font-size: 17px; font-weight: 700; padding: 13px 38px; border-radius: 10px;">ثبت‌نام</a>
            </div>
          </div>' ),
    ),
    'chrome' => '<div dir="rtl" class="szp-cs-stage" style="width: 1440px; height: 640px; background: #0F1D28; border-radius: 20px; overflow: hidden; position: relative;">
      <!-- back panel split -->
      <div style="position: absolute; inset: 0; background: linear-gradient(105deg, #000014 0%, #000014 46%, transparent 46%);"></div>
      <div style="position: absolute; top: -140px; left: 420px; width: 1px; height: 900px; background: linear-gradient(180deg, transparent, rgba(24,147,184,.6), transparent); transform: rotate(15deg);"></div>
      <!-- character fixed left -->
      <img class="szp-cs-archer" src="{ARCHER}" alt="کماندار" style="position: absolute; left: -20px; bottom: 0; width: 600px; filter: drop-shadow(24px 20px 50px rgba(0,0,0,.65));">
      <!-- heading fixed -->
      <div style="position: absolute; top: 78px; right: 96px; display: flex; flex-direction: column; gap: 8px;">
        <div style="color: #1893B8; font-size: 17px; font-weight: 500; letter-spacing: 2px;">آموزش و مشاوره کسب‌وکار</div>
        <h2 style="margin: 0; color: #f2f6f9; font-size: 42px; font-weight: 900;">دوره‌های در حال ثبت‌نام</h2>
      </div>
      <!-- glass card slider right -->
      <div style="position: absolute; top: 210px; right: 96px; width: 640px; height: 330px;">
        {SLIDES0}
      </div>
      <!-- controls -->
      <div style="position: absolute; top: 566px; right: 96px; display: flex; align-items: center; gap: 16px;">
        <button data-cs="next" style="width: 42px; height: 42px; border-radius: 10px; border: 1px solid rgba(24,147,184,.5); background: transparent; color: #4cc3e4; font-size: 18px; cursor: pointer; font-family: inherit;">›</button>
        <button data-cs="prev" style="width: 42px; height: 42px; border-radius: 10px; border: 1px solid rgba(24,147,184,.5); background: transparent; color: #4cc3e4; font-size: 18px; cursor: pointer; font-family: inherit;">‹</button>
        <div style="color: #6d8494; font-size: 15px; font-family: monospace;"><span class="szp-cs-counter"></span></div>
      </div>
    </div>
  </div>

  <!-- ═══════════ 1c — تایپوگرافی حماسی ═══════════ -->',
  ),
  '1c' => array(
    'label' => 'تایپوگرافی حماسی',
    'dots' => '<button type="button" data-i="{I}" class="szp-cs-dot{DACTIVE}" szp-cs-dot--v></button>',
    'dots_v' => true,
    'slides' => array(
      array( 'anim' => 'fade', 'tpl' => '<div class="szp-cs-deco" style="position:absolute;left:0;right:0;top:40%;text-align:center;font-size:340px;font-weight:900;color:rgba(24,147,184,.06);line-height:1;white-space:nowrap;transform:translateY(-50%);pointer-events:none;user-select:none;">{GHOST}</div>' ),
      array( 'anim' => 'fade', 'tpl' => '<div class="szp-cs-slide{ACTIVE}" data-i="{I}">
            <div style="height: 100%; display: flex; flex-direction: column; justify-content: center; gap: 24px; align-items: flex-start; padding-right: 96px;">
              <div style="display: flex; align-items: center; gap: 14px;">
                <div style="width: 54px; height: 2px; background: #1893B8;"></div>
                <div style="color: #4cc3e4; font-size: 16px; font-weight: 500; letter-spacing: 3px;">در حال ثبت‌نام</div>
              </div>
              <h2 style="margin: 0; color: #f2f6f9; font-size: 76px; font-weight: 900; line-height: 1.2;">{NAME}</h2>
              <div style="display: flex; gap: 40px; color: #6d8494; font-size: 17px;">
                <div>شروع: <span style="color: #d4e2ea; font-weight: 500;">{DATE}</span></div>
                <div>ظرفیت محدود</div>
              </div>
              <a href="{URL}" style="margin-top: 10px; border: 1.5px solid #1893B8; color: #4cc3e4; font-size: 18px; font-weight: 700; padding: 14px 46px; border-radius: 100px;" style-hover="background: #1893B8; color: #001018;">ثبت‌نام در دوره</a>
            </div>
          </div>' ),
    ),
    'chrome' => '<div dir="rtl" class="szp-cs-stage" style="width: 1440px; height: 640px; background: #000014; border-radius: 20px; overflow: hidden; position: relative;">
      <!-- giant ghost word -->
      {SLIDES0}
      <!-- character fixed left over ghost text -->
      <div style="position: absolute; left: 0; bottom: 0; width: 640px; height: 640px; pointer-events: none;">
        <div style="position: absolute; left: 100px; bottom: -230px; width: 480px; height: 480px; background: #0F1D28; border-radius: 50%;"></div>
        <img class="szp-cs-archer" src="{ARCHER}" alt="کماندار" style="position: absolute; left: 40px; bottom: 0; width: 580px; filter: drop-shadow(0 26px 55px rgba(0,0,0,.7));">
      </div>
      <!-- slide text right -->
      <div style="position: absolute; top: 0; right: 0; bottom: 0; width: 760px; padding-left: 40px; box-sizing: border-box;">
        {SLIDES1}
      </div>
      <!-- controls: vertical, far right -->
      <div style="position: absolute; top: 0; bottom: 0; right: 26px; display: flex; flex-direction: column; justify-content: center; gap: 14px;">
        <button data-cs="prev" style="width: 40px; height: 40px; border-radius: 50%; border: 1px solid rgba(24,147,184,.5); background: transparent; color: #4cc3e4; font-size: 17px; cursor: pointer; font-family: inherit;">‹</button>
        <div style="display: flex; flex-direction: column; gap: 8px; align-items: center;">
          {DOTS}
        </div>
        <button data-cs="next" style="width: 40px; height: 40px; border-radius: 50%; border: 1px solid rgba(24,147,184,.5); background: transparent; color: #4cc3e4; font-size: 17px; cursor: pointer; font-family: inherit;">›</button>
      </div>
    </div>',
  ),
);
