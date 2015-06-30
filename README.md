# Wordpress Plugin: Staff Picks

This Wordpress plugin adds a custom post type and taxonomies for library (or
  bookstore) staff picks.

This software is maintained by Forbes Library.

## Installation
+ Unzip or clone the Staff Picks plugin into your Wordpress plugin directory (`wp-content/plugins`).
+ Log into your Wordpress installation and visit the Plugins screen.
+ Find the Staff Picks plugin in the list and click **Activate Plugin** to activate it.

## Usage

The plugin creates a custom post type, `staff_picks`, and custom taxonomies,
`staff_pick_audiences`, `staff_pick_formats`, `staff_pick_categories`, and
`staff_pick_reviewers`

Users with the `manage_options` permission can create and edit new terms for any of the taxonomies. Editors without this permission may only create new
`staff_pick_categories`, but may select existing terms from the other
taxonomies.

Each `staff_picks` post must have a cover image, author, catalog url, audience,
and reviewer. If the post is missing any of these it will be saved as a draft
instead of being published.
