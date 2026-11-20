<?php
/**
 * Portal data layer.
 * All queries against the 3R custom tables.
 * Falls back to rich demo data when tables are empty (before MQTT is live).
 */
defined( 'ABSPATH' ) || exit;

class ThreeR_Portal_Data {

    // ─────────────────────────────────────────────────────────────────────────
    // MACHINES
    // ─────────────────────────────────────────────────────────────────────────

    public static function get_machines( $customer_id = null ) {
        global $wpdb;
        $table = $wpdb->prefix . '3r_machines';

        if ( ! self::table_exists( $table ) ) {
            return self::demo_machines( $customer_id );
        }

        if ( ThreeR_Portal_Auth::is_admin_user() || $customer_id === null ) {
            $rows = $wpdb->get_results( "SELECT * FROM `$table` ORDER BY id ASC" );
        } else {
            $rows = $wpdb->get_results( $wpdb->prepare(
                "SELECT * FROM `$table` WHERE customer_id = %d ORDER BY id ASC",
                $customer_id
            ) );
        }

        return $rows ?: self::demo_machines( $customer_id );
    }

    public static function get_machine( $machine_uid ) {
        global $wpdb;
        $table = $wpdb->prefix . '3r_machines';
        if ( ! self::table_exists( $table ) ) {
            foreach ( self::demo_machines() as $m ) {
                if ( $m->machine_uid === $machine_uid ) return $m;
            }
            return null;
        }
        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM `$table` WHERE machine_uid = %s LIMIT 1",
            $machine_uid
        ) );
        if ( ! $row ) {
            foreach ( self::demo_machines() as $m ) {
                if ( $m->machine_uid === $machine_uid ) return $m;
            }
        }
        return $row;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TELEMETRY
    // ─────────────────────────────────────────────────────────────────────────

    public static function get_latest_telemetry( $machine_uid ) {
        global $wpdb;
        $table = $wpdb->prefix . '3r_telemetry';
        if ( ! self::table_exists( $table ) ) {
            return self::demo_telemetry_latest( $machine_uid );
        }
        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM `$table` WHERE machine_id = %s ORDER BY recorded_at DESC LIMIT 1",
            $machine_uid
        ) );
        return $row ?: self::demo_telemetry_latest( $machine_uid );
    }

    public static function get_telemetry_range( $machine_uid, $hours = 24 ) {
        global $wpdb;
        $table = $wpdb->prefix . '3r_telemetry';
        if ( ! self::table_exists( $table ) ) {
            return self::demo_telemetry_range( $machine_uid, $hours );
        }
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM `$table`
             WHERE machine_id = %s
               AND recorded_at >= DATE_SUB(NOW(), INTERVAL %d HOUR)
             ORDER BY recorded_at ASC",
            $machine_uid, $hours
        ) );
        return $rows ?: self::demo_telemetry_range( $machine_uid, $hours );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ALERTS
    // ─────────────────────────────────────────────────────────────────────────

    public static function get_alerts( $customer_id = null, $status = 'all', $limit = 50 ) {
        global $wpdb;
        $tbl_a = $wpdb->prefix . '3r_alerts';
        $tbl_m = $wpdb->prefix . '3r_machines';

        if ( ! self::table_exists( $tbl_a ) ) {
            return self::demo_alerts( $customer_id );
        }

        $where = [];
        $args  = [];

        if ( ! ThreeR_Portal_Auth::is_admin_user() && $customer_id ) {
            $where[] = 'm.customer_id = %d';
            $args[]  = $customer_id;
        }
        if ( $status !== 'all' ) {
            $where[] = 'a.status = %s';
            $args[]  = $status;
        }

        $where_sql = $where ? 'WHERE ' . implode( ' AND ', $where ) : '';
        $sql = "SELECT a.*, m.name AS machine_name, m.location AS machine_location
                FROM `$tbl_a` a
                LEFT JOIN `$tbl_m` m ON m.machine_uid = a.machine_id
                $where_sql
                ORDER BY a.created_at DESC
                LIMIT %d";
        $args[] = $limit;

        $rows = $wpdb->get_results( $wpdb->prepare( $sql, ...$args ) );
        return $rows ?: self::demo_alerts( $customer_id );
    }

    public static function acknowledge_alert( $alert_id, $user_id ) {
        global $wpdb;
        $table = $wpdb->prefix . '3r_alerts';
        if ( ! self::table_exists( $table ) ) return true; // demo mode

        return $wpdb->update(
            $table,
            [ 'status' => 'acknowledged', 'acknowledged_by' => $user_id, 'acknowledged_at' => current_time('mysql') ],
            [ 'id'     => $alert_id ],
            [ '%s','%d','%s' ],
            [ '%d' ]
        );
    }

    public static function get_alert_counts( $customer_id = null ) {
        global $wpdb;
        $table = $wpdb->prefix . '3r_alerts';
        if ( ! self::table_exists( $table ) ) {
            return [ 'active' => 3, 'warning' => 2, 'critical' => 1, 'acknowledged' => 7 ];
        }
        $cond = '';
        if ( ! ThreeR_Portal_Auth::is_admin_user() && $customer_id ) {
            $tbl_m = $wpdb->prefix . '3r_machines';
            $cond  = $wpdb->prepare(
                "AND machine_id IN (SELECT machine_uid FROM `$tbl_m` WHERE customer_id = %d)",
                $customer_id
            );
        }
        $rows = $wpdb->get_results(
            "SELECT status, COUNT(*) as cnt FROM `{$wpdb->prefix}3r_alerts` WHERE 1=1 $cond GROUP BY status"
        );
        $counts = [ 'active' => 0, 'warning' => 0, 'critical' => 0, 'acknowledged' => 0 ];
        foreach ( $rows as $r ) $counts[ $r->status ] = (int) $r->cnt;
        return $counts;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // REPORTS
    // ─────────────────────────────────────────────────────────────────────────

    public static function get_reports( $customer_id = null, $limit = 30 ) {
        global $wpdb;
        $table = $wpdb->prefix . '3r_reports';
        if ( ! self::table_exists( $table ) ) {
            return self::demo_reports( $customer_id );
        }
        $where = ThreeR_Portal_Auth::is_admin_user() ? '' :
                 $wpdb->prepare( 'WHERE customer_id = %d', $customer_id );
        $rows  = $wpdb->get_results(
            "SELECT * FROM `$table` $where ORDER BY created_at DESC LIMIT $limit"
        );
        return $rows ?: self::demo_reports( $customer_id );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    // ─────────────────────────────────────────────────────────────────────────
    // LIVE PANEL (HMI-style, read-only)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Returns the HMI-style snapshot for a machine.
     * Real data: JSON snapshot in table {prefix}3r_panel_state (machine_uid, payload, updated_at),
     * written by the middleware once the PLC pipeline is live.
     * Until then: demo values copied from the Vizag 1000 kg HMI screens.
     */
    public static function get_panel( $machine_uid ) {
        global $wpdb;
        $table = $wpdb->prefix . '3r_panel_state';
        $demo  = self::demo_panel();
        if ( self::table_exists( $table ) ) {
            $row = $wpdb->get_row( $wpdb->prepare(
                "SELECT payload, updated_at FROM `$table` WHERE machine_uid = %s LIMIT 1", $machine_uid ) );
            if ( $row ) {
                $data = json_decode( $row->payload, true );
                if ( is_array( $data ) ) {
                    $data = array_replace_recursive( $demo, $data );
                    if ( isset( $data['alarms_live'] ) ) { $data['alarms'] = $data['alarms_live']; }
                    $data['demo']       = false;
                    $data['updated_at'] = $row->updated_at;
                    return $data;
                }
            }
        }
        $demo['demo']       = true;
        $demo['updated_at'] = current_time( 'mysql' );
        return $demo;
    }

    private static function demo_panel() {
        return [
            'status'  => [ 'healthy' => true, 'label' => 'M/C HEALTHY', 'mode' => 'Auto', 'cycle_running' => false ],
            'live'    => [
                'temperature_c' => 27.8, 'last_cycle_kwh' => 0.000, 'waste_added_kg' => 0,
                'cycle_time' => '0:0:0', 'cycles_done' => 0,
            ],
            'outputs' => [ 'main_motor' => 'OFF', 'blower' => 'OFF', 'heater' => 'OFF', 'door' => 'CLOSE' ],
            'settings' => [
                'Total cycle time'      => '24 hr',
                'Motor forward'         => '120 s',
                'Wait after forward'    => '100 s',
                'Motor reverse'         => '120 s',
                'Wait after reverse'    => '100 s',
                'Wait in cycle'         => '5 min',
                'Blower on time'        => '400 s',
                'Compost out'           => '30',
                'Heater lower limit'    => '100 °C',
                'Heater upper limit'    => '120 °C',
                'Fwd/Rev cycles'        => '5',
                'Cut-off'               => '20.0',
                'Overload current'      => '15.0 A',
                'Starting overload'     => '10.0 A',
                'Motor frequency'       => '25.00 Hz',
                'Actual current'        => '0.00 A',
                'Daily capacity'        => '900 kg',
                'Overload capacity'     => '1000 kg',
            ],
            'inputs' => [
                [ 'X0', 'Input X0', true ], [ 'X1', 'Input X1', false ], [ 'X2', 'Input X2', true ],
                [ 'X3', 'Input X3', true ], [ 'X4', 'Input X4', false ], [ 'X5', 'Input X5', true ],
                [ 'X6', 'Input X6', false ], [ 'X7', 'Input X7', false ],
            ],
            'alarms' => [
                'Main motor trip' => false, 'Blower motor trip' => false, 'Door open' => false,
                'Emergency stop' => false, 'Temperature high' => false,
            ],
        ];
    }

    private static function table_exists( $table ) {
        global $wpdb;
        return $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) === $table;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DEMO DATA (used before MQTT / DB is live)
    // ─────────────────────────────────────────────────────────────────────────

    private static function demo_machines( $customer_id = null ) {
        $machines = [
            (object)[
                'id'          => 1,
                'machine_uid' => '3R-MCH-001',
                'name'        => 'CompostMaster Pro',
                'location'    => 'Ward 14, North District',
                'customer_id' => 1,
                'status'      => 'online',
                'installed_at'=> '2024-01-15',
            ],
            (object)[
                'id'          => 2,
                'machine_uid' => '3R-MCH-002',
                'name'        => 'CompostMaster Pro',
                'location'    => 'Central Market Zone',
                'customer_id' => 1,
                'status'      => 'warning',
                'installed_at'=> '2024-02-20',
            ],
            (object)[
                'id'          => 3,
                'machine_uid' => '3R-MCH-003',
                'name'        => 'CompostMaster Lite',
                'location'    => 'South Campus',
                'customer_id' => 1,
                'status'      => 'online',
                'installed_at'=> '2024-03-10',
            ],
            (object)[
                'id'          => 4,
                'machine_uid' => '3R-VZG-1000',
                'name'        => 'Compost Machine 1000 kg',
                'location'    => 'Vizag',
                'customer_id' => 1,
                'status'      => 'online',
                'installed_at'=> '2026-11-01',
            ],
            (object)[
                'id'          => 5,
                'machine_uid' => '3R-VZG-1600',
                'name'        => 'Compost Machine 1600 kg',
                'location'    => 'Vizag',
                'customer_id' => 1,
                'status'      => 'online',
                'installed_at'=> '2026-11-19',
            ],
        ];
        return $machines;
    }

    private static function demo_telemetry_latest( $machine_uid ) {
        $data = [
            '3R-MCH-001' => (object)['temperature'=>58.4,'weight'=>320,'motor_status'=>1,'motor_rpm'=>45,'signal_strength'=>-72,'recorded_at'=>date('Y-m-d H:i:s',strtotime('-14 minutes'))],
            '3R-MCH-002' => (object)['temperature'=>74.1,'weight'=>512,'motor_status'=>1,'motor_rpm'=>38,'signal_strength'=>-68,'recorded_at'=>date('Y-m-d H:i:s',strtotime('-12 minutes'))],
            '3R-MCH-003' => (object)['temperature'=>61.2,'weight'=>285,'motor_status'=>1,'motor_rpm'=>42,'signal_strength'=>-75,'recorded_at'=>date('Y-m-d H:i:s',strtotime('-18 minutes'))],
        ];
        return $data[ $machine_uid ] ?? $data['3R-MCH-001'];
    }

    private static function demo_telemetry_range( $machine_uid, $hours ) {
        $rows   = [];
        $points = min( $hours * 2, 96 ); // every 30 min
        $base_temp   = $machine_uid === '3R-MCH-002' ? 72 : 58;
        $base_weight = $machine_uid === '3R-MCH-002' ? 500 : 310;

        for ( $i = $points; $i >= 0; $i-- ) {
            $ts = strtotime( "-{$i} minutes" * 30 );
            $rows[] = (object)[
                'recorded_at'     => date( 'Y-m-d H:i:s', $ts ),
                'temperature'     => round( $base_temp   + ( rand(-30,40)/10 ), 1 ),
                'weight'          => $base_weight + rand(-20,20),
                'motor_status'    => rand(0,10) > 1 ? 1 : 0,
                'motor_rpm'       => rand(38,52),
                'signal_strength' => rand(-80,-65),
            ];
        }
        return $rows;
    }

    private static function demo_alerts( $customer_id = null ) {
        return [
            (object)['id'=>1,'machine_id'=>'3R-MCH-002','machine_name'=>'CompostMaster Pro','machine_location'=>'Central Market Zone','alert_type'=>'temperature','severity'=>'warning','message'=>'Temperature exceeded 70°C threshold','threshold_value'=>70,'actual_value'=>74.1,'status'=>'active','created_at'=>date('Y-m-d H:i:s',strtotime('-2 hours'))],
            (object)['id'=>2,'machine_id'=>'3R-MCH-001','machine_name'=>'CompostMaster Pro','machine_location'=>'Ward 14, North District','alert_type'=>'weight','severity'=>'info','message'=>'Load approaching upper threshold','threshold_value'=>350,'actual_value'=>320,'status'=>'acknowledged','created_at'=>date('Y-m-d H:i:s',strtotime('-5 hours'))],
            (object)['id'=>3,'machine_id'=>'3R-MCH-002','machine_name'=>'CompostMaster Pro','machine_location'=>'Central Market Zone','alert_type'=>'motor','severity'=>'warning','message'=>'Motor RPM below optimal range','threshold_value'=>40,'actual_value'=>38,'status'=>'active','created_at'=>date('Y-m-d H:i:s',strtotime('-3 hours'))],
            (object)['id'=>4,'machine_id'=>'3R-MCH-003','machine_name'=>'CompostMaster Lite','machine_location'=>'South Campus','alert_type'=>'signal','severity'=>'info','message'=>'Weak signal detected','threshold_value'=>-75,'actual_value'=>-78,'status'=>'acknowledged','created_at'=>date('Y-m-d H:i:s',strtotime('-1 day'))],
            (object)['id'=>5,'machine_id'=>'3R-MCH-001','machine_name'=>'CompostMaster Pro','machine_location'=>'Ward 14, North District','alert_type'=>'temperature','severity'=>'info','message'=>'Temperature normalised after peak','threshold_value'=>70,'actual_value'=>65,'status'=>'acknowledged','created_at'=>date('Y-m-d H:i:s',strtotime('-2 days'))],
        ];
    }

    private static function demo_reports( $customer_id = null ) {
        $months = ['March 2026','February 2026','January 2026','December 2025','November 2025'];
        $types  = ['Monthly Summary','Weekly Digest','Monthly Summary','Monthly Summary','Monthly Summary'];
        $reports = [];
        foreach ( $months as $i => $month ) {
            $reports[] = (object)[
                'id'          => $i + 1,
                'machine_id'  => '3R-MCH-00' . (($i % 3) + 1),
                'customer_id' => 1,
                'report_type' => $types[$i],
                'period'      => $month,
                'file_path'   => '#', // placeholder — real path from DB
                'file_size'   => rand(180,420) . ' KB',
                'created_at'  => date('Y-m-d', strtotime("-{$i} months")),
            ];
        }
        return $reports;
    }
}
