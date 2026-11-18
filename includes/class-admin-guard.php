<?php
/**
 * Admin area guard.
 * All admin templates call ThreeR_Admin_Guard::require_admin() at the top.
 */
defined( 'ABSPATH' ) || exit;

class ThreeR_Admin_Guard {

    // ── Redirect non-admins away ──────────────────────────────
    public static function require_admin() {
        if ( ! is_user_logged_in() ) {
            wp_redirect( wp_login_url( get_permalink() ) );
            exit;
        }
        if ( ! ThreeR_Portal_Auth::is_admin_user() ) {
            $ids  = get_option( 'tru_page_ids', [] );
            $dest = isset( $ids['3r-portal-dashboard'] )
                  ? get_permalink( $ids['3r-portal-dashboard'] )
                  : home_url('/');
            wp_redirect( $dest );
            exit;
        }
        return true;
    }

    // ── Helper: nonce verify or die ───────────────────────────
    public static function verify_nonce( $nonce, $action ) {
        if ( ! wp_verify_nonce( $nonce, $action ) ) {
            wp_die( 'Security check failed.', 'Error', [ 'back_link' => true ] );
        }
    }
}
