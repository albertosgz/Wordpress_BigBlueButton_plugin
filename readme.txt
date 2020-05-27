=== BBB Administration Panel ===
Contributors: albertosgz
Donate link: https://github.com/albertosgz/Wordpress_BigBlueButton_plugin
Tags: bigbluebutton, opensource, open source, web, conferencing, webconferencing, multiconference
Requires at least: 3.0.1
Tested up to: 5.4
Stable tag: 1.1.9
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html


This plugin integrates BigBlueButton functionality into Wordpress.

== Description ==

[BigBlueButton](http://bigbluebutton.org/ "BigBlueButton") is an open source web conferencing system. This plugin integrates BigBlueButton into WordPress allowing bloggers to create and manage meetings rooms to interact with their readers.

* This plugin is a fork from the [BigBlueButton plugin](https://github.com/blindsidenetworks/bigbluebutton-integrations/tree/master/wordpress/bigbluebutton) released and maintained by <a href="http://blindsidenetworks.com/" target="_blank">Blindside Networks</a>.

For more information on setting up your own BigBlueButton server or for using an external hosting provider visit [http://bigbluebutton.org/support](http://bigbluebutton.org/support "http://bigbluebutton.org/support").

* The plugin does not store any private data. Only room settings to allow to starts multiconferences.

== Installation ==

   1. Log in as an admin and click on the Plugins menu on the sidebar.
   1. Click Add new.
   1. In the search bar enter "bbb administration panel" and click search plugins.
   1. When you find the plugin called BBB Administration panel by albertosgz click the install now link.
   1. Activate the Plugin.
   1. Click on widgets under the Appearance menu.
   1. Find the BigBlueButton Widget. Then click and drag it to either the right, content, or footer windows on the right of the screen depending on where you wish the BigBlueButton widget to appear.
   1. Click on BigBlueButton under the settings menu.
   1. Fill out the URL of where the BigBlueButton server is running (be sure to add /bigbluebutton/ to the end of the URL) and its salt. Then click on save changes.
   1. You are ready to begin creating meetings, and holding conferences.

== Frequently Asked Questions ==

**How do I create meetings?**

**How users join meetings?**

Users join meetings using a joining form. This form can be shown in a site as a sidebar element or as a page/post.

For setting up in the sidebar, add the bigbluebutton widget, as you do with any other, dragging the box to the position you want to.

For setting the joining form up as a page/post, add the shortcode [bigbluebutton] right where you want the form to appear in the page/post. If there are pre-created meetings in wordpress, their names should appear in a listbox from which users can select. If there is only one pre-created meeting the listbox will not be shown and one button with the name of the meeting will appear instead.

**Why sometimes the Name and Password are required, some others only the Name and others only the Password?**

The plugin gatters the much information it cans from Wordpress, but what will be taken depends of the configuration.

For registered users their registered name or username will be taken as Name. The BigBlueButton role (moderator/attendee) can be assigned automatically depending of the permission settings. This way a registered user in a role which permissions has been previously set would not be required nether for Name nor Password.

For registered users whose role has ben set for requiring always a password, only the Password will be required.

For anonymous users the Name will be always required, but again the Password requirment will depend of the configuration. If Moderator/Attendee role has ben set for them no Password box will be shown in their joining form.

**Is there any way users can go directly into a meeting?**

It is possible to provide direct access to the meeting rooms by adding the meeting token ID to the shortcode: (eg. [bigbluebutton token=aa2817f3a1e1]).

The joining form is the same, so with the right permission configuration users would be able to join meetings in one click.

**How can I show the recordings?**

The only way to show recordings to users is using the shortcode [bigbluebutton_recordings] in a page/post.

**Why is it giving an error about creating a meeting room?**

Make sure you are using BigBlueButton 0.8 or higher.

**What is this error: "Unable to display the meetings. Please check the url of the bigbluebutton server AND check to see if the bigbluebutton server is running."?**

You must make sure that your url ends with "/bigbluebutton/" at the end.

So as an example:

* Wrong - "http://example.com/"
* Correct - "http://example.com/bigbluebutton/"

**How can I improve security?**

You should enable the curl extension in php.ini.

== Screenshots ==

1. Login form for anonymous users.
2. Login form for registered users.
3. General settings.
4. Permission settings.
5. Create meeting room form and list of meeting rooms.
6. Recordings in a front end page.

== Changelog ==

= 1.1.13 = 
* Feature to change the Widget title

= 1.1.12 =
* Fixed security bug which allow people to analyze ajax request to get moderator and attendee password
* Fixed enforce min 5 digits in VoiceBridge field (https://github.com/albertosgz/Wordpress_BigBlueButton_plugin/issues/23)
* Fixed display active meetings with only one meeting (https://github.com/albertosgz/Wordpress_BigBlueButton_plugin/issues/22)
* Changed to more friendly message when no active meetings available

= 1.1.11 =
Tested plugin with Wordpress version 5.4

= 1.1.10 =
* Typo in commit

= 1.1.9 =
* Fixed redirect user from form once moderator logged into room
* Fixed to remove recordings from table when is displayed in public side

= 1.1.8 =
* Fixed Activity Monitor from public pages

= 1.1.6 =
* Fixed PHP Exception
* Fixed filtering recordings by token (meetingId)

= 1.1.5 =
* Make "welcome" input box bigger

= 1.1.4 =
* Allow html code in the welcome message and fix plugin versioning

= 1.1.3 =
* Avoid insertions of room with empty meeting token.

= 1.1.0 =
* Added feature: [download template csv file](https://github.com/albertosgz/Wordpress_BigBlueButton_plugin/issues/15).
* Fixing [undefined index error](https://github.com/albertosgz/Wordpress_BigBlueButton_plugin/issues/18)
* Fixing [upload rooms file do not work](https://github.com/albertosgz/Wordpress_BigBlueButton_plugin/issues/12)
* Fixing [search feature do not work](https://github.com/albertosgz/Wordpress_BigBlueButton_plugin/issues/11)
* Fixing [error displaying active meetings table](https://github.com/albertosgz/Wordpress_BigBlueButton_plugin/issues/10)

= 1.0.0 =
* Added the initial files.

== Upgrade Notice ==

= 1.0.0 =
This version is the official release of the bigbluebutton plugin.
