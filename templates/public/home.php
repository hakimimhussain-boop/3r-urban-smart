<?php
/**
 * Template Name: Home
 * Template for: 3R Urban Smart Solutions homepage
 */
defined( 'ABSPATH' ) || exit;

$partials = plugin_dir_path( __FILE__ ) . '../partials/';
include $partials . 'header.php';

$id          = get_the_ID();
$f           = 'ThreeR_Urban_Meta_Fields::get';
$page_ids    = get_option( 'tru_page_ids', [] );
$demo_url    = isset( $page_ids['3r-demo'] )    ? get_permalink( $page_ids['3r-demo'] )    : '#';
$about_url   = isset( $page_ids['3r-about'] )   ? get_permalink( $page_ids['3r-about'] )   : '#';
$contact_url = isset( $page_ids['3r-contact'] ) ? get_permalink( $page_ids['3r-contact'] ) : '#';
?>

<!-- ═══════════ HERO ═══════════ -->
<section class="tru-hero">
  <div class="tru-hero-grid-bg"></div>
  <div class="tru-hero-orb tru-hero-orb-1"></div>
  <div class="tru-hero-orb tru-hero-orb-2"></div>

  <div class="tru-hero-content tru-anim-1">
    <div class="tru-eyebrow">
      <span class="tru-eyebrow-dot"></span>
      <?php echo esc_html( $f( $id, 'tru_hero_eyebrow' ) ); ?>
    </div>
    <h1 class="tru-hero-title">
      <?php echo esc_html( $f( $id, 'tru_hero_line1' ) ); ?><br/>
      <span class="tru-accent-green"><?php echo esc_html( $f( $id, 'tru_hero_line2' ) ); ?></span><br/>
      <span class="tru-accent-earth"><?php echo esc_html( $f( $id, 'tru_hero_line3' ) ); ?></span>
    </h1>
    <p class="tru-hero-sub"><?php echo esc_html( $f( $id, 'tru_hero_sub' ) ); ?></p>
    <div class="tru-hero-actions">
      <a href="<?php echo esc_url( $demo_url ); ?>" class="tru-btn-primary">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M5 3l14 9-14 9V3z"/></svg>
        <?php echo esc_html( $f( $id, 'tru_hero_btn1' ) ); ?>
      </a>
      <a href="<?php echo esc_url( $about_url ); ?>" class="tru-btn-outline"><?php echo esc_html( $f( $id, 'tru_hero_btn2' ) ); ?></a>
    </div>
    <div class="tru-hero-stats">
      <div class="tru-hero-stat">
        <div class="tru-stat-value"><?php echo esc_html( $f( $id, 'tru_stat1_val' ) ); ?></div>
        <div class="tru-stat-label"><?php echo esc_html( $f( $id, 'tru_stat1_lbl' ) ); ?></div>
      </div>
      <div class="tru-hero-stat">
        <div class="tru-stat-value"><?php echo esc_html( $f( $id, 'tru_stat2_val' ) ); ?></div>
        <div class="tru-stat-label"><?php echo esc_html( $f( $id, 'tru_stat2_lbl' ) ); ?></div>
      </div>
      <div class="tru-hero-stat">
        <div class="tru-stat-value"><?php echo esc_html( $f( $id, 'tru_stat3_val' ) ); ?></div>
        <div class="tru-stat-label"><?php echo esc_html( $f( $id, 'tru_stat3_lbl' ) ); ?></div>
      </div>
    </div>
  </div>

  <!-- Animated machine card -->
  <div class="tru-hero-visual">
    <div class="tru-data-pill tru-pill-top"><span class="tru-pill-muted">MQTT</span> <span class="tru-pill-val">CONNECTED</span></div>
    <div class="tru-machine-card tru-float">
      <div class="tru-machine-header">
        <span class="tru-machine-id">3R-MCH-001 / Ward 14</span>
        <span class="tru-machine-live"><span class="tru-live-dot"></span>LIVE</span>
      </div>
      <div class="tru-sensor-grid">
        <div class="tru-sensor-item"><div class="tru-sensor-icon">🌡️</div><div class="tru-sensor-val">58.4</div><div class="tru-sensor-unit">°C</div><div class="tru-sensor-lbl">Temperature</div></div>
        <div class="tru-sensor-item"><div class="tru-sensor-icon">⚖️</div><div class="tru-sensor-val">320</div><div class="tru-sensor-unit">kg</div><div class="tru-sensor-lbl">Load</div></div>
        <div class="tru-sensor-item"><div class="tru-sensor-icon">⚙️</div><div class="tru-sensor-val">ON</div><div class="tru-sensor-unit">45 RPM</div><div class="tru-sensor-lbl">Motor</div></div>
      </div>
      <div class="tru-mini-chart"><div class="tru-chart-label">temp_24h // °C</div><div class="tru-chart-bars" id="truHeroChart"></div></div>
    </div>
    <div class="tru-data-pill tru-pill-btm">Alert cleared <span class="tru-pill-val">2h ago</span></div>
  </div>
</section>

<!-- ═══════════ MARQUEE ═══════════ -->
<div class="tru-marquee-section"><div class="tru-marquee-track" id="truMarquee"></div></div>

