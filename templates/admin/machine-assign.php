<?php
/**
 * Template Name: Admin — Assign Machine
 */
defined( 'ABSPATH' ) || exit;
ThreeR_Admin_Guard::require_admin();

$admin_page_title = 'Assign Machine';
$error_msg = '';
$success   = false;
$saved_uid = '';

if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tru_admin_nonce']) ) {
    ThreeR_Admin_Guard::verify_nonce( $_POST['tru_admin_nonce'], 'tru_admin_assign' );
    $uid         = sanitize_text_field( $_POST['machine_uid'] ?? '' );
    $customer_id = absint( $_POST['customer_id'] ?? 0 );
    $saved_uid   = $uid;

    if ( empty($uid) ) {
        $error_msg = 'Please select a machine.';
    } else {
        $result = ThreeR_Admin_Data::assign_machine( $uid, $customer_id );
        if ( is_wp_error($result) ) {
            $error_msg = $result->get_error_message();
        } else {
            $success = true;
        }
    }
}

// Pre-select from query string (coming from machines table)
$preselect_uid = sanitize_text_field( $_GET['uid'] ?? $saved_uid );

$machines  = ThreeR_Admin_Data::get_all_machines();
$customers = ThreeR_Admin_Data::get_all_customers();
$page_ids  = get_option( 'tru_page_ids', [] );
$machines_url = isset($page_ids['3r-admin-machines']) ? get_permalink($page_ids['3r-admin-machines']) : '#';

$partials = plugin_dir_path( __FILE__ ) . '../partials/';
include $partials . 'admin-header.php';
?>

<div class="tru-admin-form-wrap">

  <div class="tru-admin-form-header">
    <a href="<?php echo esc_url($machines_url); ?>" class="tru-admin-back">← Back to Machines</a>
    <p class="tru-admin-form-desc">Link a machine to a customer account. Customers will only see machines assigned to them.</p>
  </div>

  <?php if ($success) : ?>
    <div class="tru-admin-flash tru-flash-ok">
      ✅ Machine <strong><?php echo esc_html($saved_uid); ?></strong> assigned successfully.
    </div>
  <?php endif; ?>
  <?php if ($error_msg) : ?>
    <div class="tru-admin-flash tru-flash-err">⚠ <?php echo esc_html($error_msg); ?></div>
  <?php endif; ?>

  <form method="POST" class="tru-admin-form">
    <?php wp_nonce_field('tru_admin_assign','tru_admin_nonce'); ?>

    <div class="tru-admin-form-grid">
      <div class="tru-admin-field">
        <label class="tru-admin-label">Machine <span class="tru-required">*</span></label>
        <select name="machine_uid" class="tru-admin-select" required id="truMachineSelect">
          <option value="">Select machine…</option>
          <?php foreach ($machines as $m) :
            $sel = $preselect_uid === $m->machine_uid ? 'selected' : '';
          ?>
            <option value="<?php echo esc_attr($m->machine_uid); ?>" <?php echo $sel; ?>
              data-loc="<?php echo esc_attr($m->location); ?>"
              data-current="<?php echo esc_attr($m->customer_name ?? 'Unassigned'); ?>">
              <?php echo esc_html($m->machine_uid . ' — ' . $m->name); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="tru-admin-field">
        <label class="tru-admin-label">Assign to Customer</label>
        <select name="customer_id" class="tru-admin-select">
          <option value="">— Remove assignment —</option>
          <?php foreach ($customers as $c) :
            // Pre-select current owner if a machine is pre-selected
            $sel = '';
            if ($preselect_uid) {
              foreach ($machines as $m) {
                if ($m->machine_uid === $preselect_uid && $m->customer_id == $c->ID) { $sel = 'selected'; break; }
              }
            }
          ?>
            <option value="<?php echo $c->ID; ?>" <?php echo $sel; ?>>
              <?php echo esc_html(($c->display_name ?: $c->user_login) . ($c->organisation ? ' — '.$c->organisation : '')); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <!-- Machine info preview -->
    <div class="tru-admin-machine-preview" id="truMachinePreview" style="display:none">
      <span class="tru-mono" id="truPreviewUid"></span>
      <span class="tru-muted" id="truPreviewLoc"></span>
      <span>Currently: <strong id="truPreviewOwner"></strong></span>
    </div>

    <div class="tru-admin-form-footer">
      <button type="submit" class="tru-admin-btn-primary tru-btn-lg">Save Assignment</button>
      <a href="<?php echo esc_url($machines_url); ?>" class="tru-admin-btn-outline">Cancel</a>
    </div>
  </form>

  <!-- Current assignment map -->
  <div class="tru-admin-info-box" style="margin-top:32px">
    <div class="tru-admin-info-title">Current Assignments</div>
    <div class="tru-admin-assign-map">
      <?php foreach ($machines as $m) : ?>
      <div class="tru-assign-row">
        <span class="tru-mono tru-assign-uid"><?php echo esc_html($m->machine_uid); ?></span>
        <span class="tru-assign-arrow">→</span>
        <span class="tru-assign-customer <?php echo $m->customer_name ? '' : 'tru-muted'; ?>">
          <?php echo esc_html($m->customer_name ?? '— unassigned —'); ?>
        </span>
        <span class="tru-status-badge tru-st-<?php echo esc_attr($m->status); ?>" style="margin-left:auto">
          <?php echo ucfirst($m->status); ?>
        </span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

</div>

<script>
document.getElementById('truMachineSelect')?.addEventListener('change', function() {
  const opt = this.options[this.selectedIndex];
  const preview = document.getElementById('truMachinePreview');
  if (!opt.value) { preview.style.display='none'; return; }
  document.getElementById('truPreviewUid').textContent   = opt.value;
  document.getElementById('truPreviewLoc').textContent   = opt.dataset.loc || '';
  document.getElementById('truPreviewOwner').textContent = opt.dataset.current || 'Unassigned';
  preview.style.display = 'flex';
});
// Trigger on load if pre-selected
if (document.getElementById('truMachineSelect')?.value) {
  document.getElementById('truMachineSelect').dispatchEvent(new Event('change'));
}
</script>

<?php include $partials . 'admin-footer.php'; ?>
