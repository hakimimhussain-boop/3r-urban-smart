<?php
/**
 * Admin data layer.
 * All DB writes/reads for the admin control panel.
 */
defined( 'ABSPATH' ) || exit;

class ThreeR_Admin_Data {

    // ─────────────────────────────────────────────────────────
    // MACHINES
    // ─────────────────────────────────────────────────────────

    public static function get_all_machines( $customer_id = null ) {
        global $wpdb;
        $tm = $wpdb->prefix . '3r_machines';
        if ( ! self::table_exists( $tm ) ) return self::demo_machines();

        if ( $customer_id ) {
            return $wpdb->get_results( $wpdb->prepare(
                "SELECT m.*, u.display_name AS customer_name
                 FROM `$tm` m
                 LEFT JOIN {$wpdb->users} u ON u.ID = m.customer_id
                 WHERE m.customer_id = %d ORDER BY m.id ASC", $customer_id
            ) );
        }
        return $wpdb->get_results(
            "SELECT m.*, u.display_name AS customer_name
             FROM `$tm` m
             LEFT JOIN {$wpdb->users} u ON u.ID = m.customer_id
             ORDER BY m.id ASC"
        );
    }

    public static function add_machine( $data ) {
        global $wpdb;
        $table = $wpdb->prefix . '3r_machines';
        if ( ! self::table_exists( $table ) ) return new WP_Error( 'no_table', 'Machines table does not exist yet. Run the DB migration first.' );

        // Check UID uniqueness
        $exists = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM `$table` WHERE machine_uid = %s LIMIT 1", $data['machine_uid']
        ) );
        if ( $exists ) return new WP_Error( 'duplicate_uid', 'A machine with this UID already exists.' );

        $result = $wpdb->insert( $table, [
            'machine_uid'  => sanitize_text_field( $data['machine_uid'] ),
            'name'         => sanitize_text_field( $data['name'] ),
            'location'     => sanitize_text_field( $data['location'] ),
            'customer_id'  => absint( $data['customer_id'] ?? 0 ) ?: null,
            'status'       => 'offline',
            'installed_at' => current_time('Y-m-d'),
        ], [ '%s','%s','%s','%d','%s','%s' ] );

        return $result ? $wpdb->insert_id : new WP_Error( 'insert_failed', $wpdb->last_error );
    }

    public static function update_machine_status( $machine_uid, $status ) {
        global $wpdb;
        $table = $wpdb->prefix . '3r_machines';
        if ( ! self::table_exists( $table ) ) return false;
        return $wpdb->update( $table, ['status' => $status], ['machine_uid' => $machine_uid], ['%s'], ['%s'] );
    }

    public static function assign_machine( $machine_uid, $customer_id ) {
        global $wpdb;
        $table = $wpdb->prefix . '3r_machines';
        if ( ! self::table_exists( $table ) ) return new WP_Error( 'no_table', 'Machines table does not exist yet.' );
        $result = $wpdb->update(
            $table,
            [ 'customer_id' => absint( $customer_id ) ?: null ],
            [ 'machine_uid' => $machine_uid ],
            [ '%d' ], [ '%s' ]
        );
        return $result !== false ? true : new WP_Error( 'update_failed', $wpdb->last_error );
    }

    public static function delete_machine( $machine_uid ) {
        global $wpdb;
        $table = $wpdb->prefix . '3r_machines';
        if ( ! self::table_exists( $table ) ) return false;
        return $wpdb->delete( $table, ['machine_uid' => $machine_uid], ['%s'] );
    }

    // ─────────────────────────────────────────────────────────
    // CUSTOMERS
    // ─────────────────────────────────────────────────────────

    public static function get_all_customers() {
        $users = get_users( [ 'role' => 'customer', 'orderby' => 'registered', 'order' => 'DESC' ] );

        // Attach machine count to each user
        foreach ( $users as $user ) {
            $machines = ThreeR_Admin_Data::get_all_machines( $user->ID );
            $user->machine_count  = count( $machines );
            $user->machines_online = count( array_filter( $machines, fn($m) => $m->status === 'online' ) );
            $user->organisation   = get_user_meta( $user->ID, 'tru_organisation', true );
            $user->phone          = get_user_meta( $user->ID, 'tru_phone', true );
        }
        return $users;
    }

    public static function create_customer( $data ) {
        $user_id = wp_insert_user( [
            'user_login' => sanitize_user( $data['username'] ),
            'user_email' => sanitize_email( $data['email'] ),
            'first_name' => sanitize_text_field( $data['first_name'] ?? '' ),
            'last_name'  => sanitize_text_field( $data['last_name'] ?? '' ),
            'role'       => 'customer',
            'user_pass'  => $data['password'] ?? wp_generate_password( 12 ),
        ] );

        if ( is_wp_error( $user_id ) ) return $user_id;

        update_user_meta( $user_id, 'tru_organisation', sanitize_text_field( $data['organisation'] ?? '' ) );
        update_user_meta( $user_id, 'tru_phone',        sanitize_text_field( $data['phone'] ?? '' ) );

        return $user_id;
    }

    // ─────────────────────────────────────────────────────────
    // ALERT THRESHOLDS
    // ─────────────────────────────────────────────────────────

    public static function get_thresholds() {
        $defaults = [
            'temp_warning'    => 70,
            'temp_critical'   => 85,
            'weight_min'      => 50,
            'weight_max'      => 800,
            'motor_off_warn'  => 30,
            'motor_off_crit'  => 120,
            'offline_warn'    => 45,
            'offline_crit'    => 120,
        ];
        $saved = get_option( 'tru_alert_thresholds', [] );
        return array_merge( $defaults, $saved );
    }

    public static function save_thresholds( $data ) {
        $clean = [];
        $fields = [
            'temp_warning','temp_critical',
            'weight_min','weight_max',
            'motor_off_warn','motor_off_crit',
            'offline_warn','offline_crit',
        ];
        foreach ( $fields as $f ) {
            if ( isset( $data[$f] ) ) $clean[$f] = absint( $data[$f] );
        }
        return update_option( 'tru_alert_thresholds', $clean );
    }

    // ─────────────────────────────────────────────────────────
    // HELPERS & DEMO DATA
    // ─────────────────────────────────────────────────────────

    private static function table_exists( $table ) {
        global $wpdb;
        return $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
    }

    private static function demo_machines() {
        return [
            (object)['id'=>1,'machine_uid'=>'3R-MCH-001','name'=>'CompostMaster Pro','location'=>'Ward 14, North District','customer_id'=>1,'customer_name'=>'user1','status'=>'online','installed_at'=>'2024-01-15'],
            (object)['id'=>2,'machine_uid'=>'3R-MCH-002','name'=>'CompostMaster Pro','location'=>'Central Market Zone','customer_id'=>1,'customer_name'=>'user1','status'=>'warning','installed_at'=>'2024-02-20'],
            (object)['id'=>3,'machine_uid'=>'3R-MCH-003','name'=>'CompostMaster Lite','location'=>'South Campus','customer_id'=>1,'customer_name'=>'user1','status'=>'online','installed_at'=>'2024-03-10'],
        ];
    }
}