<!-- ═══════════ FEATURES ═══════════ -->
<section class="tru-section">
  <div class="tru-container">
    <div class="tru-section-label">Platform Features</div>
    <h2 class="tru-section-title"><?php echo esc_html( $f( $id, 'tru_feat_heading' ) ); ?></h2>
    <p class="tru-section-sub"><?php echo esc_html( $f( $id, 'tru_feat_sub' ) ); ?></p>
    <div class="tru-features-grid">
      <?php
      $feat_icons = ['📡','🔔','📊','🔒','📄','🔧'];
      $feat_tags  = ['// Modbus RTU → MQTT','// auto-triggered','// Chart.js powered','// multi-tenant','// scheduled exports','// full fleet control'];
      for ( $i = 1; $i <= 6; $i++ ) :
        $icon = $feat_icons[ $i - 1 ];
        $tag  = $feat_tags[ $i - 1 ];
      ?>
        <div class="tru-feature-card">
          <div class="tru-feature-icon"><?php echo $icon; ?></div>
          <div class="tru-feature-title"><?php echo esc_html( $f( $id, "tru_feat{$i}_title" ) ); ?></div>
          <div class="tru-feature-desc"><?php echo esc_html( $f( $id, "tru_feat{$i}_desc" ) ); ?></div>
          <div class="tru-feature-tag"><?php echo esc_html( $tag ); ?></div>
        </div>
      <?php endfor; ?>
    </div>
  </div>
</section>

<!-- ═══════════ HOW IT WORKS ═══════════ -->
<section class="tru-section tru-section-dark">
  <div class="tru-container">
    <div class="tru-section-label">How It Works</div>
    <h2 class="tru-section-title"><?php echo esc_html( $f( $id, 'tru_hiw_heading' ) ); ?></h2>
    <div class="tru-steps-row">
      <?php for ( $i = 1; $i <= 4; $i++ ) : ?>
        <div class="tru-step">
          <div class="tru-step-num">0<?php echo $i; ?></div>
          <div class="tru-step-title"><?php echo esc_html( $f( $id, "tru_step{$i}_title" ) ); ?></div>
          <div class="tru-step-desc"><?php echo esc_html( $f( $id, "tru_step{$i}_desc" ) ); ?></div>
        </div>
      <?php endfor; ?>
    </div>
  </div>
</section>

<!-- ═══════════ MACHINE PREVIEW ═══════════ -->
<section class="tru-section">
  <div class="tru-container">
    <div class="tru-section-label">Live Fleet View</div>
    <h2 class="tru-section-title">Your machines,<br/>always visible</h2>
    <p class="tru-section-sub">A real-time snapshot of your entire composting operation — at a glance, from anywhere.</p>
    <div class="tru-machines-grid">
      <div class="tru-mcard">
        <div class="tru-mcard-top"><div><div class="tru-mcard-id">3R-MCH-001</div><div class="tru-mcard-name">CompostMaster Pro</div><div class="tru-mcard-loc">📍 Ward 14, North District</div></div><span class="tru-badge tru-badge-online">● Online</span></div>
        <div class="tru-mcard-sensors"><div class="tru-msensor"><div class="tru-msensor-val">58.4°</div><div class="tru-msensor-lbl">Temp</div></div><div class="tru-msensor"><div class="tru-msensor-val">320kg</div><div class="tru-msensor-lbl">Load</div></div><div class="tru-msensor"><div class="tru-msensor-val">45rpm</div><div class="tru-msensor-lbl">Motor</div></div></div>
        <div class="tru-mcard-bar"><div class="tru-mcard-fill" style="width:68%"></div></div>
      </div>
      <div class="tru-mcard">
        <div class="tru-mcard-top"><div><div class="tru-mcard-id">3R-MCH-002</div><div class="tru-mcard-name">CompostMaster Pro</div><div class="tru-mcard-loc">📍 Central Market Zone</div></div><span class="tru-badge tru-badge-warning">⚠ Warning</span></div>
        <div class="tru-mcard-sensors"><div class="tru-msensor"><div class="tru-msensor-val tru-val-warn">74.1°</div><div class="tru-msensor-lbl">Temp</div></div><div class="tru-msensor"><div class="tru-msensor-val">512kg</div><div class="tru-msensor-lbl">Load</div></div><div class="tru-msensor"><div class="tru-msensor-val">38rpm</div><div class="tru-msensor-lbl">Motor</div></div></div>
        <div class="tru-mcard-bar"><div class="tru-mcard-fill tru-fill-warn" style="width:88%"></div></div>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════ CTA ═══════════ -->
<section class="tru-cta-section">
  <div class="tru-cta-orb"></div>
  <div class="tru-container">
    <div class="tru-cta-box">
      <div class="tru-section-label" style="justify-content:center">Get Started</div>
      <h2 class="tru-section-title"><?php echo esc_html( $f( $id, 'tru_cta_title' ) ); ?></h2>
      <p class="tru-cta-sub"><?php echo esc_html( $f( $id, 'tru_cta_sub' ) ); ?></p>
      <div class="tru-cta-buttons">
        <a href="<?php echo esc_url( $demo_url ); ?>" class="tru-btn-primary"><?php echo esc_html( $f( $id, 'tru_cta_btn1' ) ); ?></a>
        <a href="<?php echo esc_url( $contact_url ); ?>" class="tru-btn-outline"><?php echo esc_html( $f( $id, 'tru_cta_btn2' ) ); ?></a>
      </div>
    </div>
  </div>
</section>

<?php include $partials . 'footer.php'; ?>
