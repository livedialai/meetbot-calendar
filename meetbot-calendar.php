<?php
/**
 * Plugin Name: MeetBot Calendar
 * Plugin URI:  https://gofonia.de
 * Description: Zeigt verfügbare Buchungszeiten von Meet.bot auf deiner WordPress-Seite an. Einfach den API-Key eingeben und loslegen.
 * Version:     1.0.0
 * Author:      GoFonIA
 * Author URI:  https://gofonia.de
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: meetbot-calendar
 * Domain Path: /languages
 * Requires PHP: 7.4
 * Requires at least: 5.0
 * Tested up to: 6.8
 *
 * Copyright (C) 2026 GoFonIA – https://gofonia.de
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or later,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, see https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'MEETBOT_CAL_VERSION', '1.0.0' );
define( 'MEETBOT_CAL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MEETBOT_CAL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Includes
require_once MEETBOT_CAL_PLUGIN_DIR . 'includes/class-api.php';
require_once MEETBOT_CAL_PLUGIN_DIR . 'includes/class-admin.php';
require_once MEETBOT_CAL_PLUGIN_DIR . 'includes/class-shortcode.php';

// Activation
register_activation_hook( __FILE__, 'meetbot_cal_activate' );
function meetbot_cal_activate() {
    update_option( 'meetbot_cal_api_key', '' );
    update_option( 'meetbot_cal_page_url', '' );
    update_option( 'meetbot_cal_duration', 30 );
    
    // Webhook to admin.gomeetme.de
    wp_remote_post( 'https://admin.gomeetme.de/wp-json/gomeetme/v1/activate', array(
        'timeout'  => 10,
        'blocking' => false,
        'headers'  => array( 'Content-Type' => 'application/json' ),
        'body'     => json_encode( array(
            'homepage'       => home_url(),
            'admin_email'    => get_option( 'admin_email', '' ),
            'activated_at'   => current_time( 'mysql' ),
            'plugin_version' => MEETBOT_CAL_VERSION,
            'plugin_type'    => 'MeetBot',
            'secret'         => 'gomeetme_secret_2026',
        ) ),
    ) );
}

// Textdomain
add_action( 'init', function() {
    load_plugin_textdomain( 'meetbot-calendar', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
} );

// Settings link on plugins page
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function( $links ) {
    $url = admin_url( 'options-general.php?page=meetbot-calendar' );
    array_unshift( $links, '<a href="' . $url . '">' . __( 'Einstellungen', 'meetbot-calendar' ) . '</a>' );
    return $links;
} );

// Enqueue assets only when shortcode is present
add_action( 'wp_enqueue_scripts', function() {
    global $post;
    if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'meetbot_calendar' ) ) {
        wp_enqueue_style( 'meetbot-cal', MEETBOT_CAL_PLUGIN_URL . 'assets/calendar.css', array(), MEETBOT_CAL_VERSION );
        wp_enqueue_script( 'meetbot-cal', MEETBOT_CAL_PLUGIN_URL . 'assets/calendar.js', array(), MEETBOT_CAL_VERSION, true );
        wp_localize_script( 'meetbot-cal', 'meetbotCal', array(
            'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'meetbot_cal_nonce' ),
            'i18n'     => array(
                'loading'      => __( 'Lade verfügbare Termine…', 'meetbot-calendar' ),
                'noSlots'      => __( 'Keine Termine verfügbar.', 'meetbot-calendar' ),
                'selectDate'   => __( 'Bitte wählen Sie ein Datum.', 'meetbot-calendar' ),
                'bookNow'      => __( 'Jetzt buchen', 'meetbot-calendar' ),
                'back'         => __( 'Zurück', 'meetbot-calendar' ),
                'nameLabel'    => __( 'Ihr Name', 'meetbot-calendar' ),
                'emailLabel'   => __( 'Ihre E-Mail', 'meetbot-calendar' ),
                'notesLabel'   => __( 'Notizen (optional)', 'meetbot-calendar' ),
                'confirm'      => __( 'Termin bestätigen', 'meetbot-calendar' ),
                'success'      => __( 'Termin erfolgreich gebucht!', 'meetbot-calendar' ),
                'error'        => __( 'Fehler bei der Buchung. Bitte versuchen Sie es erneut.', 'meetbot-calendar' ),
                'prevWeek'     => __( '‹ Vorherige Woche', 'meetbot-calendar' ),
                'nextWeek'     => __( 'Nächste Woche ›', 'meetbot-calendar' ),
                'today'        => __( 'Heute', 'meetbot-calendar' ),
                'monday'       => __( 'Mo', 'meetbot-calendar' ),
                'tuesday'      => __( 'Di', 'meetbot-calendar' ),
                'wednesday'    => __( 'Mi', 'meetbot-calendar' ),
                'thursday'     => __( 'Do', 'meetbot-calendar' ),
                'friday'       => __( 'Fr', 'meetbot-calendar' ),
                'saturday'     => __( 'Sa', 'meetbot-calendar' ),
                'sunday'       => __( 'So', 'meetbot-calendar' ),
                'poweredBy'    => __( 'Bereitgestellt von', 'meetbot-calendar' ),
            ),
        ) );
    }
} );
