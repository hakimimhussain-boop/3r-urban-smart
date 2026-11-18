<?php
/**
 * Template Name: Contact
 */
defined( 'ABSPATH' ) || exit;
$partials = plugin_dir_path( __FILE__ ) . '../partials/';
include $partials . 'header.php';
$id = get_the_ID();
$f  = 'ThreeR_Urban_Meta_Fields::get';
?>

<section class="tru-section tru-contact-page">
  <div class="tru-container">
    <div class="tru-section-label tru-anim-1"><?php echo esc_html( $f( $id, 'tru_con_eyebrow' ) ); ?></div>
    <h1 class="tru-section-title tru-anim-2"><?php echo esc_html( $f( $id, 'tru_con_title' ) ); ?></h1>
    <p class="tru-section-sub tru-anim-2"><?php echo esc_html( $f( $id, 'tru_con_sub' ) ); ?></p>

    <div class="tru-contact-grid">

      <!-- Contact info -->
      <div class="tru-contact-info tru-anim-3">
        <div class="tru-contact-channels">
          <div class="tru-channel">
            <span class="tru-channel-icon">✉️</span>
            <div>
              <div class="tru-channel-label">Email</div>
              <a href="mailto:<?php echo esc_attr( $f( $id, 'tru_con_email' ) ); ?>" class="tru-channel-val">
                <?php echo esc_html( $f( $id, 'tru_con_email' ) ); ?>
              </a>
            </div>
          </div>
          <div class="tru-channel">
            <span class="tru-channel-icon">📞</span>
            <div>
              <div class="tru-channel-label">Phone</div>
              <a href="tel:<?php echo esc_attr( preg_replace('/\s+/', '', $f( $id, 'tru_con_phone' ) ) ); ?>" class="tru-channel-val">
                <?php echo esc_html( $f( $id, 'tru_con_phone' ) ); ?>
              </a>
            </div>
          </div>
          <div class="tru-channel">
            <span class="tru-channel-icon">🕐</span>
            <div>
              <div class="tru-channel-label">Support Hours</div>
              <div class="tru-channel-val"><?php echo esc_html( $f( $id, 'tru_con_hours' ) ); ?></div>
            </div>
          </div>
          <div class="tru-channel">
            <span class="tru-channel-icon">📍</span>
            <div>
              <div class="tru-channel-label">Office</div>
              <div class="tru-channel-val" style="white-space:pre-line"><?php echo esc_html( $f( $id, 'tru_con_address' ) ); ?></div>
            </div>
          </div>
        </div>

        <div class="tru-map-box">
          <div class="tru-map-pin"><?php echo esc_html( $f( $id, 'tru_con_map_label' ) ); ?></div>
        </div>
      </div>

      <!-- Contact form -->
      <div class="tru-form-wrap tru-anim-4">
        <h3 class="tru-form-heading"><?php echo esc_html( $f( $id, 'tru_con_form_head' ) ); ?></h3>
        <p class="tru-form-sub"><?php echo esc_html( $f( $id, 'tru_con_form_sub' ) ); ?></p>
        <form class="tru-form" id="truContactForm">
          <div class="tru-form-row">
            <div class="tru-form-group">
              <label class="tru-label">Name *</label>
              <input type="text" name="name" class="tru-input" required placeholder="Your full name"/>
            </div>
            <div class="tru-form-group">
              <label class="tru-label">Email *</label>
              <input type="email" name="email" class="tru-input" required placeholder="you@example.com"/>
            </div>
          </div>
          <div class="tru-form-group">
            <label class="tru-label">Subject *</label>
            <select name="subject" class="tru-input" required>
              <option value="">Select a topic</option>
              <option>Request a Demo</option>
              <option>Technical Support</option>
              <option>Partnership / Reseller</option>
              <option>Billing & Account</option>
              <option>Other</option>
            </select>
          </div>
          <div class="tru-form-group">
            <label class="tru-label">Message *</label>
            <textarea name="message" class="tru-textarea" rows="5" required placeholder="Tell us what you need..."></textarea>
          </div>
          <button type="submit" class="tru-btn-primary tru-btn-full">Send Message →</button>
          <div class="tru-form-msg" id="truContactMsg"></div>
        </form>
      </div>

    </div>
  </div>
</section>

<?php include $partials . 'footer.php'; ?>
