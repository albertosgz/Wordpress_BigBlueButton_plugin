# Wordpress_BigBlueButton_plugin
BigBlueButton plugin for Wordpress. Based on [bigbluebutton plugin on wodpress site] (https://es.wordpress.org/plugins/bigbluebutton/)

## Usage

### Shortcodes
After install and activate it, next shorcodes are available. Each shortcode has possible options, explained below too.

- `bigbluebutton`

  Display the form to join the meeting.

  Options:
    - `token`
    Meeting IDs to display, separated by commas (incompatible with `tokens` option)
    - `tokens`
    Meeting IDs to display, separated by commas (incompatible with `token` option)
    - `submit`
    Text to display in the button
    - `class`
    Custom CSS class to attach to form

  Example:
    ```
    [bigbluebutton token=a5e8bf58-5704-457a-b899-ef545385b98d]
    ```

- `bigbluebutton_recordings`

  Display a list of recordings.

  Options
  - `title`
    Set the title of the table
  - `token` or `tokens`
    - Token can take only 1 option, but tokens more than one
	- In case both are defined, only `token` will be considered`
	- The different options allowed are:
	  - empty: All recordings in BBB server will be displayed
	  - 'only-current-wp': All recordings related with the current WP. So if are more in BBB created by other WPs, won't be displayed.
	  - list of MeetingIDs, separated by commas.

  NOTE: The option `token` will be deprecated in future releases, so the option `tokens` **must be considered to used** instead.

  Example:
    ```
	[bigbluebutton_recordings]
	```
	```
	[bigbluebutton_recordings title='Display only recordings of rooms set by this WP' token='only-current-wp']
	```
	```
	[bigbluebutton_recordings title='Display all recordings of BBB regardless their rooms were created or not by this WP']
	```
    ```
    [bigbluebutton_recordings token=a5e8bf58-5704-457a-b899-ef545385b98d]
    ```
	```
	[bigbluebutton_recordings token='only-current-wp']
	```
	```
	[bigbluebutton_recordings tokens='only-current-wp']
	```
	```
	[bigbluebutton_recordings tokens=a5e8bf58-5704-457a-b899-ef545385b98d,12345678,meeting1,meetingFooBar]
	```


- `bigbluebutton_active_meetings`

  Display a table with all active meetings in the BBB server.

  Is intended to be used as a *Activity Monitor*.

  No options available

  Example:
    ```
    [bigbluebutton_active_meetings]
    ```

- `bigbluebutton_room_status`

  Display status of a room.

  Intended to use to know when a room is available to join. Is fully customizable.

  Options
    - `token`
    Meeting id of the room to check (mandatory). The token of a room is the value of the column `token` of the table of rooms.
    - `class`
    CSS class to apply. The word displayed is within an `span` tag.
    - `active`
    Word to display instead of `active`. Intended to be used to translate the default word.
    - `inactive`
    Word to display instead of `inactive`. Intended to be used to translate the default word.
    - `period`
    Period in milliseconds to check status. By default is 1500ms.

  Example:
    ```
    [bigbluebutton_room_status token=46c7600b-5780-45e3-95dd-67bfb06c65f3 class=bbb-room-status active=activo inactive="sala inactiva" period=5000]
    ```

### Widget
It is also available a widget, but there is no way to manage it, for example setting some option.

## Based upon original bigbluebutton plugin ##
See original [readme.txt](../master/readme.txt)

## Requirements

`sudo apt-get install php7.0-xml`
`sudo apt-get install php-curl`
