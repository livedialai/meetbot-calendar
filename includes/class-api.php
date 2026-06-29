<?php
/**
 * MeetBot Calendar – Meet.bot API Client
 *
 * Copyright (C) 2026 GoFonIA – https://gofonia.de
 * Licensed under GNU GPL v2 or later (GPL-2.0-or-later)
 * https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class MeetBot_API {

    private $api_key;
    private $base_url = 'https://meet.bot/v1';

    public function __construct( $api_key = '' ) {
        $this->api_key = $api_key ?: get_option( 'meetbot_cal_api_key', '' );
    }

    public function is_configured() {
        return ! empty( $this->api_key );
    }

    private function request( $endpoint, $params = array(), $method = 'GET' ) {
        $url = $this->base_url . $endpoint;
        
        $args = array(
            'timeout' => 15,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Accept'        => 'application/json',
            ),
        );

        if ( $method === 'GET' && ! empty( $params ) ) {
            $url = add_query_arg( $params, $url );
        } elseif ( in_array( $method, array( 'POST', 'PUT', 'PATCH' ) ) ) {
            $args['method'] = $method;
            $args['headers']['Content-Type'] = 'application/json';
            $args['body'] = wp_json_encode( $params );
        }

        $response = wp_remote_request( $url, $args );

        if ( is_wp_error( $response ) ) {
            return array( 'error' => $response->get_error_message() );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        $code = wp_remote_retrieve_response_code( $response );

        if ( $code >= 400 ) {
            return array( 'error' => $body['error'] ?? 'API Error ' . $code );
        }

        return $body;
    }

    public function get_pages() {
        return $this->request( '/pages' );
    }

    public function get_slots( $page_url, $count = 30 ) {
        return $this->request( '/slots', array(
            'page'  => $page_url,
            'count' => $count,
        ) );
    }

    /**
     * Get page config (includes web_conferencing_type)
     */
    public function get_page_config( $page_url ) {
        return $this->request( '/page-config', array( 'page' => $page_url ) );
    }

    /**
     * Update page config (e.g. set web_conferencing_type to "create")
     */
    public function update_page_config( $page_url, $data ) {
        $data['page'] = $page_url;
        return $this->request( '/page-config', $data, 'PATCH' );
    }

    /**
     * Book a slot
     */
    public function book( $page_url, $start, $name, $email, $notes = '' ) {
        return $this->request( '/book', array(
            'page'        => $page_url,
            'start'       => $start,
            'guest_name'  => $name,
            'guest_email' => $email,
            'notes'       => $notes,
        ), 'POST' );
    }

    /**
     * Get calendar events (to retrieve Google Meet link after booking)
     */
    public function get_calendar_events( $from = '', $to = '' ) {
        $params = array();
        if ( $from ) $params['from'] = $from;
        if ( $to ) $params['to'] = $to;
        return $this->request( '/calendar/events', $params );
    }

    public function get_calendars() {
        return $this->request( '/calendars' );
    }

    /**
     * Extract meeting URL from booking response
     */
    public function extract_meeting_url( $booking_result ) {
        // Direct fields
        foreach ( array( 'meeting_url', 'meet_url', 'video_url', 'web_conference_url' ) as $key ) {
            if ( ! empty( $booking_result[ $key ] ) ) {
                return $booking_result[ $key ];
            }
        }
        // Nested in event/details
        foreach ( array( 'event', 'details', 'booking' ) as $nest ) {
            if ( is_array( $booking_result[ $nest ] ?? null ) ) {
                foreach ( array( 'meeting_url', 'meet_url', 'video_url', 'location', 'web_conference_url' ) as $key ) {
                    if ( ! empty( $booking_result[ $nest ][ $key ] ) ) {
                        return $booking_result[ $nest ][ $key ];
                    }
                }
            }
        }
        return '';
    }

    /**
     * Try to find Google Meet link from recent calendar events
     */
    public function find_meet_link( $start_iso ) {
        $dt = new DateTime( $start_iso );
        $from = $dt->modify( '-1 hour' )->format( 'Y-m-d\TH:i:s\Z' );
        $dt2 = new DateTime( $start_iso );
        $to = $dt2->modify( '+2 hours' )->format( 'Y-m-d\TH:i:s\Z' );

        $events = $this->get_calendar_events( $from, $to );
        if ( isset( $events['error'] ) || ! is_array( $events ) ) return '';

        $event_list = $events['events'] ?? $events;
        if ( ! is_array( $event_list ) ) return '';

        $target = new DateTime( $start_iso );
        foreach ( $event_list as $evt ) {
            $evt_start = $evt['start'] ?? '';
            if ( ! $evt_start ) continue;
            try {
                $evt_dt = new DateTime( $evt_start );
                $diff = abs( $evt_dt->getTimestamp() - $target->getTimestamp() );
                if ( $diff < 300 ) { // within 5 min
                    foreach ( array( 'meeting_url', 'meet_url', 'video_url', 'location', 'web_conference_url' ) as $key ) {
                        if ( ! empty( $evt[ $key ] ) && strpos( $evt[ $key ], 'meet.google' ) !== false ) {
                            return $evt[ $key ];
                        }
                    }
                    // Check location field for any meet URL
                    $loc = $evt['location'] ?? '';
                    if ( $loc && ( strpos( $loc, 'meet.google' ) !== false || strpos( $loc, 'https://' ) !== false ) ) {
                        return $loc;
                    }
                }
            } catch ( Exception $e ) { continue; }
        }
        return '';
    }

    public function test_connection() {
        $result = $this->get_pages();
        if ( isset( $result['error'] ) ) {
            return array( 'success' => false, 'message' => $result['error'] );
        }
        $count = count( $result['pages'] ?? array() );
        return array(
            'success' => true,
            'message' => sprintf( 'Verbindung erfolgreich! %d Buchungsseite(n) gefunden.', $count ),
        );
    }
}
