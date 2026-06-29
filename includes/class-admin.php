<?php
/**
 * GoFonIA Booking Calendar for Meet.bot – Admin Settings
 *
 * Copyright (C) 2026 GoFonIA – https://gofonia.de
 * Licensed under GNU GPL v2 or later (GPL-2.0-or-later)
 * https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class MeetBot_Admin {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        add_action( 'wp_ajax_meetbot_cal_test', array( $this, 'ajax_test' ) );
        add_action( 'wp_ajax_meetbot_cal_fetch_pages', array( $this, 'ajax_fetch_pages' ) );
        add_action( 'wp_ajax_meetbot_cal_configure_meet', array( $this, 'ajax_configure_meet' ) );
    }

    public function add_menu() {
        add_options_page(
            esc_html__( 'GoFonIA Booking Calendar', 'meetbot-calendar' ),
            esc_html__( 'GoFonIA Booking Calendar', 'meetbot-calendar' ),
            'manage_options',
            'meetbot-calendar',
            array( $this, 'render_page' )
        );
    }

    public function register_settings() {
        $fields = array(
            'meetbot_cal_api_key'          => 'sanitize_text_field',
            'meetbot_cal_page_url'         => 'esc_url_raw',
            'meetbot_cal_duration'         => 'absint',
            'meetbot_cal_google_meet'      => 'absint',
            'meetbot_cal_suppress_email'   => 'absint',
            'meetbot_cal_custom_email'     => 'absint',
            'meetbot_cal_email_from'       => 'sanitize_email',
            'meetbot_cal_email_from_name'  => 'sanitize_text_field',
            'meetbot_cal_email_subject'    => 'sanitize_text_field',
            'meetbot_cal_email_body'       => 'wp_kses_post',
            'meetbot_cal_admin_email'      => 'sanitize_email',
        );
        foreach ( $fields as $name => $cb ) {
            register_setting( 'meetbot_cal_settings', $name, array(
                'type'              => 'string',
                'sanitize_callback' => $cb,
            ) );
        }
    }

    public function enqueue_admin_scripts( $hook ) {
        if ( $hook !== 'settings_page_meetbot-calendar' ) return;
        wp_enqueue_script( 'meetbot-cal-admin', MEETBOT_CAL_PLUGIN_URL . 'assets/admin-settings.js', array( 'jquery' ), MEETBOT_CAL_VERSION, true );
        wp_localize_script( 'meetbot-cal-admin', 'meetbotCalAdmin', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'meetbot_cal_nonce' ),
        ) );
    }

    public function ajax_test() {
        check_ajax_referer( 'meetbot_cal_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );
        $api_key = sanitize_text_field( $_POST['api_key'] ?? '' );
        if ( $api_key ) update_option( 'meetbot_cal_api_key', $api_key );
        $api = new MeetBot_API( $api_key );
        $result = $api->test_connection();
        if ( $result['success'] ) wp_send_json_success( $result );
        else wp_send_json_error( $result );
    }

    public function ajax_fetch_pages() {
        check_ajax_referer( 'meetbot_cal_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );
        $api = new MeetBot_API();
        $result = $api->get_pages();
        if ( isset( $result['error'] ) ) wp_send_json_error( $result );
        wp_send_json_success( $result );
    }

    public function ajax_configure_meet() {
        check_ajax_referer( 'meetbot_cal_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );
        $page_url = esc_url_raw( $_POST['page_url'] ?? '' );
        $enable   = absint( $_POST['enable'] ?? 1 );
        if ( ! $page_url ) wp_send_json_error( 'No page selected.' );
        $api = new MeetBot_API();
        $result = $api->update_page_config( $page_url, array(
            'web_conferencing_type' => $enable ? 'create' : 'none',
        ) );
        if ( isset( $result['error'] ) ) {
            wp_send_json_error( array( 'message' => $result['error'] ) );
        }
        wp_send_json_success( array(
            'message' => $enable
                ? 'Google Meet enabled. Meet.bot will auto-create Meet links for new bookings.'
                : 'Google Meet disabled.',
            'config'  => $result,
        ) );
    }

    public function render_page() {
        $api_key          = get_option( 'meetbot_cal_api_key', '' );
        $page_url         = get_option( 'meetbot_cal_page_url', '' );
        $duration         = get_option( 'meetbot_cal_duration', 30 );
        $google_meet      = get_option( 'meetbot_cal_google_meet', '1' );
        $suppress_email   = get_option( 'meetbot_cal_suppress_email', '0' );
        $custom_email     = get_option( 'meetbot_cal_custom_email', '1' );
        $email_from       = get_option( 'meetbot_cal_email_from', get_option( 'admin_email', '' ) );
        $email_from_name  = get_option( 'meetbot_cal_email_from_name', get_bloginfo( 'name' ) );
        $email_subject    = get_option( 'meetbot_cal_email_subject', 'Appointment confirmation: {datum} {uhrzeit}' );
        $email_body       = get_option( 'meetbot_cal_email_body', "<p>Hello {name},</p>\n<p>Your appointment has been confirmed:</p>\n<p><strong>{datum}</strong> at <strong>{uhrzeit}</strong> ({dauer} min)</p>\n{meet_link}\n<p>Best regards,<br>" . get_bloginfo( 'name' ) . "</p>" );
        $admin_email      = get_option( 'meetbot_cal_admin_email', get_option( 'admin_email', '' ) );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'GoFonIA Booking Calendar Settings', 'meetbot-calendar' ); ?></h1>
            <div id="meetbot-notice" style="display:none;margin:10px 0;"></div>

            <form method="post" action="options.php">
                <?php settings_fields( 'meetbot_cal_settings' ); ?>

                <h2><?php esc_html_e( 'Meet.bot Connection', 'meetbot-calendar' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><label for="meetbot_cal_api_key"><?php esc_html_e( 'API Key', 'meetbot-calendar' ); ?></label></th>
                        <td>
                            <input type="text" id="meetbot_cal_api_key" name="meetbot_cal_api_key" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text" />
                            <button type="button" class="button" id="meetbot-test-btn"><?php esc_html_e( 'Connect', 'meetbot-calendar' ); ?></button>
                            <p class="description"><a href="https://meet.bot/dashboard/settings" target="_blank" rel="noopener"><?php esc_html_e( 'meet.bot Dashboard', 'meetbot-calendar' ); ?> &rarr;</a></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="meetbot_cal_page_url"><?php esc_html_e( 'Booking Page', 'meetbot-calendar' ); ?></label></th>
                        <td>
                            <select id="meetbot_cal_page_url" name="meetbot_cal_page_url" style="min-width:400px;">
                                <option value=""><?php esc_html_e( '— Save API Key & load pages —', 'meetbot-calendar' ); ?></option>
                            </select>
                            <button type="button" class="button" id="meetbot-fetch-pages"><?php esc_html_e( 'Refresh', 'meetbot-calendar' ); ?></button>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="meetbot_cal_duration"><?php esc_html_e( 'Duration', 'meetbot-calendar' ); ?></label></th>
                        <td>
                            <select id="meetbot_cal_duration" name="meetbot_cal_duration">
                                <option value="15" <?php selected( $duration, 15 ); ?>>15 min</option>
                                <option value="30" <?php selected( $duration, 30 ); ?>>30 min</option>
                                <option value="45" <?php selected( $duration, 45 ); ?>>45 min</option>
                                <option value="60" <?php selected( $duration, 60 ); ?>>60 min</option>
                            </select>
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e( 'Video Meeting (Google Meet)', 'meetbot-calendar' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e( 'Google Meet', 'meetbot-calendar' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="meetbot_cal_google_meet" value="1" <?php checked( $google_meet, '1' ); ?> />
                                <?php esc_html_e( 'Auto-create Google Meet links', 'meetbot-calendar' ); ?>
                            </label>
                            <p class="description"><?php esc_html_e( 'Meet.bot will automatically create a Google Meet link for each booking. The link appears in the calendar event and in your confirmation email.', 'meetbot-calendar' ); ?></p>
                            <button type="button" class="button button-secondary" id="meetbot-configure-meet" <?php echo ! $page_url ? 'disabled' : ''; ?>><?php esc_html_e( 'Configure Meet.bot page', 'meetbot-calendar' ); ?></button>
                            <span id="meetbot-meet-status" style="margin-left:8px;font-size:12px;"></span>
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e( 'Email Settings', 'meetbot-calendar' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e( 'Meet.bot Emails', 'meetbot-calendar' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="meetbot_cal_suppress_email" value="1" <?php checked( $suppress_email, '1' ); ?> />
                                <?php esc_html_e( 'Suppress Meet.bot confirmation emails', 'meetbot-calendar' ); ?>
                            </label>
                            <p class="description"><?php esc_html_e( 'Guests will only receive YOUR email (below). Meet.bot calendar invite with Google Meet link is still sent.', 'meetbot-calendar' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Custom Email', 'meetbot-calendar' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="meetbot_cal_custom_email" value="1" <?php checked( $custom_email, '1' ); ?> />
                                <?php esc_html_e( 'Send confirmation email via WordPress', 'meetbot-calendar' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e( 'Sender', 'meetbot-calendar' ); ?></label></th>
                        <td>
                            <input type="text" name="meetbot_cal_email_from_name" value="<?php echo esc_attr( $email_from_name ); ?>" placeholder="Company Name" style="width:200px;" />
                            &lt;<input type="email" name="meetbot_cal_email_from" value="<?php echo esc_attr( $email_from ); ?>" placeholder="noreply@example.com" style="width:250px;" />&gt;
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e( 'Subject', 'meetbot-calendar' ); ?></label></th>
                        <td>
                            <input type="text" name="meetbot_cal_email_subject" value="<?php echo esc_attr( $email_subject ); ?>" class="large-text" />
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e( 'Email Body (HTML)', 'meetbot-calendar' ); ?></label></th>
                        <td>
                            <?php wp_editor( $email_body, 'meetbot_cal_email_body', array( 'textarea_rows' => 12, 'media_buttons' => false, 'teeny' => true ) ); ?>
                            <p class="description">
                                <strong><?php esc_html_e( 'Placeholders:', 'meetbot-calendar' ); ?></strong>
                                <code>{name}</code> <code>{datum}</code> <code>{uhrzeit}</code> <code>{dauer}</code> <code>{meet_link}</code> <code>{notizen}</code> <code>{seite}</code>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e( 'Admin Copy', 'meetbot-calendar' ); ?></label></th>
                        <td>
                            <input type="email" name="meetbot_cal_admin_email" value="<?php echo esc_attr( $admin_email ); ?>" class="regular-text" />
                            <p class="description"><?php esc_html_e( 'Leave empty for no admin copy.', 'meetbot-calendar' ); ?></p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>

            <hr/>
            <h3><?php esc_html_e( 'Shortcode', 'meetbot-calendar' ); ?></h3>
            <code>[meetbot_calendar]</code> &mdash; <code>[meetbot_calendar lang="en"]</code>

            <hr/>
            <p style="color:#999;font-size:12px;">
                <?php echo wp_kses_post( sprintf(
                    __( 'GoFonIA Booking Calendar v%s by <a href="%s" target="_blank" rel="noopener">GoFonIA</a> &middot; <a href="%s" target="_blank" rel="noopener">GitHub</a>', 'meetbot-calendar' ),
                    MEETBOT_CAL_VERSION,
                    'https://gofonia.de',
                    'https://github.com/livedialai/meetbot-calendar'
                ) ); ?>
            </p>
        </div>
        <?php
    }
}

new MeetBot_Admin();
