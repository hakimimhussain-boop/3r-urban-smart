<?php
/**
 * Template Name: Admin — Machines
 */
defined( 'ABSPATH' ) || exit;
ThreeR_Admin_Guard::require_admin();

// Handle status toggle (AJAX handled in JS, but also support direct POST)
if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tru_admin_nonce']) ) {
    ThreeR_Admin_Guard::verify_nonce( $_POST['tru_admin_nonce'], 'tru_admin_action' );
    if ( isset($_POST['toggle_status']) && isset($_POST['machine_uid']) ) {
        $new = $_POST['toggle_status'] === 'online' ? 'offline' : 'online';
        ThreeR_Admin_Data::update_machine_status( sanitize_text_field($_POST['machine_uid']), $new );
        wp_redirect( get_permalink() ); exit;
    }
    if ( isset($_POST['delete_uid']) ) {
        ThreeR_Admin_Data::delete_machine( sanitize_text_field($_POST['delete_uid']) );
        wp_redirect( get_permalink() . '?deleted=1' ); exit;
    }
}

$admin_page_title = 'All Machines';
$filter_customer  = absint( $_GET['customer'] ?? 0 );
$machines         = ThreeR_Admin_Data::get_all_machines( $filter_customer ?: null );
$customers        = ThreeR_Admin_Data::get_all_customers();
$page_ids         = get_option( 'tru_page_ids', [] );
$add_url          = isset($page_ids['3r-admin-add'])    ? get_permalink($page_ids['3r-admin-add'])    : '#';
$assign_url       = isset($page_ids['3r-admin-assign']) ? get_permalink($page_ids['3r-admin-assign']) : '#';

$partials = plugin_dir_path( __FILE__ ) . '../partials/';
include $partials . 'admin-header.php';

$online  = count(array_filter($machines, fn($m)=>$m->status==='online'));
$warning = count(array_filter($machines, fn($m)=>$m->status==='warning'));
$offline = count(array_filter($machines, fn($m)=>$m->status==='offline'));
?>

<!-- Summary bar -->
<div class="tru-admin-summary">
  <div class="tru-admin-stat"><span class="tru-admin-stat-val"><?php echo count($machines); ?></span><span class="tru-admin-stat-lbl">Total</span></div>
  <div class="tru-admin-stat"><span class="tru-admin-stat-val tru-green"><?php echo $online; ?></span><span class="tru-admin-stat-lbl">Online</span></div>
  <div class="tru-admin-stat"><span class="tru-admin-stat-val tru-amber"><?php echo $warning; ?></span><span class="tru-admin-stat-lbl">Warning</span></div>
  <div class="tru-admin-stat"><span class="tru-admin-stat-val tru-red"><?php echo $offline; ?></span><span class="tru-admin-stat-lbl">Offline</span></div>
</div>

<!-- Controls bar -->
<div class="tru-admin-bar">
  <div class="tru-admin-bar-left">
    <!-- Filter by customer -->
    <form method="GET" class="tru-admin-filter-form">
      <select name="customer" class="tru-admin-select" onchange="this.form.submit()">
        <option value="">All Customers</option>
        <?php foreach ($customers as $c) : ?>
          <option value="<?php echo $c->ID; ?>" <?php selected($filter_customer, $c->ID); ?>>
            <?php echo esc_html($c->display_name ?: $c->user_login); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </form>
    <input type="text" id="truMachineSearch" class="tru-admin-search" placeholder="Search UID, name, location…" oninput="truFilterTable()"/>
  </div>
  <div class="tru-admin-bar-right">
    <a href="<?php echo esc_url($assign_url); ?>" class="tru-admin-btn-outline">Assign →</a>
    <a href="<?php echo esc_url($add_url); ?>" class="tru-admin-btn-primary">+ Add Machine</a>
  </div>
</div>

<?php if ( !empty($_GET['deleted']) ) : ?>
  <div class="tru-admin-flash tru-flash-ok">✅ Machine deleted.</div>
<?php endif; ?>

<!-- Machines table -->
<div class="tru-admin-table-wrap">
  <table class="tru-admin-table" id="truMachineTable">
    <thead>
      <tr>
        <th>UID</th>
        <th>Name</th>
        <th>Location</th>
        <th>Customer</th>
        <th>Status</th>
        <th>Installed</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($machines as $m) :
        $st = $m->status;
        $tel = ThreeR_Portal_Data::get_latest_telemetry($m->machine_uid);
        $age = $tel ? round((time()-strtotime($tel->recorded_at))/60) : '—';
      ?>
      <tr class="tru-admin-tr" data-search="<?php echo esc_attr(strtolower($m->machine_uid.' '.$m->name.' '.$m->location)); ?>">
        <td><span class="tru-mono"><?php echo esc_html($m->machine_uid); ?></span></td>
        <td><?php echo esc_html($m->name); ?></td>
        <td class="tru-muted"><?php echo esc_html($m->location); ?></td>
        <td><?php echo esc_html($m->customer_name ?? '— unassigned —'); ?></td>
        <td>
          <span class="tru-status-badge tru-st-<?php echo esc_attr($st); ?>">
            <?php echo $st === 'online' ? '● Online' : ($st === 'warning' ? '⚠ Warning' : '○ Offline'); ?>
          </span>
        </td>
        <td class="tru-muted tru-mono"><?php echo esc_html($m->installed_at ?? '—'); ?></td>
        <td class="tru-admin-row-actions">
          <button class="tru-admin-btn-xs"
            onclick="truToggleStatus('<?php echo esc_js($m->machine_uid); ?>','<?php echo esc_js($st); ?>')">
            <?php echo $st === 'online' ? 'Set Offline' : 'Set Online'; ?>
          </button>
          <a href="<?php echo esc_url(add_query_arg('uid',$m->machine_uid,isset($page_ids['3r-admin-assign'])?get_permalink($page_ids['3r-admin-assign']):'#')); ?>"
             class="tru-admin-btn-xs">Assign</a>
          <button class="tru-admin-btn-xs tru-btn-danger"
            onclick="truConfirmDelete('<?php echo esc_js($m->machine_uid); ?>')">Delete</button>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($machines)) : ?>
      <tr><td colspan="7" class="tru-admin-empty">No machines found. <a href="<?php echo esc_url($add_url); ?>">Add the first one →</a></td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Hidden delete form -->
<form method="POST" id="truDeleteForm" style="display:none">
  <?php wp_nonce_field('tru_admin_action','tru_admin_nonce'); ?>
  <input type="hidden" name="delete_uid" id="truDeleteUid"/>
</form>

<script>
function truFilterTable() {
  const q = document.getElementById('truMachineSearch').value.toLowerCase();
  document.querySelectorAll('#truMachineTable tbody tr[data-search]').forEach(row => {
    row.style.display = row.dataset.search.includes(q) ? '' : 'none';
  });
}
function truConfirmDelete(uid) {
  if (!confirm('Delete machine ' + uid + '? This cannot be undone.')) return;
  document.getElementById('truDeleteUid').value = uid;
  document.getElementById('truDeleteForm').submit();
}
function truToggleStatus(uid, currentStatus) {
  fetch(truAdmin.restBase + '3r/v1/admin/machines/' + uid + '/status', {
    method: 'POST',
    headers: { 'X-WP-Nonce': truAdmin.nonce, 'Content-Type': 'application/json' },
    body: JSON.stringify({ status: currentStatus === 'online' ? 'offline' : 'online' })
  }).then(() => location.reload());
}
</script>

<?php include $partials . 'admin-footer.php'; ?>
