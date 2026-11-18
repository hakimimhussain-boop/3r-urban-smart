<?php
/**
 * Template Name: Admin — Add Machine
 */
defined( 'ABSPATH' ) || exit;
ThreeR_Admin_Guard::require_admin();

$admin_page_title = 'Add Machine';
$error_msg = '';
$success   = false;

if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tru_admin_nonce']) ) {
    ThreeR_Admin_Guard::verify_nonce( $_POST['tru_admin_nonce'], 'tru_admin_add_machine' );

    $data = [
        'machine_uid' => strtoupper( sanitize_text_field( $_POST['machine_uid'] ?? '' ) ),
        'name'        => sanitize_text_field( $_POST['machine_name'] ?? '' ),
        'location'    => sanitize_text_field( $_POST['location'] ?? '' ),
        'customer_id' => absint( $_POST['customer_id'] ?? 0 ),
    ];

    if ( empty($data['machine_uid']) || empty($data['name']) || empty($data['location']) ) {
        $error_msg = 'UID, name, and location are required.';
    } else {
        $result = ThreeR_Admin_Data::add_machine( $data );
        if ( is_wp_error($result) ) {
            $error_msg = $result->get_error_message();
        } else {
            $success = true;
        }
    }
}

$customers = ThreeR_Admin_Data::get_all_customers();
$page_ids  = get_option( 'tru_page_ids', [] );
$machines_url = isset($page_ids['3r-admin-machines']) ? get_permalink($page_ids['3r-admin-machines']) : '#';

$partials = plugin_dir_path( __FILE__ ) . '../partials/';
include $partials . 'admin-header.php';
?>

<div class="tru-admin-form-wrap">

  <div class="tru-admin-form-header">
    <a href="<?php echo esc_url($machines_url); ?>" class="tru-admin-back">← Back to Machines</a>
    <p class="tru-admin-form-desc">Register a new machine in the fleet. The UID must match the machine_id broadcast over MQTT.</p>
  </div>

  <?php if ($success) : ?>
    <div class="tru-admin-flash tru-flash-ok">
      ✅ Machine <strong><?php echo esc_html(strtoupper($_POST['machine_uid'])); ?></strong> added successfully.
      <a href="<?php echo esc_url($machines_url); ?>">View all machines →</a>
    </div>
  <?php endif; ?>

  <?php if ($error_msg) : ?>
    <div class="tru-admin-flash tru-flash-err">⚠ <?php echo esc_html($error_msg); ?></div>
  <?php endif; ?>

  <form method="POST" class="tru-admin-form">
    <?php wp_nonce_field('tru_admin_add_machine','tru_admin_nonce'); ?>

    <div class="tru-admin-form-grid">

      <!-- Machine UID -->
      <div class="tru-admin-field tru-field-full">
        <label class="tru-admin-label">Machine UID <span class="tru-required">*</span></label>
        <input type="text" name="machine_uid" class="tru-admin-input tru-mono-input"
          placeholder="3R-MCH-004"
          value="<?php echo esc_attr($_POST['machine_uid'] ?? ''); ?>"
          pattern="3R-MCH-[0-9]{3,}" title="Format: 3R-MCH-XXX"
          required autocomplete="off"/>
        <span class="tru-admin-hint">Must match the machine_id in MQTT payload. Format: 3R-MCH-XXX</span>
      </div>

      <!-- Machine Name -->
      <div class="tru-admin-field">
        <label class="tru-admin-label">Machine Model / Name <span class="tru-required">*</span></label>
        <select name="machine_name" class="tru-admin-select" required>
          <option value="">Select model…</option>
          <?php
          $names = ['CompostMaster Pro','CompostMaster Lite','CompostMaster Industrial','CompostMaster Mini'];
          foreach ($names as $n) :
            $sel = ($_POST['machine_name'] ?? '') === $n ? 'selected' : '';
          ?>
            <option value="<?php echo esc_attr($n); ?>" <?php echo $sel; ?>><?php echo esc_html($n); ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Assign to Customer -->
      <div class="tru-admin-field">
        <label class="tru-admin-label">Assign to Customer</label>
        <select name="customer_id" class="tru-admin-select">
          <option value="">— Unassigned —</option>
          <?php foreach ($customers as $c) :
            $sel = (int)($_POST['customer_id'] ?? 0) === $c->ID ? 'selected' : '';
          ?>
            <option value="<?php echo $c->ID; ?>" <?php echo $sel; ?>>
              <?php echo esc_html(($c->display_name ?: $c->user_login) . ($c->organisation ? ' — '.$c->organisation : '')); ?>
            </option>
          <?php endforeach; ?>
        </select>
        <span class="tru-admin-hint">Can be assigned later from the Assign page.</span>
      </div>

      <!-- Location -->
      <div class="tru-admin-field tru-field-full">
        <label class="tru-admin-label">Installation Location <span class="tru-required">*</span></label>
        <input type="text" name="location" class="tru-admin-input"
          placeholder="e.g. Ward 14, North District, Pune"
          value="<?php echo esc_attr($_POST['location'] ?? ''); ?>"
          required/>
      </div>

    </div><!-- /.tru-admin-form-grid -->

    <!-- UID Preview -->
    <div class="tru-admin-uid-preview" id="truUidPreview" style="display:none">
      <span class="tru-admin-uid-preview-lbl">// MQTT machine_id will be</span>
      <span class="tru-admin-uid-preview-val" id="truUidVal"></span>
    </div>

    <div class="tru-admin-form-footer">
      <button type="submit" class="tru-admin-btn-primary tru-btn-lg">Register Machine</button>
      <a href="<?php echo esc_url($machines_url); ?>" class="tru-admin-btn-outline">Cancel</a>
    </div>

  </form>

  <!-- Info box -->
  <div class="tru-admin-info-box">
    <div class="tru-admin-info-title">📡 MQTT Integration Note</div>
    <p>Once registered, this machine UID will be accepted by the Node.js middleware when it arrives from the Mosquitto broker. Set the same UID in your Teltonika RUT200 MQTT topic.</p>
    <div class="tru-admin-info-code">topic: <span>3r/machines/3R-MCH-XXX/telemetry</span></div>
  </div>

</div>

<script>
document.querySelector('[name="machine_uid"]')?.addEventListener('input', function() {
  const val = this.value.toUpperCase();
  this.value = val;
  const preview = document.getElementById('truUidPreview');
  const valEl   = document.getElementById('truUidVal');
  if (val.length > 2) {
    preview.style.display = 'flex';
    valEl.textContent = val;
  } else {
    preview.style.display = 'none';
  }
});
</script>

<?php include $partials . 'admin-footer.php'; ?>
