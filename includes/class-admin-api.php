<?php
defined( 'ABSPATH' ) || exit;

class ThreeR_Admin_API {

    public static function register_routes() {
        $ns = '3r/v1/admin';

        // Toggle machine status
        register_rest_route( $ns, '/machines/(?P<uid>[A-Z0-9\-]+)/status', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'set_machine_status' ],
            'permission_callback' => [ __CLASS__, 'admin_permission' ],
            'args' => [
                'status' => [ 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ],
            ],
        ] );

        // Assign machine
        register_rest_route( $ns, '/machines/(?P<uid>[A-Z0-9\-]+)/assign', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'assign_machine' ],
            'permission_callback' => [ __CLASS__, 'admin_permission' ],
        ] );

        // Get all customers (for JS dropdowns)
        register_rest_route( $ns, '/customers', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'get_customers' ],
            'permission_callback' => [ __CLASS__, 'admin_permission' ],
        ] );
    }

    public static function admin_permission() {
        return is_user_logged_in() && ThreeR_Portal_Auth::is_admin_user();
    }

    public static function set_machine_status( WP_REST_Request $req ) {
        $uid    = $req->get_param('uid');
        $status = $req->get_param('status');
        if ( ! in_array( $status, ['online','offline','warning'], true ) ) {
            return new WP_Error( 'invalid_status', 'Invalid status value.', [ 'status' => 400 ] );
        }
        $result = ThreeR_Admin_Data::update_machine_status( $uid, $status );
        return rest_ensure_response( [ 'success' => (bool) $result, 'machine_uid' => $uid, 'status' => $status ] );
    }

    public static function assign_machine( WP_REST_Request $req ) {
        $uid         = $req->get_param('uid');
        $customer_id = absint( $req->get_json_params()['customer_id'] ?? 0 );
        $result      = ThreeR_Admin_Data::assign_machine( $uid, $customer_id );
        if ( is_wp_error( $result ) ) {
            return new WP_Error( 'assign_failed', $result->get_error_message(), [ 'status' => 400 ] );
        }
        return rest_ensure_response( [ 'success' => true, 'machine_uid' => $uid, 'customer_id' => $customer_id ] );
    }

    public static function get_customers() {
        $users = ThreeR_Admin_Data::get_all_customers();
        return rest_ensure_response( array_map( fn($u) => [
            'id'           => $u->ID,
            'login'        => $u->user_login,
            'name'         => $u->display_name ?: $u->user_login,
            'email'        => $u->user_email,
            'organisation' => $u->organisation,
            'machine_count'=> $u->machine_count,
        ], $users ) );
    }
}

add_action( 'rest_api_init', [ 'ThreeR_Admin_API', 'register_routes' ] );
