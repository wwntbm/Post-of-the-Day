# Post of the Day #
Contributors: macbookandrew, mdavison

Donate link: http://morgandavison.com

Tags: rotating posts, post of the day, random post, testimonials

Requires at least: 3.2.1

Tested up to: 3.2.1

Stable tag: trunk

Plugin to display a random post from a particular category and/or post_type.

## Description ##

Choose your categories and interval from the settings menu and it will display 1 post from the chosen categories and/or post types at your set interval, chosen randomly.


## Installation ##

1. Upload the entire `post-of-the-day` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. You should see “Post of the Day” under your Settings. Edit the settings.
4. Include this shortcode within a post or page: `[potd]`

Alternatively, if you want to include the post somewhere in your theme that is not in a post or page, just include the following in your php page:

	<?php echo do_shortcode('[potd]');  ?>

## Frequently Asked Questions ##

* Q: Can I display more than one post at a time?
* A: No, not at this point.

## Changelog ##
### 1.2 ###
Fix some logic preventing more than the first 10 posts from ever showing up.

### 1.1 ###
Add capability to choose post_types in addition to categories

### 1.0 ###
First Release

## Upgrade Notice ##
N/A
