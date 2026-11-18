<?php
/**
 * Template Name: Login
 */
defined( 'ABSPATH' ) || exit;

// Already logged in? Go straight to portal
if ( is_user_logged_in() ) {
    $ids = get_option( 'tru_page_ids', [] );
    $dest = isset( $ids['3r-portal-dashboard'] ) ? get_permalink( $ids['3r-portal-dashboard'] ) : home_url('/');
    wp_redirect( $dest ); exit;
}

$home_url    = get_option('tru_page_ids') ? get_permalink( get_option('tru_page_ids')['3r-home'] ?? 0 ) : home_url('/');
$redirect_to = esc_url_raw( $_GET['redirect_to'] ?? '' );
$error_code  = sanitize_text_field( $_GET['login_error'] ?? '' );
$error_msg   = $error_code ? ThreeR_Portal_Login::error_message( $error_code ) : '';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="tru-page" style="background:#080c08!important">
<head>
<meta charset="<?php bloginfo('charset'); ?>"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Sign In — 3R Urban Smart Solutions</title>
<link rel="preconnect" href="https://fonts.googleapis.com" crossorigin/>
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=Outfit:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap"/>
<?php wp_head(); ?>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html, body {
  background: #080c08 !important;
  font-family: 'Outfit', sans-serif;
  height: 100%; overflow: hidden;
  --green:      #5dd862;
  --green-dim:  rgba(93,216,98,.12);
  --green-glow: rgba(93,216,98,.06);
  --earth:      #c8a96e;
  --bg:         #080c08;
  --bg2:        #0d130d;
  --bg3:        #111a11;
  --border:     #1c2b1c;
  --border2:    #243824;
  --text:       #dceadc;
  --text2:      #8aaa8a;
  --text3:      #4a6a4a;
  --red:        #e05555;
  --mono:       'DM Mono', monospace;
}

/* ── Shell ── */
.tru-login-shell {
  display: grid;
  grid-template-columns: 1fr 480px;
  min-height: 100vh;
}

/* ── Left panel — visual ── */
.tru-login-visual {
  position: relative;
  background: var(--bg2);
  border-right: 1px solid var(--border);
  overflow: hidden;
  display: flex; flex-direction: column;
  justify-content: space-between;
  padding: 48px 52px;
}

/* Grid background */
.tru-login-visual::before {
  content: '';
  position: absolute; inset: 0;
  background-image:
    linear-gradient(var(--border) 1px, transparent 1px),
    linear-gradient(90deg, var(--border) 1px, transparent 1px);
  background-size: 52px 52px;
  opacity: .4;
  mask-image: radial-gradient(ellipse 100% 100% at 50% 50%, black 40%, transparent 100%);
}

/* Green glow orb */
.tru-login-visual::after {
  content: '';
  position: absolute;
  width: 600px; height: 600px;
  background: radial-gradient(circle, rgba(93,216,98,.10) 0%, transparent 65%);
  bottom: -100px; left: -100px;
  filter: blur(40px);
  pointer-events: none;
}

.tru-login-brand {
  position: relative; z-index: 1;
  display: flex; align-items: center; gap: 12px;
}
.tru-login-brand-icon {
  width: 40px; height: 40px; border-radius: 10px;
  background: var(--green);
  display: grid; place-items: center;
  flex-shrink: 0;
}
.tru-login-brand-name {
  font-family: 'Syne', sans-serif; font-weight: 700; font-size: 18px;
  color: var(--text);
}
.tru-login-brand-name span { color: var(--green); }

/* Hero text */
.tru-login-hero { position: relative; z-index: 1; }
.tru-login-eyebrow {
  display: inline-flex; align-items: center; gap: 8px;
  font-family: var(--mono); font-size: 11px; color: var(--green);
  letter-spacing: .1em; text-transform: uppercase;
  background: var(--green-dim); border: 1px solid rgba(93,216,98,.2);
  padding: 5px 13px; border-radius: 100px; margin-bottom: 24px;
}
.tru-login-eyebrow-dot {
  width: 6px; height: 6px; border-radius: 50%;
  background: var(--green);
  animation: truPulse 2s infinite;
}
@keyframes truPulse {
  0%,100% { opacity:1; transform:scale(1); }
  50%      { opacity:.5; transform:scale(.8); }
}
.tru-login-headline {
  font-family: 'Syne', sans-serif; font-weight: 800;
  font-size: clamp(32px, 3.5vw, 52px);
  line-height: 1.08; letter-spacing: -.03em;
  color: var(--text); margin-bottom: 20px;
}
.tru-login-headline em { font-style: normal; color: var(--green); }
.tru-login-subtext {
  font-size: 15px; color: var(--text2); line-height: 1.75;
  max-width: 420px; font-weight: 400;
}

