<?php
/**
 * Plugin Name: GoFonIA Booking Calendar for Meet.bot
 * Plugin URI:  https://github.com/livedialai/meetbot-calendar
 * Description: Displays available booking slots from Meet.bot on your WordPress site. Integrates with Google Meet and supports custom confirmation emails.
 * Version:     1.0.1
 * Author:      GoFonIA
 * Author URI:  https://gofonia.de
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: meetbot-calendar
 * Domain Path: /languages
 * Requires PHP: 7.4
 * Requires at least: 5.0
 * Tested up to: 7.0
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

define( 'MEETBOT_CAL_VERSION', '1.0.1' );
define( 'MEETBOT_CAL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MEETBOT_CAL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Includes
require_once MEETBOT_CAL_PLUGIN_DIR . 'includes/class-api.php';
require_once MEETBOT_CAL_PLUGIN_DIR . 'includes/class-admin.php';
require_once MEETBOT_CAL_PLUGIN_DIR . 'includes/class-shortcode.php';

// Activation — set defaults only (no external calls)
register_activation_hook( __FILE__, 'meetbot_cal_activate' );
function meetbot_cal_activate() {
    update_option( 'meetbot_cal_api_key', '' );
    update_option( 'meetbot_cal_page_url', '' );
    update_option( 'meetbot_cal_duration', 30 );
}

// Settings link on plugins page
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function( $links ) {
    $url = admin_url( 'options-general.php?page=meetbot-calendar' );
    array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'meetbot-calendar' ) . '</a>' );
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
                'loading'      => esc_html__( 'Loading available appointments...', 'meetbot-calendar' ),
                'noSlots'      => esc_html__( 'No appointments available.', 'meetbot-calendar' ),
                'selectDate'   => esc_html__( 'Please select a date.', 'meetbot-calendar' ),
                'bookNow'      => esc_html__( 'Book now', 'meetbot-calendar' ),
                'back'         => esc_html__( 'Back', 'meetbot-calendar' ),
                'nameLabel'    => esc_html__( 'Your Name', 'meetbot-calendar' ),
                'emailLabel'   => esc_html__( 'Your Email', 'meetbot-calendar' ),
                'notesLabel'   => esc_html__( 'Notes (optional)', 'meetbot-calendar' ),
                'confirm'      => esc_html__( 'Confirm appointment', 'meetbot-calendar' ),
                'success'      => esc_html__( 'Appointment booked successfully!', 'meetbot-calendar' ),
                'error'        => esc_html__( 'Booking failed. Please try again.', 'meetbot-calendar' ),
                'prevWeek'     => esc_html__( '\u2039 Previous week', 'meetbot-calendar' ),
                'nextWeek'     => esc_html__( 'Next week \u203a', 'meetbot-calendar' ),
                'today'        => esc_html__( 'Today', 'meetbot-calendar' ),
                'monday'       => esc_html__( 'Mo', 'meetbot-calendar' ),
                'tuesday'      => esc_html__( 'Tu', 'meetbot-calendar' ),
                'wednesday'    => esc_html__( 'We', 'meetbot-calendar' ),
                'thursday'     => esc_html__( 'Th', 'meetbot-calendar' ),
                'friday'       => esc_html__( 'Fr', 'meetbot-calendar' ),
                'saturday'     => esc_html__( 'Sa', 'meetbot-calendar' ),
                'sunday'       => esc_html__( 'Su', 'meetbot-calendar' ),
                'poweredBy'    => esc_html__( 'Powered by', 'meetbot-calendar' ),
            ),
        ) );
    }
} );
