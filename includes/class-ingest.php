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
        $payload = ( strpos( $uid, '3R-ESP-250' ) === 0 ) ? self::build_panel_comp250( $raw ) : self::build_panel( $raw );
        $payload['raw'] = $raw;
        $wpdb->replace( $t, [
            'machine_uid' => $uid,
            'payload'     => wp_json_encode( $payload ),
            'updated_at'  => current_time( 'mysql' ),
        ] );
        do_action( 'tru_panel_saved', $uid, $payload, $raw );
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
                    if ( is_array( $vals ) && (int) $rec['addr'] === 1281 ) {
                        // Coil read of Y0..Y3 (Delta Y0 = 1280, router is 1-based): router returns the coil byte in the high byte
                        $v0 = (int) ( array_values( $vals )[0] ?? 0 );
                        $out[9000] = ( $v0 & 0xFF00 ) ? ( $v0 >> 8 ) : $v0;
                    } elseif ( is_array( $vals ) && (int) $rec['addr'] === 1025 ) {
                        // Discrete inputs X0..X3 (Delta X0 = 1024, router 1-based, function 2): byte comes back in the high byte
                        $v0 = (int) ( array_values( $vals )[0] ?? 0 );
                        $out[9002] = ( $v0 & 0xFF00 ) ? ( $v0 >> 8 ) : $v0;
                    } elseif ( is_array( $vals ) && (int) $rec['addr'] === 2054 ) {
                        // Coil M5 (Delta M0 = 2048, router 1-based): auto/manual mode, coil byte comes back in the high byte
                        $v0 = (int) ( array_values( $vals )[0] ?? 0 );
                        $out[9001] = ( $v0 & 0xFF00 ) ? ( $v0 >> 8 ) : $v0;
                    } elseif ( is_array( $vals ) ) {
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

    /**
     * COMP-250 (Spain) register profile. Mapped 2026-11-23 from HMI photos vs raw registers:
     * D70..D73 elapsed cycle sec/min/hr/days; D414 cycle days (Setting-1); D416 total cycle days (Help screen);
     * D421 heating set temp x10; D438 cooling set temp x10; D426 heater cut-off x10; D436 inside/outside diff x10;
     * D580/D590/D618 floats (overload, starting overload, actual current); D598 freq x100; D102 inside temp x10.
     * Note: the HMI 'daily capacity' box (53) just mirrors elapsed seconds; D450..D452 are NOT capacity.
     * Heater limits, motor minutes, humidity and weight are NOT located yet, so they are not shown.
     */
    public static function build_panel_comp250( $r ) {
        $p = [
            'status'   => [ 'healthy' => true, 'label' => 'M/C HEALTHY' ],
            'live'     => [],
            'outputs'  => [ 'main_motor' => '—', 'blower' => '—', 'heater' => '—', 'door' => '—' ],
            'settings' => [],
        ];
        if ( isset( $r[2] ) ) {
            $names = [ 0 => 'IDLE', 1 => 'COMPOSTING', 2 => 'CYCLE COMPLETE / STANDBY', 3 => 'REMOVING COMPOST',
                       4 => 'EMERGENCY STOP', 5 => 'MAIN MOTOR TRIP', 6 => 'BLOWER MOTOR TRIP', 7 => 'DOOR OPEN',
                       8 => 'PHASE LOSS / REVERSE FAULT', 9 => 'HEATER TRIP', 10 => 'DOOR OPEN' ];
            $c = (int) $r[2];
            $p['status'] = [ 'healthy' => ! ( $c >= 4 && $c <= 9 ), 'label' => $names[ $c ] ?? ( 'STATE ' . $c ),
                             'cycle_running' => ( $c === 1 || $c === 3 ), 'state_code' => $c ];
            $p['outputs']['door'] = ( $c === 10 ) ? 'OPEN' : 'CLOSE'; // door open = state 10 (tested on site 23 Nov 2026); X2 is not the door
        }
        if ( isset( $r[0] ) ) { $p['outputs']['main_motor'] = [ 0 => 'OFF', 1 => 'FWD', 2 => 'REV' ][ (int) $r[0] ] ?? '—'; }
        if ( isset( $r[9000] ) ) {
            $y = (int) $r[9000];
            $p['outputs']['blower'] = ( $y & 4 ) ? 'ON' : 'OFF';
            $p['outputs']['heater'] = ( $y & 8 ) ? 'ON' : 'OFF';
        }
        if ( isset( $r[9001] ) ) { $p['status']['mode'] = ( (int) $r[9001] & 1 ) ? 'Manual' : 'Auto'; }
        // PLC inputs (manual 11.11): X0 main motor trip, X1 blower trip, X2 door open (ON = open), X3 SPP OK (ON = healthy).
        // X0/X1 polarity is not verified on this machine (X0 read 1 while healthy), so they are exposed raw only.
        if ( isset( $r[9002] ) ) {
            $x = (int) $r[9002];
            $door = isset( $r[2] ) && (int) $r[2] === 10;
            $phase_bad = ( $x & 8 ) === 0;
            $p['inputs_raw'] = [ 'X0' => $x & 1, 'X1' => ( $x >> 1 ) & 1, 'X2' => ( $x >> 2 ) & 1, 'X3' => ( $x >> 3 ) & 1 ];
            $p['alarms_live'] = [ 'Door open' => $door, 'Phase loss / reverse' => $phase_bad ];
            if ( $phase_bad ) { $p['status']['healthy'] = false; }
        }
        if ( isset( $r[102] ) ) { $p['live']['temperature_c'] = round( $r[102] / 10, 1 ); } // inside heater temp (D102 read 86.1 vs HMI 85.5 C)
        if ( isset( $r[132] ) ) { $p['live']['humidity_pct'] = (int) $r[132]; }
        if ( isset( $r[109] ) ) { $p['live']['energy_meter_kwh'] = round( ( (int) $r[109] & 0xFFFF ) / 100, 2 ); } // 0.01 kWh, unsigned 16-bit (wraps at 655.35)
        if ( isset( $r[70], $r[71], $r[72], $r[73] ) ) {
            $h = (int) $r[73] * 24 + (int) $r[72];
            $p['live']['cycle_time'] = $h . ':' . (int) $r[71] . ':' . (int) $r[70];
        }
        $m = [
            'Cycle days (set)'      => isset( $r[414] ) ? (int) $r[414] . ' days' : null,
            'Total cycle (set)'     => isset( $r[416] ) ? (int) $r[416] . ' days' : null,
            'Heating set temp'      => isset( $r[421] ) ? number_format( $r[421] / 10, 1 ) . ' °C' : null,
            'Cooling set temp'      => isset( $r[438] ) ? number_format( $r[438] / 10, 1 ) . ' °C' : null,
            'Humidity'              => isset( $r[132] ) ? (int) $r[132] . ' %' : null, // D132 read 4 vs HMI 4 % (probable, unconfirmed)
            'Outside temp'          => isset( $r[110] ) ? number_format( $r[110] / 10, 1 ) . ' °C' : null,
            'Heater cut-off'        => isset( $r[426] ) ? number_format( $r[426] / 10, 1 ) . ' °C' : null,
            'Inside/outside diff'   => isset( $r[436] ) ? number_format( $r[436] / 10, 1 ) . ' °C' : null,
            'Motor frequency'       => isset( $r[598] ) ? number_format( $r[598] / 100, 2 ) . ' Hz' : null,
        ];
        $ol = self::f32( $r, 580 );
        if ( $ol !== null && is_finite( $ol ) ) { $m['Overload current'] = number_format( $ol, 1 ) . ' A'; }
        $so = self::f32( $r, 590 );
        if ( $so !== null && is_finite( $so ) ) { $m['Starting overload'] = number_format( $so, 1 ) . ' A'; }
        $cur = self::f32( $r, 618 );
        if ( $cur !== null && is_finite( $cur ) && abs( $cur ) < 1000 ) { $p['live']['motor_current_a'] = round( $cur, 2 ); }
        foreach ( $m as $k => $v ) { if ( $v !== null ) { $p['settings'][ $k ] = $v; } }
        return $p;
    }

    public static function build_panel( $r ) {
        $g = function ( $d, $div = 1, $unit = '', $dec = 0 ) use ( $r ) {
            if ( ! isset( $r[ $d ] ) ) { return null; }
            return number_format( $r[ $d ] / $div, $dec, '.', '' ) . $unit;
        };
        $p = [
            'status' => [
                'healthy' => true,
                'label'   => 'M/C HEALTHY',
            ],
            'live'     => [],
            'outputs'  => [ 'main_motor' => '—', 'blower' => '—', 'heater' => '—', 'door' => '—' ],
            'settings' => [],
        ];
        // D2 = machine state code (HMI text bank): 1 composting, 2 cycle complete/standby, 3 removing compost,
        // 4 emergency stop, 5 main motor trip, 6 blower motor trip, 7 door open, 8 phase loss/reverse, 9 heater trip
        if ( isset( $r[2] ) ) {
            $names = [ 0 => 'IDLE', 1 => 'COMPOSTING', 2 => 'CYCLE COMPLETE / STANDBY', 3 => 'REMOVING COMPOST',
                       4 => 'EMERGENCY STOP', 5 => 'MAIN MOTOR TRIP', 6 => 'BLOWER MOTOR TRIP', 7 => 'DOOR OPEN',
                       8 => 'PHASE LOSS / REVERSE FAULT', 9 => 'HEATER TRIP' ];
            $c = (int) $r[2];
            $p['status'] = [
                'healthy'       => ! ( $c >= 4 && $c <= 9 ),
                'label'         => $names[ $c ] ?? ( 'STATE ' . $c ),
                'cycle_running' => ( $c === 1 || $c === 3 ),
                'state_code'    => $c,
            ];
            $p['alarms_live'] = [
                'Emergency stop'          => $c === 4,
                'Main motor trip'         => $c === 5,
                'Blower motor trip'       => $c === 6,
                'Door open'               => $c === 7,
                'Phase loss / reverse'    => $c === 8,
                'Heater trip'             => $c === 9,
            ];
        }
        // D0 = main motor status (confirmed by manual test with operator): 0 off, 1 forward, 2 reverse
        if ( isset( $r[0] ) ) {
            $p['outputs']['main_motor'] = [ 0 => 'OFF', 1 => 'FWD', 2 => 'REV' ][ (int) $r[0] ] ?? ( 'CODE ' . (int) $r[0] );
        }
        // Y outputs (coils 1280..1283), confirmed by operator manual test: bit0 Y0 main fwd, bit1 Y1 main rev, bit2 Y2 blower, bit3 Y3 heater
        if ( isset( $r[9000] ) ) {
            $y = (int) $r[9000];
            $p['outputs']['blower'] = ( $y & 4 ) ? 'ON' : 'OFF';
            $p['outputs']['heater'] = ( $y & 8 ) ? 'ON' : 'OFF';
            if ( ! isset( $r[0] ) ) { $p['outputs']['main_motor'] = ( $y & 1 ) ? 'FWD' : ( ( $y & 2 ) ? 'REV' : 'OFF' ); }
        }
        // M5 = Auto/Manual: 1 = Manual, 0 = Auto (v2.3.11 test: machine in Auto running a cycle read M5 = 0)
        if ( isset( $r[9001] ) ) { $p['status']['mode'] = ( (int) $r[9001] & 1 ) ? 'Manual' : 'Auto'; }
        // D550 = waste added (kg, assumed whole kg; confirm once non-zero)
        if ( isset( $r[550] ) ) { $p['live']['waste_added_kg'] = (int) $r[550]; }
        // D10 = alarm bit word: value 4 (bit 2) = door open (confirmed by manual test); 128 seen during compost-out step (not mapped)
        $door_open = isset( $r[10] ) ? ( ( (int) $r[10] & 4 ) === 4 ) : null;
        if ( $door_open !== null ) {
            $p['outputs']['door'] = $door_open ? 'OPEN' : 'CLOSE';
            if ( isset( $p['alarms_live'] ) ) { $p['alarms_live']['Door open'] = $door_open || $p['alarms_live']['Door open']; }
        }
        // D1 is NOT a health flag: it reads 1 when idle and 2 while a cycle runs (1600 kg machine). Health comes from D2 only.
        // D618:D619 = live motor current (A), 32-bit float, low word first (programmer, 2026-11-19)
        $cur = self::f32( $r, 618 );
        if ( $cur !== null && is_finite( $cur ) && abs( $cur ) < 1000 ) { $p['live']['motor_current_a'] = round( $cur, 2 ); }
        if ( isset( $r[102] ) ) { $p['live']['temperature_c'] = round( $r[102] / 10, 1 ); }
        // Energy (programmer, 2026-11-20): D109 = cumulative energy meter in 0.01 kWh (949 = 9.49 kWh);
        // D560 = meter value at cycle start, D562 = meter value at cycle stop; cycle energy = D562 - D560.
        // (D564 returned garbage ~5.7e31 after a cycle on the 1000 kg, so it is no longer used.)
        if ( isset( $r[109] ) ) { $p['live']['energy_meter_kwh'] = round( ( (int) $r[109] ) / 100, 2 ); }
        $start = isset( $r[560] ) ? (int) $r[560] : null;
        $stop  = isset( $r[562] ) ? (int) $r[562] : null;
        $kwh   = 0;
        if ( $start !== null && $stop !== null && $stop > 0 && $stop >= $start ) { $kwh = ( $stop - $start ) / 100; }
        $p['live']['last_cycle_kwh'] = round( $kwh, 2 );
        if ( $start !== null && isset( $r[109] ) && (int) $r[109] >= $start && $start > 0 ) { $p['live']['cycle_kwh_so_far'] = round( ( (int) $r[109] - $start ) / 100, 2 ); }
        if ( isset( $r[70], $r[71] ) ) { $p['live']['cycle_time'] = $r[70] . ':' . $r[71] . ':0'; }
        $map = [
            'Total cycle time'   => $g( 416, 1, ' hr' ),
            'Motor forward'      => $g( 411, 10, ' s' ),
            'Wait after forward' => $g( 418, 10, ' s' ),
            'Motor reverse'      => $g( 412, 10, ' s' ),
            'Wait after reverse' => $g( 413, 10, ' s' ),
            'Wait in cycle'      => $g( 415, 1, ' min' ),
            'Blower on time'     => $g( 417, 10, ' s' ),
            'Compost out'        => $g( 419, 1, ' min' ),
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
        // Running (actual) timers per programmer's list: D50 fwd s, D58 wait-fwd s, D51 rev s, D52 wait-rev s,
        // D54:D55 wait-in-cycle min:sec, D62 blower s, D59:D60 compost-out min:sec
        $now = function ( $key, $txt ) use ( &$map ) {
            if ( isset( $map[ $key ] ) && $txt !== null ) { $map[ $key ] .= '  ·  now ' . $txt; }
        };
        $sec = fn( $d ) => isset( $r[ $d ] ) ? number_format( $r[ $d ] / 10, 1, '.', '' ) . ' s' : null; // running timers are in 0.1 s like the set values
        $ms  = fn( $m, $s ) => ( isset( $r[ $m ], $r[ $s ] ) ) ? sprintf( '%d:%02d', $r[ $m ], $r[ $s ] ) : null;
        $now( 'Motor forward', $sec( 50 ) );
        $now( 'Wait after forward', $sec( 58 ) );
        $now( 'Motor reverse', $sec( 51 ) );
        $now( 'Wait after reverse', $sec( 52 ) );
        $now( 'Wait in cycle', $ms( 54, 55 ) );
        $now( 'Blower on time', $sec( 62 ) );
        $now( 'Compost out', $ms( 59, 60 ) );
        if ( isset( $p['live']['motor_current_a'] ) ) { $map['Actual current'] = number_format( $p['live']['motor_current_a'], 2 ) . ' A'; }
        foreach ( $map as $k => $v ) { if ( $v !== null ) { $p['settings'][ $k ] = $v; } }
        if ( isset( $r[414] ) ) { $p['settings']['Fwd/Rev cycles'] = (string) $r[414]; }
        if ( isset( $r[425] ) ) { $p['live']['cycles_done'] = (int) $r[425]; }
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
        echo '<p><b>URL — Vizag 1000 kg</b><br><code>' . esc_html( $url ) . '</code></p>';
        echo '<p><b>URL — Vizag 1600 kg</b><br><code>' . esc_html( rest_url( '3r/v1/ingest/3R-VZG-1600' ) ) . '</code></p>';
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
