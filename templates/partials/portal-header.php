<?php
defined( 'ABSPATH' ) || exit;

$page_ids    = get_option( 'tru_page_ids', [] );
$dash_url    = isset( $page_ids['3r-portal-dashboard'] ) ? get_permalink( $page_ids['3r-portal-dashboard'] ) : '#';
$alerts_url  = isset( $page_ids['3r-portal-alerts'] )    ? get_permalink( $page_ids['3r-portal-alerts'] )    : '#';
$reports_url = isset( $page_ids['3r-portal-reports'] )   ? get_permalink( $page_ids['3r-portal-reports'] )   : '#';
$account_url = isset( $page_ids['3r-portal-account'] )   ? get_permalink( $page_ids['3r-portal-account'] )   : '#';
$home_url    = isset( $page_ids['3r-home'] )              ? get_permalink( $page_ids['3r-home'] )              : home_url('/');

$guest       = ! is_user_logged_in();
$current     = get_the_ID();
$user        = wp_get_current_user();
$user_name   = $user->display_name ?: $user->user_login;
$user_init   = strtoupper( substr( $user_name, 0, 1 ) );

// Alert badge count
$active_alerts = 0;
if ( ! $guest ) {
    $alert_counts = ThreeR_Portal_Data::get_alert_counts( get_current_user_id() );
    $active_alerts = ( $alert_counts['active'] ?? 0 );
}

$nav_items = [
    [ 'url' => $dash_url,    'id' => '3r-portal-dashboard', 'icon' => '⬡', 'label' => 'Dashboard'  ],
    [ 'url' => $alerts_url,  'id' => '3r-portal-alerts',    'icon' => '🔔', 'label' => 'Alerts',   'badge' => $active_alerts ],
    [ 'url' => $reports_url, 'id' => '3r-portal-reports',   'icon' => '📄', 'label' => 'Reports'   ],
    [ 'url' => $account_url, 'id' => '3r-portal-account',   'icon' => '👤', 'label' => 'My Account' ],
];
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="tru-page" style="background:#080c08!important">
<head>
<meta charset="<?php bloginfo('charset'); ?>"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title><?php echo esc_html( $page_title ?? 'Portal' ); ?> — 3R Urban</title>
<link rel="preconnect" href="https://fonts.googleapis.com" crossorigin/>
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=Outfit:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap"/>
<?php wp_head(); ?>
<style>
html.tru-page, html.tru-page body, html.tru-page body.tru-portal-body {
  background: #080c08 !important; background-color: #080c08 !important;
  color: #dceadc !important; font-family: 'Outfit', sans-serif !important;
}
html.tru-page .wp-site-blocks,html.tru-page .wp-block-group,
html.tru-page .wp-block-template-part,html.tru-page main,
html.tru-page #page,html.tru-page .site {
  background:#080c08!important; max-width:none!important; padding:0!important; margin:0!important;
}
html.tru-page .wp-site-blocks>header,html.tru-page .wp-site-blocks>footer { display:none!important; }
html.tru-page body.tru-portal-body {
  --wp--preset--color--base:#080c08!important;
  --wp--preset--color--contrast:#dceadc!important;
}
</style>
</head>
<body <?php body_class('tru-portal-body'); ?> style="background:#080c08!important">

<div class="tru-portal-shell">

  <!-- ── Sidebar ── -->
  <aside class="tru-sidebar" id="truSidebar">
    <div class="tru-sidebar-logo">
      <a href="<?php echo esc_url( $home_url ); ?>" class="tru-sidebar-brand">
        <div class="tru-sidebar-icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#080c08" stroke-width="2.5">
            <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10z"/>
            <path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/>
          </svg>
        </div>
        <span class="tru-sidebar-name">3R <span>Urban</span></span>
      </a>
      <button class="tru-sidebar-close" onclick="truToggleSidebar()" aria-label="Close menu">✕</button>
    </div>

    <div class="tru-sidebar-context">
      <div class="tru-sidebar-ctx-label">// PORTAL</div>
      <div class="tru-sidebar-ctx-org"><?php echo $guest ? 'Public live view' : esc_html( get_user_meta( $user->ID, 'tru_organisation', true ) ?: 'Customer Account' ); ?></div>
    </div>

    <nav class="tru-sidebar-nav">
      <?php if ( $guest ) : $nav_items = [ [ 'url' => wp_login_url(), 'id' => '', 'icon' => '👤', 'label' => 'Customer sign in' ] ]; endif; ?>
      <?php foreach ( $nav_items as $item ) :
        $is_active = isset( $page_ids[ $item['id'] ] ) && $current === $page_ids[ $item['id'] ];
      ?>
        <a href="<?php echo esc_url( $item['url'] ); ?>"
           class="tru-sidebar-link <?php echo $is_active ? 'active' : ''; ?>">
          <span class="tru-sidebar-link-icon"><?php echo $item['icon']; ?></span>
          <span class="tru-sidebar-link-label"><?php echo esc_html( $item['label'] ); ?></span>
          <?php if ( ! empty( $item['badge'] ) && $item['badge'] > 0 ) : ?>
            <span class="tru-sidebar-badge"><?php echo (int) $item['badge']; ?></span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </nav>

    <div class="tru-sidebar-footer">
      <?php if ( ! $guest ) : ?>
      <?php if ( ThreeR_Portal_Auth::is_admin_user() ) :
        $admin_url = isset($page_ids['3r-admin-machines']) ? get_permalink($page_ids['3r-admin-machines']) : '#';
      ?>
        <a href="<?php echo esc_url($admin_url); ?>" class="tru-admin-panel-link">
          <span>⬡</span> Admin Panel
        </a>
      <?php endif; ?>
      <div class="tru-sidebar-user">
        <div class="tru-sidebar-avatar"><?php echo esc_html( $user_init ); ?></div>
        <div class="tru-sidebar-user-info">
          <div class="tru-sidebar-user-name"><?php echo esc_html( $user_name ); ?></div>
          <div class="tru-sidebar-user-role">Customer</div>
        </div>
      </div>
      <a href="<?php echo esc_url( wp_logout_url( $home_url ) ); ?>" class="tru-sidebar-logout" title="Log out">⏻</a>
      <?php endif; ?>
    </div>
  </aside>

  <!-- ── Main area ── -->
  <div class="tru-portal-main">

    <!-- Top bar -->
    <header class="tru-portal-topbar">
      <button class="tru-topbar-menu" onclick="truToggleSidebar()" aria-label="Open menu">
        <span></span><span></span><span></span>
      </button>
      <div class="tru-topbar-title"><?php echo esc_html( $page_title ?? 'Portal' ); ?></div>
      <div class="tru-topbar-right">
        <div class="tru-topbar-live"><span class="tru-live-dot"></span>Live</div>
        <?php if ( ! $guest ) : ?>
        <a href="<?php echo esc_url( $alerts_url ); ?>" class="tru-topbar-bell">
          🔔<?php if ( $active_alerts > 0 ) : ?><span class="tru-bell-badge"><?php echo $active_alerts; ?></span><?php endif; ?>
        </a>
        <a href="<?php echo esc_url( $account_url ); ?>" class="tru-topbar-avatar"><?php echo esc_html( $user_init ); ?></a>
        <?php endif; ?>
      </div>
    </header>

    <!-- Page content starts here -->
    <div class="tru-portal-content">
