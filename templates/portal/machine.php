<?php
/**
 * Template Name: Portal — Machine Detail
 */
defined( 'ABSPATH' ) || exit;
ThreeR_Portal_Auth::require_login();

$uid     = sanitize_text_field( $_GET['uid'] ?? '' );
$machine = $uid ? ThreeR_Portal_Data::get_machine( $uid ) : null;

if ( ! $machine ) {
    wp_redirect( get_permalink( get_option('tru_page_ids')['3r-portal-dashboard'] ?? 0 ) );
    exit;
}

$tel         = ThreeR_Portal_Data::get_latest_telemetry( $uid );
$history     = ThreeR_Portal_Data::get_telemetry_range( $uid, 24 );
$page_title  = esc_html( $machine->machine_uid ) . ' — ' . esc_html( $machine->name );
$partials    = plugin_dir_path( __FILE__ ) . '../partials/';
include $partials . 'portal-header.php';

// Build chart data arrays for JS
$chart_labels = array_map( fn($r) => date('H:i', strtotime($r->recorded_at)), $history );
$chart_temp   = array_map( fn($r) => (float)$r->temperature, $history );
$chart_weight = array_map( fn($r) => (int)$r->weight, $history );
$chart_rpm    = array_map( fn($r) => (int)$r->motor_rpm, $history );

$status    = $machine->status;
$temp_warn = $tel->temperature > 70;
$age_mins  = round( ( time() - strtotime($tel->recorded_at) ) / 60 );
$page_ids  = get_option( 'tru_page_ids', [] );
$dash_url  = isset($page_ids['3r-portal-dashboard']) ? get_permalink($page_ids['3r-portal-dashboard']) : '#';
?>