/* Stats strip at bottom */
.tru-login-stats {
  position: relative; z-index: 1;
  display: flex; gap: 40px;
  padding-top: 28px; border-top: 1px solid var(--border);
}
.tru-login-stat-val {
  font-family: 'Syne', sans-serif; font-weight: 800;
  font-size: 22px; color: var(--green); letter-spacing: -.02em;
}
.tru-login-stat-lbl {
  font-family: var(--mono); font-size: 10px; color: var(--text3);
  text-transform: uppercase; letter-spacing: .08em; margin-top: 3px;
}

/* Floating machine card */
.tru-login-card {
  position: absolute; right: 52px; top: 50%;
  transform: translateY(-50%);
  width: 300px;
  background: var(--bg); border: 1px solid var(--border2);
  border-radius: 16px; padding: 20px;
  z-index: 2;
  animation: truFloat 5s ease-in-out infinite;
  box-shadow: 0 24px 60px rgba(0,0,0,.5);
}
@keyframes truFloat {
  0%,100% { transform: translateY(-50%) translateY(0); }
  50%      { transform: translateY(-50%) translateY(-10px); }
}
.tru-lcard-header {
  display: flex; justify-content: space-between; align-items: center;
  margin-bottom: 16px;
}
.tru-lcard-id { font-family: var(--mono); font-size: 11px; color: var(--text3); }
.tru-lcard-live {
  display: flex; align-items: center; gap: 5px;
  font-family: var(--mono); font-size: 11px; color: var(--green);
}
.tru-lcard-live-dot {
  width: 6px; height: 6px; border-radius: 50%;
  background: var(--green);
  box-shadow: 0 0 6px var(--green);
  animation: truPulse 2s infinite;
}
.tru-lcard-sensors {
  display: grid; grid-template-columns: 1fr 1fr 1fr;
  gap: 8px; margin-bottom: 14px;
}
.tru-lcard-sensor {
  background: var(--bg2); border-radius: 8px;
  padding: 10px 8px; text-align: center;
}
.tru-lcard-sensor-val {
  font-family: 'Syne', sans-serif; font-weight: 700;
  font-size: 16px; color: var(--text); display: block;
}
.tru-lcard-sensor-lbl {
  font-family: var(--mono); font-size: 9px;
  color: var(--text3); display: block; margin-top: 3px;
}
.tru-lcard-bar {
  height: 3px; background: var(--bg3); border-radius: 2px; overflow: hidden;
}
.tru-lcard-bar-fill {
  height: 100%; background: var(--green);
  width: 68%; border-radius: 2px;
  animation: truGrow 3s ease-in-out infinite alternate;
}
@keyframes truGrow {
  from { width: 60%; } to { width: 76%; }
}
.tru-lcard-footer {
  display: flex; justify-content: space-between;
  margin-top: 10px;
  font-family: var(--mono); font-size: 10px; color: var(--text3);
}

/* ── Right panel — form ── */
.tru-login-form-panel {
  background: var(--bg);
  display: flex; flex-direction: column;
  justify-content: center; align-items: center;
  padding: 48px 52px;
  position: relative;
}

.tru-login-form-wrap { width: 100%; max-width: 360px; }

.tru-login-form-title {
  font-family: 'Syne', sans-serif; font-weight: 800;
  font-size: 28px; letter-spacing: -.02em;
  color: var(--text); margin-bottom: 6px;
}
.tru-login-form-sub {
  font-size: 14px; color: var(--text2); margin-bottom: 36px; line-height: 1.6;
}

/* Error message */
.tru-login-error {
  display: flex; align-items: center; gap: 10px;
  background: rgba(224,85,85,.1); border: 1px solid rgba(224,85,85,.3);
  border-radius: 8px; padding: 12px 14px;
  font-size: 13px; color: var(--red);
  margin-bottom: 20px;
  animation: truShake .3s ease;
}
@keyframes truShake {
  0%,100% { transform: translateX(0); }
  25%      { transform: translateX(-4px); }
  75%      { transform: translateX(4px); }
}

