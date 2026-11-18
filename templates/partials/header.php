<?php defined( 'ABSPATH' ) || exit; ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="tru-page" style="background:#080c08!important">
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title><?php wp_title( '—', true, 'right' ); bloginfo( 'name' ); ?></title>

<!-- Fonts: preconnect first for speed, then load directly (bypasses WP enqueue delay) -->
<link rel="preconnect" href="https://fonts.googleapis.com" crossorigin/>
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=Outfit:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap"/>

<?php wp_head(); ?>

<style>
/* ── Twenty Twenty-Five hard reset ────────────────────────── */
html.tru-page,
html.tru-page body,
html.tru-page body.tru-urban-body {
  background: #080c08 !important;
  background-color: #080c08 !important;
  color: #dceadc !important;
  font-family: 'Outfit', sans-serif !important;
}
/* Kill TT5 CSS custom properties */
html.tru-page body.tru-urban-body {
  --wp--preset--color--base:            #080c08 !important;
  --wp--preset--color--base-2:          #0d130d !important;
  --wp--preset--color--contrast:        #dceadc !important;
  --wp--style--global--content-size:    100%    !important;
  --wp--style--global--wide-size:       100%    !important;
  /* Override TT5 typography presets */
  --wp--preset--font-family--body:      'Outfit', sans-serif !important;
  --wp--preset--font-family--heading:   'Syne', sans-serif   !important;
  --wp--preset--font-size--medium:      16px !important;
}
/* TT5 FSE block wrappers */
html.tru-page .wp-site-blocks,
html.tru-page .wp-block-group,
html.tru-page .wp-block-template-part,
html.tru-page .wp-block-post-content,
html.tru-page .entry-content,
html.tru-page main, html.tru-page #main,
html.tru-page #page, html.tru-page .site,
html.tru-page p, html.tru-page li,
html.tru-page span, html.tru-page div {
  background-color: transparent !important;
  color: inherit !important;
  font-family: 'Outfit', sans-serif !important;
}
html.tru-page h1, html.tru-page h2,
html.tru-page h3, html.tru-page h4 {
  font-family: 'Syne', sans-serif !important;
}
html.tru-page .wp-site-blocks,
html.tru-page .wp-block-group,
html.tru-page .wp-block-template-part,
html.tru-page .wp-block-post-content,
html.tru-page .entry-content,
html.tru-page main, html.tru-page #main,
html.tru-page #page, html.tru-page .site {
  background: #080c08 !important;
  max-width: none !important;
  padding: 0 !important;
  margin: 0 !important;
}
/* Hide TT5's own header/footer */
html.tru-page .wp-site-blocks > header,
html.tru-page .wp-site-blocks > footer { display: none !important; }
</style>
</head>
<body <?php body_class( '3r-urban-body' ); ?> style="background:#080c08!important;font-family:'Outfit',sans-serif!important">

<?php
$page_ids    = get_option( 'tru_page_ids', [] );
$home_url    = isset( $page_ids['3r-home'] )    ? get_permalink( $page_ids['3r-home'] )    : home_url('/');
$about_url   = isset( $page_ids['3r-about'] )   ? get_permalink( $page_ids['3r-about'] )   : '#';
$demo_url    = isset( $page_ids['3r-demo'] )    ? get_permalink( $page_ids['3r-demo'] )    : '#';
$contact_url = isset( $page_ids['3r-contact'] ) ? get_permalink( $page_ids['3r-contact'] ) : '#';
$login_url   = isset( $page_ids['3r-login'] )   ? get_permalink( $page_ids['3r-login'] )   : wp_login_url();
$portal_url  = isset( $page_ids['3r-portal-dashboard'] ) ? get_permalink( $page_ids['3r-portal-dashboard'] ) : '#';
$current     = get_the_ID();

// Smart auth state
$logged_in = is_user_logged_in();
$user      = $logged_in ? wp_get_current_user() : null;
$user_init = $logged_in ? strtoupper( substr( $user->display_name ?: $user->user_login, 0, 1 ) ) : '';
?>

