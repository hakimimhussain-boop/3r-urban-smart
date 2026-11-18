<?php
/**
 * Template Name: Portal — Live Panel
 * HMI-style, READ-ONLY machine panel. No control actions exist here by design.
 */
defined( 'ABSPATH' ) || exit;
$tru_public = ThreeR_Ingest::panel_is_public();
if ( ! $tru_public ) { ThreeR_Portal_Auth::require_login(); }
$tru_guest = ! is_user_logged_in();

$page_ids = get_option( 'tru_page_ids', [] );
$uid      = sanitize_text_field( $_GET['uid'] ?? '' );
$machine  = $uid ? ThreeR_Portal_Data::get_machine( $uid ) : null;
if ( ! $machine ) {
    wp_redirect( ( ! $tru_guest && isset( $page_ids['3r-portal-dashboard'] ) ) ? get_permalink( $page_ids['3r-portal-dashboard'] ) : home_url( '/' ) );
    exit;
}

$panel      = ThreeR_Portal_Data::get_panel( $uid );
$page_title = 'Live Panel — ' . $machine->name;
$partials   = plugin_dir_path( __FILE__ ) . '../partials/';
include $partials . 'portal-header.php';

$dash_url = isset( $page_ids['3r-portal-dashboard'] ) ? get_permalink( $page_ids['3r-portal-dashboard'] ) : '#';
$mach_url = isset( $page_ids['3r-portal-machine'] ) ? add_query_arg( 'uid', $machine->machine_uid, get_permalink( $page_ids['3r-portal-machine'] ) ) : '#';
$healthy  = ! empty( $panel['status']['healthy'] );
$onoff    = fn( $v ) => in_array( strtoupper( (string) $v ), [ 'ON', 'OPEN' ], true ) ? 'on' : 'off';
?>
<div class="tru-panel" data-uid="<?php echo esc_attr( $uid ); ?>">

  <div class="tru-breadcrumb">
    <?php if ( $tru_guest ) : ?>
      <span><?php echo esc_html( $machine->name ); ?></span><span>›</span><span>Live Panel (view only)</span>
    <?php else : ?>
    <a href="<?php echo esc_url( $dash_url ); ?>">Dashboard</a><span>›</span>
    <a href="<?php echo esc_url( $mach_url ); ?>"><?php echo esc_html( $machine->machine_uid ); ?></a><span>›</span>
    <span>Live Panel</span>
    <?php endif; ?>
  </div>

  <?php if ( ! empty( $panel['demo'] ) ) : ?>
    <div class="tru-panel-demo" id="truPanelDemo">DEMO DATA — values copied from the machine's HMI screen. Live PLC data will replace them once the data link is connected.</div>
  <?php endif; ?>

  <div class="tru-panel-head">
    <div>
      <div class="tru-mdetail-uid"><?php echo esc_html( $machine->machine_uid ); ?></div>
      <h1 class="tru-mdetail-name"><?php echo esc_html( $machine->name ); ?></h1>
      <div class="tru-mdetail-loc">📍 <?php echo esc_html( $machine->location ); ?></div>
    </div>
    <div class="tru-panel-health <?php echo $healthy ? 'ok' : 'bad'; ?>" id="pHealth"><?php echo esc_html( $panel['status']['label'] ); ?></div>
  </div>

  <div class="tru-panel-grid">
    <div class="tru-panel-card"><div class="tru-panel-lbl">Temperature</div><div class="tru-panel-big"><span id="pTemp"><?php echo esc_html( $panel['live']['temperature_c'] ); ?></span><small>°C</small></div></div>
    <div class="tru-panel-card"><div class="tru-panel-lbl">Last cycle power</div><div class="tru-panel-big"><span id="pKwh"><?php echo esc_html( number_format( (float) $panel['live']['last_cycle_kwh'], 3 ) ); ?></span><small>kWh</small></div></div>
    <div class="tru-panel-card"><div class="tru-panel-lbl">Waste added</div><div class="tru-panel-big"><span id="pWaste"><?php echo esc_html( $panel['live']['waste_added_kg'] ); ?></span><small>kg</small></div></div>
    <div class="tru-panel-card"><div class="tru-panel-lbl">Cycle time</div><div class="tru-panel-big"><span id="pCycle"><?php echo esc_html( $panel['live']['cycle_time'] ); ?></span><small>h:m:s</small></div></div>
  </div>

  <div class="tru-panel-cols">
    <section class="tru-panel-card">
      <h3>Outputs</h3>
      <?php foreach ( [ 'main_motor' => 'Main motor', 'blower' => 'Blower', 'heater' => 'Heater', 'door' => 'Door' ] as $k => $label ) : ?>
        <div class="tru-panel-row"><span><?php echo esc_html( $label ); ?></span>
          <b class="tru-pill <?php echo $onoff( $panel['outputs'][ $k ] ); ?>" data-out="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $panel['outputs'][ $k ] ); ?></b></div>
      <?php endforeach; ?>
      <h3 style="margin-top:20px">Alarms</h3>
      <?php foreach ( $panel['alarms'] as $label => $active ) : ?>
        <div class="tru-panel-row"><span><?php echo esc_html( $label ); ?></span>
          <b class="tru-pill <?php echo $active ? 'bad' : 'off'; ?>" data-alarm="<?php echo esc_attr( $label ); ?>"><?php echo $active ? 'ACTIVE' : 'OK'; ?></b></div>
      <?php endforeach; ?>
    </section>

    <section class="tru-panel-card">
      <h3>Cycle settings <em>(view only)</em></h3>
      <?php foreach ( $panel['settings'] as $label => $val ) : ?>
        <div class="tru-panel-row"><span><?php echo esc_html( $label ); ?></span><b class="tru-mono"><?php echo esc_html( $val ); ?></b></div>
      <?php endforeach; ?>
    </section>

    <section class="tru-panel-card">
      <h3>Digital inputs</h3>
      <div class="tru-panel-inputs">
        <?php foreach ( $panel['inputs'] as [ $id, $label, $state ] ) : ?>
          <div class="tru-panel-in <?php echo $state ? 'on' : ''; ?>"><i></i><?php echo esc_html( $id ); ?></div>
        <?php endforeach; ?>
      </div>
      <p class="tru-panel-note">Monitoring only. Starting, stopping and manual operation are done at the machine.</p>
    </section>
  </div>

  <div class="tru-panel-foot">Updated <span id="pUpdated"><?php echo esc_html( $panel['updated_at'] ); ?></span> · refreshes every 30 s</div>
</div>
<?php include $partials . 'portal-footer.php'; ?>
