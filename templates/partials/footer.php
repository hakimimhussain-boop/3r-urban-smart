<?php defined( 'ABSPATH' ) || exit;
$page_ids    = get_option( 'tru_page_ids', [] );
$home_url    = isset( $page_ids['3r-home'] )    ? get_permalink( $page_ids['3r-home'] )    : home_url('/');
$about_url   = isset( $page_ids['3r-about'] )   ? get_permalink( $page_ids['3r-about'] )   : '#';
$demo_url    = isset( $page_ids['3r-demo'] )    ? get_permalink( $page_ids['3r-demo'] )    : '#';
$contact_url = isset( $page_ids['3r-contact'] ) ? get_permalink( $page_ids['3r-contact'] ) : '#';
?>
</div><!-- /.tru-page-wrap -->

<footer class="tru-footer">
  <div class="tru-container">
    <div class="tru-footer-top">
      <div class="tru-footer-brand">
        <div class="tru-footer-logo">
          <div class="tru-nav-logo-icon" style="width:32px;height:32px">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#080c08" stroke-width="2.5">
              <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10z"/>
              <path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/>
            </svg>
          </div>
          <span class="tru-footer-logo-text">3R Urban Smart Solutions</span>
        </div>
        <p class="tru-footer-desc">Intelligent composting infrastructure for modern urban municipalities. Built on open standards, designed for reliability.</p>
      </div>
      <div class="tru-footer-col">
        <div class="tru-footer-col-title">Platform</div>
        <ul class="tru-footer-links">
          <li><a href="<?php echo esc_url( $home_url ); ?>">Home</a></li>
          <li><a href="<?php echo esc_url( $about_url ); ?>">About</a></li>
          <li><a href="<?php echo esc_url( $demo_url ); ?>">Request Demo</a></li>
          <li><a href="<?php echo esc_url( $contact_url ); ?>">Contact</a></li>
        </ul>
      </div>
      <div class="tru-footer-col">
        <div class="tru-footer-col-title">Technology</div>
        <ul class="tru-footer-links">
          <li><a href="#">MQTT Integration</a></li>
          <li><a href="#">Teltonika RUT200</a></li>
          <li><a href="#">Customer Portal</a></li>
          <li><a href="#">Alert Engine</a></li>
        </ul>
      </div>
      <div class="tru-footer-col">
        <div class="tru-footer-col-title">Contact</div>
        <ul class="tru-footer-links">
          <li><a href="mailto:info@3rurban.com">info@3rurban.com</a></li>
          <li><a href="<?php echo esc_url( $contact_url ); ?>">Support</a></li>
          <li><a href="#">Privacy Policy</a></li>
        </ul>
      </div>
    </div>
    <div class="tru-footer-bottom">
      <span>© <?php echo date('Y'); ?> 3R Urban Smart Solutions. All rights reserved.</span>
      <div class="tru-status-badge">
        <span class="tru-status-dot"></span>
        All systems operational
      </div>
    </div>
  </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
