<?php
/**
 * GoFonIA Booking Calendar for Meet.bot – Shortcode & Booking Handler
 *
 * Copyright (C) 2026 GoFonIA – https://gofonia.de
 * Licensed under GNU GPL v2 or later (GPL-2.0-or-later)
 * https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class MeetBot_Shortcode {

    public function __construct() {
        add_shortcode( 'meetbot_calendar', array( $this, 'render' ) );
        add_action( 'wp_ajax_meetbot_get_slots', array( $this, 'ajax_get_slots' ) );
        add_action( 'wp_ajax_nopriv_meetbot_get_slots', array( $this, 'ajax_get_slots' ) );
        add_action( 'wp_ajax_meetbot_book', array( $this, 'ajax_book' ) );
        add_action( 'wp_ajax_nopriv_meetbot_book', array( $this, 'ajax_book' ) );
    }

    public function ajax_get_slots() {
        check_ajax_referer( 'meetbot_cal_nonce', 'nonce' );
        $page_url = esc_url_raw( $_POST['page_url'] ?? '' );
        if ( ! $page_url ) wp_send_json_error( 'No page.' );
        $api = new MeetBot_API();
        $slots = $api->get_slots( $page_url, 50 );
        if ( isset( $slots['error'] ) ) wp_send_json_error( $slots );
        wp_send_json_success( $slots );
    }

    public function ajax_book() {
        check_ajax_referer( 'meetbot_cal_nonce', 'nonce' );

        $page_url = esc_url_raw( $_POST['page_url'] ?? '' );
        $start    = sanitize_text_field( $_POST['start'] ?? '' );
        $name     = sanitize_text_field( $_POST['guest_name'] ?? '' );
        $email    = sanitize_email( $_POST['guest_email'] ?? '' );
        $notes    = sanitize_textarea_field( $_POST['notes'] ?? '' );

        if ( ! $page_url || ! $start || ! $name || ! $email ) {
            wp_send_json_error( esc_html__( 'Please fill in all required fields.', 'meetbot-calendar' ) );
        }

        $api    = new MeetBot_API();
        $result = $api->book( $page_url, $start, $name, $email, $notes );

        if ( isset( $result['error'] ) ) {
            wp_send_json_error( $result );
        }

        $meet_link = $api->extract_meeting_url( $result );

        $google_meet = get_option( 'meetbot_cal_google_meet', '1' );
        if ( ! $meet_link && $google_meet ) {
            $meet_link = $api->find_meet_link( $start );
        }

        if ( get_option( 'meetbot_cal_custom_email', '1' ) ) {
            $this->send_custom_email( $name, $email, $start, $notes, $meet_link );
        }

        $admin_email = get_option( 'meetbot_cal_admin_email', '' );
        if ( $admin_email ) {
            $this->send_admin_notification( $admin_email, $name, $email, $start, $notes, $meet_link );
        }

        wp_send_json_success( array(
            'ical_uid'  => $result['ical_uid'] ?? '',
            'meet_link' => $meet_link,
        ) );
    }

    private function format_email_body( $template, $name, $start, $duration, $notes, $meet_link ) {
        $dt   = new DateTime( $start );
        $days = array( 'Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday' );
        $mons = array( 'January','February','March','April','May','June','July','August','September','October','November','December' );

        $datum    = $days[ (int) $dt->format( 'w' ) ] . ', ' . $mons[ (int) $dt->format( 'n' ) - 1 ] . ' ' . $dt->format( 'd' ) . ', ' . $dt->format( 'Y' );
        $uhrzeit  = $dt->format( 'H:i' );

        $meet_html = '';
        if ( $meet_link ) {
            $meet_html = '<p style="margin:12px 0;padding:12px;background:#f0f7ff;border:1px solid #b3d4fc;border-radius:6px;text-align:center;"><a href="' . esc_url( $meet_link ) . '" style="color:#014786;font-weight:600;font-size:15px;text-decoration:none;">' . esc_html__( 'Join Video Meeting', 'meetbot-calendar' ) . '</a><br><span style="font-size:11px;color:#6b7280;margin-top:4px;display:inline-block;">Google Meet</span></p>';
        }

        $replacements = array(
            '{name}'      => esc_html( $name ),
            '{datum}'     => $datum,
            '{uhrzeit}'   => $uhrzeit,
            '{dauer}'     => $duration . ' min',
            '{meet_link}' => $meet_html,
            '{notizen}'   => $notes ? '<p><em>' . esc_html( $notes ) . '</em></p>' : '',
            '{seite}'     => esc_html( get_bloginfo( 'name' ) ),
        );

        return str_replace( array_keys( $replacements ), array_values( $replacements ), $template );
    }

    private function send_custom_email( $name, $email, $start, $notes, $meet_link ) {
        $duration = get_option( 'meetbot_cal_duration', 30 );
        $subject  = get_option( 'meetbot_cal_email_subject', 'Appointment confirmation' );
        $body     = get_option( 'meetbot_cal_email_body', '' );
        $from     = get_option( 'meetbot_cal_email_from', get_option( 'admin_email', '' ) );
        $fromname = get_option( 'meetbot_cal_email_from_name', get_bloginfo( 'name' ) );

        $dt = new DateTime( $start );
        $subject = str_replace( array( '{name}', '{datum}', '{uhrzeit}' ), array( $name, $dt->format( 'd.m.Y' ), $dt->format( 'H:i' ) ), $subject );

        $html = $this->format_email_body( $body, $name, $start, $duration, $notes, $meet_link );
        $full_html = '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body style="font-family:-apple-system,BlinkMacSystemFont,sans-serif;font-size:14px;color:#333;max-width:600px;margin:0 auto;padding:20px;">' . $html . '</body></html>';

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $fromname . ' <' . $from . '>',
        );

        wp_mail( $email, $subject, $full_html, $headers );
    }

    private function send_admin_notification( $admin_email, $name, $email, $start, $notes, $meet_link ) {
        $duration = get_option( 'meetbot_cal_duration', 30 );
        $dt = new DateTime( $start );
        $days = array( 'Sun','Mon','Tue','Wed','Thu','Fri','Sat' );

        $subject = 'New booking: ' . $name . ' - ' . $days[ (int) $dt->format( 'w' ) ] . ' ' . $dt->format( 'd.m. H:i' );
        $body = '<p><strong>New booking:</strong></p>';
        $body .= '<p><strong>' . esc_html( $name ) . '</strong> (' . esc_html( $email ) . ')</p>';
        $body .= '<p>' . esc_html( $dt->format( 'd.m.Y H:i' ) ) . ' (' . esc_html( $duration ) . ' min)</p>';
        if ( $notes ) $body .= '<p>Notes: ' . esc_html( $notes ) . '</p>';
        if ( $meet_link ) $body .= '<p><a href="' . esc_url( $meet_link ) . '">' . esc_url( $meet_link ) . '</a></p>';

        $headers = array( 'Content-Type: text/html; charset=UTF-8' );
        wp_mail( $admin_email, $subject, $body, $headers );
    }

    public function render( $atts ) {
        $atts = shortcode_atts( array( 'lang' => substr( get_locale(), 0, 2 ) ), $atts, 'meetbot_calendar' );

        $api = new MeetBot_API();
        if ( ! $api->is_configured() ) {
            return '<p style="color:#c00;padding:20px;">' . esc_html__( 'Booking calendar not configured.', 'meetbot-calendar' ) . '</p>';
        }
        $page_url = get_option( 'meetbot_cal_page_url', '' );
        if ( ! $page_url ) {
            return '<p style="color:#c00;padding:20px;">' . esc_html__( 'No booking page selected.', 'meetbot-calendar' ) . '</p>';
        }

        $duration    = get_option( 'meetbot_cal_duration', 30 );
        $google_meet = get_option( 'meetbot_cal_google_meet', '1' );

        ob_start();
        ?>
        <div id="meetbot-app" class="gomeetme" data-page="<?php echo esc_url( $page_url ); ?>" data-duration="<?php echo esc_attr( $duration ); ?>" data-google-meet="<?php echo esc_attr( $google_meet ); ?>">

            <div class="caldav-steps">
                <div class="caldav-step active" data-step="1">
                    <span class="caldav-step-num">1</span>
                    <span class="caldav-step-label"><?php esc_html_e( 'Date & Time', 'meetbot-calendar' ); ?></span>
                </div>
                <div class="caldav-step-line"></div>
                <div class="caldav-step" data-step="2">
                    <span class="caldav-step-num">2</span>
                    <span class="caldav-step-label"><?php esc_html_e( 'Contact Details', 'meetbot-calendar' ); ?></span>
                </div>
                <div class="caldav-step-line"></div>
                <div class="caldav-step" data-step="3">
                    <span class="caldav-step-num">3</span>
                    <span class="caldav-step-label"><?php esc_html_e( 'Confirmation', 'meetbot-calendar' ); ?></span>
                </div>
            </div>

            <div id="meetbot-step-1" class="caldav-step-content active">
                <h3 class="caldav-step-title"><?php esc_html_e( 'Select Date & Time', 'meetbot-calendar' ); ?></h3>
                <div class="caldav-step2-layout">
                    <div class="caldav-calendar-col">
                        <div class="caldav-calendar-nav">
                            <button type="button" id="meetbot-cal-prev" class="caldav-cal-nav">&lsaquo;</button>
                            <span id="meetbot-cal-month"></span>
                            <button type="button" id="meetbot-cal-next" class="caldav-cal-nav">&rsaquo;</button>
                        </div>
                        <div id="meetbot-calendar-grid" class="caldav-calendar-grid"></div>
                    </div>
                    <div class="caldav-slots-col">
                        <div id="meetbot-slots-container" class="caldav-slots-container">
                            <h4 id="meetbot-slots-title"></h4>
                            <button type="button" class="caldav-scroll-btn caldav-scroll-btn-up" id="meetbot-scroll-up">&uarr;</button>
                            <div id="meetbot-slots-list" class="caldav-slots-list"></div>
                            <button type="button" class="caldav-scroll-btn caldav-scroll-btn-down" id="meetbot-scroll-down">&darr;</button>
                        </div>
                    </div>
                </div>
            </div>

            <div id="meetbot-step-2" class="caldav-step-content">
                <h3 class="caldav-step-title"><?php esc_html_e( 'Your Contact Details', 'meetbot-calendar' ); ?></h3>
                <button type="button" class="caldav-back-btn" data-target="1">&larr; <?php esc_html_e( 'Back', 'meetbot-calendar' ); ?></button>
                <div id="meetbot-summary" class="caldav-summary"></div>
                <form id="meetbot-form" class="caldav-form" novalidate>
                    <div class="caldav-form-row">
                        <div class="caldav-form-group">
                            <label for="meetbot-name"><?php esc_html_e( 'Name', 'meetbot-calendar' ); ?> *</label>
                            <input type="text" id="meetbot-name" name="guest_name" required />
                        </div>
                        <div class="caldav-form-group">
                            <label for="meetbot-email"><?php esc_html_e( 'Email', 'meetbot-calendar' ); ?> *</label>
                            <input type="email" id="meetbot-email" name="guest_email" required />
                        </div>
                    </div>
                    <div class="caldav-form-group">
                        <label for="meetbot-notes"><?php esc_html_e( 'Notes', 'meetbot-calendar' ); ?></label>
                        <textarea id="meetbot-notes" name="notes" rows="2"></textarea>
                    </div>
                    <button type="submit" class="caldav-submit-btn" id="meetbot-submit"><?php esc_html_e( 'Confirm Appointment', 'meetbot-calendar' ); ?></button>
                </form>
            </div>

            <div id="meetbot-step-3" class="caldav-step-content">
                <div class="caldav-success">
                    <div class="caldav-success-icon">&#10003;</div>
                    <h3><?php esc_html_e( 'Appointment Booked!', 'meetbot-calendar' ); ?></h3>
                    <p id="meetbot-success-detail"></p>
                    <div id="meetbot-meet-box" style="display:none;margin-top:12px;background:#f0f7ff;border:1px solid #b3d4fc;border-radius:6px;padding:12px;text-align:center;">
                        <div style="font-size:12px;color:#014786;font-weight:600;margin-bottom:6px;"><?php esc_html_e( 'Google Meet Video Meeting', 'meetbot-calendar' ); ?></div>
                        <a id="meetbot-meet-link" href="#" target="_blank" rel="noopener" style="color:#00a3e0;font-weight:600;font-size:14px;"></a>
                        <div style="font-size:11px;color:#6b7280;margin-top:4px;"><?php esc_html_e( 'Link also sent by email', 'meetbot-calendar' ); ?></div>
                    </div>
                    <p style="margin-top:12px;font-size:12px;color:#6b7280;"><?php esc_html_e( 'A confirmation email has been sent to you.', 'meetbot-calendar' ); ?></p>
                </div>
            </div>

            <div style="text-align:center;padding:10px;font-size:10px;color:#aaa;border-top:1px solid #eee;margin-top:16px;">
                <?php esc_html_e( 'Powered by', 'meetbot-calendar' ); ?>
                <a href="https://gofonia.de" target="_blank" rel="noopener" style="color:#00a3e0;text-decoration:none;font-weight:600;">GoFonIA</a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

new MeetBot_Shortcode();
