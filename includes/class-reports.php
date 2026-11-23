<?php
/**
 * Reports built from the history log (3r_history).
 *   GET /wp-json/3r/v1/reports?type=daily|monthly|cycles|alarms|offline&uid=&from=YYYY-MM-DD&to=YYYY-MM-DD[&format=csv]
 *   JSON returns every dataset at once when type is omitted.
 * Weekly email: Mondays 08:00 site time to the addresses saved at Tools > Inorbit reports.
 */
defined( 'ABSPATH' ) || exit;

class ThreeR_Reports {

    const CRON = 'tru_weekly_report';
    const GAP_OFFLINE = 900; // seconds without a log row = no data / offline

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'routes' ] );
        add_action( self::CRON, [ $this, 'send_weekly' ] );
        add_action( 'init', [ $this, 'schedule' ] );
        add_action( 'admin_menu', [ $this, 'menu' ] );
    }

    public function routes() {
        register_rest_route( '3r/v1', '/reports', [
            'methods' => 'GET', 'callback' => [ $this, 'get' ], 'permission_callback' => [ $this, 'perm' ],
        ] );
    }

    public function perm() {
        if ( ThreeR_Ingest::panel_is_public() ) { return true; }
        return is_user_logged_in() ? true : new WP_Error( 'unauthorized', 'Authentication required.', [ 'status' => 401 ] );
    }

    private static function state_label( $c ) {
        $n = [ 4 => 'Emergency stop', 5 => 'Main motor trip', 6 => 'Blower motor trip', 7 => 'Door open', 8 => 'Phase loss / reverse fault', 9 => 'Heater trip' ];
        return $n[ $c ] ?? ( 'State ' . $c );
    }

    /** Compute every dataset for one machine and a date range (site time). */
    public static function compute( $uid, $from, $to ) {
        global $wpdb;
        $t  = $wpdb->prefix . ThreeR_Fleet::TABLE;
        $f  = $from . ' 00:00:00';
        $e  = $to . ' 23:59:59';
        $prev = $wpdb->get_row( $wpdb->prepare( "SELECT ts,state,motor,temp,kwh,current,waste FROM `$t` WHERE machine_uid=%s AND ts<%s ORDER BY id DESC LIMIT 1", $uid, $f ), ARRAY_A );
        $rows = $wpdb->get_results( $wpdb->prepare( "SELECT ts,state,motor,temp,kwh,current,waste FROM `$t` WHERE machine_uid=%s AND ts>=%s AND ts<=%s ORDER BY id ASC", $uid, $f, $e ), ARRAY_A );

        $daily = []; $cycles = []; $alarms = []; $offline = [];
        $last = $prev; $start = null; $open_alarm = null;
        foreach ( $rows as $r ) {
            $d  = substr( $r['ts'], 0, 10 );
            $ts = strtotime( $r['ts'] );
            if ( ! isset( $daily[ $d ] ) ) {
                $daily[ $d ] = [ 'date' => $d, 'machine_uid' => $uid, 'cycles' => 0, 'run_hours' => 0, 'waste_kg' => 0, 'temp_avg' => null, 'temp_max' => null,
                                 'current_avg' => null, 'current_max' => null, 'kwh' => 0, 'faults' => 0, 'offline_hours' => 0, '_tn' => 0, '_ts' => 0, '_cn' => 0, '_cs' => 0 ];
            }
            $D =& $daily[ $d ];
            $st = (int) $r['state'];
            if ( $last ) {
                $gap = $ts - strtotime( $last['ts'] );
                if ( $gap > self::GAP_OFFLINE ) {
                    $D['offline_hours'] += $gap / 3600;
                    $offline[] = [ 'machine_uid' => $uid, 'from' => $last['ts'], 'to' => $r['ts'], 'minutes' => round( $gap / 60 ) ];
                } elseif ( (int) $last['motor'] > 0 ) {
                    $D['run_hours'] += $gap / 3600;
                }
            }
            $ls = $last ? (int) $last['state'] : null;
            if ( $st === 2 && $ls !== 2 ) { $D['cycles']++; }
            if ( $st >= 4 && $st <= 9 && $ls !== $st ) {
                $D['faults']++;
                $alarms[] = [ 'machine_uid' => $uid, 'alarm' => self::state_label( $st ), 'from' => $r['ts'], 'to' => null, 'minutes' => null ];
                $open_alarm = count( $alarms ) - 1;
            } elseif ( $open_alarm !== null && ( $st < 4 || $st > 9 ) ) {
                $a =& $alarms[ $open_alarm ];
                $a['to'] = $r['ts']; $a['minutes'] = round( ( $ts - strtotime( $a['from'] ) ) / 60 );
                unset( $a ); $open_alarm = null;
            }
            if ( $r['temp'] !== null ) {
                $tv = (float) $r['temp'];
                $D['_ts'] += $tv; $D['_tn']++;
                $D['temp_max'] = $D['temp_max'] === null ? $tv : max( $D['temp_max'], $tv );
            }
            if ( $r['current'] !== null && (float) $r['current'] > 0.1 ) {
                $cv = (float) $r['current'];
                $D['_cs'] += $cv; $D['_cn']++;
                $D['current_max'] = $D['current_max'] === null ? $cv : max( $D['current_max'], $cv );
            }
            if ( $r['waste'] !== null ) { $D['waste_kg'] = max( $D['waste_kg'], (int) $r['waste'] ); }
            if ( $r['kwh'] !== null ) { $D['kwh'] = max( $D['kwh'], (float) $r['kwh'] ); }

            // Cycle log: state 1 starts a cycle, state 2 completes it; idle / removing compost aborts it.
            if ( $start === null && $st === 1 ) { $start = [ 'from' => $r['ts'], 'peak' => null, 'cur' => null, 'waste' => 0 ]; }
            if ( $start !== null ) {
                if ( $r['temp'] !== null ) { $start['peak'] = $start['peak'] === null ? (float) $r['temp'] : max( $start['peak'], (float) $r['temp'] ); }
                if ( $r['current'] !== null ) { $start['cur'] = $start['cur'] === null ? (float) $r['current'] : max( $start['cur'], (float) $r['current'] ); }
                if ( $r['waste'] !== null ) { $start['waste'] = max( $start['waste'], (int) $r['waste'] ); }
                if ( $st === 2 ) {
                    $cycles[] = [ 'machine_uid' => $uid, 'start' => $start['from'], 'end' => $r['ts'],
                                  'duration_min' => round( ( $ts - strtotime( $start['from'] ) ) / 60 ),
                                  'peak_temp_c' => $start['peak'], 'max_current_a' => $start['cur'], 'waste_kg' => $start['waste'] ];
                    $start = null;
                } elseif ( $st === 0 || $st === 3 ) { $start = null; }
            }
            unset( $D );
            $last = $r;
        }
        foreach ( $daily as &$D ) {
            $D['temp_avg']    = $D['_tn'] ? round( $D['_ts'] / $D['_tn'], 1 ) : null;
            $D['current_avg'] = $D['_cn'] ? round( $D['_cs'] / $D['_cn'], 2 ) : null;
            $D['current_max'] = $D['current_max'] === null ? null : round( $D['current_max'], 2 );
            $D['run_hours']   = round( $D['run_hours'], 2 );
            $D['offline_hours'] = round( $D['offline_hours'], 2 );
        }
        unset( $D );
        $monthly = [];
        foreach ( $daily as $D ) {
            $m = substr( $D['date'], 0, 7 );
            if ( ! isset( $monthly[ $m ] ) ) { $monthly[ $m ] = [ 'month' => $m, 'machine_uid' => $uid, 'days_active' => 0, 'cycles' => 0, 'run_hours' => 0, 'waste_kg' => 0, 'temp_avg' => null, 'temp_max' => null, 'current_max' => null, 'kwh' => 0, 'faults' => 0, 'offline_hours' => 0, '_ts' => 0, '_tn' => 0 ]; }
            $M =& $monthly[ $m ];
            $M['days_active']++;
            foreach ( [ 'cycles', 'run_hours', 'waste_kg', 'kwh', 'faults', 'offline_hours' ] as $k ) { $M[ $k ] += $D[ $k ]; }
            $M['_ts'] += $D['_ts']; $M['_tn'] += $D['_tn'];
            if ( $D['temp_max'] !== null ) { $M['temp_max'] = $M['temp_max'] === null ? $D['temp_max'] : max( $M['temp_max'], $D['temp_max'] ); }
            if ( $D['current_max'] !== null ) { $M['current_max'] = $M['current_max'] === null ? $D['current_max'] : max( $M['current_max'], $D['current_max'] ); }
            unset( $M );
        }
        foreach ( $monthly as &$M ) {
            $M['temp_avg'] = $M['_tn'] ? round( $M['_ts'] / $M['_tn'], 1 ) : null;
            $M['run_hours'] = round( $M['run_hours'], 1 ); $M['offline_hours'] = round( $M['offline_hours'], 1 );
            unset( $M['_ts'], $M['_tn'] );
        }
        unset( $M );
        foreach ( $daily as &$D ) { unset( $D['_tn'], $D['_ts'], $D['_cn'], $D['_cs'] ); }
        unset( $D );
        return [ 'daily' => array_values( $daily ), 'monthly' => array_values( $monthly ), 'cycles' => $cycles, 'alarms' => $alarms, 'offline' => $offline ];
    }

    private static function date_ok( $s, $def ) { return ( is_string( $s ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $s ) ) ? $s : $def; }

    public function get( $req ) {
        $now  = current_time( 'timestamp' );
        $to   = self::date_ok( $req->get_param( 'to' ), date( 'Y-m-d', $now ) );
        $from = self::date_ok( $req->get_param( 'from' ), date( 'Y-m-d', $now - 29 * 86400 ) );
        if ( $from > $to ) { $x = $from; $from = $to; $to = $x; }
        if ( ( strtotime( $to ) - strtotime( $from ) ) > 400 * 86400 ) { $from = date( 'Y-m-d', strtotime( $to ) - 400 * 86400 ); }
        $uid   = $req->get_param( 'uid' );
        $fleet = ThreeR_Fleet::fleet();
        $out   = [ 'daily' => [], 'monthly' => [], 'cycles' => [], 'alarms' => [], 'offline' => [] ];
        $names = [];
        foreach ( $fleet as $m ) {
            $names[ $m['uid'] ] = ( $m['customer'] ?? '' ) . ' - ' . $m['name'];
            if ( $uid && $uid !== 'all' && $uid !== $m['uid'] ) { continue; }
            $c = self::compute( $m['uid'], $from, $to );
            foreach ( $out as $k => $_ ) { $out[ $k ] = array_merge( $out[ $k ], $c[ $k ] ); }
        }
        foreach ( $out as $k => &$list ) { foreach ( $list as &$r ) { $r = [ 'machine' => $names[ $r['machine_uid'] ] ?? $r['machine_uid'] ] + $r; } }
        unset( $list, $r );
        $type = $req->get_param( 'type' );
        if ( $req->get_param( 'format' ) === 'csv' && isset( $out[ $type ] ) ) { self::csv( $type, $out[ $type ], $from, $to ); }
        return rest_ensure_response( [ 'from' => $from, 'to' => $to, 'uid' => $uid ?: 'all' ] + $out );
    }

    private static function csv( $type, $rows, $from, $to ) {
        nocache_headers();
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="inorbit-' . $type . '-' . $from . '_' . $to . '.csv"' );
        $h = fopen( 'php://output', 'w' );
        fwrite( $h, "\xEF\xBB\xBF" );
        if ( $rows ) { fputcsv( $h, array_keys( $rows[0] ) ); foreach ( $rows as $r ) { fputcsv( $h, $r ); } }
        else { fputcsv( $h, [ 'No data for this range' ] ); }
        fclose( $h );
        exit;
    }

    // ── Weekly email ───────────────────────────────────────────────────────
    public function schedule() {
        if ( ! wp_next_scheduled( self::CRON ) ) {
            $next = strtotime( 'next monday 08:00', current_time( 'timestamp' ) ) - (int) ( get_option( 'gmt_offset' ) * 3600 );
            wp_schedule_event( $next, 'weekly', self::CRON );
        }
    }

    public static function recipients() {
        $raw = (string) get_option( 'tru_report_emails', '' );
        return array_values( array_filter( array_map( 'sanitize_email', preg_split( '/[\s,;]+/', $raw ) ), 'is_email' ) );
    }

    public function send_weekly() { return self::send_now(); }

    public static function customer_emails( $c ) {
        $raw = (string) get_option( 'tru_emails_' . strtolower( $c ), '' );
        return array_values( array_filter( array_map( 'sanitize_email', preg_split( '/[\s,;]+/', $raw ) ), 'is_email' ) );
    }

    public static function send_now() {
        $ok = false;
        if ( self::recipients() ) { $ok = self::send_report( null, self::recipients() ) || $ok; }
        foreach ( [ 'Inorbit', 'LEANpio' ] as $c ) {
            $to = array_values( array_diff( self::customer_emails( $c ), self::recipients() ) );
            if ( $to ) { $ok = self::send_report( $c, $to ) || $ok; }
        }
        return $ok;
    }

    private static function send_report( $only, $to ) {
        $now  = current_time( 'timestamp' );
        $from = date( 'Y-m-d', $now - 7 * 86400 );
        $end  = date( 'Y-m-d', $now - 86400 );
        $html = '<h2 style="font-family:Arial">' . ( $only ? esc_html( $only ) . ' weekly report' : 'Weekly report (all customers)' ) . ' &middot; ' . esc_html( $from ) . ' to ' . esc_html( $end ) . '</h2>';
        foreach ( ThreeR_Fleet::fleet() as $m ) {
            if ( $only && $m['customer'] !== $only ) { continue; }
            $c = self::compute( $m['uid'], $from, $end );
            $cy = array_sum( wp_list_pluck( $c['daily'], 'cycles' ) );
            $rh = array_sum( wp_list_pluck( $c['daily'], 'run_hours' ) );
            $fl = array_sum( wp_list_pluck( $c['daily'], 'faults' ) );
            $html .= '<h3 style="font-family:Arial;margin-bottom:4px">' . esc_html( $m['customer'] . ' - ' . $m['name'] ) . '</h3><p style="font-family:Arial;margin:0 0 6px;font-size:13px">Cycles: <b>' . (int) $cy . '</b> &middot; Motor run: <b>' . round( $rh, 1 ) . ' h</b> &middot; Fault events: <b>' . (int) $fl . '</b> &middot; Offline periods: <b>' . count( $c['offline'] ) . '</b></p>';
            $html .= '<table cellpadding="5" style="border-collapse:collapse;font-family:Arial;font-size:12px"><tr style="background:#e8f4ee"><th align="left">Date</th><th>Cycles</th><th>Run h</th><th>Waste kg</th><th>Avg &deg;C</th><th>Max &deg;C</th><th>Max A</th><th>Faults</th></tr>';
            foreach ( $c['daily'] as $d ) {
                $html .= '<tr style="border-top:1px solid #ddd"><td>' . esc_html( $d['date'] ) . '</td><td align="center">' . (int) $d['cycles'] . '</td><td align="center">' . esc_html( $d['run_hours'] ) . '</td><td align="center">' . (int) $d['waste_kg'] . '</td><td align="center">' . esc_html( $d['temp_avg'] ?? '-' ) . '</td><td align="center">' . esc_html( $d['temp_max'] ?? '-' ) . '</td><td align="center">' . esc_html( $d['current_max'] ?? '-' ) . '</td><td align="center">' . (int) $d['faults'] . '</td></tr>';
            }
            $html .= '</table>';
        }
        $html .= '<p style="font-family:Arial;font-size:12px;margin-top:16px"><a href="' . esc_url( home_url( '/inorbit-dashboard/#/reports' ) ) . '">Open the full reports</a></p>';
        $fe = function () { return get_option( 'tru_report_from', 'admin@urbancompost.cloud' ); };
        $fn = function () { return '3R Urban Smart Solutions'; };
        add_filter( 'wp_mail_from', $fe ); add_filter( 'wp_mail_from_name', $fn );
        $ok = wp_mail( $to, ( $only ? $only . ' weekly report ' : '3R weekly report (all customers) ' ) . $from . ' to ' . $end, $html, [ 'Content-Type: text/html; charset=UTF-8' ] );
        remove_filter( 'wp_mail_from', $fe ); remove_filter( 'wp_mail_from_name', $fn );
        return $ok;
    }

    // ── Settings page ──────────────────────────────────────────────────────
    public function menu() {
        add_management_page( 'Inorbit reports', 'Inorbit reports', 'manage_options', 'tru-reports', [ $this, 'page' ] );
    }

    public function page() {
        if ( ! current_user_can( 'manage_options' ) ) { return; }
        $msg = '';
        if ( isset( $_POST['tru_rep_nonce'] ) && wp_verify_nonce( $_POST['tru_rep_nonce'], 'tru_rep' ) ) {
            update_option( 'tru_report_emails', sanitize_textarea_field( wp_unslash( $_POST['emails'] ?? '' ) ) );
            update_option( 'tru_alert_emails', sanitize_textarea_field( wp_unslash( $_POST['alert_emails'] ?? '' ) ) );
            update_option( 'tru_emails_inorbit', sanitize_textarea_field( wp_unslash( $_POST['emails_inorbit'] ?? '' ) ) );
            update_option( 'tru_emails_leanpio', sanitize_textarea_field( wp_unslash( $_POST['emails_leanpio'] ?? '' ) ) );
            update_option( 'tru_alert_door', isset( $_POST['alert_door'] ) ? '1' : '0' );
            $msg = 'Saved.';
            if ( isset( $_POST['send_alert_test'] ) ) { $msg .= ThreeR_Alerts::mail( '[Inorbit] Test alert', '<h2>Test alert</h2><p>Critical alarm emails are working.</p>' ) ? ' Test alert sent.' : ' Could not send the test alert (add a valid address).'; }
            if ( isset( $_POST['send_now'] ) ) { $msg .= self::send_now() ? ' Test report sent.' : ' Could not send (add at least one valid address).'; }
        }
        echo '<div class="wrap"><h1>Inorbit reports</h1>';
        if ( $msg ) { echo '<div class="notice notice-success"><p>' . esc_html( $msg ) . '</p></div>'; }
        echo '<p>Weekly summary goes out Mondays at 08:00 (site time) for the previous 7 days. Reports tab: <a href="' . esc_url( home_url( '/inorbit-dashboard/#/reports' ) ) . '" target="_blank">open</a>.</p>';
        echo '<form method="post">';
        wp_nonce_field( 'tru_rep', 'tru_rep_nonce' );
        echo '<p><label for="emails"><b>Weekly report recipients</b> (comma or new line separated)</label></p>';
        echo '<textarea id="emails" name="emails" rows="4" cols="60">' . esc_textarea( (string) get_option( 'tru_report_emails', '' ) ) . '</textarea>';
        echo '<h2>Critical alarm emails</h2><p>Sent immediately for emergency stop, motor trips, phase loss and heater trip, and when a machine goes offline for 15 minutes (and when it recovers). Leave blank to use the weekly report recipients.</p>';
        echo '<textarea name="alert_emails" rows="3" cols="60">' . esc_textarea( (string) get_option( 'tru_alert_emails', '' ) ) . '</textarea>';
        echo '<h2>Per-customer recipients</h2><p>Receive alerts AND the weekly report for that customer\'s machines only.</p>';
        echo '<p><b>Inorbit</b></p><textarea name="emails_inorbit" rows="2" cols="60">' . esc_textarea( (string) get_option( 'tru_emails_inorbit', '' ) ) . '</textarea>';
        echo '<p><b>LEANpio</b></p><textarea name="emails_leanpio" rows="2" cols="60">' . esc_textarea( (string) get_option( 'tru_emails_leanpio', '' ) ) . '</textarea>';
        echo '<p><label><input type="checkbox" name="alert_door" value="1"' . checked( get_option( 'tru_alert_door', '0' ), '1', false ) . '> Also email when the door is open</label></p>';
        echo '<p><button class="button button-primary" name="save" value="1">Save</button> <button class="button" name="send_now" value="1">Save &amp; send a test weekly report</button> <button class="button" name="send_alert_test" value="1">Save &amp; send a test alert</button></p></form></div>';
    }
}
