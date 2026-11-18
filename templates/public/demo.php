<?php
/**
 * Template Name: Demo
 */
defined( 'ABSPATH' ) || exit;
$partials = plugin_dir_path( __FILE__ ) . '../partials/';
include $partials . 'header.php';
$id = get_the_ID();
$f  = 'ThreeR_Urban_Meta_Fields::get';
?>

<section class="tru-section tru-demo-page">
  <div class="tru-container">
    <div class="tru-section-label tru-anim-1"><?php echo esc_html( $f( $id, 'tru_demo_eyebrow' ) ); ?></div>
    <h1 class="tru-section-title tru-anim-2"><?php echo esc_html( $f( $id, 'tru_demo_title' ) ); ?></h1>

    <div class="tru-demo-grid">

      <!-- Form -->
      <div class="tru-form-wrap tru-anim-3">
        <h3 class="tru-form-heading"><?php echo esc_html( $f( $id, 'tru_demo_form_head' ) ); ?></h3>
        <p class="tru-form-sub"><?php echo esc_html( $f( $id, 'tru_demo_form_sub' ) ); ?></p>
        <form class="tru-form" id="truDemoForm">
          <div class="tru-form-row">
            <div class="tru-form-group">
              <label class="tru-label">First Name *</label>
              <input type="text" name="first_name" class="tru-input" required placeholder="Arjun"/>
            </div>
            <div class="tru-form-group">
              <label class="tru-label">Last Name *</label>
              <input type="text" name="last_name" class="tru-input" required placeholder="Mehta"/>
            </div>
          </div>
          <div class="tru-form-group">
            <label class="tru-label">Work Email *</label>
            <input type="email" name="email" class="tru-input" required placeholder="arjun@municipality.gov.in"/>
          </div>
          <div class="tru-form-group">
            <label class="tru-label">Organisation *</label>
            <input type="text" name="org" class="tru-input" required placeholder="Pune Municipal Corporation"/>
          </div>
          <div class="tru-form-row">
            <div class="tru-form-group">
              <label class="tru-label">Phone</label>
              <input type="tel" name="phone" class="tru-input" placeholder="+91 98000 12345"/>
            </div>
            <div class="tru-form-group">
              <label class="tru-label">No. of Machines</label>
              <select name="machines" class="tru-input">
                <option value="">Select range</option>
                <option>1–5 machines</option>
                <option>6–20 machines</option>
                <option>20+ machines</option>
              </select>
            </div>
          </div>
          <div class="tru-form-group">
            <label class="tru-label">Anything specific you'd like to see?</label>
            <textarea name="message" class="tru-textarea" rows="3" placeholder="e.g. alert configuration, PDF reports, multi-site view..."></textarea>
          </div>
          <button type="submit" class="tru-btn-primary tru-btn-full">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M5 3l14 9-14 9V3z"/></svg>
            Book My Demo
          </button>
          <div class="tru-form-msg" id="truDemoMsg"></div>
        </form>
      </div>

      <!-- Info sidebar -->
      <div class="tru-demo-info tru-anim-4">
        <h3 class="tru-demo-info-title"><?php echo esc_html( $f( $id, 'tru_demo_info_title' ) ); ?></h3>

        <div class="tru-demo-meta">
          <div class="tru-meta-row">
            <span class="tru-meta-icon">⏱</span>
            <div><div class="tru-meta-num"><?php echo esc_html( $f( $id, 'tru_demo_duration' ) ); ?> min</div><div class="tru-meta-desc">Live walkthrough</div></div>
          </div>
          <div class="tru-meta-row">
            <span class="tru-meta-icon">💚</span>
            <div><div class="tru-meta-num"><?php echo esc_html( $f( $id, 'tru_demo_cost' ) ); ?></div><div class="tru-meta-desc">No cost, no commitment</div></div>
          </div>
          <div class="tru-meta-row">
            <span class="tru-meta-icon">📬</span>
            <div><div class="tru-meta-num"><?php echo esc_html( $f( $id, 'tru_demo_response' ) ); ?></div><div class="tru-meta-desc">Response time</div></div>
          </div>
        </div>

        <div class="tru-demo-checklist">
          <div class="tru-trust-title"><?php echo esc_html( $f( $id, 'tru_demo_trust_title' ) ); ?></div>
          <?php
          $points = [
            'Live dashboard walkthrough with real sensor data',
            'Alert engine demo — see a threshold breach trigger',
            'Role-based access — customer vs admin view',
            'PDF report generation in real time',
            'Q&A with our technical team',
          ];
          foreach ( $points as $p ) echo '<div class="tru-check-item"><span class="tru-check">✓</span>' . esc_html($p) . '</div>';
          ?>
        </div>
      </div>

    </div>
  </div>
</section>

<?php include $partials . 'footer.php'; ?>