/* Form fields */
.tru-lf-group { margin-bottom: 18px; }
.tru-lf-label {
  display: block; font-size: 12px; font-weight: 600;
  color: var(--text2); margin-bottom: 7px; letter-spacing: .02em;
}
.tru-lf-input {
  width: 100%; padding: 11px 14px;
  background: var(--bg2); border: 1px solid var(--border2);
  border-radius: 8px; color: var(--text);
  font-family: 'Outfit', sans-serif; font-size: 14px;
  outline: none; transition: border-color .15s, box-shadow .15s;
  appearance: none;
}
.tru-lf-input:focus {
  border-color: var(--green);
  box-shadow: 0 0 0 3px rgba(93,216,98,.12);
}
.tru-lf-input::placeholder { color: var(--text3); }
.tru-lf-input.error { border-color: var(--red); }

/* Password field wrapper */
.tru-lf-pw-wrap { position: relative; }
.tru-lf-pw-wrap .tru-lf-input { padding-right: 44px; }
.tru-lf-pw-toggle {
  position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
  background: none; border: none; cursor: pointer;
  color: var(--text3); font-size: 16px; padding: 4px;
  transition: color .15s;
}
.tru-lf-pw-toggle:hover { color: var(--text2); }

/* Remember + forgot row */
.tru-lf-row {
  display: flex; justify-content: space-between; align-items: center;
  margin-bottom: 24px;
}
.tru-lf-remember {
  display: flex; align-items: center; gap: 8px;
  font-size: 13px; color: var(--text2); cursor: pointer;
}
.tru-lf-remember input[type="checkbox"] {
  width: 14px; height: 14px; accent-color: var(--green); cursor: pointer;
}
.tru-lf-forgot {
  font-size: 13px; color: var(--text3);
  transition: color .15s;
}
.tru-lf-forgot:hover { color: var(--green); }

