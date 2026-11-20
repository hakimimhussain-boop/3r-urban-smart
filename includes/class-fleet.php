<?php
/**
 * Fleet API + history log for the Inorbit dashboard.
 *   GET /wp-json/3r/v1/fleet                      -> every machine, latest panel + online flag
 *   GET /wp-json/3r/v1/fleet/{uid}/history?hours= -> logged readings (max 30 days, <=600 points)
 * History rows are written from the ingest, at most every 4 minutes per machine
 * (and immediately when the machine state or motor changes). Read-only.
 * Add a machine: add it to the tru_fleet option (or the default list below) and
 * point its router at /wp-json/3r/v1/ingest/{uid}.
 */
defined( 'ABSPATH' ) || exit;

class ThreeR_Fleet {

    const TABLE = '3r_history';

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'routes' ] );
        add_action( 'tru_panel_saved', [ $this, 'log' ], 10, 3 );
        add_action( 'init', [ __CLASS__, 'migrate_tz' ], 5 );
        // Never let a browser, proxy or page cache keep live data.
        add_filter( 'rest_post_dispatch', function ( $res, $server, $req ) {
            if ( $req->get_method() === 'GET' && strpos( $req->get_route(), '/3r/v1/' ) === 0 && $res instanceof WP_REST_Response ) {
                $res->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0' );
            }
            return $res;
        }, 10, 3 );
    }

    /**
     * One-time: history rows written while the site timezone was UTC are shifted to the new site timezone.
     * Only runs when option tru_tz_cutoff (UTC time of the switch) is set and the site timezone is no longer UTC.
     */
    public static function migrate_tz() {
        $cut = get_option( 'tru_tz_cutoff' );
        if ( ! $cut || get_option( 'tru_tz_done' ) ) { return; }
        $off = (int) wp_timezone()->getOffset( new DateTime( 'now', new DateTimeZone( 'UTC' ) ) );
        if ( $off === 0 ) { return; }
        global $wpdb;
        $t = $wpdb->prefix . self::TABLE;
        $wpdb->query( $wpdb->prepare( "UPDATE `$t` SET ts = DATE_ADD(ts, INTERVAL %d MINUTE) WHERE ts <= %s", (int) ( $off / 60 ), $cut ) );
        update_option( 'tru_tz_done', current_time( 'mysql' ) );
    }

    public static function install() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $t = $wpdb->prefix . self::TABLE;
        dbDelta( "CREATE TABLE $t (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            machine_uid varchar(40) NOT NULL,
            ts datetime NOT NULL,
            state smallint NOT NULL DEFAULT 0,
            motor tinyint NOT NULL DEFAULT 0,
            blower tinyint NOT NULL DEFAULT 0,
            heater tinyint NOT NULL DEFAULT 0,
            temp float NULL,
            kwh float NULL,
            current float NULL,
            waste int NULL,
            cycles int NULL,
            alarms int NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            KEY uid_ts (machine_uid, ts)
        ) {$wpdb->get_charset_collate()};" );
    }

    public static function fleet() {
        $default = [
            [ 'uid' => '3R-VZG-1000', 'name' => 'Compost Machine 1000 kg', 'location' => 'Vizag', 'capacity_kg' => 1000 ],
            [ 'uid' => '3R-VZG-1600', 'name' => 'Compost Machine 1600 kg', 'location' => 'Vizag', 'capacity_kg' => 1600 ],
        ];
        $opt = get_option( 'tru_fleet' );
        return ( is_array( $opt ) && $opt ) ? $opt : $default;
    }

    public function routes() {
        register_rest_route( '3r/v1', '/fleet', [
            'methods' => 'GET', 'callback' => [ $this, 'get_fleet' ], 'permission_callback' => [ $this, 'perm' ],
        ] );
        register_rest_route( '3r/v1', '/fleet/(?P<uid>[A-Z0-9\-]+)/history', [
            'methods' => 'GET', 'callback' => [ $this, 'get_history' ], 'permission_callback' => [ $this, 'perm' ],
        ] );
    }

    public function perm( $req ) {
        if ( ThreeR_Ingest::panel_is_public() ) { return true; }
        if ( ! is_user_logged_in() ) { return new WP_Error( 'unauthorized', 'Authentication required.', [ 'status' => 401 ] ); }
        return true;
    }

    private static function out( $v ) { return ( $v === 'ON' || $v === 'FWD' || $v === 'REV' || $v === 'OPEN' ) ? ( $v === 'REV' ? 2 : 1 ) : 0; }

    public function log( $uid, $payload, $raw ) {
        global $wpdb;
        $t   = $wpdb->prefix . self::TABLE;
        $st  = (int) ( $payload['status']['state_code'] ?? 0 );
        $o   = $payload['outputs'] ?? [];
        $mot = self::out( $o['main_motor'] ?? '' );
        $last = $wpdb->get_row( $wpdb->prepare( "SELECT ts, state, motor FROM `$t` WHERE machine_uid = %s ORDER BY id DESC LIMIT 1", $uid ) );
        $now = current_time( 'timestamp' );
        $gap = $mot > 0 ? 50 : 240; // log every push while the motor runs (for the current graph), else every 4 min
        if ( $last && ( $now - strtotime( $last->ts ) ) < $gap && (int) $last->state === $st && (int) $last->motor === $mot ) { return; }
        $wpdb->insert( $t, [
            'machine_uid' => $uid,
            'ts'     => current_time( 'mysql' ),
            'state'  => $st,
            'motor'  => $mot,
            'blower' => self::out( $o['blower'] ?? '' ),
            'heater' => self::out( $o['heater'] ?? '' ),
            'temp'   => isset( $payload['live']['temperature_c'] ) ? (float) $payload['live']['temperature_c'] : null,
            'kwh'    => isset( $payload['live']['last_cycle_kwh'] ) ? (float) $payload['live']['last_cycle_kwh'] : null,
            'current' => isset( $payload['live']['motor_current_a'] ) ? (float) $payload['live']['motor_current_a'] : null,
            'waste'  => isset( $payload['live']['waste_added_kg'] ) ? (int) $payload['live']['waste_added_kg'] : null,
            'cycles' => isset( $payload['live']['cycles_done'] ) ? (int) $payload['live']['cycles_done'] : null,
            'alarms' => (int) ( $raw[10] ?? 0 ),
        ] );
    }

    /** Finished cycles today (site time): each entry into state 2 = cycle complete/standby counts once. */
    public static function cycles_today( $uid ) {
        global $wpdb;
        $t     = $wpdb->prefix . self::TABLE;
        $start = date( 'Y-m-d 00:00:00', current_time( 'timestamp' ) );
        $prev  = $wpdb->get_var( $wpdb->prepare( "SELECT state FROM `$t` WHERE machine_uid = %s AND ts < %s ORDER BY id DESC LIMIT 1", $uid, $start ) );
        $rows  = $wpdb->get_col( $wpdb->prepare( "SELECT state FROM `$t` WHERE machine_uid = %s AND ts >= %s ORDER BY id ASC", $uid, $start ) );
        $n = 0; $last = ( $prev === null ) ? null : (int) $prev;
        foreach ( $rows as $st ) { $st = (int) $st; if ( $st === 2 && $last !== 2 ) { $n++; } $last = $st; }
        return $n;
    }

    public function get_fleet( $req ) {
        global $wpdb;
        $t = $wpdb->prefix . ThreeR_Ingest::TABLE;
        $out = [];
        foreach ( self::fleet() as $m ) {
            $row = $wpdb->get_row( $wpdb->prepare( "SELECT payload, updated_at FROM `$t` WHERE machine_uid = %s", $m['uid'] ) );
            $p = $row ? json_decode( $row->payload, true ) : null;
            if ( is_array( $p ) ) { unset( $p['raw'] ); }
            $age = $row ? ( current_time( 'timestamp' ) - strtotime( $row->updated_at ) ) : null;
            $m['cycles_today'] = self::cycles_today( $m['uid'] );
            $m['panel']      = $p;
            $m['updated_at'] = $row ? $row->updated_at : null;
            $m['age_s']      = $age;
            $m['online']     = ( $age !== null && $age < 300 );
            $out[] = $m;
        }
        return rest_ensure_response( [ 'machines' => $out, 'server_time' => current_time( 'mysql' ) ] );
    }

    public function get_history( $req ) {
        global $wpdb;
        $t     = $wpdb->prefix . self::TABLE;
        $hours = max( 1, min( 720, (int) ( $req->get_param( 'hours' ) ?: 24 ) ) );
        $from  = date( 'Y-m-d H:i:s', current_time( 'timestamp' ) - $hours * 3600 );
        $rows  = $wpdb->get_results( $wpdb->prepare(
            "SELECT ts, state, motor, blower, heater, temp, kwh, current, waste, cycles, alarms FROM `$t` WHERE machine_uid = %s AND ts >= %s ORDER BY ts ASC",
            $req['uid'], $from
        ), ARRAY_A );
        $n = count( $rows );
        if ( $n > 600 ) {
            $step = (int) ceil( $n / 600 );
            $rows = array_values( array_filter( $rows, function ( $k ) use ( $step ) { return $k % $step === 0; }, ARRAY_FILTER_USE_KEY ) );
        }
        return rest_ensure_response( [ 'uid' => $req['uid'], 'hours' => $hours, 'points' => $rows ] );
    }
}
