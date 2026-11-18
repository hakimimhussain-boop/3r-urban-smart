<?php
/**
 * Template Name: Portal — Dashboard
 */
defined( 'ABSPATH' ) || exit;
ThreeR_Portal_Auth::require_login();

$page_title  = 'Dashboard';
$customer_id = ThreeR_Portal_Auth::current_customer_id();
$machines    = ThreeR_Portal_Data::get_machines( $customer_id );
$counts      = ThreeR_Portal_Data::get_alert_counts( $customer_id );
$page_ids    = get_option( 'tru_page_ids', [] );
$alerts_url  = isset( $page_ids['3r-portal-alerts'] )  ? get_permalink( $page_ids['3r-portal-alerts'] )  : '#';
$reports_url = isset( $page_ids['3r-portal-reports'] ) ? get_permalink( $page_ids['3r-portal-reports'] ) : '#';

$partials = plugin_dir_path( __FILE__ ) . '../partials/';
include $partials . 'portal-header.php';

$total   = count( $machines );
$online  = count( array_filter( $machines, fn($m) => $m->status === 'online'  ) );
$warning = count( array_filter( $machines, fn($m) => $m->status === 'warning' ) );
$offline = count( array_filter( $machines, fn($m) => $m->status === 'offline' ) );
?>

<!-- ── Summary cards ── -->
<div class="tru-dash-summary">
  <div class="tru-summary-card">
    <div class="tru-summary-icon tru-icon-green">⬡</div>
    <div class="tru-summary-val"><?php echo $total; ?></div>
    <div class="tru-summary-lbl">Total Machines</div>
  </div>
  <div class="tru-summary-card">
    <div class="tru-summary-icon tru-icon-green">●</div>
    <div class="tru-summary-val tru-val-green"><?php echo $online; ?></div>
    <div class="tru-summary-lbl">Online</div>
  </div>
  <div class="tru-summary-card">
    <div class="tru-summary-icon tru-icon-amber">⚠</div>
    <div class="tru-summary-val tru-val-amber"><?php echo $warning; ?></div>
    <div class="tru-summary-lbl">Warning</div>
  </div>
  <div class="tru-summary-card">
    <div class="tru-summary-icon tru-icon-red">🔔</div>
    <div class="tru-summary-val tru-val-red"><?php echo $counts['active'] ?? 0; ?></div>
    <div class="tru-summary-lbl">Active Alerts</div>
  </div>
</div>

<!-- ── Machine fleet grid ── -->
<div class="tru-portal-section-header">
  <h2 class="tru-portal-section-title">Your Fleet</h2>
  <div class="tru-portal-section-meta">Updated every 15 min &nbsp;·&nbsp; <span class="tru-live-dot"></span> Live</div>
</div>

