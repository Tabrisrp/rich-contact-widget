# Rich Contact Widget

This contact widget for WordPress comes with enhanced markup for microdatas & microformats, so that search engines can use it in their search results. They can help display contact informations about your business or yourself below your website in search results, and even a map with your location.

The telephone & email are linked so that visitors can click on it and make a call (through mobile or skype) or send a mail from their computer or their mobile devices.

You can also display a static image map of your location, linking to the address' Google Maps page, and display a download link for a vCard.

More informations on microdatas microformats can be found here :

*   http://schema.org
*   http://microformats.org

Feedbacks and suggestions for improvement are greatly appreciated !

Rich Contact Widget requires PHP5 to work !

Credits for translation :

* Slovak by <a href="http://webhostinggeeks.com/blog/">WebHostingGeeks.com</a>

# Installation

1. Upload the `rich-contact-widget` directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the `Appearance` -> `Plugins` menu in WordPress
3. Go to the Widgets sub-section and add the Rich Contact Widget in the required sidebar
4. Fill the fields to add your contact information
5. Save

# Frequently Asked Questions

## How to change the output using the filters ?

*   You can add or remove fields in the contact widget output and widget form with the `rc_widget_keys`, `rc_widget_form_ouput` and `rc_widget_output` filters.
*   You can change the Company type of the schema.org itemtype to better fit your business with the `rc_widget_type` filter. Values could be Organization, LocalBusiness, etc.

# Changelog

## 1.2
* Undefined variable fix
* Added Slovak translation

## 1.1
* Added Russian translation

## 1.0
* Fix : bug with WP version lower than 3.4 with wp_is_mobile
* Added : optional link for vCard download

## 0.7
* Updated to work with US addresses (added state field and changed output a little)
* Added width and height attributes to image map
* 
## 0.6
* Added mobile check with wp_is_mobile() for the "tel:" link (link is displayed only if wp_is_mobile() is true)
* Cleaned up some code with checked() and selected() functions
* Moved screenshots to assets folder, reducing the size of the plugin zip file

## 0.5
Added option for static image map displayed in the widget, linking to Google Maps

## 0.4
Added antispambot on email adress

## 0.3
Added filters to change the output of the widget

## 0.2
* Added choice between Person or Company for microdata/microformat tagging
* Added activity/job field

## 0.1
Initial release
