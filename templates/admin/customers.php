<?php
/**
 * Template Name: Admin — Customers
 */
defined( 'ABSPATH' ) || exit;
ThreeR_Admin_Guard::require_admin();

$admin_page_title = 'All Customers';
$error_msg = '';
$success   = false;
$show_form = ! empty( $_GET['new'] );

if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tru_admin_nonce']) ) {
    ThreeR_Admin_Guard::verify_nonce( $_POST['tru_admin_nonce'], 'tru_admin_new_customer' );

    $data = [
        'username'     => sanitize_user( $_POST['username'] ?? '' ),
        'email'        => sanitize_email( $_POST['email'] ?? '' ),
        'first_name'   => sanitize_text_field( $_POST['first_name'] ?? '' ),
        'last_name'    => sanitize_text_field( $_POST['last_name'] ?? '' ),
        'password'     => $_POST['password'] ?? '',
        'organisation' => sanitize_text_field( $_POST['organisation'] ?? '' ),
        'phone'        => sanitize_text_field( $_POST['phone'] ?? '' ),
    ];

    if ( empty($data['username']) || empty($data['email']) || empty($data['password']) ) {
        $error_msg = 'Username, email, and password are required.';
        $show_form = true;
    } elseif ( strlen($data['password']) < 8 ) {
        $error_msg = 'Password must be at least 8 characters.';
        $show_form = true;
    } else {
        $result = ThreeR_Admin_Data::create_customer( $data );
        if ( is_wp_error($result) ) {
            $error_msg = $result->get_error_message();
            $show_form = true;
        } else {
            $success = true;
        }
    }
}

$customers = ThreeR_Admin_Data::get_all_customers();
$page_ids  = get_option( 'tru_page_ids', [] );
$assign_url = isset($page_ids['3r-admin-assign']) ? get_permalink($page_ids['3r-admin-assign']) : '#';

$partials = plugin_dir_path( __FILE__ ) . '../partials/';
include $partials . 'admin-header.php';
?>

<!-- Flash messages -->
<?php if ($success) : ?>
  <div class="tru-admin-flash tru-flash-ok">✅ Customer account created. They can now log in at <code><?php echo esc_url(get_permalink(get_page_by_path('3r-login'))); ?></code></div>
<?php endif; ?>
<?php if ($error_msg) : ?>
  <div class="tru-admin-flash tru-flash-err">⚠ <?php echo esc_html($error_msg); ?></div>
<?php endif; ?>

<!-- Controls bar -->
<div class="tru-admin-bar">
  <div class="tru-admin-bar-left">
    <input type="text" id="truCustomerSearch" class="tru-admin-search" placeholder="Search name, email, org…" oninput="truFilterCustomers()"/>
  </div>
  <div class="tru-admin-bar-right">
    <a href="?new=1" class="tru-admin-btn-primary">+ New Customer</a>
  </div>
</div>