<div class="tru-fleet-grid">
  <?php foreach ( $machines as $machine ) :
    $tel      = ThreeR_Portal_Data::get_latest_telemetry( $machine->machine_uid );
    $st       = $machine->status;
    $st_class = 'tru-st-' . $st;
    $st_label = ucfirst( $st );
    $page_ids2 = get_option( 'tru_page_ids', [] );
    $mach_url  = isset( $page_ids2['3r-portal-machine'] )
                 ? add_query_arg( 'uid', $machine->machine_uid, get_permalink( $page_ids2['3r-portal-machine'] ) )
                 : '#';
    $temp_warn = $tel->temperature > 70;
    $age_mins  = isset( $tel->recorded_at ) ? round( ( time() - strtotime( $tel->recorded_at ) ) / 60 ) : '?';
  ?>
  <div class="tru-fleet-card <?php echo $st_class; ?>">
    <div class="tru-fleet-card-top">
      <div>
        <div class="tru-fleet-uid"><?php echo esc_html( $machine->machine_uid ); ?></div>
        <div class="tru-fleet-name"><?php echo esc_html( $machine->name ); ?></div>
        <div class="tru-fleet-loc">📍 <?php echo esc_html( $machine->location ); ?></div>
      </div>
      <span class="tru-fleet-badge tru-badge-<?php echo $st; ?>">
        <?php echo $st === 'online' ? '●' : ($st === 'warning' ? '⚠' : '○'); ?>
        <?php echo esc_html( $st_label ); ?>
      </span>
    </div>

    <div class="tru-fleet-sensors">
      <div class="tru-fleet-sensor <?php echo $temp_warn ? 'tru-sensor-warn' : ''; ?>">
        <span class="tru-fs-icon">🌡️</span>
        <span class="tru-fs-val"><?php echo esc_html( $tel->temperature ); ?>°C</span>
        <span class="tru-fs-lbl">Temp</span>
      </div>
      <div class="tru-fleet-sensor">
        <span class="tru-fs-icon">⚖️</span>
        <span class="tru-fs-val"><?php echo esc_html( $tel->weight ); ?> kg</span>
        <span class="tru-fs-lbl">Load</span>
      </div>
      <div class="tru-fleet-sensor">
        <span class="tru-fs-icon">⚙️</span>
        <span class="tru-fs-val"><?php echo $tel->motor_status ? esc_html($tel->motor_rpm).' rpm' : 'OFF'; ?></span>
        <span class="tru-fs-lbl">Motor</span>
      </div>
      <div class="tru-fleet-sensor">
        <span class="tru-fs-icon">📶</span>
        <span class="tru-fs-val"><?php echo esc_html( $tel->signal_strength ); ?> dBm</span>
        <span class="tru-fs-lbl">Signal</span>
      </div>
    </div>

    <!-- Mini sparkline (rendered by JS) -->
    <div class="tru-fleet-spark" data-uid="<?php echo esc_attr( $machine->machine_uid ); ?>"></div>

    <div class="tru-fleet-card-footer">
      <span class="tru-fleet-age">Updated <?php echo esc_html( $age_mins ); ?> min ago</span>
      <a href="<?php echo esc_url( $mach_url ); ?>" class="tru-fleet-detail-btn">View Details →</a>
      <?php $pp = get_option('tru_page_ids', []); if ( isset($pp['3r-portal-panel']) ) : ?>
        <a href="<?php echo esc_url( add_query_arg( 'uid', $machine->machine_uid, get_permalink($pp['3r-portal-panel']) ) ); ?>" class="tru-fleet-detail-btn">Live Panel →</a>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- ── Recent alerts preview ── -->
<?php $alerts = ThreeR_Portal_Data::get_alerts( $customer_id, 'active', 4 ); ?>
<?php if ( $alerts ) : ?>
<div class="tru-portal-section-header" style="margin-top:40px">
  <h2 class="tru-portal-section-title">Active Alerts</h2>
  <a href="<?php echo esc_url( $alerts_url ); ?>" class="tru-portal-section-link">View all →</a>
</div>
<div class="tru-alert-list">
  <?php foreach ( $alerts as $alert ) :
    $sev = $alert->severity;
  ?>
  <div class="tru-alert-row tru-alert-<?php echo esc_attr( $sev ); ?>" data-alert-id="<?php echo (int)$alert->id; ?>">
    <span class="tru-alert-sev-dot"></span>
    <div class="tru-alert-info">
      <div class="tru-alert-msg"><?php echo esc_html( $alert->message ); ?></div>
      <div class="tru-alert-meta">
        <span><?php echo esc_html( $alert->machine_id ); ?></span>
        <span>·</span>
        <span><?php echo esc_html( $alert->machine_location ); ?></span>
        <span>·</span>
        <span><?php echo esc_html( human_time_diff( strtotime( $alert->created_at ), time() ) ); ?> ago</span>
      </div>
    </div>
    <span class="tru-alert-sev-badge tru-sev-<?php echo esc_attr( $sev ); ?>"><?php echo ucfirst( $sev ); ?></span>
    <button class="tru-ack-btn" data-id="<?php echo (int)$alert->id; ?>">Acknowledge</button>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php include $partials . 'portal-footer.php'; ?>
