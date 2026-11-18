<?php
/**
 * Template Name: Portal — Alerts
 */
defined( 'ABSPATH' ) || exit;
ThreeR_Portal_Auth::require_login();

$page_title  = 'Alerts';
$customer_id = ThreeR_Portal_Auth::current_customer_id();
$filter      = sanitize_text_field( $_GET['filter'] ?? 'all' );
$alerts      = ThreeR_Portal_Data::get_alerts( $customer_id, $filter, 100 );
$counts      = ThreeR_Portal_Data::get_alert_counts( $customer_id );
$page_ids    = get_option( 'tru_page_ids', [] );

$partials = plugin_dir_path( __FILE__ ) . '../partials/';
include $partials . 'portal-header.php';

$filters = [
  'all'          => 'All',
  'active'       => 'Active',
  'acknowledged' => 'Acknowledged',
];

$sev_icons = [
  'critical' => '🔴',
  'warning'  => '🟡',
  'info'     => '🔵',
];
?>

<!-- Filter tabs -->
<div class="tru-alert-filters">
  <?php foreach ( $filters as $key => $label ) :
    $current_url = add_query_arg( 'filter', $key, get_permalink() );
    $active = ($filter === $key) ? 'active' : '';
  ?>
    <a href="<?php echo esc_url($current_url); ?>" class="tru-filter-tab <?php echo $active; ?>">
      <?php echo esc_html($label); ?>
      <?php if ($key === 'active' && !empty($counts['active'])) : ?>
        <span class="tru-filter-count"><?php echo (int)$counts['active']; ?></span>
      <?php endif; ?>
    </a>
  <?php endforeach; ?>
  <div class="tru-filter-actions">
    <button class="tru-btn-sm" id="truAckAll">Acknowledge All Active</button>
  </div>
</div>

<!-- Alerts table -->
<?php if ( $alerts ) : ?>
<div class="tru-alerts-table-wrap">
  <table class="tru-alerts-table">
    <thead>
      <tr>
        <th>Severity</th>
        <th>Machine</th>
        <th>Location</th>
        <th>Alert</th>
        <th>Value</th>
        <th>Time</th>
        <th>Status</th>
        <th></th>
      </tr>
    </thead>
    <tbody id="truAlertsBody">
      <?php foreach ( $alerts as $alert ) :
        $sev   = $alert->severity;
        $icon  = $sev_icons[$sev] ?? '⚪';
        $is_active = $alert->status === 'active';
      ?>
      <tr class="tru-alert-tr tru-alert-tr-<?php echo esc_attr($sev); ?>" data-alert-id="<?php echo (int)$alert->id; ?>">
        <td><span class="tru-sev-badge tru-sev-<?php echo esc_attr($sev); ?>"><?php echo $icon; ?> <?php echo ucfirst($sev); ?></span></td>
        <td class="tru-td-mono"><?php echo esc_html($alert->machine_id); ?></td>
        <td class="tru-td-location"><?php echo esc_html($alert->machine_location ?? '—'); ?></td>
        <td><?php echo esc_html($alert->message); ?></td>
        <td class="tru-td-mono"><?php echo esc_html($alert->actual_value ?? '—'); ?> <span class="tru-td-muted">/ <?php echo esc_html($alert->threshold_value ?? '—'); ?></span></td>
        <td class="tru-td-time" title="<?php echo esc_attr($alert->created_at); ?>"><?php echo esc_html(human_time_diff(strtotime($alert->created_at),time())); ?> ago</td>
        <td>
          <span class="tru-status-pill tru-status-<?php echo esc_attr($alert->status); ?>">
            <?php echo ucfirst($alert->status); ?>
          </span>
        </td>
        <td>
          <?php if ($is_active) : ?>
            <button class="tru-ack-btn" data-id="<?php echo (int)$alert->id; ?>">Ack</button>
          <?php else : ?>
            <span class="tru-td-muted">—</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php else : ?>
<div class="tru-empty-state">
  <div class="tru-empty-icon">✅</div>
  <div class="tru-empty-title">No alerts<?php echo $filter !== 'all' ? ' in this filter' : ''; ?></div>
  <div class="tru-empty-sub">Your fleet is running clean.</div>
</div>
<?php endif; ?>

<?php include $partials . 'portal-footer.php'; ?>
