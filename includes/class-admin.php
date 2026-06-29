<?php
/**
 * MeetBot Calendar – Admin Settings
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
        add_action( 'wp_ajax_meetbot_cal_test', array( $this, 'ajax_test' ) );
        add_action( 'wp_ajax_meetbot_cal_fetch_pages', array( $this, 'ajax_fetch_pages' ) );
        add_action( 'wp_ajax_meetbot_cal_configure_meet', array( $this, 'ajax_configure_meet' ) );
    }

    public function add_menu() {
        add_options_page(
            __( 'MeetBot Kalender', 'meetbot-calendar' ),
            __( 'MeetBot Kalender', 'meetbot-calendar' ),
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
            'meetbot_cal_google_meet'      => 'sanitize_text_field',
            'meetbot_cal_suppress_email'   => 'sanitize_text_field',
            'meetbot_cal_custom_email'     => 'sanitize_text_field',
            'meetbot_cal_email_from'       => 'sanitize_email',
            'meetbot_cal_email_from_name'  => 'sanitize_text_field',
            'meetbot_cal_email_subject'    => 'sanitize_text_field',
            'meetbot_cal_email_body'       => 'wp_kses_post',
            'meetbot_cal_admin_email'      => 'sanitize_email',
        );
        foreach ( $fields as $name => $cb ) {
            register_setting( 'meetbot_cal_settings', $name, array( 'sanitize_callback' => $cb ) );
        }
    }

    public function ajax_test() {
        check_ajax_referer( 'meetbot_cal_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Keine Berechtigung.' );
        $api_key = sanitize_text_field( $_POST['api_key'] ?? '' );
        if ( $api_key ) update_option( 'meetbot_cal_api_key', $api_key );
        $api = new MeetBot_API( $api_key );
        $result = $api->test_connection();
        if ( $result['success'] ) wp_send_json_success( $result );
        else wp_send_json_error( $result );
    }

    public function ajax_fetch_pages() {
        check_ajax_referer( 'meetbot_cal_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Keine Berechtigung.' );
        $api = new MeetBot_API();
        $result = $api->get_pages();
        if ( isset( $result['error'] ) ) wp_send_json_error( $result );
        wp_send_json_success( $result );
    }

    /**
     * AJAX: Configure Meet.bot page for Google Meet
     */
    public function ajax_configure_meet() {
        check_ajax_referer( 'meetbot_cal_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Keine Berechtigung.' );

        $page_url = esc_url_raw( $_POST['page_url'] ?? '' );
        $enable   = sanitize_text_field( $_POST['enable'] ?? '1' );

        if ( ! $page_url ) wp_send_json_error( 'Keine Seite gewählt.' );

        $api = new MeetBot_API();

        if ( $enable === '1' ) {
            $result = $api->update_page_config( $page_url, array(
                'web_conferencing_type' => 'create',
            ) );
        } else {
            $result = $api->update_page_config( $page_url, array(
                'web_conferencing_type' => 'none',
            ) );
        }

        if ( isset( $result['error'] ) ) {
            wp_send_json_error( array( 'message' => $result['error'] ) );
        }

        wp_send_json_success( array(
            'message' => $enable === '1'
                ? 'Google Meet aktiviert! Meet.bot erstellt automatisch Meet-Links für neue Buchungen.'
                : 'Google Meet deaktiviert.',
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
        $email_subject    = get_option( 'meetbot_cal_email_subject', 'Terminbestätigung: {datum} {uhrzeit}' );
        $email_body       = get_option( 'meetbot_cal_email_body', "<p>Hallo {name},</p>\n<p>Ihr Termin wurde bestätigt:</p>\n<p><strong>{datum}</strong> um <strong>{uhrzeit}</strong> Uhr ({dauer} Min)</p>\n{meet_link}\n<p>Mit freundlichen Grüßen,<br>" . get_bloginfo( 'name' ) . "</p>" );
        $admin_email      = get_option( 'meetbot_cal_admin_email', get_option( 'admin_email', '' ) );
        $nonce            = wp_create_nonce( 'meetbot_cal_nonce' );
        $ajax_url         = admin_url( 'admin-ajax.php' );
        ?>
        <div class="wrap">
            <h1><?php _e( 'MeetBot Kalender Einstellungen', 'meetbot-calendar' ); ?></h1>
            <div id="meetbot-notice" style="display:none;margin:10px 0;"></div>

            <form method="post" action="options.php">
                <?php settings_fields( 'meetbot_cal_settings' ); ?>

                <h2><?php _e( '🔌 Meet.bot Verbindung', 'meetbot-calendar' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><label for="meetbot_cal_api_key"><?php _e( 'API-Schlüssel', 'meetbot-calendar' ); ?></label></th>
                        <td>
                            <input type="text" id="meetbot_cal_api_key" name="meetbot_cal_api_key" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text" />
                            <button type="button" class="button" id="meetbot-test-btn"><?php _e( 'Verbinden', 'meetbot-calendar' ); ?></button>
                            <p class="description"><a href="https://meet.bot/dashboard/settings" target="_blank">meet.bot Dashboard →</a></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="meetbot_cal_page_url"><?php _e( 'Buchungsseite', 'meetbot-calendar' ); ?></label></th>
                        <td>
                            <select id="meetbot_cal_page_url" name="meetbot_cal_page_url" style="min-width:400px;">
                                <option value=""><?php _e( '— API-Key speichern & Seiten laden —', 'meetbot-calendar' ); ?></option>
                            </select>
                            <button type="button" class="button" id="meetbot-fetch-pages"><?php _e( 'Aktualisieren', 'meetbot-calendar' ); ?></button>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="meetbot_cal_duration"><?php _e( 'Dauer', 'meetbot-calendar' ); ?></label></th>
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

                <h2><?php _e( '📹 Video-Meeting (Google Meet)', 'meetbot-calendar' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php _e( 'Google Meet', 'meetbot-calendar' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="meetbot_cal_google_meet" value="1" <?php checked( $google_meet, '1' ); ?> />
                                <?php _e( 'Google Meet automatisch erstellen', 'meetbot-calendar' ); ?>
                            </label>
                            <p class="description">
                                <?php _e( 'Meet.bot erstellt automatisch einen Google Meet Link für jede Buchung. Der Link erscheint im Kalender-Event und in Ihrer Bestätigungs-E-Mail.', 'meetbot-calendar' ); ?>
                            </p>
                            <button type="button" class="button button-secondary" id="meetbot-configure-meet" <?php echo ! $page_url ? 'disabled' : ''; ?>>
                                <?php _e( '⚙️ Meet.bot Seite konfigurieren', 'meetbot-calendar' ); ?>
                            </button>
                            <span id="meetbot-meet-status" style="margin-left:8px;font-size:12px;"></span>
                            <p class="description"><?php _e( 'Setzt web_conferencing_type auf "create" in der Meet.bot Buchungsseite.', 'meetbot-calendar' ); ?></p>
                        </td>
                    </tr>
                </table>

                <h2><?php _e( '📧 E-Mail Versand', 'meetbot-calendar' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php _e( 'Meet.bot E-Mails', 'meetbot-calendar' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="meetbot_cal_suppress_email" value="1" <?php checked( $suppress_email, '1' ); ?> />
                                <?php _e( 'Meet.bot Bestätigungs-E-Mails unterdrücken', 'meetbot-calendar' ); ?>
                            </label>
                            <p class="description"><?php _e( 'Gäste bekommen nur noch IHRE E-Mail (unten). Meet.bot-Kalender-Invite mit Google Meet Link wird trotzdem gesendet.', 'meetbot-calendar' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e( 'Eigene E-Mail', 'meetbot-calendar' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="meetbot_cal_custom_email" value="1" <?php checked( $custom_email, '1' ); ?> />
                                <?php _e( 'Bestätigungs-E-Mail über WordPress/Brevo senden', 'meetbot-calendar' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php _e( 'Absender', 'meetbot-calendar' ); ?></label></th>
                        <td>
                            <input type="text" name="meetbot_cal_email_from_name" value="<?php echo esc_attr( $email_from_name ); ?>" placeholder="Firmenname" style="width:200px;" />
                            &lt;<input type="email" name="meetbot_cal_email_from" value="<?php echo esc_attr( $email_from ); ?>" placeholder="noreply@example.de" style="width:250px;" />&gt;
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php _e( 'Betreff', 'meetbot-calendar' ); ?></label></th>
                        <td>
                            <input type="text" name="meetbot_cal_email_subject" value="<?php echo esc_attr( $email_subject ); ?>" class="large-text" />
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php _e( 'E-Mail Text (HTML)', 'meetbot-calendar' ); ?></label></th>
                        <td>
                            <?php wp_editor( $email_body, 'meetbot_cal_email_body', array( 'textarea_rows' => 12, 'media_buttons' => false, 'teeny' => true ) ); ?>
                            <p class="description">
                                <strong><?php _e( 'Platzhalter:', 'meetbot-calendar' ); ?></strong>
                                <code>{name}</code> <code>{datum}</code> <code>{uhrzeit}</code> <code>{dauer}</code> <code>{meet_link}</code> <code>{notizen}</code> <code>{seite}</code>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php _e( 'Admin-Kopie', 'meetbot-calendar' ); ?></label></th>
                        <td>
                            <input type="email" name="meetbot_cal_admin_email" value="<?php echo esc_attr( $admin_email ); ?>" class="regular-text" />
                            <p class="description"><?php _e( 'Leer lassen = keine Admin-Kopie', 'meetbot-calendar' ); ?></p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>

            <hr/>
            <h3><?php _e( 'Shortcode', 'meetbot-calendar' ); ?></h3>
            <code>[meetbot_calendar]</code> — <code>[meetbot_calendar lang="en"]</code>

            <hr/>
            <p style="color:#999;font-size:12px;">
                <?php printf( __( 'MeetBot Calendar v%s von <a href="%s" target="_blank">GoFonIA</a> · <a href="%s" target="_blank">GitHub</a>', 'meetbot-calendar' ), MEETBOT_CAL_VERSION, 'https://gofonia.de', 'https://github.com/livedialai/meetbot-calendar' ); ?>
            </p>
        </div>

        <script>
        jQuery(function($) {
            var nonce = '<?php echo $nonce; ?>';
            var ajaxUrl = '<?php echo $ajax_url; ?>';

            if ($('#meetbot_cal_api_key').val()) loadPages();

            $('#meetbot-test-btn').on('click', function() {
                var btn = $(this); btn.prop('disabled',true).text('...');
                $.post(ajaxUrl, { action:'meetbot_cal_test', nonce:nonce, api_key:$('#meetbot_cal_api_key').val() }, function(res) {
                    var n = $('#meetbot-notice').show();
                    if (res.success) { n.html('<div class="notice notice-success"><p>✅ '+res.data.message+'</p></div>'); loadPages(); }
                    else { n.html('<div class="notice notice-error"><p>❌ '+(res.data?.message||res.data||'Fehler')+'</p></div>'); }
                    btn.prop('disabled',false).text('Verbinden');
                });
            });

            $('#meetbot-fetch-pages').on('click', loadPages);

            // Configure Meet.bot for Google Meet
            $('#meetbot-configure-meet').on('click', function() {
                var btn = $(this);
                var pageUrl = $('#meetbot_cal_page_url').val();
                if (!pageUrl) { alert('Bitte zuerst eine Buchungsseite wählen.'); return; }
                btn.prop('disabled',true).text('Konfiguriere...');
                var status = $('#meetbot-meet-status');
                $.post(ajaxUrl, {
                    action: 'meetbot_cal_configure_meet',
                    nonce: nonce,
                    page_url: pageUrl,
                    enable: '1'
                }, function(res) {
                    if (res.success) {
                        status.html('✅ <span style="color:green;">' + res.data.message + '</span>');
                    } else {
                        status.html('❌ <span style="color:red;">' + (res.data?.message || 'Fehler') + '</span>');
                    }
                    btn.prop('disabled',false).text('⚙️ Meet.bot Seite konfigurieren');
                });
            });

            function loadPages() {
                $.post(ajaxUrl, { action:'meetbot_cal_fetch_pages', nonce:nonce }, function(res) {
                    if (res.success && res.data.pages) {
                        var sel = $('#meetbot_cal_page_url');
                        var cur = '<?php echo esc_js( $page_url ); ?>';
                        sel.empty().append('<option value="">— wählen —</option>');
                        $.each(res.data.pages, function(i,p) {
                            sel.append('<option value="'+p.url+'"'+(p.url===cur?' selected':'')+'>'+p.title+' ('+p.duration+' min) — '+p.url+'</option>');
                        });
                        $('#meetbot-configure-meet').prop('disabled', !sel.val());
                    }
                });
            }
        });
        </script>
        <?php
    }
}

new MeetBot_Admin();
