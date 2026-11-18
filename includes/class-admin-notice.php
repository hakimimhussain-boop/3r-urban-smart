<?php
defined( 'ABSPATH' ) || exit;

class ThreeR_Urban_Admin_Notice {

    public function __construct() {
        add_action( 'admin_notices', [ $this, 'activation_notice' ] );
        add_action( 'wp_ajax_tru_dismiss_notice', [ $this, 'dismiss_notice' ] );
    }

    public function activation_notice() {
        if ( get_option( 'tru_notice_dismissed' ) ) return;
        if ( ! current_user_can( 'manage_options' ) ) return;

        $page_ids  = get_option( 'tru_page_ids', [] );
        $home_url  = isset( $page_ids['3r-home'] )    ? get_permalink( $page_ids['3r-home'] )    : '';
        $demo_url  = isset( $page_ids['3r-demo'] )    ? get_edit_post_link( $page_ids['3r-demo'] ) : '';
        $about_url = isset( $page_ids['3r-about'] )   ? get_permalink( $page_ids['3r-about'] )   : '';
        ?>
        <div class="notice notice-success" id="3r-urban-notice" style="padding:16px 20px;border-left-color:#5dd862">
            <p style="font-size:15px;font-weight:600;margin-bottom:6px">
                🌱 3R Urban Smart Solutions — Website Activated!
            </p>
            <p style="margin-bottom:10px;color:#555">
                Your public website pages have been created and set as the front page.
                <a href="<?php echo esc_url( $home_url ); ?>" target="_blank"><strong>View homepage →</strong></a>
            </p>
            <ul style="list-style:disc;padding-left:20px;color:#555;margin-bottom:12px">
                <?php foreach ( $page_ids as $slug => $id ) : ?>
                    <li>
                        <a href="<?php echo esc_url( get_permalink( $id ) ); ?>" target="_blank">
                            <?php echo esc_html( get_the_title( $id ) ); ?>
                        </a>
                        — <a href="<?php echo esc_url( get_edit_post_link( $id ) ); ?>">Edit</a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p style="color:#888;font-size:13px">
                💡 Tip: Go to <strong>Appearance → Menus</strong> to confirm "3R Urban Main Menu" is assigned to your primary location.
            </p>
            <a href="#" onclick="jQuery('#3r-urban-notice').fadeOut();jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>',{action:'tru_dismiss_notice',nonce:'<?php echo wp_create_nonce('tru_dismiss'); ?>'});return false;"
               style="position:absolute;top:12px;right:16px;text-decoration:none;color:#999;font-size:18px">✕</a>
        </div>
        <?php
    }

    public function dismiss_notice() {
        check_ajax_referer( 'tru_dismiss', 'nonce' );
        update_option( 'tru_notice_dismissed', true );
        wp_die();
    }
}
