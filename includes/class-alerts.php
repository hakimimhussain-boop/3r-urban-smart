<?php
/**
 * Critical alarm emails.
 *  - Fault states from the PLC (D2): emergency stop, main/blower motor trip, phase loss, heater trip. Door open is optional.
 *  - Machine offline (no data for 15 min) and back online.
 * Recipients: option tru_alert_emails (falls back to the weekly report recipients). Each machine+alarm is emailed at most once per 30 min.
 */
defined( 'ABSPATH' ) || exit;

class ThreeR_Alerts {

    const CRON = 'tru_offline_check';

    public function __construct() {
        add_action( 'tru_panel_saved', [ $this, 'on_panel' ], 20, 3 );
        add_filter( 'cron_schedules', function ( $s ) { $s['tru_5min'] = [ 'interval' => 300, 'display' => 'Every 5 minutes' ]; return $s; } );
        add_action( self::CRON, [ $this, 'check_offline' ] );
        add_action( 'init', function () { if ( ! wp_next_scheduled( self::CRON ) ) { wp_schedule_event( time() + 120, 'tru_5min', self::CRON ); } } );
    }

    public static function recipients() {
        $raw = trim( (string) get_option( 'tru_alert_emails', '' ) );
        if ( $raw === '' ) { $raw = (string) get_option( 'tru_report_emails', '' ); }
        return array_values( array_filter( array_map( 'sanitize_email', preg_split( '/[\s,;]+/', $raw ) ), 'is_email' ) );
    }

    private static function name( $uid ) {
        foreach ( ThreeR_Fleet::fleet() as $m ) { if ( $m['uid'] === $uid ) { return $m['name'] . ' (' . ( $m['location'] ?? '' ) . ')'; } }
        return $uid;
    }

    public static function mail( $subject, $body, $customer = 'Inorbit' ) {
        $to = array_values( array_unique( array_merge( self::recipients(), ThreeR_Reports::customer_emails( $customer ) ) ) );
        if ( ! $to ) { return false; }
        $fe = function () { return get_option( 'tru_report_from', 'admin@urbancompost.cloud' ); };
        $fn = function () { return $customer . ' alerts'; };
        add_filter( 'wp_mail_from', $fe ); add_filter( 'wp_mail_from_name', $fn );
        $html = '<div style="font-family:Arial;font-size:14px">' . $body . '<p style="font-size:12px;color:#666;margin-top:18px"><a href="' . esc_url( home_url( '/' . ThreeR_Fleet::dash_slug( $customer ) . '/' ) ) . '">Open the ' . esc_html( $customer ) . ' dashboard</a></p></div>';
        $ok = wp_mail( $to, $subject, $html, [ 'Content-Type: text/html; charset=UTF-8' ] );
        remove_filter( 'wp_mail_from', $fe ); remove_filter( 'wp_mail_from_name', $fn );
        return $ok;
    }

    public function on_panel( $uid, $payload, $raw ) {
        $st = (int) ( $payload['status']['state_code'] ?? 0 );
        $crit = [ 4, 5, 6, 8, 9 ];
        if ( get_option( 'tru_alert_door', '0' ) === '1' ) { $crit[] = 7; }
        if ( ! in_array( $st, $crit, true ) ) { return; }
        $key = 'tru_al_' . md5( $uid . '|' . $st );
        if ( get_transient( $key ) ) { return; }
        set_transient( $key, 1, 1800 );
        $label = $payload['status']['label'] ?? ( 'State ' . $st );
        $temp  = $payload['live']['temperature_c'] ?? null;
        $body  = '<h2 style="color:#c0392b;margin:0 0 8px">Critical alarm: ' . esc_html( $label ) . '</h2><p><b>' . esc_html( self::name( $uid ) ) . '</b><br>Time: ' . esc_html( current_time( 'd M Y, H:i' ) ) . ( $temp !== null ? '<br>Temperature: ' . esc_html( $temp ) . ' &deg;C' : '' ) . '</p><p>The machine is stopped or faulted. Please check it on site.</p>';
        $cu = ThreeR_Fleet::customer_for( $uid );
        self::mail( '[' . $cu . '] ' . $label . ' - ' . self::name( $uid ), $body, $cu );
    }

    public function check_offline() {
        global $wpdb;
        $t = $wpdb->prefix . ThreeR_Ingest::TABLE;
        foreach ( ThreeR_Fleet::fleet() as $m ) {
            $row = $wpdb->get_var( $wpdb->prepare( "SELECT updated_at FROM `$t` WHERE machine_uid = %s", $m['uid'] ) );
            if ( ! $row ) { continue; }
            $age  = current_time( 'timestamp' ) - strtotime( $row );
            $flag = 'tru_off_' . md5( $m['uid'] );
            if ( $age > 900 && ! get_option( $flag ) ) {
                update_option( $flag, current_time( 'mysql' ), false );
                self::mail( '[' . $m['customer'] . '] Machine offline - ' . self::name( $m['uid'] ), '<h2 style="color:#b7791f;margin:0 0 8px">Machine offline</h2><p><b>' . esc_html( self::name( $m['uid'] ) ) . '</b> has sent no data since ' . esc_html( $row ) . ' (' . round( $age / 60 ) . ' min ago).<br>Check power, the router and its SIM or network.</p>', $m['customer'] );
            } elseif ( $age <= 300 && get_option( $flag ) ) {
                delete_option( $flag );
                self::mail( '[' . $m['customer'] . '] Machine back online - ' . self::name( $m['uid'] ), '<h2 style="color:#1f7a4d;margin:0 0 8px">Machine back online</h2><p><b>' . esc_html( self::name( $m['uid'] ) ) . '</b> is sending data again.</p>', $m['customer'] );
            }
        }
    }
}
