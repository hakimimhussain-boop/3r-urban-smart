<?php
/**
 * Template Name: Portal — My Account
 */
defined( 'ABSPATH' ) || exit;
ThreeR_Portal_Auth::require_login();

$page_title = 'My Account';
$user       = wp_get_current_user();
$saved_msg  = '';

// Handle profile update
if ( isset( $_POST['tru_account_nonce'] ) && wp_verify_nonce( $_POST['tru_account_nonce'], 'tru_save_account' ) ) {
    $data = [
        'ID'           => $user->ID,
        'display_name' => sanitize_text_field( $_POST['display_name'] ?? '' ),
        'user_email'   => sanitize_email( $_POST['user_email'] ?? '' ),
    ];
    if ( ! empty( $_POST['new_password'] ) && strlen( $_POST['new_password'] ) >= 8 ) {
        $data['user_pass'] = $_POST['new_password'];
    }
    $result = wp_update_user( $data );
    update_user_meta( $user->ID, 'tru_organisation', sanitize_text_field( $_POST['organisation'] ?? '' ) );
    update_user_meta( $user->ID, 'tru_phone',        sanitize_text_field( $_POST['phone'] ?? '' ) );

    $saved_msg = is_wp_error($result) ? '❌ Error: ' . $result->get_error_message() : '✅ Profile updated successfully.';
    $user = wp_get_current_user(); // refresh
}

$org   = get_user_meta( $user->ID, 'tru_organisation', true );
$phone = get_user_meta( $user->ID, 'tru_phone', true );
$machines = ThreeR_Portal_Data::get_machines( $user->ID );
$counts   = ThreeR_Portal_Data::get_alert_counts( $user->ID );

$partials = plugin_dir_path( __FILE__ ) . '../partials/';
include $partials . 'portal-header.php';
?>

<div class="tru-account-grid">

  <!-- Profile form -->
  <div class="tru-account-main">
    <div class="tru-account-card">
      <h2 class="tru-account-section-title">Profile Information</h2>

      <?php if ($saved_msg) : ?>
        <div class="tru-account-msg <?php echo str_starts_with($saved_msg,'✅') ? 'tru-msg-ok' : 'tru-msg-err'; ?>">
          <?php echo esc_html($saved_msg); ?>
        </div>
      <?php endif; ?>

      <form method="POST" class="tru-account-form">
        <?php wp_nonce_field('tru_save_account','tru_account_nonce'); ?>

        <div class="tru-form-row">
          <div class="tru-form-group">
            <label class="tru-label">Display Name</label>
            <input type="text" name="display_name" class="tru-input" value="<?php echo esc_attr($user->display_name); ?>" required/>
          </div>
          <div class="tru-form-group">
            <label class="tru-label">Email Address</label>
            <input type="email" name="user_email" class="tru-input" value="<?php echo esc_attr($user->user_email); ?>" required/>
          </div>
        </div>
        <div class="tru-form-row">
          <div class="tru-form-group">
            <label class="tru-label">Organisation</label>
            <input type="text" name="organisation" class="tru-input" value="<?php echo esc_attr($org); ?>" placeholder="e.g. Pune Municipal Corporation"/>
          </div>
          <div class="tru-form-group">
            <label class="tru-label">Phone</label>
            <input type="tel" name="phone" class="tru-input" value="<?php echo esc_attr($phone); ?>" placeholder="+91 98000 12345"/>
          </div>
        </div>

        <h3 class="tru-account-sub-title">Change Password</h3>
        <div class="tru-form-row">
          <div class="tru-form-group">
            <label class="tru-label">New Password <span class="tru-label-hint">(min 8 chars)</span></label>
            <input type="password" name="new_password" class="tru-input" placeholder="Leave blank to keep current"/>
          </div>
          <div class="tru-form-group">
            <label class="tru-label">Confirm Password</label>
            <input type="password" name="confirm_password" class="tru-input" placeholder="Repeat new password" id="truConfirmPass"/>
          </div>
        </div>

        <button type="submit" class="tru-btn-primary">Save Changes</button>
      </form>
    </div>
  </div>

  <!-- Account sidebar -->
  <div class="tru-account-sidebar">

    <!-- Account overview card -->
    <div class="tru-account-card tru-account-overview">
      <div class="tru-acc-avatar"><?php echo esc_html(strtoupper(substr($user->display_name,0,1))); ?></div>
      <div class="tru-acc-name"><?php echo esc_html($user->display_name); ?></div>
      <div class="tru-acc-email"><?php echo esc_html($user->user_email); ?></div>
      <?php if ($org) : ?>
        <div class="tru-acc-org">🏢 <?php echo esc_html($org); ?></div>
      <?php endif; ?>
      <div class="tru-acc-since">Member since <?php echo esc_html(date('M Y', strtotime($user->user_registered))); ?></div>
    </div>

    <!-- Fleet summary -->
    <div class="tru-account-card">
      <h3 class="tru-account-section-title">Your Fleet</h3>
      <div class="tru-acc-stats">
        <div class="tru-acc-stat">
          <div class="tru-acc-stat-val"><?php echo count($machines); ?></div>
          <div class="tru-acc-stat-lbl">Machines</div>
        </div>
        <div class="tru-acc-stat">
          <div class="tru-acc-stat-val tru-val-green"><?php echo count(array_filter($machines,fn($m)=>$m->status==='online')); ?></div>
          <div class="tru-acc-stat-lbl">Online</div>
        </div>
        <div class="tru-acc-stat">
          <div class="tru-acc-stat-val tru-val-red"><?php echo $counts['active'] ?? 0; ?></div>
          <div class="tru-acc-stat-lbl">Active Alerts</div>
        </div>
      </div>
    </div>

    <!-- Login info -->
    <div class="tru-account-card">
      <h3 class="tru-account-section-title">Account Info</h3>
      <div class="tru-acc-info-rows">
        <div class="tru-acc-info-row">
          <span class="tru-acc-info-label">Username</span>
          <span class="tru-acc-info-val tru-td-mono"><?php echo esc_html($user->user_login); ?></span>
        </div>
        <div class="tru-acc-info-row">
          <span class="tru-acc-info-label">Role</span>
          <span class="tru-acc-info-val">Customer</span>
        </div>
        <div class="tru-acc-info-row">
          <span class="tru-acc-info-label">User ID</span>
          <span class="tru-acc-info-val tru-td-mono">#<?php echo $user->ID; ?></span>
        </div>
      </div>
      <a href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>" class="tru-btn-outline tru-btn-full" style="margin-top:16px;font-size:13px;">Log Out</a>
    </div>

  </div>
</div>

<script>
// Confirm password match
document.querySelector('form.tru-account-form')?.addEventListener('submit', function(e) {
  const np = document.querySelector('[name="new_password"]').value;
  const cp = document.getElementById('truConfirmPass').value;
  if (np && np !== cp) { e.preventDefault(); alert('Passwords do not match.'); }
});
</script>

<?php include $partials . 'portal-footer.php'; ?>
