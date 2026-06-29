=== GoFonIA Booking Calendar for Meet.bot ===
Contributors: gomeetme, gofonia
Tags: booking, calendar, meet-bot, scheduling, video-meeting
Requires at least: 5.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Displays available booking slots from Meet.bot on your WordPress site. Integrates with Google Meet and supports custom confirmation emails.

== Description ==

**GoFonIA Booking Calendar for Meet.bot** connects your WordPress site with the [Meet.bot](https://meet.bot) scheduling platform. Display available time slots and let visitors book appointments directly on your site.

**Features:**

* Interactive monthly calendar with available time slots
* Direct appointment booking on your site
* Google Meet video integration (automatic Meet links)
* Custom confirmation emails via WordPress
* Admin settings with API key testing
* Mobile-responsive design
* "Powered by GoFonIA" branding

**How it works:**

1. Create a free account at [Meet.bot](https://meet.bot)
2. Connect your calendars (Google, Outlook, Apple)
3. Install this plugin and enter your API key
4. Add the shortcode `[meetbot_calendar]` to a page
5. Done! Visitors can now book appointments

**Meet.bot benefits:**

* Syncs with Google Calendar, Microsoft Outlook, and Apple Calendar
* Automatic Google Meet links for video calls
* No double bookings through real-time sync
* Free to use

== Installation ==

1. Upload the plugin via the WordPress plugin manager or extract the folder to `/wp-content/plugins/`
2. Activate the plugin under "Plugins" in WordPress
3. Go to "Settings > GoFonIA Booking Calendar" and enter your Meet.bot API key
4. Select your booking page
5. Add the shortcode `[meetbot_calendar]` to any page or post

== External Services ==

This plugin connects to external services to provide its functionality. Below is a detailed description of each service, what data is sent, and when.

=== Meet.bot API ===

This plugin connects to the [Meet.bot API](https://meet.bot) to retrieve available booking slots and create appointments.

* **What data is sent:** When loading the calendar, the booking page URL is sent to retrieve available time slots. When a visitor books an appointment, their name, email address, optional notes, and the selected time slot are sent to the Meet.bot API.
* **When data is sent:** Time slots are loaded when a page containing the `[meetbot_calendar]` shortcode is visited. Booking data is sent only when a visitor submits the booking form.
* **Service provider:** Connected Product S.L. (Spain)
* **Terms of service:** [Meet.bot Terms](https://meet.bot/terms)
* **Privacy policy:** [Meet.bot Privacy Policy](https://meet.bot/privacy)

== Frequently Asked Questions ==

= Do I need a Meet.bot account? =

Yes, create a free account at [meet.bot](https://meet.bot). There you will also get the API key.

= Which calendars are supported? =

Meet.bot supports Google Calendar, Microsoft Outlook, and Apple Calendar (via CalDAV).

= Can I send custom emails? =

Yes! Enable "Send custom email via WordPress" in the settings. The plugin will send confirmation emails via WordPress (e.g., with Brevo/SMTP). The email template can be customized with placeholders like {name}, {datum}, {uhrzeit}, and {meet_link}.

= Does Google Meet work automatically? =

Yes, when "Auto-create Google Meet links" is enabled, Meet.bot automatically creates a video link for each booking.

= Is this plugin GDPR compliant? =

The plugin does not store personal data in WordPress. All booking data is transmitted directly to Meet.bot. Please review Meet.bot's privacy policy for details on their data handling.

== Screenshots ==

1. Calendar view with available slots
2. Booking form
3. Confirmation page with video link
4. Admin settings

== Changelog ==

= 1.0.1 =
* Renamed to "GoFonIA Booking Calendar for Meet.bot" to clarify branding
* Translated all descriptions to English
* Fixed escaping issues (_e to esc_html_e, __ to esc_html__)
* Moved inline admin script to enqueued JS file
* Improved input sanitization for settings
* Documented external services (Meet.bot API)
* Removed activation webhook
* Removed unnecessary load_plugin_textdomain() call

= 1.0.0 =
* Initial release
* Meet.bot API integration (slots, booking, calendar)
* Monthly calendar with slot display
* Booking form with name, email, notes
* Google Meet video integration
* Custom confirmation emails (WordPress/Brevo)
* Admin settings with API key test
* Mobile-responsive design
* "Powered by GoFonIA" footer

== Upgrade Notice ==

= 1.0.1 =
Security and compliance improvements. Recommended update.

= 1.0.0 =
Initial release.
