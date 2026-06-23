# CompactForm – Drag & Drop Form Builder for Contact Form 7 #

**Contributors:** rstheme2017, sagar7258
**Author:** RSTheme
**Author URI:** https://rstheme.com/
**Plugin URI:** https://rstheme.com/compactform
**Tags:** contact form 7, cf7, form builder, drag and drop, conditional logic
**Requires at least:** 6.4
**Tested up to:** 7.1
**Requires PHP:** 7.4
**Requires Plugins:** contact-form-7
**Stable tag:** 1.0.0
**License:** GPLv2 or later
**License URI:** http://www.gnu.org/licenses/gpl-2.0.html

Drag-and-drop visual form builder for Contact Form 7 — add fields, layouts, and conditional logic without touching shortcode syntax.

## Description ##

**CompactForm – Drag & Drop Form Builder for Contact Form 7** turns Contact Form 7's shortcode editor into a real visual builder. Drag fields onto a canvas, arrange layout, style each field, and set conditional logic — all from a live preview, without memorizing a single CF7 tag.

Contact Form 7 is one of the most trusted form plugins in WordPress, but building anything beyond a basic form means hand-writing shortcodes. CompactForm keeps every form 100% Contact Form 7 underneath — same shortcode, same submission pipeline, same compatibility with every CF7 addon you already run — while giving you a drag-and-drop canvas on top.


### Why you'll love it ###

* A real drag-and-drop canvas over Contact Form 7 — no shortcode typing.
* 30+ ready field types, from basic inputs to star rating, signature, and country dropdown.
* Server-side validation and conditional logic — never just client-side JavaScript.
* Per-field style controls: typography, color, spacing, borders, responsive width.
* Existing Contact Form 7 forms import straight into the builder.
* Lightweight — assets load only on pages where a form actually uses them.

### Key Features ###

🧩 **Visual Drag & Drop Builder**
Build forms on a live canvas — drag fields in, reorder them, and see the result as you go. Opt in per form; your hand-written CF7 markup is left alone until you do.

🗂 **30+ Field Types**
Text, Email, Tel, URL, Number, Date, Textarea, Quiz, File Upload, Submit, Select, Radio, Checkbox, Acceptance, Heading, Divider, Raw HTML, Star Rating, Country Dropdown, Date/Time/DateTime Picker, Signature Pad, Range Slider, Google reCAPTCHA, Spam Protection, and a Layout Container to group and structure fields.

🎨 **Per-Field Style Controls**
Style every field individually — colors, typography, spacing, borders, and responsive width — directly from the builder panel, no custom CSS required.

🔀 **Conditional Logic**
Show or hide fields based on other field values. Rules are enforced on the server, so visibility can never be bypassed by disabling JavaScript.

🛡 **Advanced Spam Protection & Security**
Multi-layered defense against spam and bot submissions with reCAPTCHA v3 integration, honeypot fields, customizable minimum fill time, and keyword blocking. All protection mechanisms are server-side enforced, ensuring maximum security against client-side circumvention attempts.

🔐 **Server-Authoritative Validation**
Every field validates on the server against the stored form schema and posted data. Client-side checks are for user experience only — they are never the security gate.

📥 **Import Existing Forms**
Bring an existing Contact Form 7 form into the builder and keep building visually from there.

💾 **Form Import & Export**
Export any CompactForm-built form to a portable JSON file with complete configuration, styling, and logic intact. Import that file into any other WordPress site running CompactForm to instantly recreate the form — perfect for reusing forms across multiple sites, sharing with clients, or version control.

📧 **Intelligent Email Template Auto-Fill**
Every form ships with a professionally designed default email template — no manual setup required. The system intelligently detects all form fields and auto-populates the email template with a single click. Edit templates visually with instant field variable insertion, or stick with the built-in template and let submissions flow automatically. Zero configuration for quick wins; full customization available when you need it.

👁️ **Live Email Template Preview**
See exactly how your email will appear in recipients' inboxes before a single submission arrives. The live preview on the mail tab renders your template in real-time as you edit, eliminating guesswork and ensuring perfect formatting across all email clients.

🎛️ **Centralized Dashboard for Addons & Configuration**
Manage all CompactForm addons, integrations, API keys, and settings from a unified, intuitive dashboard. Enable or disable features, configure reCAPTCHA keys, manage webhook endpoints, control field type visibility, and organize all your plugin settings in one place — no scattered admin pages or confusing menus.

🔗 **Webhook Integration**
Send form submissions to any external URL as they come in.

📊 **Admin Bar Quick Access**
Jump to a form's builder straight from the WordPress admin bar while browsing the front end.

🧰 **Works Alongside Contact Form 7**
Requires Contact Form 7 — every builder-made form is still a genuine CF7 form, so it keeps working with the CF7 ecosystem you already rely on.


## External services ##

This plugin connects to two third-party services. Both are optional and disabled by default.

### ipapi.co ###

Used by the Country Dropdown field to pre-select the visitor's country automatically.

This service is only contacted when a form author enables the "IP auto-complete" option on a Country Dropdown field. The option is off by default. When it is enabled, the visitor's browser sends a request to `https://ipapi.co/country/` each time a page containing that form is loaded. The request is made directly by the visitor's browser, so ipapi.co receives the visitor's IP address. Only the two-letter country code is returned, and no data from the request is stored by this plugin.

Service provided by ipapi.
Terms of service: https://ipapi.co/terms/
Privacy policy: https://ipapi.co/privacy/

### Google reCAPTCHA ###

Used by the Google reCAPTCHA field to detect automated form submissions.

