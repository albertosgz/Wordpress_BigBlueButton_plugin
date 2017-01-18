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
    
  Example:
    ```
    [bigbluebutton token=a5e8bf58-5704-457a-b899-ef545385b98d]
    ```
    
- `bigbluebutton_recordings`

  Display a list of recordings.
  
  Options
  - `title`
    Set the title of the table
  - `token`
    Filter by one meeting ID (incompatible with `tokens` option)
  - `tokens`
    Meeting IDs to display, separated by commas (incompatible with `token` option)
  Example:
    ```
    [bigbluebutton_recordings token=a5e8bf58-5704-457a-b899-ef545385b98d]
    ```
- bigbluebutton_active_meetings

  Display a table with all active meetings in the BBB server.

  Is intended to be used as a *Activity Monitor*.

  No options available
  
  Example:
    ```
    [bigbluebutton_active_meetings]
    ```
    
  In case the message `Loading...` is always displayed, check if you have enable the [cross domain issue] (https://developer.mozilla.org/en-US/docs/Web/HTTP/Access_control_CORS) in the BBB Server (  )
  
  To fix, add these lines in `?` file on BBB Server:
    ```
    ?
    ```
    
### Widget
It is also available a widget, but there is no way to manage it, for example setting some option.

## Based upon original bigbluebutton plugin ##
See original [readme.txt](../master/readme.txt) 
