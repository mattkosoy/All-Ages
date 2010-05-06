=== All_Ages  ===
Contributors: @mattkosoy
Tags: vimeo, plugin
Requires at least: 2.9.2
Tested up to: 2.9.2
Stable tag: 0.4

A plugin to grab and save a list of videos from Viemo. 

== Description ==
This will create a page for each video that a particular user has uploaded to vim, and associates it to a parent page of the admin's choice.  Also, this plugin saves all vimeo api data to the local db, and associates it to these dynamic pages as wp post_meta data.  
 
== Installation ==
So when you log into your Wordpress backend, in the left rail under tools you'll see your new Vimeo tool.

To get content onto your site, follow these steps-

Upload the video to your Vimeo account
In the Vimeo tool, use the drop down menu to select the "parent" category the new video should fall under (default is set to "spot")
Click "Update videos" to have video ingested
Videos that are ingested as drafts by default, so you must publish them before they're public. To publish, click "Pages" in the left rail.
Locate the new video page draft, and click on it.  This will bring you to the edit page for that draft
Once on the edit page, make sure that the drop downs in the right rail fall in the correct "parent" category, and that you are using the "vimeo" template
PUBLISH!

== Frequently Asked Questions ==
= Forthcoming =

== Screenshots ==
= Forthcoming =

== Changelog ==
0.4 - added custom page template for public facing output
0.3 - added in support for wpdb->prefix
0.2 - updated to support php 4
0.1 - added plugin admin screens, and enabled automatic importing of vimeo data into wpdb.
