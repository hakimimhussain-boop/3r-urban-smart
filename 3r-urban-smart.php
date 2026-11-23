<?php
/**
 * Plugin Name:     3R Urban Smart Solutions
 * Plugin URI:      https://3rurban.com
 * Description:     Full 3R Urban Smart Solutions platform — public website + customer portal with live telemetry, alerts, reports, and account management.
 * Version:         2.7.2
 * Author:          3R Urban Smart Solutions
 * Author URI:      https://3rurban.com
 * Text Domain:     3r-urban
 * Requires at least: 5.8
 * Requires PHP:    7.4
 */

defined( 'ABSPATH' ) || exit;

define( 'TRU_VERSION',    '2.7.2' );
define( 'TRU_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'TRU_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// ── Includes ────────────────────────────────────────────────────────────────
require_once TRU_PLUGIN_DIR . 'includes/class-activator.php';
require_once TRU_PLUGIN_DIR . 'includes/class-page-templates.php';
require_once TRU_PLUGIN_DIR . 'includes/class-assets.php';
require_once TRU_PLUGIN_DIR . 'includes/class-ajax.php';
require_once TRU_PLUGIN_DIR . 'includes/class-admin-notice.php';
require_once TRU_PLUGIN_DIR . 'includes/class-meta-fields.php';
require_once TRU_PLUGIN_DIR . 'includes/class-portal-auth.php';
require_once TRU_PLUGIN_DIR . 'includes/class-portal-login.php';
require_once TRU_PLUGIN_DIR . 'includes/class-portal-data.php';
require_once TRU_PLUGIN_DIR . 'includes/class-portal-api.php';
require_once TRU_PLUGIN_DIR . 'includes/class-ingest.php';
require_once TRU_PLUGIN_DIR . 'includes/class-fleet.php';
require_once TRU_PLUGIN_DIR . 'includes/class-reports.php';
require_once TRU_PLUGIN_DIR . 'includes/class-alerts.php';
require_once TRU_PLUGIN_DIR . 'includes/class-admin-guard.php';
require_once TRU_PLUGIN_DIR . 'includes/class-admin-data.php';
require_once TRU_PLUGIN_DIR . 'includes/class-admin-api.php';

// ── Activation / Deactivation ────────────────────────────────────────────────
register_activation_hook(   __FILE__, [ 'ThreeR_Urban_Activator', 'activate'   ] );
register_activation_hook(   __FILE__, [ 'ThreeR_Portal_Auth',      'register_role'] );
register_deactivation_hook( __FILE__, [ 'ThreeR_Urban_Activator', 'deactivate' ] );
register_deactivation_hook( __FILE__, [ 'ThreeR_Portal_Auth',      'remove_role'  ] );

// ── Bootstrap ────────────────────────────────────────────────────────────────
add_action( 'plugins_loaded', function () {
    new ThreeR_Urban_Page_Templates();
    new ThreeR_Urban_Assets();
    new ThreeR_Urban_Ajax();
    new ThreeR_Urban_Admin_Notice();
    new ThreeR_Urban_Meta_Fields();
    new ThreeR_Portal_Auth();
    new ThreeR_Portal_Login();
    new ThreeR_Portal_API();
    new ThreeR_Ingest();
    new ThreeR_Fleet();
    new ThreeR_Reports();
    new ThreeR_Alerts();

    // Never let LiteSpeed/page caches store our live REST data (stale history hid new readings).
    add_filter( 'rest_post_dispatch', function ( $result, $server, $request ) {
        if ( strpos( $request->get_route(), '/3r/v1' ) === 0 ) {
            if ( ! headers_sent() ) {
                header( 'X-LiteSpeed-Cache-Control: no-cache, no-store' );
                header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
            }
            do_action( 'litespeed_control_set_nocache', '3R live API' );
        }
        return $result;
    }, 10, 3 );

    // Create any new pages (e.g. Live Panel) after a plugin update without re-activation.
    if ( get_option( 'tru_version' ) !== TRU_VERSION ) {
        add_action( 'init', function () {
            ThreeR_Urban_Activator::create_pages();
            ThreeR_Ingest::install();
            ThreeR_Fleet::install();
            update_option( 'tru_version', TRU_VERSION );
        }, 20 );
    }
} );