<!-- New customer form (collapsible) -->
<?php if ($show_form) : ?>
<div class="tru-admin-form-card">
  <div class="tru-admin-form-card-title">Create Customer Account</div>
  <form method="POST" class="tru-admin-form">
    <?php wp_nonce_field('tru_admin_new_customer','tru_admin_nonce'); ?>
    <div class="tru-admin-form-grid">
      <div class="tru-admin-field">
        <label class="tru-admin-label">Username <span class="tru-required">*</span></label>
        <input type="text" name="username" class="tru-admin-input tru-mono-input"
          value="<?php echo esc_attr($_POST['username'] ?? ''); ?>" placeholder="customer_abc" required/>
      </div>
      <div class="tru-admin-field">
        <label class="tru-admin-label">Email <span class="tru-required">*</span></label>
        <input type="email" name="email" class="tru-admin-input"
          value="<?php echo esc_attr($_POST['email'] ?? ''); ?>" placeholder="name@org.com" required/>
      </div>
      <div class="tru-admin-field">
        <label class="tru-admin-label">First Name</label>
        <input type="text" name="first_name" class="tru-admin-input" value="<?php echo esc_attr($_POST['first_name'] ?? ''); ?>"/>
      </div>
      <div class="tru-admin-field">
        <label class="tru-admin-label">Last Name</label>
        <input type="text" name="last_name" class="tru-admin-input" value="<?php echo esc_attr($_POST['last_name'] ?? ''); ?>"/>
      </div>
      <div class="tru-admin-field">
        <label class="tru-admin-label">Organisation</label>
        <input type="text" name="organisation" class="tru-admin-input"
          value="<?php echo esc_attr($_POST['organisation'] ?? ''); ?>" placeholder="e.g. Pune Municipal Corporation"/>
      </div>
      <div class="tru-admin-field">
        <label class="tru-admin-label">Phone</label>
        <input type="tel" name="phone" class="tru-admin-input"
          value="<?php echo esc_attr($_POST['phone'] ?? ''); ?>" placeholder="+91 98000 12345"/>
      </div>
      <div class="tru-admin-field tru-field-full">
        <label class="tru-admin-label">Password <span class="tru-required">*</span></label>
        <input type="password" name="password" class="tru-admin-input" placeholder="Min 8 characters" minlength="8" required/>
        <span class="tru-admin-hint">Share this with the customer. They can change it from My Account.</span>
      </div>
    </div>
    <div class="tru-admin-form-footer">
      <button type="submit" class="tru-admin-btn-primary">Create Account</button>
      <a href="<?php echo esc_url(get_permalink()); ?>" class="tru-admin-btn-outline">Cancel</a>
    </div>
  </form>
</div>
<?php endif; ?>

<!-- Customers table -->
<div class="tru-admin-table-wrap">
  <table class="tru-admin-table" id="truCustomerTable">
    <thead>
      <tr>
        <th>Username</th>
        <th>Name</th>
        <th>Email</th>
        <th>Organisation</th>
        <th>Machines</th>
        <th>Joined</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($customers as $c) :
        $online = $c->machines_online ?? 0;
        $total  = $c->machine_count  ?? 0;
      ?>
      <tr class="tru-admin-tr" data-search="<?php echo esc_attr(strtolower($c->user_login.' '.($c->display_name??'').' '.$c->user_email.' '.($c->organisation??''))); ?>">
        <td><span class="tru-mono"><?php echo esc_html($c->user_login); ?></span></td>
        <td><?php echo esc_html(trim($c->first_name.' '.$c->last_name) ?: '—'); ?></td>
        <td class="tru-muted"><?php echo esc_html($c->user_email); ?></td>
        <td><?php echo esc_html($c->organisation ?: '—'); ?></td>
        <td>
          <span class="tru-machine-count">
            <span class="tru-green"><?php echo $online; ?></span>/<span><?php echo $total; ?></span>
            <span class="tru-muted" style="font-size:11px">online</span>
          </span>
        </td>
        <td class="tru-muted tru-mono"><?php echo esc_html(date('d M Y', strtotime($c->user_registered))); ?></td>
        <td class="tru-admin-row-actions">
          <a href="<?php echo esc_url(add_query_arg('customer',$c->ID,$assign_url)); ?>" class="tru-admin-btn-xs">Assign Machines</a>
          <a href="<?php echo esc_url(get_edit_user_link($c->ID)); ?>" class="tru-admin-btn-xs" target="_blank">WP Edit</a>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($customers)) : ?>
      <tr><td colspan="7" class="tru-admin-empty">No customers yet. <a href="?new=1">Create the first one →</a></td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<script>
function truFilterCustomers() {
  const q = document.getElementById('truCustomerSearch').value.toLowerCase();
  document.querySelectorAll('#truCustomerTable tbody tr[data-search]').forEach(row => {
    row.style.display = row.dataset.search.includes(q) ? '' : 'none';
  });
}
</script>

<?php include $partials . 'admin-footer.php'; ?>
