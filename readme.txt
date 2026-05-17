=== Flipnzee Analytics ===
Contributors: Rajeev Bagra
Tags: analytics, google analytics, ga4, search console, traffic stats, listings
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Google Analytics and Search Console powered listing analytics dashboard for in-house website portfolio listings.

== Description ==

Flipnzee Analytics is a custom WordPress plugin designed for showcasing verified analytics of in-house website listings.

The plugin integrates with:

- Google Analytics 4
- Google Search Console

It displays:

- Verified user counts
- Sessions
- Traffic growth trends
- Traffic sources
- Top countries
- Top keywords
- Listing rankings

Built specifically for curated in-house digital asset portfolios.

== Features ==

- Google OAuth connection
- GA4 analytics integration
- Search Console keyword insights
- Verified analytics dashboard shortcode
- Listings ranking shortcode
- Traffic source breakdown
- Country insights
- Growth indicators
- Cached API responses using transients
- Modular plugin architecture
- Dedicated frontend CSS asset pipeline

== Shortcodes ==

[flipnzee_verified_badge]

Displays detailed verified analytics dashboard for a listing.

[flipnzee_all_listings]

Displays all listings ranked by traffic.

== Installation ==

1. Upload the plugin ZIP file
2. Activate the plugin
3. Open:
   Flipnzee Analytics
   from WordPress admin
4. Add Google OAuth credentials
5. Connect Google account
6. Create Listings
7. Add:
   - Domain
   - GA4 Property ID
8. Use shortcodes

== Frequently Asked Questions ==

= Does this support third-party listings? =

No. This plugin is designed for in-house owned listings only.

= Does this support Universal Analytics? =

No. GA4 only.

= Does this require Search Console access? =

Only for keyword insights.

== Changelog ==

= 2.0 =

- Refactored frontend styles into dedicated CSS asset pipeline
- Added reusable Google API helper
- Improved modular architecture
- Split admin components into dedicated files
- Improved caching structure
- Improved shortcode maintainability

== Upgrade Notice ==

= 2.0 =

Major architectural improvements and frontend asset refactor.
