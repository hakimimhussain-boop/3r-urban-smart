<?php
defined( 'ABSPATH' ) || exit;

class ThreeR_Urban_Ajax {

    public function __construct() {
        add_action( 'wp_ajax_nopriv_tru_submit_demo',    [ $this, 'handle_demo'    ] );
        add_action( 'wp_ajax_tru_submit_demo',           [ $this, 'handle_demo'    ] );
        add_action( 'wp_ajax_nopriv_tru_submit_contact', [ $this, 'handle_contact' ] );
        add_action( 'wp_ajax_tru_submit_contact',        [ $this, 'handle_contact' ] );
    }

    // ── Demo form ─────────────────────────────────────────────────────────────
    public function handle_demo() {
        check_ajax_referer( 'tru_nonce', 'nonce' );

        $first_name = sanitize_text_field( $_POST['first_name'] ?? '' );
        $last_name  = sanitize_text_field( $_POST['last_name']  ?? '' );
        $email      = sanitize_email(      $_POST['email']      ?? '' );
        $org        = sanitize_text_field( $_POST['organisation']?? '' );
        $phone      = sanitize_text_field( $_POST['phone']      ?? '' );
        $machines   = sanitize_text_field( $_POST['machines']   ?? '' );
        $demo_date  = sanitize_text_field( $_POST['demo_date']  ?? '' );
        $notes      = sanitize_textarea_field( $_POST['notes']  ?? '' );

        if ( empty( $email ) || ! is_email( $email ) ) {
            wp_send_json_error( [ 'message' => 'Please enter a valid email address.' ] );
        }

        $to      = get_option( 'admin_email' );
        $subject = '[3R Urban] New Demo Request — ' . $first_name . ' ' . $last_name;
        $body    = "New demo request received:\n\n"
                 . "Name:          {$first_name} {$last_name}\n"
                 . "Email:         {$email}\n"
                 . "Organisation:  {$org}\n"
                 . "Phone:         {$phone}\n"
                 . "Machines:      {$machines}\n"
                 . "Preferred Date:{$demo_date}\n"
                 . "Notes:\n{$notes}\n";

        wp_mail( $to, $subject, $body );

        // Auto-reply to requester
        $reply_subject = 'Thanks for requesting a 3R Urban demo!';
        $reply_body    = "Hi {$first_name},\n\nThank you for your interest in 3R Urban Smart Solutions.\n\n"
                       . "We've received your demo request and will contact you within 24 hours to schedule your personalised walkthrough.\n\n"
                       . "Best regards,\nThe 3R Urban Team\ninfo@3rurban.com";
        wp_mail( $email, $reply_subject, $reply_body );

        wp_send_json_success( [ 'message' => 'Demo request submitted successfully.' ] );
    }

    // ── Contact form ──────────────────────────────────────────────────────────
    public function handle_contact() {
        check_ajax_referer( 'tru_nonce', 'nonce' );

        $name    = sanitize_text_field(     $_POST['name']    ?? '' );
        $email   = sanitize_email(          $_POST['email']   ?? '' );
        $org     = sanitize_text_field(     $_POST['org']     ?? '' );
        $subject = sanitize_text_field(     $_POST['subject'] ?? '' );
        $message = sanitize_textarea_field( $_POST['message'] ?? '' );

        if ( empty( $email ) || ! is_email( $email ) ) {
            wp_send_json_error( [ 'message' => 'Please enter a valid email address.' ] );
        }

        $to   = get_option( 'admin_email' );
        $subj = '[3R Urban] Contact: ' . $subject . ' — ' . $name;
        $body = "New contact form submission:\n\n"
              . "Name:         {$name}\n"
              . "Email:        {$email}\n"
              . "Organisation: {$org}\n"
              . "Subject:      {$subject}\n\n"
              . "Message:\n{$message}";

        wp_mail( $to, $subj, $body );

        wp_send_json_success( [ 'message' => 'Message sent successfully.' ] );
    }
}
