<?php
/**
 * Custom branded login page.
 * Replaces wp-login.php with our own page at /3r-login/
 */
defined( 'ABSPATH' ) || exit;

class ThreeR_Portal_Login {

    public function __construct() {
        // Point all WP login URLs to our custom page
        add_filter( 'login_url',          [ $this, 'custom_login_url' ], 10, 3 );
        add_filter( 'logout_redirect',    [ $this, 'after_logout'     ]        );

        // Handle form submission (runs before headers sent)
        add_action( 'template_redirect',  [ $this, 'handle_login'     ]        );

        // Block direct wp-login.php access for non-admins
        add_action( 'login_init',         [ $this, 'maybe_redirect_wp_login' ] );
    }

    // ── Return our page URL instead of wp-login.php ───────────
    public function custom_login_url( $login_url, $redirect, $force_reauth ) {
        $page = get_page_by_path( '3r-login' );
        if ( ! $page ) return $login_url;
        $url = get_permalink( $page->ID );
        if ( $redirect ) $url = add_query_arg( 'redirect_to', rawurlencode( $redirect ), $url );
        return $url;
    }

    // ── Redirect to home after logout ─────────────────────────
    public function after_logout() {
        return home_url( '/' );
    }

    // ── Process login form submission ─────────────────────────
    public function handle_login() {
        if ( ! is_page( '3r-login' ) ) return;
        if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) return;
        if ( ! isset( $_POST['tru_login_nonce'] ) ) return;
        if ( ! wp_verify_nonce( $_POST['tru_login_nonce'], 'tru_login' ) ) {
            wp_die( 'Security check failed.' );
        }

        $username    = sanitize_user( $_POST['log'] ?? '' );
        $password    = $_POST['pwd'] ?? '';
        $remember    = ! empty( $_POST['rememberme'] );
        $redirect_to = esc_url_raw( $_POST['redirect_to'] ?? '' );

        if ( empty( $username ) || empty( $password ) ) {
            return; // template will show error via GET param trick below
        }

        $user = wp_signon( [
            'user_login'    => $username,
            'user_password' => $password,
            'remember'      => $remember,
        ], is_ssl() );

        if ( is_wp_error( $user ) ) {
            // Redirect back to login page with error code
            $login_page = get_permalink( get_page_by_path( '3r-login' ) );
            $error_code = $user->get_error_code();
            $params = [ 'login_error' => $error_code ];
            if ( $redirect_to ) $params['redirect_to'] = rawurlencode( $redirect_to );
            wp_redirect( add_query_arg( $params, $login_page ) );
            exit;
        }

        // Success — determine redirect destination
        if ( ! $redirect_to ) {
            $ids = get_option( 'tru_page_ids', [] );
            $redirect_to = isset( $ids['3r-portal-dashboard'] )
                ? get_permalink( $ids['3r-portal-dashboard'] )
                : home_url( '/' );
        }

        wp_redirect( $redirect_to );
        exit;
    }

    // ── Redirect wp-login.php to our page for non-admins ──────
    public function maybe_redirect_wp_login() {
        // Allow admin actions through (wp-admin, password reset, etc.)
        $action = $_REQUEST['action'] ?? 'login';
        if ( in_array( $action, [ 'logout', 'lostpassword', 'rp', 'resetpass', 'register', 'postpass' ] ) ) {
            return;
        }
        // If already logged in as admin, let them through to wp-admin
        if ( is_user_logged_in() && current_user_can( 'manage_options' ) ) {
            return;
        }
        // Everyone else → branded login page
        $login_page = get_page_by_path( '3r-login' );
        if ( $login_page ) {
            wp_redirect( get_permalink( $login_page->ID ) );
            exit;
        }
    }

    // ── Helper: human-readable error messages ─────────────────
    public static function error_message( $code ) {
        $messages = [
            'invalid_username'   => 'No account found with that username.',
            'invalid_email'      => 'No account found with that email address.',
            'incorrect_password' => 'Incorrect password. Please try again.',
            'empty_username'     => 'Please enter your username or email.',
            'empty_password'     => 'Please enter your password.',
            ''                   => 'Login failed. Please check your credentials.',
        ];
        return $messages[ $code ] ?? $messages[''];
    }
}