This service is only contacted when a site administrator enters their own reCAPTCHA site key and secret key in the CompactForm dashboard and adds a reCAPTCHA field to a form. No keys are configured by default. When it is configured, two requests occur: the visitor's browser loads `https://www.google.com/recaptcha/api.js` on any page containing that form, and on submission the site sends the reCAPTCHA response token together with the site's secret key from the server to `https://www.google.com/recaptcha/api/siteverify` for verification. Google receives the visitor's IP address and browser interaction data as part of its bot-detection process.

Service provided by Google.
Terms of service: https://policies.google.com/terms
Privacy policy: https://policies.google.com/privacy

### Source code ###

The source code of this plugin is publicly available and open source. The plugin is released under the GNU General Public License (GPL).

You are free to view, use, modify, and redistribute the source code in accordance with the terms of the GPL license.

The complete source code is available here:

Here is the github [SOURCE CODE](https://github.com/menurhosain/compactform.git)


## Requirements ##

* WordPress 6.4 or newer
* PHP 7.4 or newer
* Contact Form 7 installed and activated

## Installation ##

1. Upload the plugin folder to the `/wp-content/plugins/` directory, or install it directly from the WordPress **Plugins → Add New** screen.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Make sure **Contact Form 7** is installed and activated.
4. Open a Contact Form 7 form's edit screen, switch to the **Visual Editor** tab, and click **Edit with Builder** to start building — or create a new form with the builder enabled from the start.

## Frequently Asked Questions ##

= What does this plugin do? =
It adds a drag-and-drop visual builder on top of Contact Form 7, so you can add fields, arrange layout, style fields, and set conditional logic without writing shortcodes by hand.

= Do I need to know how to code? =
No. Fields, layout, styling, and logic are all handled visually in the builder panel.

= Does it work without Contact Form 7? =
No. It's an extension for Contact Form 7, so Contact Form 7 must be installed and activated.

= Will it change my existing Contact Form 7 forms? =
No. The builder is opt-in per form. A form only becomes builder-managed once you explicitly choose to edit it with the builder; until then it behaves exactly like stock Contact Form 7.

= Can I import an existing form into the builder? =
Yes. Existing Contact Form 7 shortcode markup can be imported into the builder and edited visually from there.

= Can I export and import forms between sites? =
Yes. Any form built with CompactForm can be exported to a portable JSON file containing all field configurations, styling, conditional logic, and settings. That file can then be imported into any other WordPress site that has CompactForm installed, instantly recreating the form with all its properties intact. This is ideal for multi-site deployments, sharing templates with clients, or maintaining form libraries across environments.

= What field types are included in the free version? =
30+ types, including all the standard Contact Form 7 fields (Text, Email, Tel, URL, Number, Date, Textarea, File, Select, Radio, Checkbox, Acceptance, Quiz, Submit) plus Star Rating, Country Dropdown, Date/Time/DateTime Picker, Signature, Range Slider, Google reCAPTCHA, Spam Protection, Layout Container, Heading, Divider, and Raw HTML.

= Is conditional logic enforced on the server? =
Yes. Visibility rules are always re-checked from the stored form schema and the actual posted data — a visitor cannot bypass a rule by disabling JavaScript.

= Can I style each field individually? =
Yes. Typography, color, spacing, borders, and responsive width are all available per field from the builder panel.

= Can I turn individual field types on or off? =
Yes. Every field type has its own toggle in the CompactForm dashboard, so you can disable any field you don't want available in the builder.

= Where do I manage addons and API keys? =
Everything is centralized in the CompactForm dashboard — a beautifully designed, intuitive control center. From one place, you can manage all addons, configure integrations, store and organize API keys (like reCAPTCHA, webhooks), enable or disable features, customize field type availability, and adjust all plugin settings. No scattered admin pages or confusing WordPress menus — everything you need is in one organized dashboard.

= Can I send submissions to an external URL? =
Yes. The free version includes a Webhook field-level integration that posts submission data to any URL you configure.

= What spam protection options are available? =
CompactForm includes four advanced spam defense layers: reCAPTCHA v3 for bot detection, honeypot fields for automated submissions, customizable minimum fill time to block instant submissions, and specific word/keyword blocking to filter unwanted content. All checks are enforced server-side for complete security.

= Do I need to set up email templates manually? =
No. Every form automatically uses a beautifully designed default email template — submission data flows without any template configuration. If you want to customize the email, click the auto-fill button and CompactForm intelligently detects all your form fields and pre-populates the template with dynamic field variables. You can edit the template visually, or leave the default and let it work out of the box. Either way, zero manual template setup is required.

= Can I preview how emails will look? =
Yes. Every form includes a live email template preview that renders in real-time as you edit. This shows exactly how your email will appear in recipients' inboxes across all email clients, eliminating formatting surprises and ensuring professional delivery every time.

= Where do I find the builder? =
Open any Contact Form 7 form's edit screen — the **Visual Editor** tab is the first tab, and it walks you through enabling the builder for that form.

= Is there a limit on how many forms or fields I can create? =
No. You can build as many forms as you need, each with as many fields as you need.

= Where can I get support? =
Support is available through the plugin's support forum on WordPress.org.

## Screenshots ##

1. Drag-and-drop builder canvas with the field palette.
2. Per-field style controls panel.
3. Conditional logic configuration.
4. Field library with 30+ ready field types.
5. CompactForm dashboard — enable or disable individual field types.

## Changelog ##

= 1.0.0 =
* Initial release with the drag-and-drop visual builder, 30+ field types, per-field styling, conditional logic, form import, webhook integration, and admin bar quick access.
