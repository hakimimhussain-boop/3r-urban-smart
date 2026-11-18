<?php
/**
 * Portal REST API endpoints.
 * Base: /wp-json/3r/v1/
 */
defined( 'ABSPATH' ) || exit;

class ThreeR_Portal_API {

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes() {
        $ns = '3r/v1';

        // Machines
        register_rest_route( $ns, '/machines', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_machines' ],
            'permission_callback' => [ $this, 'require_auth' ],
        ] );

        register_rest_route( $ns, '/machines/(?P<uid>[A-Z0-9\-]+)', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_machine' ],
            'permission_callback' => [ $this, 'require_auth' ],
        ] );

        // Telemetry
        register_rest_route( $ns, '/machines/(?P<uid>[A-Z0-9\-]+)/telemetry', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_telemetry' ],
            'permission_callback' => [ $this, 'require_auth' ],
        ] );

        // Live panel (read-only)
        register_rest_route( $ns, '/machines/(?P<uid>[A-Z0-9\-]+)/panel', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_panel' ],
            'permission_callback' => [ $this, 'panel_permission' ],
        ] );

        // Alerts
        register_rest_route( $ns, '/alerts', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_alerts' ],
            'permission_callback' => [ $this, 'require_auth' ],
        ] );

        register_rest_route( $ns, '/alerts/(?P<id>\d+)/acknowledge', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'acknowledge_alert' ],
            'permission_callback' => [ $this, 'require_auth' ],
        ] );

        // Dashboard summary
        register_rest_route( $ns, '/dashboard', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_dashboard' ],
            'permission_callback' => [ $this, 'require_auth' ],
        ] );
    }

    // ── Permission check ─────────────────────────────────────────────────────
    public function panel_permission( $request ) {
        if ( ThreeR_Ingest::panel_is_public() ) { return true; }
        return $this->require_auth( $request );
    }

    public function require_auth( $request ) {
        if ( ! is_user_logged_in() ) {
            return new WP_Error( 'unauthorized', 'Authentication required.', [ 'status' => 401 ] );
        }
        $user = wp_get_current_user();
        if ( ! array_intersect( ['customer','3r_admin','administrator'], (array) $user->roles ) ) {
            return new WP_Error( 'forbidden', 'Insufficient permissions.', [ 'status' => 403 ] );
        }
        return true;
    }

    private function cid() {
        return ThreeR_Portal_Auth::is_admin_user() ? null : get_current_user_id();
    }

    // ── Endpoints ────────────────────────────────────────────────────────────
    public function get_machines( $req ) {
        return rest_ensure_response( ThreeR_Portal_Data::get_machines( $this->cid() ) );
    }

    public function get_machine( $req ) {
        $machine = ThreeR_Portal_Data::get_machine( $req['uid'] );
        if ( ! $machine ) return new WP_Error( 'not_found', 'Machine not found.', [ 'status' => 404 ] );
        return rest_ensure_response( $machine );
    }

    public function get_panel( $req ) {
        if ( ! ThreeR_Portal_Data::get_machine( $req['uid'] ) ) {
            return new WP_Error( 'not_found', 'Machine not found.', [ 'status' => 404 ] );
        }
        return rest_ensure_response( ThreeR_Portal_Data::get_panel( $req['uid'] ) );
    }

    public function get_telemetry( $req ) {
        $hours = (int) ( $req->get_param('hours') ?: 24 );
        $hours = max( 1, min( $hours, 168 ) ); // 1h–7d
        return rest_ensure_response( ThreeR_Portal_Data::get_telemetry_range( $req['uid'], $hours ) );
    }

    public function get_alerts( $req ) {
        $status = $req->get_param('status') ?: 'all';
        return rest_ensure_response( ThreeR_Portal_Data::get_alerts( $this->cid(), $status ) );
    }

    public function acknowledge_alert( $req ) {
        $result = ThreeR_Portal_Data::acknowledge_alert( (int) $req['id'], get_current_user_id() );
        if ( $result === false ) {
            return new WP_Error( 'update_failed', 'Could not acknowledge alert.', [ 'status' => 500 ] );
        }
        return rest_ensure_response( [ 'success' => true, 'alert_id' => (int) $req['id'] ] );
    }

    public function get_dashboard( $req ) {
        $machines = ThreeR_Portal_Data::get_machines( $this->cid() );
        $counts   = ThreeR_Portal_Data::get_alert_counts( $this->cid() );
        $online   = count( array_filter( $machines, fn($m) => $m->status === 'online' ) );
        $warning  = count( array_filter( $machines, fn($m) => $m->status === 'warning' ) );
        $offline  = count( array_filter( $machines, fn($m) => $m->status === 'offline' ) );

        return rest_ensure_response( [
            'machines_total'   => count( $machines ),
            'machines_online'  => $online,
            'machines_warning' => $warning,
            'machines_offline' => $offline,
            'alerts'           => $counts,
        ] );
    }
}