<div class="tru-machine-detail">

  <!-- Breadcrumb -->
  <div class="tru-breadcrumb">
    <a href="<?php echo esc_url($dash_url); ?>">Dashboard</a>
    <span>›</span>
    <span><?php echo esc_html($machine->machine_uid); ?></span>
  </div>

  <!-- Machine header -->
  <div class="tru-mdetail-header">
    <div>
      <div class="tru-mdetail-uid"><?php echo esc_html($machine->machine_uid); ?></div>
      <h1 class="tru-mdetail-name"><?php echo esc_html($machine->name); ?></h1>
      <div class="tru-mdetail-loc">📍 <?php echo esc_html($machine->location); ?></div>
      <?php if ( isset($page_ids['3r-portal-panel']) ) : ?>
        <a class="tru-panel-link" href="<?php echo esc_url( add_query_arg( 'uid', $machine->machine_uid, get_permalink($page_ids['3r-portal-panel']) ) ); ?>">Open Live Panel →</a>
      <?php endif; ?>
    </div>
    <div class="tru-mdetail-status-block">
      <span class="tru-fleet-badge tru-badge-<?php echo esc_attr($status); ?>">
        <?php echo $status === 'online' ? '● Online' : ($status === 'warning' ? '⚠ Warning' : '○ Offline'); ?>
      </span>
      <div class="tru-mdetail-age">Updated <?php echo $age_mins; ?> min ago</div>
      <div class="tru-mdetail-installed">Installed <?php echo esc_html($machine->installed_at ?? '—'); ?></div>
    </div>
  </div>

  <!-- Live sensors -->
  <div class="tru-mdetail-sensors">
    <div class="tru-mdetail-sensor <?php echo $temp_warn ? 'tru-sensor-warn' : ''; ?>">
      <div class="tru-mds-icon">🌡️</div>
      <div class="tru-mds-val"><?php echo esc_html($tel->temperature); ?><span class="tru-mds-unit">°C</span></div>
      <div class="tru-mds-lbl">Temperature</div>
      <?php if ($temp_warn) echo '<div class="tru-mds-alert">⚠ Above 70°C threshold</div>'; ?>
    </div>
    <div class="tru-mdetail-sensor">
      <div class="tru-mds-icon">⚖️</div>
      <div class="tru-mds-val"><?php echo esc_html($tel->weight); ?><span class="tru-mds-unit">kg</span></div>
      <div class="tru-mds-lbl">Load / Weight</div>
    </div>
    <div class="tru-mdetail-sensor">
      <div class="tru-mds-icon">⚙️</div>
      <div class="tru-mds-val"><?php echo $tel->motor_status ? esc_html($tel->motor_rpm) : 'OFF'; ?><span class="tru-mds-unit"><?php echo $tel->motor_status ? 'rpm' : ''; ?></span></div>
      <div class="tru-mds-lbl">Motor</div>
    </div>
    <div class="tru-mdetail-sensor">
      <div class="tru-mds-icon">📶</div>
      <div class="tru-mds-val"><?php echo esc_html($tel->signal_strength); ?><span class="tru-mds-unit">dBm</span></div>
      <div class="tru-mds-lbl">Signal Strength</div>
    </div>
  </div>

  <!-- Charts -->
  <div class="tru-mdetail-charts">

    <div class="tru-chart-card">
      <div class="tru-chart-card-header">
        <div class="tru-chart-card-title">Temperature // 24h</div>
        <div class="tru-chart-threshold">Threshold: 70°C</div>
      </div>
      <canvas id="tempChart" height="80"></canvas>
    </div>

    <div class="tru-chart-card">
      <div class="tru-chart-card-header">
        <div class="tru-chart-card-title">Load (kg) // 24h</div>
        <div class="tru-chart-threshold">Max: 800 kg</div>
      </div>
      <canvas id="weightChart" height="80"></canvas>
    </div>

    <div class="tru-chart-card tru-chart-full">
      <div class="tru-chart-card-header">
        <div class="tru-chart-card-title">Motor RPM // 24h</div>
        <div class="tru-chart-threshold">Optimal: 38–52 rpm</div>
      </div>
      <canvas id="rpmChart" height="60"></canvas>
    </div>

  </div>

  <!-- Recent alerts for this machine -->
  <?php $alerts = ThreeR_Portal_Data::get_alerts( null, 'all', 10 );
  $machine_alerts = array_filter( $alerts, fn($a) => $a->machine_id === $uid );
  if ( $machine_alerts ) : ?>
  <div class="tru-portal-section-header" style="margin-top:32px">
    <h2 class="tru-portal-section-title">Recent Alerts — <?php echo esc_html($uid); ?></h2>
  </div>
  <div class="tru-alert-list">
    <?php foreach ( array_slice($machine_alerts,0,5) as $alert ) : ?>
    <div class="tru-alert-row tru-alert-<?php echo esc_attr($alert->severity); ?>" data-alert-id="<?php echo (int)$alert->id; ?>">
      <span class="tru-alert-sev-dot"></span>
      <div class="tru-alert-info">
        <div class="tru-alert-msg"><?php echo esc_html($alert->message); ?></div>
        <div class="tru-alert-meta">
          <span><?php echo esc_html(human_time_diff(strtotime($alert->created_at),time())); ?> ago</span>
          <span>·</span>
          <span class="tru-alert-status-<?php echo esc_attr($alert->status); ?>"><?php echo ucfirst($alert->status); ?></span>
        </div>
      </div>
      <span class="tru-alert-sev-badge tru-sev-<?php echo esc_attr($alert->severity); ?>"><?php echo ucfirst($alert->severity); ?></span>
      <?php if ($alert->status === 'active') : ?>
        <button class="tru-ack-btn" data-id="<?php echo (int)$alert->id; ?>">Acknowledge</button>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</div>

<!-- Chart data injected for portal-charts.js -->
<script>
window.truChartData = {
  labels:  <?php echo wp_json_encode( $chart_labels ); ?>,
  temp:    <?php echo wp_json_encode( $chart_temp   ); ?>,
  weight:  <?php echo wp_json_encode( $chart_weight ); ?>,
  rpm:     <?php echo wp_json_encode( $chart_rpm    ); ?>,
};
</script>

<?php include $partials . 'portal-footer.php'; ?>
