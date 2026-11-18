<?php
defined( 'ABSPATH' ) || exit;

$page_ids   = get_option( 'tru_page_ids', [] );
$user       = wp_get_current_user();
$user_init  = strtoupper( substr( $user->display_name ?: $user->user_login, 0, 1 ) );
$current    = get_the_ID();
$portal_url = isset($page_ids['3r-portal-dashboard']) ? get_permalink($page_ids['3r-portal-dashboard']) : '#';
$home_url   = isset($page_ids['3r-home'])             ? get_permalink($page_ids['3r-home'])             : home_url('/');

$machines_total = count( ThreeR_Admin_Data::get_all_machines() );
$customers_total = count( ThreeR_Admin_Data::get_all_customers() );

$nav_items = [
    [ 'id' => '3r-admin-machines',   'label' => 'All Machines',       'icon' => '⬡', 'badge' => $machines_total ],
    [ 'id' => '3r-admin-add',        'label' => 'Add Machine',        'icon' => '+' ],
    [ 'id' => '3r-admin-assign',     'label' => 'Assign Machine',     'icon' => '↗' ],
    [ 'id' => '3r-admin-customers',  'label' => 'All Customers',      'icon' => '👥', 'badge' => $customers_total ],
    [ 'id' => '3r-admin-thresholds', 'label' => 'Alert Thresholds',   'icon' => '⚠' ],
];
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="tru-page" style="background:#080c08!important">
<head>
<meta charset="<?php bloginfo('charset'); ?>"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title><?php echo esc_html( $admin_page_title ?? 'Admin' ); ?> — 3R Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com" crossorigin/>
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=Outfit:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap"/>
<?php wp_head(); ?>
<style>
html.tru-page,html.tru-page body,html.tru-page body.tru-admin-body{background:#080c08!important;color:#dceadc!important;font-family:'Outfit',sans-serif!important}
html.tru-page .wp-site-blocks,html.tru-page #page,html.tru-page .site,html.tru-page main{background:#080c08!important;max-width:none!important;padding:0!important;margin:0!important}
html.tru-page .wp-site-blocks>header,html.tru-page .wp-site-blocks>footer{display:none!important}
html.tru-page body.tru-admin-body{--wp--preset--color--base:#080c08!important;--wp--preset--color--contrast:#dceadc!important}
</style>
</head>
<body <?php body_class('tru-admin-body'); ?> style="background:#080c08!important">

<div class="tru-admin-shell">

  <!-- ── Admin Sidebar ── -->
  <aside class="tru-admin-sidebar" id="truAdminSidebar">
    <div class="tru-admin-sidebar-logo">
      <a href="<?php echo esc_url($home_url); ?>" class="tru-admin-brand">
        <div class="tru-admin-brand-icon">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#080c08" stroke-width="2.5">
            <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10z"/>
            <path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/>
          </svg>
        </div>
        <div>
          <div class="tru-admin-brand-name">3R <span>Urban</span></div>
          <div class="tru-admin-brand-sub">// ADMIN</div>
        </div>
      </a>
      <button class="tru-admin-sidebar-close" onclick="truAdminToggle()">✕</button>
    </div>

    <nav class="tru-admin-nav">
      <div class="tru-admin-nav-section">Machines</div>
      <?php foreach ( array_slice($nav_items, 0, 3) as $item ) :
        $url = isset($page_ids[$item['id']]) ? get_permalink($page_ids[$item['id']]) : '#';
        $active = isset($page_ids[$item['id']]) && $current === $page_ids[$item['id']];
      ?>
        <a href="<?php echo esc_url($url); ?>" class="tru-admin-nav-link <?php echo $active ? 'active' : ''; ?>">
          <span class="tru-admin-nav-icon"><?php echo $item['icon']; ?></span>
          <span class="tru-admin-nav-label"><?php echo esc_html($item['label']); ?></span>
          <?php if (!empty($item['badge'])) : ?>
            <span class="tru-admin-nav-badge"><?php echo (int)$item['badge']; ?></span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>

      <div class="tru-admin-nav-section" style="margin-top:16px">Customers</div>
      <?php foreach ( array_slice($nav_items, 3, 1) as $item ) :
        $url = isset($page_ids[$item['id']]) ? get_permalink($page_ids[$item['id']]) : '#';
        $active = isset($page_ids[$item['id']]) && $current === $page_ids[$item['id']];
      ?>
        <a href="<?php echo esc_url($url); ?>" class="tru-admin-nav-link <?php echo $active ? 'active' : ''; ?>">
          <span class="tru-admin-nav-icon"><?php echo $item['icon']; ?></span>
          <span class="tru-admin-nav-label"><?php echo esc_html($item['label']); ?></span>
          <?php if (!empty($item['badge'])) : ?>
            <span class="tru-admin-nav-badge"><?php echo (int)$item['badge']; ?></span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>

      <div class="tru-admin-nav-section" style="margin-top:16px">Settings</div>
      <?php foreach ( array_slice($nav_items, 4) as $item ) :
        $url = isset($page_ids[$item['id']]) ? get_permalink($page_ids[$item['id']]) : '#';
        $active = isset($page_ids[$item['id']]) && $current === $page_ids[$item['id']];
      ?>
        <a href="<?php echo esc_url($url); ?>" class="tru-admin-nav-link <?php echo $active ? 'active' : ''; ?>">
          <span class="tru-admin-nav-icon"><?php echo $item['icon']; ?></span>
          <span class="tru-admin-nav-label"><?php echo esc_html($item['label']); ?></span>
        </a>
      <?php endforeach; ?>
    </nav>

    <div class="tru-admin-sidebar-footer">
      <a href="<?php echo esc_url($portal_url); ?>" class="tru-admin-switch-btn">← Customer Portal</a>
      <div class="tru-admin-sidebar-user">
        <div class="tru-admin-user-avatar"><?php echo esc_html($user_init); ?></div>
        <div class="tru-admin-user-info">
          <div class="tru-admin-user-name"><?php echo esc_html($user->display_name ?: $user->user_login); ?></div>
          <div class="tru-admin-user-role">Administrator</div>
        </div>
        <a href="<?php echo esc_url(wp_logout_url($home_url)); ?>" class="tru-admin-logout" title="Log out">⏻</a>
      </div>
    </div>
  </aside>

  <!-- ── Admin Main ── -->
  <div class="tru-admin-main">
    <header class="tru-admin-topbar">
      <button class="tru-admin-hamburger" onclick="truAdminToggle()"><span></span><span></span><span></span></button>
      <div class="tru-admin-topbar-title"><?php echo esc_html($admin_page_title ?? 'Admin'); ?></div>
      <div class="tru-admin-topbar-right">
        <div class="tru-admin-topbar-badge">Admin Panel</div>
        <div class="tru-admin-topbar-avatar"><?php echo esc_html($user_init); ?></div>
      </div>
    </header>
    <div class="tru-admin-content">