/* Submit button */
.tru-lf-submit {
  width: 100%; padding: 13px;
  background: var(--green); color: #080c08;
  font-family: 'Syne', sans-serif; font-weight: 700; font-size: 14px;
  border: none; border-radius: 8px; cursor: pointer;
  letter-spacing: .02em; transition: all .2s;
  display: flex; align-items: center; justify-content: center; gap: 8px;
}
.tru-lf-submit:hover { background: #7dea82; transform: translateY(-1px); box-shadow: 0 6px 24px rgba(93,216,98,.3); }
.tru-lf-submit:active { transform: translateY(0); }
.tru-lf-submit.loading { opacity: .7; cursor: not-allowed; }

/* Back to site link */
.tru-lf-back {
  display: block; text-align: center;
  margin-top: 28px; font-size: 13px; color: var(--text3);
  transition: color .15s;
}
.tru-lf-back:hover { color: var(--text2); }
.tru-lf-back::before { content: '← '; }

/* Bottom label */
.tru-lf-secure {
  position: absolute; bottom: 24px;
  font-family: var(--mono); font-size: 10px; color: var(--text3);
  display: flex; align-items: center; gap: 6px; letter-spacing: .05em;
}
.tru-lf-secure::before {
  content: '🔒';
  font-size: 11px;
}

/* ── Mobile ── */
@media (max-width: 800px) {
  .tru-login-shell { grid-template-columns: 1fr; }
  .tru-login-visual { display: none; }
  .tru-login-form-panel { min-height: 100vh; padding: 40px 28px; }
}
</style>
</head>
<body style="background:#080c08!important;overflow:hidden">
<?php wp_body_open(); ?>

<div class="tru-login-shell">

  <!-- ── Left — Visual ── -->
  <div class="tru-login-visual">

    <!-- Brand -->
    <a href="<?php echo esc_url( $home_url ); ?>" class="tru-login-brand">
      <div class="tru-login-brand-icon">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#080c08" stroke-width="2.5">
          <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10z"/>
          <path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/>
        </svg>
      </div>
      <span class="tru-login-brand-name">3R <span>Urban</span></span>
    </a>

    <!-- Hero text -->
    <div class="tru-login-hero">
      <div class="tru-login-eyebrow">
        <span class="tru-login-eyebrow-dot"></span>
        Smart Composting Intelligence
      </div>
      <h1 class="tru-login-headline">
        Your fleet.<br/>
        <em>Always</em> in view.
      </h1>
      <p class="tru-login-subtext">
        Real-time PLC data, predictive alerts, and automated reports —
        all in one secure portal built for urban composting operations.
      </p>
    </div>

    <!-- Floating machine card -->
    <div class="tru-login-card">
      <div class="tru-lcard-header">
        <span class="tru-lcard-id">3R-MCH-001 / Ward 14</span>
        <span class="tru-lcard-live"><span class="tru-lcard-live-dot"></span>LIVE</span>
      </div>
      <div class="tru-lcard-sensors">
        <div class="tru-lcard-sensor">
          <span class="tru-lcard-sensor-val">58.4°</span>
          <span class="tru-lcard-sensor-lbl">Temp</span>
        </div>
        <div class="tru-lcard-sensor">
          <span class="tru-lcard-sensor-val">320kg</span>
          <span class="tru-lcard-sensor-lbl">Load</span>
        </div>
        <div class="tru-lcard-sensor">
          <span class="tru-lcard-sensor-val">ON</span>
          <span class="tru-lcard-sensor-lbl">Motor</span>
        </div>
      </div>
      <div class="tru-lcard-bar"><div class="tru-lcard-bar-fill"></div></div>
      <div class="tru-lcard-footer">
        <span>temp_24h // °C</span>
        <span>Updated 14 min ago</span>
      </div>
    </div>

    <!-- Stats -->
    <div class="tru-login-stats">
      <div>
        <div class="tru-login-stat-val">98.4%</div>
        <div class="tru-login-stat-lbl">Uptime SLA</div>
      </div>
      <div>
        <div class="tru-login-stat-val">15min</div>
        <div class="tru-login-stat-lbl">Data Refresh</div>
      </div>
      <div>
        <div class="tru-login-stat-val">3 Cities</div>
        <div class="tru-login-stat-lbl">Deployed</div>
      </div>
    </div>

  </div><!-- /.tru-login-visual -->

  <!-- ── Right — Form ── -->
  <div class="tru-login-form-panel">
    <div class="tru-login-form-wrap">

      <h2 class="tru-login-form-title">Sign in</h2>
      <p class="tru-login-form-sub">Enter your credentials to access your fleet portal.</p>

      <?php if ( $error_msg ) : ?>
        <div class="tru-login-error">⚠ <?php echo esc_html( $error_msg ); ?></div>
      <?php endif; ?>

      <form method="POST" action="" id="truLoginForm">
        <?php wp_nonce_field( 'tru_login', 'tru_login_nonce' ); ?>
        <?php if ( $redirect_to ) : ?>
          <input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>"/>
        <?php endif; ?>

        <div class="tru-lf-group">
          <label class="tru-lf-label" for="truUser">Username or Email</label>
          <input
            type="text"
            id="truUser"
            name="log"
            class="tru-lf-input <?php echo ( $error_code === 'invalid_username' || $error_code === 'invalid_email' ) ? 'error' : ''; ?>"
            placeholder="your@email.com"
            autocomplete="username"
            required
            value="<?php echo esc_attr( $_GET['log'] ?? '' ); ?>"
            autofocus
          />
        </div>

        <div class="tru-lf-group">
          <label class="tru-lf-label" for="truPwd">Password</label>
          <div class="tru-lf-pw-wrap">
            <input
              type="password"
              id="truPwd"
              name="pwd"
              class="tru-lf-input <?php echo $error_code === 'incorrect_password' ? 'error' : ''; ?>"
              placeholder="••••••••"
              autocomplete="current-password"
              required
            />
            <button type="button" class="tru-lf-pw-toggle" onclick="truTogglePwd()" aria-label="Show password" id="truPwdToggle">
              👁
            </button>
          </div>
        </div>

        <div class="tru-lf-row">
          <label class="tru-lf-remember">
            <input type="checkbox" name="rememberme" value="forever"/>
            Remember me
          </label>
          <a href="<?php echo esc_url( wp_lostpassword_url() ); ?>" class="tru-lf-forgot">Forgot password?</a>
        </div>

        <button type="submit" class="tru-lf-submit" id="truSubmitBtn">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
          Sign In to Portal
        </button>
      </form>

      <a href="<?php echo esc_url( $home_url ); ?>" class="tru-lf-back">Back to website</a>

    </div><!-- /.tru-login-form-wrap -->

    <div class="tru-lf-secure">SECURED · TLS ENCRYPTED · 3R URBAN</div>
  </div><!-- /.tru-login-form-panel -->

</div><!-- /.tru-login-shell -->

<script>
function truTogglePwd() {
  const input  = document.getElementById('truPwd');
  const toggle = document.getElementById('truPwdToggle');
  const show   = input.type === 'password';
  input.type   = show ? 'text' : 'password';
  toggle.textContent = show ? '🙈' : '👁';
}
document.getElementById('truLoginForm')?.addEventListener('submit', function() {
  const btn = document.getElementById('truSubmitBtn');
  btn.classList.add('loading');
  btn.innerHTML = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="animation:spin .8s linear infinite"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg> Signing in…';
  btn.disabled = true;
});
</script>
<style>@keyframes spin{from{transform:rotate(0)}to{transform:rotate(360deg)}}</style>
<?php wp_footer(); ?>
</body>
</html>