<nav class="tru-nav" id="truNav">
  <a href="<?php echo esc_url( $home_url ); ?>" class="tru-nav-logo">
    <div class="tru-nav-logo-icon">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#080c08" stroke-width="2.5">
        <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10z"/>
        <path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/>
      </svg>
    </div>
    <span class="tru-nav-logo-text">3R <span>Urban</span></span>
  </a>

  <div class="tru-nav-links" id="truNavLinks">
    <a href="<?php echo esc_url( $home_url ); ?>"    <?php if ( isset( $page_ids['3r-home'] )    && $current === $page_ids['3r-home'] )    echo 'class="active"'; ?>>Home</a>
    <a href="<?php echo esc_url( $about_url ); ?>"   <?php if ( isset( $page_ids['3r-about'] )   && $current === $page_ids['3r-about'] )   echo 'class="active"'; ?>>About</a>
    <a href="<?php echo esc_url( $contact_url ); ?>" <?php if ( isset( $page_ids['3r-contact'] ) && $current === $page_ids['3r-contact'] ) echo 'class="active"'; ?>>Contact</a>
  </div>

  <a href="<?php echo esc_url( $demo_url ); ?>" class="tru-nav-cta <?php if ( isset( $page_ids['3r-demo'] ) && $current === $page_ids['3r-demo'] ) echo 'active'; ?>">
    Request Demo →
  </a>

  <?php if ( $logged_in ) : ?>
    <!-- Logged-in: portal avatar button with dropdown -->
    <div class="tru-nav-portal" id="truNavPortal">
      <button class="tru-nav-portal-btn" onclick="truTogglePortalMenu()" aria-label="Portal menu">
        <span class="tru-nav-portal-avatar"><?php echo esc_html( $user_init ); ?></span>
        <span class="tru-nav-portal-label">My Portal</span>
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="opacity:.6"><polyline points="6 9 12 15 18 9"/></svg>
      </button>
      <div class="tru-nav-portal-drop" id="truPortalDrop">
        <a href="<?php echo esc_url( $portal_url ); ?>" class="tru-nav-drop-item">
          <span class="tru-drop-icon">⬡</span> Dashboard
        </a>
        <a href="<?php echo esc_url( isset( $page_ids['3r-portal-alerts'] ) ? get_permalink( $page_ids['3r-portal-alerts'] ) : '#' ); ?>" class="tru-nav-drop-item">
          <span class="tru-drop-icon">🔔</span> Alerts
        </a>
        <a href="<?php echo esc_url( isset( $page_ids['3r-portal-account'] ) ? get_permalink( $page_ids['3r-portal-account'] ) : '#' ); ?>" class="tru-nav-drop-item">
          <span class="tru-drop-icon">👤</span> <?php echo esc_html( $user->display_name ?: $user->user_login ); ?>
        </a>
        <?php if ( class_exists('ThreeR_Portal_Auth') && ThreeR_Portal_Auth::is_admin_user() ) :
          $admin_url = isset($page_ids['3r-admin-machines']) ? get_permalink($page_ids['3r-admin-machines']) : '#';
        ?>
        <div class="tru-drop-divider"></div>
        <a href="<?php echo esc_url( $admin_url ); ?>" class="tru-nav-drop-item tru-drop-admin">
          <span class="tru-drop-icon">⚙</span> Admin Panel
        </a>
        <?php endif; ?>
        <div class="tru-drop-divider"></div>
        <a href="<?php echo esc_url( wp_logout_url( $home_url ) ); ?>" class="tru-nav-drop-item tru-drop-logout">
          <span class="tru-drop-icon">⏻</span> Log Out
        </a>
      </div>
    </div>
  <?php else : ?>
    <!-- Logged-out: Sign In button -->
    <a href="<?php echo esc_url( $login_url ); ?>" class="tru-nav-signin">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
      Sign In
    </a>
  <?php endif; ?>

  <button class="tru-mobile-toggle" onclick="truToggleMenu()" aria-label="Toggle menu">
    <span></span><span></span><span></span>
  </button>
</nav>

<div class="tru-page-wrap">
