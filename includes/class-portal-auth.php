<?php
/**
 * Portal authentication and role management.
 */
defined( 'ABSPATH' ) || exit;

class ThreeR_Portal_Auth {

    public function __construct() {
        add_action( 'init',           [ $this, 'register_role'        ] );
        add_action( 'login_redirect', [ $this, 'redirect_after_login' ], 10, 3 );
        add_filter( 'logout_redirect', [ $this, 'redirect_after_logout'] );
    }

    // ── Register customer role ────────────────────────────────
    public static function register_role() {
        if ( ! get_role( 'customer' ) ) {
            add_role( 'customer', '3R Customer', [ 'read' => true ] );
        }
    }

    public static function remove_role() {
        remove_role( 'customer' );
    }

    // ── Redirect customer to portal after login ───────────────
    public function redirect_after_login( $redirect_to, $requested, $user ) {
        if ( is_wp_error( $user ) ) return $redirect_to;
        if ( in_array( 'customer', (array) $user->roles ) ) {
            $ids = get_option( 'tru_page_ids', [] );
            if ( isset( $ids['3r-portal-dashboard'] ) ) {
                return get_permalink( $ids['3r-portal-dashboard'] );
            }
        }
        return $redirect_to;
    }

    public function redirect_after_logout( $redirect_to ) {
        return home_url( '/' );
    }

    // ── Portal login guard ────────────────────────────────────
    // Blocks non-logged-in users. Any authenticated WP user can
    // access the portal — role-based data scoping is handled in
    // ThreeR_Portal_Data (customers see only their machines).
    public static function require_login() {
        if ( is_user_logged_in() ) {
            return true;
        }
        // Not logged in — redirect to WP login with return URL
        $current_url = ( is_ssl() ? 'https://' : 'http://' )
                     . $_SERVER['HTTP_HOST']
                     . $_SERVER['REQUEST_URI'];
        wp_redirect( wp_login_url( $current_url ) );
        exit;
    }

    // ── Helpers ───────────────────────────────────────────────
    public static function is_admin_user() {
        $user = wp_get_current_user();
        return (bool) array_intersect( ['3r_admin','administrator'], (array) $user->roles );
    }

    public static function current_customer_id() {
        return get_current_user_id();
    }
}
