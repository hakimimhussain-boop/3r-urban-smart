<?php
/**
 * Template Name: About
 */
defined( 'ABSPATH' ) || exit;
$partials = plugin_dir_path( __FILE__ ) . '../partials/';
include $partials . 'header.php';
$id = get_the_ID();
$f  = 'ThreeR_Urban_Meta_Fields::get';
?>

<section class="tru-section tru-about-hero">
  <div class="tru-container">
    <div class="tru-section-label tru-anim-1"><?php echo esc_html( $f( $id, 'tru_ab_eyebrow' ) ); ?></div>
    <div class="tru-about-grid">
      <div class="tru-anim-2">
        <h1 class="tru-section-title"><?php echo esc_html( $f( $id, 'tru_ab_title' ) ); ?></h1>
        <p class="tru-about-mission"><?php echo wp_kses_post( $f( $id, 'tru_ab_mission' ) ); ?></p>
      </div>
      <div class="tru-anim-3">
        <div class="tru-values-list">
          <?php for ( $i = 1; $i <= 3; $i++ ) : ?>
            <div class="tru-value-item">
              <div class="tru-value-num">0<?php echo $i; ?></div>
              <div>
                <div class="tru-value-title"><?php echo esc_html( $f( $id, "tru_ab_val{$i}_title" ) ); ?></div>
                <div class="tru-value-desc"><?php echo esc_html( $f( $id, "tru_ab_val{$i}_desc" ) ); ?></div>
              </div>
            </div>
          <?php endfor; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="tru-stats-row">
  <?php for ( $i = 1; $i <= 4; $i++ ) : ?>
    <div class="tru-about-stat">
      <div class="tru-about-stat-num"><?php echo esc_html( $f( $id, "tru_ab_stat{$i}_val" ) ); ?></div>
      <div class="tru-about-stat-label"><?php echo esc_html( $f( $id, "tru_ab_stat{$i}_lbl" ) ); ?></div>
    </div>
  <?php endfor; ?>
</div>

<section class="tru-section">
  <div class="tru-container">
    <div class="tru-section-label">The Team</div>
    <h2 class="tru-section-title">People behind the platform</h2>
    <div class="tru-team-grid">
      <?php
      $avatars = ['🧑‍💼','👩‍💻','🧑‍🔧'];
      for ( $i = 1; $i <= 3; $i++ ) : ?>
        <div class="tru-team-card">
          <div class="tru-team-avatar"><?php echo $avatars[$i-1]; ?></div>
          <div class="tru-team-name"><?php echo esc_html( $f( $id, "tru_tm{$i}_name" ) ); ?></div>
          <div class="tru-team-role"><?php echo esc_html( $f( $id, "tru_tm{$i}_role" ) ); ?></div>
          <div class="tru-team-bio"><?php echo esc_html( $f( $id, "tru_tm{$i}_bio" ) ); ?></div>
        </div>
      <?php endfor; ?>
    </div>
  </div>
</section>

<section class="tru-section" style="padding-top:0">
  <div class="tru-container">
    <div class="tru-section-label">Technology</div>
    <h2 class="tru-section-title"><?php echo esc_html( $f( $id, 'tru_tech_title' ) ); ?></h2>
    <p class="tru-section-sub"><?php echo esc_html( $f( $id, 'tru_tech_sub' ) ); ?></p>
    <div class="tru-tech-row">
      <?php
      $tech = ['Teltonika RUT200','Modbus RTU','MQTT / Mosquitto','Node.js','MySQL','WordPress CMS','Chart.js','WP REST API','Let\'s Encrypt TLS','PM2 Process Manager','Ubuntu VPS','WP Mail SMTP'];
      foreach ( $tech as $t ) echo '<span class="tru-tech-pill">' . esc_html( $t ) . '</span>';
      ?>
    </div>
  </div>
</section>

<?php include $partials . 'footer.php'; ?>
