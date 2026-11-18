<?php
/**
 * PLC data ingest: the RUT200 router POSTs Modbus register values here over HTTPS.
 * Endpoint: POST /wp-json/3r/v1/ingest/{machine_uid}   header: X-3R-Token
 * Read-only for the machine: nothing here ever writes back to the PLC.
 */
defined( 'ABSPATH' ) || exit;

class ThreeR_Ingest {

    const TABLE = '3r_panel_state';

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'routes' ] );
        add_action( 'admin_menu', [ $this, 'menu' ] );
    }

    public static function install() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $t = $wpdb->prefix . self::TABLE;
        dbDelta( "CREATE TABLE $t (
            machine_uid varchar(40) NOT NULL,
            payload longtext NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (machine_uid)
        ) {$wpdb->get_charset_collate()};" );
        if ( ! get_option( 'tru_ingest_token' ) ) {
            update_option( 'tru_ingest_token', wp_generate_password( 40, false ), false );
        }
    }

    public function routes() {
        register_rest_route( '3r/v1', '/ingest/(?P<uid>[A-Z0-9\-]+)', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'handle' ],
            'permission_callback' => [ $this, 'auth' ],
        ] );
    }

    public function auth( $req ) {
        $token = (string) get_option( 'tru_ingest_token' );
        $sent  = (string) $req->get_header( 'x-3r-token' );
        $ok = $token !== '' && hash_equals( $token, $sent );
        if ( ! $ok ) { self::log_last( 'auth_failed', $req->get_body() ); }
        return $ok;
    }

    private static function log_last( $status, $body ) {
        update_option( 'tru_ingest_last', [
            'time'   => current_time( 'mysql' ),
            'status' => $status,
            'body'   => substr( (string) $body, 0, 1500 ),
        ], false );
    }

    public function handle( $req ) {
        global $wpdb;
        $uid  = $req['uid'];
        $body = json_decode( $req->get_body(), true );
        if ( ! is_array( $body ) ) {
            self::log_last( 'bad json', $req->get_body() );
            return new WP_Error( 'bad_json', 'Body must be JSON', [ 'status' => 400 ] );
        }
        $new = self::extract_registers( $body );
        self::log_last( $new ? 'ok (' . count( $new ) . ' registers)' : 'no registers found', $req->get_body() );
        if ( ! $new ) {
            return new WP_Error( 'no_registers', 'No register values found', [ 'status' => 422 ] );
        }
        $t   = $wpdb->prefix . self::TABLE;
        $row = $wpdb->get_var( $wpdb->prepare( "SELECT payload FROM `$t` WHERE machine_uid = %s", $uid ) );
        $old = $row ? json_decode( $row, true ) : [];
        $raw = ( is_array( $old ) && isset( $old['raw'] ) ) ? $old['raw'] : [];
        foreach ( $new as $d => $v ) { $raw[ $d ] = $v; }
        $payload = self::build_panel( $raw );
        $payload['raw'] = $raw;
        $wpdb->replace( $t, [
            'machine_uid' => $uid,
            'payload'     => wp_json_encode( $payload ),
            'updated_at'  => current_time( 'mysql' ),
        ] );
        return [ 'ok' => true, 'registers' => count( $new ) ];
    }

    /**
     * Accepts: {"D102":278,"D411":[1200,1200,1000]}   (key = first D number, array = consecutive registers)
     * or the router's list form: {"data":[{"name":"D411","value":[...]}]} / [{"name":"D102","value":278}]
     */
    public static function extract_registers( $body ) {
        $out = [];
        $add = function ( $name, $val ) use ( &$out ) {
            if ( ! preg_match( '/D\s*(\d+)/i', (string) $name, $m ) ) { return; }
            $start = (int) $m[1];
            if ( is_string( $val ) && strpos( $val, ',' ) !== false ) { $val = explode( ',', $val ); }
            foreach ( (array) $val as $i => $v ) {
                if ( is_numeric( $v ) ) { $out[ $start + (int) $i ] = (int) $v; }
            }
        };
        // Router "Modbus" data input: {"<input>":[{"addr":4661,"data":"[1,2,3]","name":"D564",...}]}
        foreach ( $body as $recs ) {
            if ( ! is_array( $recs ) ) { continue; }
            foreach ( $recs as $rec ) {
                if ( is_array( $rec ) && isset( $rec['addr'], $rec['data'] ) ) {
                    $vals = is_array( $rec['data'] ) ? $rec['data'] : json_decode( (string) $rec['data'], true );
                    if ( is_array( $vals ) ) {
                        foreach ( array_values( $vals ) as $i => $v ) {
                            if ( is_numeric( $v ) ) { $out[ (int) $rec['addr'] - 4097 + $i ] = (int) $v; }
                        }
                    }
                }
            }
        }
        if ( $out ) { return $out; }
        $list = isset( $body['data'] ) && is_array( $body['data'] ) ? $body['data'] : $body;
        foreach ( $list as $k => $item ) {
            if ( is_array( $item ) && isset( $item['name'] ) ) {
                $val = $item['value'] ?? ( $item['values'] ?? null );
                $add( $item['name'], $val );
            } elseif ( ! is_int( $k ) ) {
                $add( $k, $item );
            } elseif ( is_array( $item ) ) {
                foreach ( $item as $kk => $vv ) { if ( ! is_int( $kk ) ) { $add( $kk, $vv ); } }
            }
        }
        return $out;
    }

    private static function f32( $raw, $lo ) {
        if ( ! isset( $raw[ $lo ], $raw[ $lo + 1 ] ) ) { return null; }
        $b = pack( 'v2', $raw[ $lo ] & 0xFFFF, $raw[ $lo + 1 ] & 0xFFFF ); // low word first
        $u = unpack( 'g', $b );
        return $u[1];
    }

    public static function build_panel( $r ) {
        $g = function ( $d, $div = 1, $unit = '', $dec = 0 ) use ( $r ) {
            if ( ! isset( $r[ $d ] ) ) { return null; }
            return number_format( $r[ $d ] / $div, $dec, '.', '' ) . $unit;
        };
        $p = [
            'status' => [
                'healthy' => isset( $r[1] ) ? ( (int) $r[1] === 1 ) : true,
                'label'   => ( isset( $r[1] ) && (int) $r[1] !== 1 ) ? 'M/C FAULT' : 'M/C HEALTHY',
            ],
            'live'     => [],
            'outputs'  => [ 'main_motor' => '—', 'blower' => '—', 'heater' => '—', 'door' => '—' ],
            'settings' => [],
        ];
        if ( isset( $r[102] ) ) { $p['live']['temperature_c'] = round( $r[102] / 10, 1 ); }
        $kwh = self::f32( $r, 564 );
        if ( $kwh !== null && is_finite( $kwh ) ) { $p['live']['last_cycle_kwh'] = round( $kwh, 3 ); }
        if ( isset( $r[70], $r[71] ) ) { $p['live']['cycle_time'] = $r[70] . ':' . $r[71] . ':0'; }
        $map = [
            'Total cycle time'   => $g( 416, 1, ' hr' ),
            'Motor forward'      => $g( 411, 10, ' s' ),
            'Wait'               => $g( 413, 10, ' s' ),
            'Motor reverse'      => $g( 412, 10, ' s' ),
            'Wait (2)'           => $g( 418, 10, ' s' ),
            'Wait in cycle'      => $g( 415, 1, ' min' ),
            'Blower on time'     => $g( 417, 10, ' s' ),
            'Compost out'        => $g( 419 ),
            'Heater lower limit' => $g( 420, 10, ' °C' ),
            'Heater upper limit' => $g( 421, 10, ' °C' ),
            'Fwd/Rev cycles'     => $g( 414 ),
            'Cut-off'            => $g( 426, 10, '', 1 ),
            'Motor frequency'    => $g( 598, 100, ' Hz', 2 ),
            'Daily capacity'     => $g( 450, 1, ' kg' ),
            'Overload capacity'  => $g( 452, 1, ' kg' ),
        ];
        $ol = self::f32( $r, 580 );
        if ( $ol !== null && is_finite( $ol ) ) { $map['Overload current'] = number_format( $ol, 1 ) . ' A'; }
        $so = self::f32( $r, 590 );
        if ( $so !== null && is_finite( $so ) ) { $map['Starting overload'] = number_format( $so, 1 ) . ' A'; }
        foreach ( $map as $k => $v ) { if ( $v !== null ) { $p['settings'][ $k ] = $v; } }
        return $p;
    }

    // Public (no login) read-only Live Panel. Default ON; switch off at Tools > 3R Data Ingest.
    public static function panel_is_public() {
        return get_option( 'tru_panel_public', '1' ) === '1';
    }

    // ── Admin page: shows endpoint + token for the router ──
    public function menu() {
        add_management_page( '3R Data Ingest', '3R Data Ingest', 'manage_options', 'tru-ingest', [ $this, 'page' ] );
    }

    public function page() {
        if ( ! current_user_can( 'manage_options' ) ) { return; }
        if ( isset( $_POST['tru_regen'] ) && check_admin_referer( 'tru_regen' ) ) {
            update_option( 'tru_ingest_token', wp_generate_password( 40, false ), false );
            echo '<div class="notice notice-success"><p>New token created. Update the router.</p></div>';
        }
        if ( isset( $_POST['tru_pub'] ) && check_admin_referer( 'tru_pub' ) ) {
            update_option( 'tru_panel_public', $_POST['tru_pub'] === '1' ? '1' : '0', false );
            echo '<div class="notice notice-success"><p>Public panel setting saved.</p></div>';
        }
        $url = rest_url( '3r/v1/ingest/3R-VZG-1000' );
        echo '<div class="wrap"><h1>3R Data Ingest</h1>';
        echo '<p>The router sends HTTP POST (JSON) to this URL with the header below.</p>';
        echo '<p><b>URL</b><br><code>' . esc_html( $url ) . '</code></p>';
        echo '<p><b>Header</b><br><code>X-3R-Token: ' . esc_html( get_option( 'tru_ingest_token' ) ) . '</code></p>';
        $last = get_option( 'tru_ingest_last' );
        echo '<h2>Last request received</h2>';
        if ( is_array( $last ) ) {
            echo '<p>' . esc_html( $last['time'] . ' — ' . $last['status'] ) . '</p>';
            echo '<pre style="white-space:pre-wrap;max-width:900px;background:#fff;padding:8px;border:1px solid #ccd">' . esc_html( $last['body'] ) . '</pre>';
        } else { echo '<p>Nothing received yet.</p>'; }
        $pub = self::panel_is_public();
        echo '<h2>Public Live Panel</h2><p>Live Panel is currently <b>' . ( $pub ? 'PUBLIC (anyone with the link)' : 'login required' ) . '</b>. It is read-only.</p>';
        echo '<form method="post">'; wp_nonce_field( 'tru_pub' );
        echo '<button class="button" name="tru_pub" value="' . ( $pub ? '0' : '1' ) . '">' . ( $pub ? 'Require login again' : 'Make public' ) . '</button></form>';
        echo '<form method="post" style="margin-top:20px">'; wp_nonce_field( 'tru_regen' );
        echo '<button class="button" name="tru_regen" value="1">Regenerate token</button></form></div>';
    }
}
