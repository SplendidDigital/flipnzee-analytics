# Flipnzee Analytics â€” Complete Plugin Documentation

**Current documented version:** 2.15.30  
**Platform:** WordPress  
**Primary integrations:** Google Analytics 4 (GA4), Google Search Console, Gemini API, optional Flipnzee companion plugins, and Escrow.com checkout workflow when the companion Auctions integration is active.  
**Repository:** https://github.com/SplendidDigital/flipnzee-analytics

Flipnzee Analytics connects website analytics, search-performance reporting, AI-assisted analysis, portfolio comparison, watchlists, and digital-asset sale listings inside WordPress. It is designed for WordPress installations that manage a collection of website/domain listings and want to display selected analytics and marketplace information on the frontend.

> **Important scope note:** This README documents the code in version 2.15.30. Some shortcodes in the unified portfolio page are supplied by companion plugins, not by Flipnzee Analytics itself. The plugin checks whether those shortcodes exist before rendering their sections. Do not assume a companion feature is available unless the relevant plugin is installed and configured.

---

## Contents

1. [Feature overview](#feature-overview)
2. [Requirements and integrations](#requirements-and-integrations)
3. [Installation and upgrade](#installation-and-upgrade)
4. [Initial configuration](#initial-configuration)
5. [Shortcode quick reference](#shortcode-quick-reference)
6. [Every shortcode in detail](#every-shortcode-in-detail)
7. [Building common pages](#building-common-pages)
8. [Listing setup and metadata](#listing-setup-and-metadata)
9. [Analytics data and refresh behaviour](#analytics-data-and-refresh-behaviour)
10. [AI features](#ai-features)
11. [Market Geography in v2.15.30](#market-geography-in-v21530)
12. [Fixed-price sales and Escrow workflow](#fixed-price-sales-and-escrow-workflow)
13. [Portfolio ranking methodology](#portfolio-ranking-methodology)
14. [Technical architecture](#technical-architecture)
15. [Security and privacy considerations](#security-and-privacy-considerations)
16. [Troubleshooting](#troubleshooting)
17. [Version 2.15.30 changes](#version-21530-changes)
18. [Limitations and verification checklist](#limitations-and-verification-checklist)

---

## Feature overview

### Analytics and SEO
- Retrieves GA4 analytics through the Google Analytics Data API.
- Retrieves Google Search Console performance data through the Search Console API.
- Supports stored analytics metadata and cached results to reduce repeated API calls.
- Displays selected traffic, search, engagement, country, source, and keyword information where data is available.
- Provides a global portfolio market overview with selectable 30-, 60-, 90-, and 180-day geography periods, up to 20 countries, broad regional summaries, and a globe-style regional visualization.

### AI-assisted analysis
- Generates AI summaries of analytics and Search Console history.
- Generates website descriptions from listing context.
- Generates monetization recommendations using available website content and analytics.
- Generates a portfolio-level AI insight.
- Uses a Gemini API key configured by an administrator. Generated reports are saved/cached for later display; public visitors do not need to enter the API key.

### Portfolio and marketplace tools
- Creates a cumulative portfolio analytics page from available shortcode sections.
- Shows published portfolio websites, manually assigned niches and tags, and portfolio ranking.
- Includes a visitor watchlist shortcode.
- Provides a public sale catalogue and sale actions for individual listing pages.
- Supports buyer inquiries/offers and an administrator-facing Marketplace Messages area.
- Can participate in the companion Flipnzee Auctions Buy It Now/Escrow workflow when that plugin and its required components are active.

### WordPress implementation
- Registers a public `listing` custom post type with title, editor, featured image, archive, and REST API support.
- Adds listing metadata for a GA4 property ID, domain, and sale configuration.
- Enqueues frontend CSS and watchlist JavaScript.
- Uses WordPress AJAX endpoints for several administrator actions and watchlist behaviour.
- Schedules analytics and AI-related background hooks on activation.

---

## Requirements and integrations

### Required
- A working WordPress installation.
- Administrator access for setup and connection tasks.
- Published `listing` posts for portfolio/listing shortcodes to display meaningful content.

### Required only for the related features
- **Google Analytics 4:** A GA4 property for each site whose analytics should be collected.
- **Google Search Console:** A verified Search Console property for each site whose organic search data should be included.
- **Google OAuth credentials:** OAuth client ID and client secret configured in the plugin settings, with the callback URI registered in the Google Cloud project.
- **Gemini API key:** Required for generating AI reports, descriptions, monetization recommendations, and portfolio insights.
- **Flipnzee Auctions:** Needed for the integrated automated Buy It Now intent/Escrow workflow described below. Analytics-only sale cards can still display without the companion workflow, but the purchase-request path is not equivalent to confirmed payment.
- **Flipnzee Site Inventory:** Optional. If installed on a portfolio website and its public inventory endpoint is available, portfolio ranking can use its inventory counts.
- **Other companion shortcodes/plugins:** The cumulative overview can include third-party/companion shortcodes such as domain details, public analytics reports, GitHub analytics, and portfolio inventory if those shortcodes are registered on the same WordPress site.

### API permissions
The Google authorization flow requests read-only access to:
- Google Analytics data: `https://www.googleapis.com/auth/analytics.readonly`
- Google Search Console data: `https://www.googleapis.com/auth/webmasters.readonly`

The exact API data available depends on the connected Google account, the GA4 property, Search Console property permissions, data retention, and the reports implemented by the plugin.

---

## Installation and upgrade

1. Back up the WordPress site and database before installing or upgrading.
2. In WordPress, open **Plugins â†’ Add New Plugin â†’ Upload Plugin**.
3. Upload the Flipnzee Analytics ZIP package.
4. Install and activate it. If updating, follow your normal staging and backup process first.
5. Open the **Flipnzee Analytics** administration menu.
6. Configure Google credentials and connect/reconnect Google.
7. Add or edit the relevant `Listing` posts and enter their domain and GA4 property ID.
8. Configure Gemini only if AI features are needed.
9. Add the desired shortcodes to WordPress pages using a **Shortcode block**.
10. Test on staging before enabling public sales or payment-related workflows.

The supplied v2.15.30 package includes plugin PHP files, `includes/` modules, frontend CSS, watchlist JavaScript, changelog and lesson/implementation notes. It does not include a separate end-user manual for each external companion plugin.

---

## Initial configuration

### 1. Google OAuth and analytics

In **Flipnzee Analytics** settings:

1. Enter the Google OAuth **Client ID** and **Client Secret**.
2. Register the plugin callback URI in the Google Cloud OAuth client configuration. The plugin defines it as:

   `https://YOUR-WORDPRESS-HOST/flipnzee-ga-callback`

   The actual URI is generated with `home_url('/flipnzee-ga-callback')`. Use the exact URI shown by your WordPress site's URL and configuration.
3. Save the credentials.
4. Click **Connect / Reconnect Google** and complete Google's consent flow.
5. Confirm the settings page shows a saved authorization and no unresolved recent API error.
6. Use **Refresh Analytics for All Listings** after confirming each listing has the correct GA4 property ID and Search Console site URL.

The plugin uses read-only Google scopes. If an API returns 401/403, check that the correct Google account has access to the relevant property and that the required APIs are enabled in Google Cloud.

### 2. Gemini AI

In **Flipnzee Analytics â†’ settings**:
1. Enter a Gemini API key.
2. Choose a model from the model selector.
3. Save the AI settings.
4. Optional: add a model in the AI Model Registry using the exact model ID supported by the Gemini API.
5. Use the administrator-only generation button shown by the relevant AI shortcode.

The key is stored server-side. Do not paste it into a public page, shortcode, JavaScript, or public repository. The key input is intentionally blank when editing an already saved key; leave it blank to retain the existing key.

### 3. Escrow seller configuration

If using the integrated Escrow workflow, configure the seller email and numeric Escrow user ID in the plugin's **Escrow.com Checkout Automation** settings. Per-listing sale settings are described in [Fixed-price sales and Escrow workflow](#fixed-price-sales-and-escrow-workflow).

Do not treat a generated purchase request as proof that money has been paid or that ownership has transferred.

---

## Shortcode quick reference

These 13 shortcodes are registered by the v2.15.30 plugin code.

| Shortcode | Main output | Attributes |
|---|---|---|
| `[flipnzee_analytics_overview]` | Cumulative portfolio analytics page assembled from available shortcodes | None |
| `[flipnzee_verified_badge]` | Listing-specific verification/analytics panel, including available traffic and recent activity | None; only renders on a single `listing` page |
| `[flipnzee_all_listings]` | Portfolio listing cards/table with available analytics, SEO and ranking-based sorting | None |
| `[flipnzee_portfolio_overview]` | Alphabetical website portfolio with domain, niche and tags; no traffic/SEO data | `title`, `show_domain` |
| `[flipnzee_market_overview]` | Portfolio analytics summary and market geography visualization | None; period selected using `?market_days=30`, `60`, `90`, or `180` |
| `[flipnzee_portfolio_ranking]` | Comparative 1â€“10 portfolio ranking and factor breakdown | None |
| `[flipnzee_ai_insights]` | AI analysis for the current listing using historical GA4/Search Console data | None; only on a single `listing` page |
| `[flipnzee_listing_description]` | Saved AI-generated â€œAbout This Websiteâ€ description | Optional `id` |
| `[flipnzee_ai_monetization]` | AI monetization report using site content and available analytics | Optional `id` |
| `[flipnzee_ai_portfolio_insight]` | Saved AI-generated portfolio-level insight | None |
| `[flipnzee_for_sale]` | Fixed-price sale catalogue for eligible listings | `limit` (default 50, maximum 100) |
| `[flipnzee_sale_actions]` | Sale actions for the current eligible listing | None; place on a single `listing` page |
| `[flipnzee_watchlist]` | Logged-in user's saved listing watchlist; login prompt for logged-out visitors | None |

**Important:** The `flipnzee_analytics_overview` shortcode also attempts to include companion shortcodes when they exist, including `[flipnzee_portfolio_inventory]`, `[domain_details]`, `[flipnzee_ai_portfolio_insight]`, `[public_analytics_report]`, and `[github_analytics]`. The first, fourth and fifth items in that list are not registered by this plugin package itself; their output depends on another plugin being installed. The overview hides sections whose exact shortcode is unavailable.

---

## Every shortcode in detail

### 1. `[flipnzee_analytics_overview]`

**Purpose:** Creates a single cumulative portfolio analytics page with a hero, table of contents, section headings, and any supported section shortcodes that are active on the WordPress site.

**Example:**

```text
[flipnzee_analytics_overview]
```

**What visitors may see:**
1. Portfolio Ranking â€” comparative rating, if the ranking shortcode is available.
2. Portfolio Market Overview â€” aggregate analytics and geography.
3. Websites in the Portfolio â€” listing cards and comparisons.
4. Portfolio Content & Site Inventory â€” only if the companion inventory shortcode is registered.
5. Domain Portfolio â€” only if `[domain_details]` is registered.
6. AI Portfolio Insight â€” if available.
7. Detailed Analytics â€” only if `[public_analytics_report]` is registered.
8. Technology & Open Source â€” only if `[github_analytics]` is registered.

Each available section receives an anchor in the table of contents. The overview composes other shortcodes; it does not itself regenerate the analytics or ranking dataset.

**Use it for:** A central portfolio performance page, such as an overview page for buyers or stakeholders.

**Note:** The overview uses fixed companion shortcode names. If a companion plugin uses a different shortcode, its section will not appear until the integration is updated.

---

### 2. `[flipnzee_verified_badge]`

**Purpose:** Displays a listing-specific analytics/verification panel.

**Example:** Add this shortcode to the template/content of an individual `listing` post:

```text
[flipnzee_verified_badge]
```

**What it displays:** Available listing analytics, live-user/recent-activity information and associated verification-style content. Exact values depend on the connected GA4 property and the data returned by the analytics helpers.

**Important behaviour:**
- It returns no output unless the current page is a single `listing` post.
- It intentionally returns no output in the WordPress admin REST request context.
- A correct GA4 property ID and working Google connection are needed for live analytics.

**Use it for:** Showing an analytics/verification panel on each individual website listing page.

---

### 3. `[flipnzee_all_listings]`

**Purpose:** Displays published `listing` posts that have a GA4 property ID, with available traffic, Search Console and portfolio-ranking information. The interface includes sorting options driven by the `sort` query parameter.

**Example:**

```text
[flipnzee_all_listings]
```

**What visitors can get:** A browsable portfolio of sites with available analytics/SEO metrics and a portfolio rating, depending on the listing data and cached ranking. Sorting modes in the current implementation include:

- `ai_rank` â€” portfolio rating/ranking (default)
- `growth` â€” traffic growth
- `keywords` â€” ranking keyword count
- `clicks` â€” organic clicks
- Additional modes may be present in the renderer; verify the live page's available controls before documenting a specific mode as user-visible.

Sorting is selected through the page query string, for example:

```text
https://example.com/portfolio/?sort=growth
```

**Important behaviour:**
- Only published `listing` posts are queried.
- A listing without `_ga_property_id` is skipped.
- This is the analytics-oriented catalogue, not the simple alphabetical portfolio table.
- Public page loads do not generate fresh portfolio ranking data; an administrator must use the ranking refresh action where needed.

**Use it for:** A data-rich portfolio page where visitors compare website properties.

---

### 4. `[flipnzee_portfolio_overview]`

**Purpose:** Shows a lightweight public website portfolio without loading traffic, users, geography, Search Console, or SEO data.

**Default:**

```text
[flipnzee_portfolio_overview]
```

**Custom title and domain column:**

```text
[flipnzee_portfolio_overview title="Digital Asset Portfolio" show_domain="yes"]
```

**Hide the domain column:**

```text
[flipnzee_portfolio_overview title="Our Websites" show_domain="no"]
```

**Attributes:**

| Attribute | Default | Behaviour |
|---|---|---|
| `title` | `Website Portfolio` | Heading above the table. Use an empty value to suppress the heading. |
| `show_domain` | `yes` | `no` hides the Domain column; other values show it. |

**What visitors see:**
- Website/domain label linking to the internal listing page.
- Domain column when enabled.
- Manually assigned niche categories.
- Manually assigned tags.
- Responsive table layout that changes to stacked rows on smaller screens.

An administrator also sees a **Generate / Refresh Niche & Tags** action for AI-assisted classification, when the relevant API setup is configured. This action is not shown to ordinary visitors.

**Data note:** The shortcode currently excludes the domain `webkund.com` from this public portfolio overview. It does not remove or delete that listing from WordPress.

**Use it for:** A clean, fast, alphabetical showcase of websites without exposing analytics data.

---

### 5. `[flipnzee_market_overview]`

**Purpose:** Provides a consolidated view of the portfolio's measured audience and performance, including the Global Market Geography feature introduced in v2.15.30.

**Example:**

```text
[flipnzee_market_overview]
```

**Choose the geography period with the page URL:**

```text
https://example.com/portfolio-market/?market_days=30
https://example.com/portfolio-market/?market_days=60
https://example.com/portfolio-market/?market_days=90
https://example.com/portfolio-market/?market_days=180
```

Supported values are `30`, `60`, `90`, and `180` days. Any other value is normalized to 30 days.

**What visitors can see, when data is available:**
- Aggregate portfolio traffic/user and session information.
- Country-level audience totals.
- Up to 20 countries, with active users and share of the summed country total.
- Broad regional audience summaries.
- A responsive, globe-style visualization with regional clusters.
- Existing portfolio sorting/comparison information included by the renderer.

**Data and accuracy notes:**
- Country reporting uses GA4 country active-user reports for the selected period and connected properties.
- Results are cached per property/period for six hours.
- Stored country metadata can be used as a fallback when live period data cannot be retrieved.
- Totals are summed across connected properties. A person visiting multiple sites may be counted more than once across the portfolio.
- The globe is a stylized regional visualization, not a precise geographic map. Markers represent broad regions, not exact country boundaries.
- A country may be missing when the connected property lacks data, the API request fails, or permissions are insufficient.

**Use it for:** A portfolio market/geography page showing where the combined audience is located over several time windows.

---

### 6. `[flipnzee_portfolio_ranking]`

**Purpose:** Displays a comparative 1â€“10 ranking of portfolio websites based on available domain, traffic, engagement, search, content and technical factors.

**Example:**

```text
[flipnzee_portfolio_ranking]
```

**What visitors see:**
- Portfolio ranking table.
- A comparative rating, with the strongest site in the current dataset rated 10.0.
- Rank movement where prior snapshots are available.
- An expandable score breakdown for each site's factors.
- The available scoring weight and raw score.
- A methodology explanation.

**First use / refresh:**
- If no ranking data has been generated, the page explains this.
- Administrators see **Generate / Refresh Ranking Data**.
- Public page views do not generate or change ranking history.
- After generating/refreshing, reload the page to see the new results.

**Interpretation:** This is a relative comparison index, not a professional business valuation, traffic guarantee, revenue forecast, or assurance that one website will sell for more than another.

See [Portfolio ranking methodology](#portfolio-ranking-methodology).

---

### 7. `[flipnzee_ai_insights]`

**Purpose:** Displays AI-generated analytics insights for the current individual listing.

**Example:** Place on a single `listing` page:

```text
[flipnzee_ai_insights]
```

**What it uses:**
- Aggregated GA4 historical data.
- Google Search Console historical/search performance data.
- Available periods such as 30 days, 90 days, 6 months, and 12 months, where history is available.

The AI analysis uses aggregated history rather than raw event-level rows.

**What visitors see:**
- The saved AI report, when one exists.
- A brief empty-state message if no report has been generated.
- An administrator-only **Generate AI Analysis** button if a Gemini key is configured.

**Requirements and limits:**
- Only renders on a single `listing` page.
- Requires a configured Gemini API key for generating a report.
- Only administrators can trigger report generation.
- The public page displays saved output; visitors do not need the API key.
- The report is AI-generated and should be reviewed rather than treated as guaranteed financial or SEO advice.

---

### 8. `[flipnzee_listing_description]`

**Purpose:** Displays a saved AI-generated description of what a listed website is about. It is intended as a content description, not an analytics report.

**Current listing:**

```text
[flipnzee_listing_description]
```

**Specific listing by WordPress post ID:**

```text
[flipnzee_listing_description id="123"]
```

Replace `123` with the WordPress post ID of the target `listing`.

**Attribute:**

| Attribute | Default | Behaviour |
|---|---|---|
| `id` | `0` | When omitted/zero, uses the current single `listing` page. When supplied, it must identify a `listing` post. |

**What visitors see:**
- The heading â€œAbout This Websiteâ€.
- The saved AI-generated description, if available.
- An empty-state message if no description exists.

Administrators can regenerate the description when a Gemini key is configured. The generated description is stored and is not regenerated on every page load.

---

### 9. `[flipnzee_ai_monetization]`

**Purpose:** Displays AI-generated monetization recommendations for an individual website.

**Current listing:**

```text
[flipnzee_ai_monetization]
```

**Specific listing:**

```text
[flipnzee_ai_monetization id="123"]
```

Replace `123` with the WordPress post ID of the listing.

**Attribute:**

| Attribute | Default | Behaviour |
|---|---|---|
| `id` | `0` | Uses the current single `listing` when zero; otherwise uses the specified `listing` post ID. |

**What it uses:** Website content collected from the target site, available Flipnzee Analytics measurements, and the existing AI analytics insight. Content crawling has implementation limits; the code uses a bounded number of pages and character volume rather than an unlimited crawl.

**What visitors see:**
- A saved monetization analysis, when available.
- An empty-state message before the first report is generated.
- An administrator-only **Generate / Refresh Monetization Analysis** button when Gemini is configured.

**Use it for:** Presenting possible monetization strategies and opportunities alongside a website listing.

**Caution:** AI suggestions are hypotheses for review, not guaranteed income estimates or financial advice.

---

### 10. `[flipnzee_ai_portfolio_insight]`

**Purpose:** Displays a saved AI analysis of the overall published listing portfolio.

**Example:**

```text
[flipnzee_ai_portfolio_insight]
```

**What it uses:** Portfolio listing content and available analytics data assembled by the plugin.

**What visitors see:**
- A portfolio-level AI monetization insight, if one has been generated.
- An empty-state message if no portfolio analysis exists.
- An administrator-only **Generate / Refresh Portfolio Analysis** button when a Gemini key is configured.

No `id` attribute is used by this shortcode. It operates on the portfolio rather than a single listing.

**Use it for:** A portfolio-wide overview of possible opportunities and themes. Review the report before relying on its recommendations.

---

### 11. `[flipnzee_for_sale]`

**Purpose:** Shows the public fixed-price sale catalogue for listings that have explicitly been marked for sale.

**Default:**

```text
[flipnzee_for_sale]
```

**Show up to 10 sale listings:**

```text
[flipnzee_for_sale limit="10"]
```

**Attributes:**

| Attribute | Default | Allowed behaviour |
|---|---|---|
| `limit` | `50` | Number of sale listings requested; normalized to a minimum of 1 and maximum of 100. |

**What each card can display:**
- Featured image, when the listing has one.
- Listing title and link.
- A clean summary based on the excerpt or content.
- Sale price and currency.
- Escrow fee-payer policy.
- Availability status, such as Available, Under Process, or Sold.
- Buy It Now, offer, or inquiry actions when configured and supported by the current listing state.

Only published `listing` posts with the `_flipnzee_for_sale` value set to `1` are included. The catalogue is ordered by publication date, newest first.

**How to enable a listing for sale:** Edit the listing and configure the sale-related fields exposed by the plugin's listing meta box, including sale eligibility, price, currency, whether offers are accepted, and whether inquiries are allowed.

**Currency note:** USD is the currency currently eligible for the implemented Escrow workflow. Other currencies may be display-only until payment conversion is implemented.

---

### 12. `[flipnzee_sale_actions]`

**Purpose:** Displays sale actions for the current individual `listing` page without rendering the full catalogue card.

**Example:**

```text
[flipnzee_sale_actions]
```

**What it does:**
- Checks that the current post is a `listing`.
- Checks that the listing is marked for sale.
- Displays sale notices and the action panel when applicable.
- Returns an empty string outside an eligible listing context.

**Use it for:** Placing purchase, offer, or inquiry controls at a specific location in a listing page template/content.

This shortcode does not accept a listing ID attribute in the current implementation; it uses the current post.

---

### 13. `[flipnzee_watchlist]`

**Purpose:** Lets logged-in visitors view and manage the websites they have saved to their watchlist.

**Example:**

```text
[flipnzee_watchlist]
```

**What logged-out visitors see:**
- A prompt to sign in.
- A sign-in link.
- A registration link if WordPress user registration is enabled.

**What logged-in visitors see:**
- Their saved watchlist items.
- Controls for adding/removing items where those controls are available in the listing UI.
- A list associated with their WordPress user account, allowing access from another device after signing in.

**Implementation notes:**
- Uses WordPress AJAX and a nonce for watchlist actions.
- Watchlist storage is associated with WordPress user metadata.
- The shortcode itself does not accept attributes.

**Use it for:** A dedicated â€œMy Watchlistâ€ page. Make sure your theme and listing templates expose the watchlist toggle/button where visitors can save listings.

---

## Building common pages

### A. Cumulative analytics page

Create a WordPress page named â€œCumulative Analyticsâ€ and add:

```text
[flipnzee_analytics_overview]
```

This is the simplest way to compose the ranking, market overview, listings, AI insight, and any available companion-plugin sections into one page.

### B. Simple website portfolio page

```text
[flipnzee_portfolio_overview title="Our Website Portfolio" show_domain="yes"]
```

Use this for a lightweight public showcase that displays website, domain, niche and tags without traffic or SEO metrics.

### C. Market geography page

```text
[flipnzee_market_overview]
```

Add a link or navigation control to the same page with `?market_days=30`, `?market_days=60`, `?market_days=90`, and `?market_days=180` to switch the geography period. The shortcode accepts the period through the URL query parameter, not through a shortcode attribute.

### D. Ranking page

```text
[flipnzee_portfolio_ranking]
```

An administrator should click **Generate / Refresh Ranking Data** first if the page has no cached ranking.

### E. Sale catalogue

```text
[flipnzee_for_sale limit="12"]
```

Mark listings for sale and configure their price and sale options before expecting them to appear.

### F. Watchlist page

```text
[flipnzee_watchlist]
```

Create a page such as â€œMy Watchlistâ€ and add the shortcode. Test it with a logged-out browser and a regular test account.

### G. Individual listing page

Possible shortcodes for an individual `listing` post include:

```text
[flipnzee_verified_badge]
[flipnzee_listing_description]
[flipnzee_ai_insights]
[flipnzee_ai_monetization]
[flipnzee_sale_actions]
```

Add only the sections you want to display. AI sections need reports to be generated and saved first; sale actions require that the listing is eligible for sale.

---

## Listing setup and metadata

The plugin registers a public custom post type:

- **Post type:** `listing`
- **Archive:** enabled
- **URL slug:** `listings`
- **REST API:** enabled
- **Supports:** title, editor, featured image

Each listing can be used to represent a website, domain, or other digital property.

### Analytics fields
- **Domain** â€” stored in `_ga_domain`.
- **GA4 property ID** â€” stored in `_ga_property_id`.

Use the GA4 numeric property ID expected by the API, not the public domain name. Confirm the property ID in Google Analytics.

### Sale-related fields
The sale workflow uses listing metadata including:
- `_flipnzee_for_sale`
- `_flipnzee_sale_price`
- `_flipnzee_sale_currency`
- `_flipnzee_accept_offers`
- `_flipnzee_allow_inquiries`
- `_flipnzee_escrow_fee_payer`
- `_flipnzee_sale_escrow_checkout_url` (legacy field; the current generated checkout form does not use this URL)

The Escrow fee-payer value is one of:
- `buyer` â€” buyer pays all fees.
- `seller` â€” seller pays all fees (default for listings without a saved choice).
- `both` â€” buyer and seller share fees equally (50/50).

---

## Analytics data and refresh behaviour

### Scheduled tasks
On activation, the plugin schedules:
- `flipnzee_cron_fetch` hourly.
- `flipnzee_cron_insights` twice daily.

On deactivation, these scheduled hooks are cleared. WordPress cron execution depends on site traffic and hosting configuration; a low-traffic site may not execute scheduled work exactly on the wall-clock schedule.

### Manual refresh
The settings page provides **Refresh Analytics for All Listings**. It clears cached metrics and requests fresh data for published listings that have a GA4 property ID.

If values remain blank or zero:
1. Confirm Google authorization is saved.
2. Confirm the Google account can access the GA4 property.
3. Confirm the listing's property ID is correct.
4. Confirm the Search Console property matches the site and the account has access.
5. Check current Google connection diagnostics on the settings page.
6. Retry a manual refresh.

### Cache behaviour
- Analytics values are cached to reduce API requests.
- Global Market Geography country results are cached by property and period for six hours.
- AI reports and ranking data are persisted and reused; public page loads do not regenerate them.
- A refresh action may be required to update displayed reports.

---

## AI features

### Models and configuration
The plugin supports a configurable Gemini model registry in settings. The built-in default in this package is `gemini-3.5-flash-lite`; administrators can add model IDs accepted by the Gemini API, select the active model, and remove non-default entries.

### What AI reports do
- **AI Analytics Insights:** interprets aggregated traffic and search-performance history.
- **Listing Description:** creates a human-readable description of the website.
- **AI Monetization Insights:** combines website content and available analytics to suggest monetization opportunities.
- **AI Portfolio Insight:** interprets the published portfolio at an aggregate level.
- **Niche/tag classification:** the portfolio overview includes an administrator-only action to generate or refresh niche and tags.

### Generation controls
The generation buttons are shown to users with administrator-level permissions, and require a saved Gemini API key. Reports are persisted so a public visitor does not trigger a new Gemini request simply by opening a page.

### Responsible use
AI output may be incomplete, outdated, or inaccurate. Review generated descriptions and recommendations before publication. Do not present AI-generated monetization suggestions as guaranteed revenue or valuations.

---

## Market Geography in v2.15.30

Version 2.15.30 adds a broader geographic view to `[flipnzee_market_overview]`.

### Supported periods
- 30 days
- 60 days
- 90 days
- 180 days

### Country and region output
- Up to 20 countries.
- Active-user counts and share of the summed country total.
- Regional audience summaries.
- A responsive globe-style regional cluster visualization.

### Technical behaviour
- Uses GA4 country active-user reports for each connected property and selected period.
- Caches each property/period result for six hours.
- Falls back to stored country metadata when live period data is unavailable.

### Interpretation cautions
- Country totals are summed across properties, so the same person can be counted on more than one portfolio website.
- The globe is illustrative and regional; it is not a precise country-boundary map.
- Live authorization, property access, and data accuracy should be validated on the actual installation.
- GA4 may not return country-level data for every property/period.

---

## Fixed-price sales and Escrow workflow

The fixed-price marketplace features were introduced in v2.15.0 and expanded in later releases.

### Public shortcodes
- `[flipnzee_for_sale]` â€” catalogue.
- `[flipnzee_sale_actions]` â€” action panel for the current listing.

### Listing configuration
For a listing to appear in the catalogue:
1. Publish it as a `listing` post.
2. Mark it as for sale.
3. Enter a price and currency.
4. Configure offer acceptance and inquiries as required.
5. Add a featured image and a useful excerpt for better catalogue cards.
6. Set the Escrow fee-payer choice if using Escrow.

### Buyer offers and inquiries
Buyer messages are stored in the WordPress database and are surfaced through **Flipnzee Analytics â†’ Marketplace Messages**. Administrator email notifications are also part of the workflow. Test email delivery on the actual hosting environment.

### Buy It Now / Escrow integration
When the required Flipnzee Auctions component is active, a fixed-price purchase request can reuse the existing `flipnzee_buy_now_intents` table and administrator-controlled Escrow transaction workflow.

Important distinctions:
- A request creates a temporary hold; it is **not** payment confirmation.
- The administrator must review the request and use the Auctions Buy Now intent workflow to create/recover the Escrow transaction and send the buyer agreement.
- Production transaction creation remains governed by the companion Auctions safety gate.
- The shared listing-level lock prevents competing Analytics/Auctions Buy It Now requests for the same listing and can pause auction bidding while an Analytics-originated reservation is active.
- The plugin can display `Under Process` or `Sold` based on the shared intent lifecycle.
- A confirmed active auction takes precedence over a stale listing-level sale lock when no active purchase intent exists.
- Sale Lock Recovery is an administrator tool. It checks purchase intents, Escrow-linked states, completed sales and auction state before offering to remove a stale shared lock. It does not edit or delete purchase-intent records or Escrow transactions.
- Escrow-linked intents, including certain cancelled/expired/rejected local statuses, are guarded against unsafe stale-lock removal.

### Currency and fee notes
- USD is the currency eligible for the currently implemented Escrow workflow.
- Other display currencies are informational until payment conversion is implemented.
- The selected fee-payer label is displayed near the public price and sent as `fee_payer` to the generated Escrow form. Confirm the actual fee arrangement on Escrow.com before completing a transaction.
- Do not assume that a successful request, a displayed â€œUnder Processâ€ status, or a local status update means Escrow has released funds or that ownership has transferred.

### Test before production
Use a staging environment to test:
- Listing availability.
- Purchase request creation.
- Buyer and administrator email delivery.
- Temporary hold expiry and cancellation.
- Escrow transaction creation/recovery.
- Webhook reconciliation in the companion Auctions integration.
- Interactions between sale locks and active auctions.
- Sold/Under Process/Available states.

---

## Portfolio ranking methodology

`[flipnzee_portfolio_ranking]` compares sites using available signals such as:

- Domain age.
- Top-level domain (TLD).
- GA4 users and sessions.
- Traffic growth.
- Average engagement/session duration.
- Pages per user.
- Returning visitors.
- Google Search Console clicks, impressions, CTR, ranking keywords, average position, and indexed/search-visible pages where available.
- Bing sessions.
- Published posts, pages, media, categories, tags, and content/media diversity where available.
- Technical availability.
- Optional verified inventory data from Flipnzee Site Inventory.

### Normalization and score
- Volume metrics use logarithmic normalization so one very large site does not dominate every factor.
- Search position rewards stronger average ranking positions.
- Missing measurements are excluded from the site's denominator rather than automatically scored as zero.
- The final rating is relative to the strongest site in the current portfolio dataset: `10 Ã— (site score Ã· best site score)`. Therefore, the leader is always 10.0.
- A score breakdown displays factor scores, weights, available weight and raw score.
- Up to 30 previous ranking snapshots are retained when an administrator refreshes ranking data; rank movement can then be displayed.

The factor weights visible in the current implementation are:

| Factor | Weight |
|---|---:|
| Domain age | 8% |
| TLD | 3% |
| GA4 users/traffic | 16% |
| Sessions | 6% |
| Growth | 7% |
| Engagement time | 5% |
| Pages per user | 3% |
| Returning visitors | 2% |
| Search Console clicks | 9% |
| Search Console impressions | 5% |
| Search Console CTR | 3% |
| Ranking keywords | 5% |
| Average search position | 5% |
| Indexed pages | 2% |
| Bing traffic | 2% |
| Published posts | 5% |
| Pages | 3% |
| Media | 3% |
| Categories | 2% |
| Tags | 2% |
| Media/content depth | 1% |
| Technical availability | 3% |

**The rating is a comparison index, not a valuation, guarantee of future traffic, or prediction of revenue.** The quality and completeness of the underlying data affect the result.

### Site Inventory integration
If Flipnzee Site Inventory is installed on a portfolio site, ranking refresh can request its public REST endpoint:

```text
/wp-json/flipnzee/v1/inventory
```

The integration is optional. If unavailable, ranking uses the data it can obtain and excludes unavailable factors from the denominator. Inventory results are persisted in listing metadata so public page loads do not repeatedly contact portfolio sites.

---

## Technical architecture

### Main plugin files
The v2.15.30 package is organized around these files:

| File | Responsibility |
|---|---|
| `flipnzee-analytics.php` | Plugin bootstrap, listing post type, listing metadata, scheduled hooks, frontend assets, watchlist and refresh handlers |
| `includes/ga-api.php` | Google API and analytics retrieval helpers |
| `includes/shortcodes.php` | Portfolio, listing, market overview and verification shortcodes |
| `includes/ai-insights.php` | Gemini model configuration, AI data preparation, report generation, caching and AI shortcodes |
| `includes/portfolio-ranking.php` | Ranking dataset, normalization, score calculation, inventory integration and ranking shortcode |
| `includes/sales.php` | Sale catalogue/actions, messages, purchase-request handling, fee-payer display and stale-lock recovery |
| `includes/admin/menu.php` | Administration menu registration |
| `includes/admin/settings-page.php` | Google, Gemini and Escrow settings |
| `includes/admin/meta-boxes.php` | Listing-related metadata box helpers |
| `assets/css/frontend.css` | Frontend styles |
| `assets/js/watchlist.js` | Watchlist interactions |

Some packaged admin files may be empty or superseded by handlers in the main plugin file in this particular ZIP. Treat the actual source in the installed version as authoritative.

### WordPress hooks and data flow
- Registers the `listing` custom post type.
- Uses post meta to associate listings with a domain and GA4 property ID.
- Uses WordPress options to store OAuth credentials/tokens, connection diagnostics, Gemini configuration, cached reports and ranking datasets.
- Uses AJAX endpoints for administrator-triggered refresh/generation and watchlist operations.
- Uses scheduled hooks for background analytics/insight processing.
- Uses WordPress database tables and companion Auctions structures for marketplace messages and purchase-intent integration where applicable.
- Uses the companion plugin's public REST inventory endpoint only during administrator-initiated ranking refresh.

### Frontend assets
The plugin enqueues:
- `assets/css/frontend.css`
- `assets/js/watchlist.js`

The watchlist script receives the WordPress AJAX URL, a nonce, and the logged-in state through localized script data.

### External APIs and services
- Google Analytics Data API.
- Google Search Console API.
- Gemini API.
- Optional Escrow.com transaction workflow through the companion Auctions integration.
- Optional remote WordPress inventory endpoint for ranking data.

---

## Security and privacy considerations

- Keep Google client secrets and Gemini API keys private.
- Use HTTPS on the WordPress site and in Google OAuth configuration.
- Restrict settings and report-generation actions to trusted administrators.
- Several administrator actions use WordPress nonces and capability checks.
- Do not expose raw API keys or tokens in public content or repository commits.
- Review what analytics data is displayed publicly before placing analytics shortcodes on public pages.
- Ensure your privacy notice describes any analytics integrations and the handling of buyer inquiries/watchlists as appropriate for your site.
- Treat public AI output as generated content that may require review.
- Test marketplace and payment-related features on staging before enabling them on a live site.

---

## Troubleshooting

### A shortcode displays nothing
- Confirm the shortcode is spelled correctly and uses square brackets.
- Add it in a WordPress Shortcode block or an editor location that processes WordPress shortcodes.
- Check whether it requires a single `listing` page (`flipnzee_verified_badge`, `flipnzee_ai_insights`) or a listing ID.
- Confirm the target post is published and uses the `listing` post type.
- For the cumulative overview, check that any desired companion plugin is installed and that its shortcode exists.

### No portfolio listings appear
- Confirm the listing posts are published.
- `[flipnzee_all_listings]` skips listings without a GA4 property ID.
- `[flipnzee_for_sale]` only shows listings explicitly marked for sale.
- `[flipnzee_portfolio_overview]` shows published listings alphabetically and currently excludes `webkund.com`.

### Analytics is zero or missing
- Check Google authorization and current connection diagnostics.
- Verify the GA4 property ID on the listing.
- Confirm the connected Google account has access to the property.
- Verify the required Google APIs are enabled.
- Use **Refresh Analytics for All Listings**.
- Allow for API data availability and cache behaviour.

### Market Geography has no countries
- Confirm the connected GA4 property returns country data for the selected date period.
- Verify Google authorization and property access.
- Try another period (30/60/90/180 days).
- Remember that stored country metadata may be used as a fallback and that each property/period is cached for six hours.

### AI buttons do not appear
- AI generation controls are administrator-only.
- Confirm the Gemini API key is saved.
- Confirm the shortcode is in a supported listing/portfolio context.
- Check browser developer tools and WordPress/PHP logs if an AJAX request fails.
- Verify that the selected Gemini model ID is accepted by the API.

### AI report is empty
- Generate the report using the administrator button.
- Confirm the site has usable analytics/content data.
- Check Gemini key validity, quota, model availability, outbound HTTP access and WordPress logs.
- Remember that AI reports are persisted; a page refresh alone does not necessarily regenerate them.

### Ranking is empty or stale
- Open the ranking page as an administrator.
- Click **Generate / Refresh Ranking Data**.
- Confirm listings have the required analytics/domain metadata.
- Verify remote Site Inventory availability if inventory factors are expected.
- Reload the page after a successful refresh.

### Sale listing is missing or cannot be purchased
- Confirm it is published and marked for sale.
- Confirm a valid price and currency are saved.
- USD is required for the implemented Escrow workflow.
- Confirm the companion Flipnzee Auctions integration is active for automated purchase-intent handling.
- Check whether an active auction, current purchase intent, or shared lock affects availability.
- A purchase request is not payment confirmation; review the administrator's marketplace/intent workflow.
- Check WordPress email delivery and Escrow settings.

### Watchlist does not work
- Test while logged in as a normal user.
- Confirm the watchlist shortcode and frontend script load.
- Check for JavaScript conflicts or caching that serves stale scripts.
- Confirm WordPress AJAX requests are not blocked by security/CDN rules.

---

## Version 2.15.30 changes

The v2.15.30 release adds **Global Market Geography**:

- Selectable 30-, 60-, 90-, and 180-day reporting periods.
- GA4 country active-user reports for connected properties.
- Up to 20 countries with active users and share of the summed country total.
- Broad regional audience summaries.
- A responsive globe-style regional cluster visualization.
- Six-hour cache per property and period.
- Stored-country metadata fallback when live period data cannot be retrieved.

See `CHANGELOG-2.15.30.md` in the package for the release-specific summary.

---

## Limitations and verification checklist

Before announcing or deploying the plugin, verify the following on the actual WordPress installation:

- [ ] Google OAuth callback URL matches the live site's URL.
- [ ] Google Analytics and Search Console data refresh successfully.
- [ ] Each listing uses the correct GA4 property ID and domain.
- [ ] All required companion plugins are installed for the sections you want to display.
- [ ] AI generation works with the configured Gemini key and model.
- [ ] Portfolio ranking has been generated and its source data reviewed.
- [ ] Market Geography periods and country results have been checked against GA4.
- [ ] The globe is described as a regional visualization rather than a precise map.
- [ ] Sale cards, inquiries, temporary holds, and availability states are tested on staging.
- [ ] Escrow workflow is tested with the companion Auctions integration and the appropriate environment.
- [ ] Watchlist works for logged-in users.
- [ ] Public pages do not reveal private credentials or unintended analytics.
- [ ] Each public repository has an appropriate licence and current installation instructions.

The source package can be inspected and its PHP syntax checked, but live Google authorization, API data accuracy, Gemini generation, and production payment workflows must be verified on the target WordPress installation.

---

## Support and source

- **GitHub organization:** https://github.com/SplendidDigital
- **Flipnzee Analytics repository:** https://github.com/SplendidDigital/flipnzee-analytics
- **Issues:** Use the repository's Issues tab to report reproducible bugs and feature requests.

When reporting an issue, include the plugin version, WordPress/PHP versions, the shortcode involved, expected versus actual behaviour, relevant non-secret error messages, and steps to reproduce. Never include OAuth secrets, access tokens, or Gemini API keys in an issue.
