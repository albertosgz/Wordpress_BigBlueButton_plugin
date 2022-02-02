<?php
/*
Plugin Name: BBB Administration Panel
Plugin URI: https://github.com/albertosgz/Wordpress_BigBlueButton_plugin
Description: Administraton panel to manage a Bigbluebutton server, its rooms and recordigns. Integrates login forms as widgets.
Version: 1.1.22
Author: Alberto Sanchez Gonzalez
Author URI: https://github.com/albertosgz
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

//================================================================================
//---------------------------Standard Plugin definition---------------------------
//================================================================================

//validate
global $wp_version;
$exit_msg = "This plugin has been designed for Wordpress 2.5 and later, please upgrade your current one.";
if(version_compare($wp_version, "2.5", "<")) {
    exit($exit_msg);
}

//constant definition
define('BBB_ADMINISTRATION_PANEL_PLUGIN_VERSION', bbb_admin_panel_get_version());
define('BBB_ADMINISTRATION_PANEL_PLUGIN_URL', plugin_dir_url( __FILE__ ));

//constant message definition
define('BBB_ADMINISTRATION_PANEL_STRING_WELCOME', '<br>Welcome to <b>%%CONFNAME%%</b>!<br><br>To understand how BigBlueButton works see our <a href="event:http://www.bigbluebutton.org/content/videos"><u>tutorial videos</u></a>.<br><br>To join the audio bridge click the headset icon (upper-left hand corner). <b>Please use a headset to avoid causing noise for others.</b>');
define('BBB_ADMINISTRATION_PANEL_STRING_MEETING_RECORDED', '<br><br>This session may be recorded.');

//constant internal definition
define("BBB_ADMINISTRATION_PANEL_FORM_IN_WIDGET", TRUE );

//================================================================================
//------------------Required Libraries and Global Variables-----------------------
//================================================================================
require('php/bbb_api.php');

//================================================================================
//------------------Code for development------------------------------------------
//================================================================================
if(!function_exists('bbb_admin_panel_log')) {
    function bbb_admin_panel_log( $message ) {
        if( WP_DEBUG === true ) {
            if( is_array( $message ) || is_object( $message ) ) {
                error_log( print_r( $message, true ) );
            } else {
                error_log( $message );
            }
        }
    }
}
bbb_admin_panel_log('Loading the plugin');

//================================================================================
//------------------------------------Main----------------------------------------
//================================================================================
//hook definitions
register_activation_hook(__FILE__, 'bbb_admin_panel_install' ); //Runs the install script (including the databse and options set up)
//register_deactivation_hook(__FILE__, 'bbb_admin_panel_uninstall') ); //Runs the uninstall function (it includes the database and options delete)
register_uninstall_hook(__FILE__, 'bbb_admin_panel_uninstall' ); //Runs the uninstall function (it includes the database and options delete)

//shortcode definitions
add_shortcode('bigbluebutton', 'bbb_admin_panel_shorcode');
add_shortcode('bigbluebutton_recordings', 'bbb_admin_panel_recordings_shortcode');
add_shortcode('bigbluebutton_active_meetings', 'bbb_admin_panel_active_meetings_shortcode');
add_shortcode('bigbluebutton_room_status', 'bbb_admin_panel_room_status_shortcode');

//action definitions
add_action('init', 'bbb_admin_panel_init');
add_action('admin_menu', 'bbb_admin_panel_add_pages', 1);
add_action('admin_init', 'bbb_admin_panel_admin_init', 1);
add_action('plugins_loaded', 'bbb_admin_panel_update' );
add_action('plugins_loaded', 'bbb_admin_panel_widget_init' );
set_error_handler("bbb_admin_panel_warning_handler", E_WARNING);

//constant DB table names
define("BBB_ADMINISTRATION_PANEL_DB_TABLE_NAME", "bigbluebutton");
define("BBB_ADMINISTRATION_PANEL_DB_LOGS_TABLE_NAME", "bigbluebutton_logs");

//================================================================================
//------------------------------ Main Functions ----------------------------------
//================================================================================
function bbb_admin_panel_get_db_table_name() {
    global $wpdb;
    return $wpdb->prefix . BBB_ADMINISTRATION_PANEL_DB_TABLE_NAME;
}

function bbb_admin_panel_get_db_table_name_logs() {
    global $wpdb;
    return $wpdb->prefix . BBB_ADMINISTRATION_PANEL_DB_LOGS_TABLE_NAME;
}

// Sessions are required by the plugin to work.
function bbb_admin_panel_init() {
    bbb_admin_panel_init_sessions();
    bbb_admin_panel_init_scripts();
    bbb_admin_panel_init_styles();

    //Attaches the plugin's stylesheet to the plugin page just created
    add_action('wp_print_styles', 'bbb_admin_panel_admin_styles');

    ////////////// ADDING JS
}

function bbb_admin_panel_init_sessions() {
}

function bbb_admin_panel_init_scripts() {
    if (!is_admin()) {
        wp_enqueue_script('jquery');
    }
    wp_enqueue_script('DataTable', BBB_ADMINISTRATION_PANEL_PLUGIN_URL . '/DataTables/datatables.min.js');
    wp_localize_script('DataTable', 'wp_ajax_tets_vars', array(
        'ajaxurl' => admin_url( 'admin-ajax.php' )
    ));
}

//Registers the plugin's stylesheet
function bbb_admin_panel_init_styles() {
    wp_register_style('BBBAdminPanelStylesheet', BBB_ADMINISTRATION_PANEL_PLUGIN_URL . '/css/bigbluebutton_stylesheet.css');
    wp_register_style('DataTable', BBB_ADMINISTRATION_PANEL_PLUGIN_URL . '/DataTables/datatables.min.css');
}

//Registers the plugin's stylesheet
function bbb_admin_panel_admin_init() {
    bbb_admin_panel_init_styles();
}

//Adds the plugin stylesheet to wordpress
function bbb_admin_panel_admin_styles() {
    wp_enqueue_style('BBBAdminPanelStylesheet');
    wp_enqueue_style('DataTable');
}

//Registers the bigbluebutton widget
function bbb_admin_panel_widget_init() {
    wp_register_sidebar_widget('bigbluebuttonsidebarwidget', __('BigBlueButton'), 'bbb_admin_panel_sidebar', array( 'description' => 'Displays a BigBlueButton login form in a sidebar.'));
}

//Inserts the plugin pages in the admin panel
function bbb_admin_panel_add_pages() {

    //Add a new submenu under Settings
    $page = add_options_page(__('BBB Admin Panel','menu-test'), __('BBB Admin Panel','menu-test'), 'manage_options', 'bigbluebutton_general', 'bbb_admin_panel_general_options');

    //Attaches the plugin's stylesheet to the plugin page just created
    add_action('admin_print_styles-' . $page, 'bbb_admin_panel_admin_styles');

}

function bbb_admin_panel_display_error_installation() {
    ?>
    <div class="error notice">
        <p><?php _e( 'Collision with other BigBlueButton plugin', 'Original BigBlueButton plugin is already activated, and must be disabled before' ); ?></p>
    </div>
    <?php
}

function bbb_admin_panel_display_installation_ok() {
    ?>
    <div class="updated notice">
        <p><?php _e( 'The plugin has been updated, excellent!', 'Enjoy it!' ); ?></p>
    </div>
    <?php
}

//Sets up the bigbluebutton table to store meetings in the wordpress database
function bbb_admin_panel_install () {
    global $wp_roles;

    if ( is_plugin_active( 'bigbluebutton/bigbluebutton-plugin.php' ) ) {
        add_action( 'admin_notices', 'bbb_admin_panel_display_error_installation' );
        return false;
    }

    // Load roles if not set
    if ( ! isset( $wp_roles ) )
        $wp_roles = new WP_Roles();

    //Installation code
    if( !get_option('bbb_admin_panel_plugin_version') ) {
        bbb_admin_panel_init_database();
    }

    ////////////////// Initialize Settings //////////////////
    if( !get_option('bbb_admin_panel_url') ) update_option( 'bbb_admin_panel_url', 'http://test-install.blindsidenetworks.com/bigbluebutton/' );
    if( !get_option('bbb_admin_panel_salt') ) update_option( 'bbb_admin_panel_salt', '8cd8ef52e8e101574e400365b55e11a6' );
    if( !get_option('bbb_admin_panel_permissions') ) {
        $roles = $wp_roles->role_names;
        $roles['anonymous'] = 'Anonymous';
        foreach($roles as $key => $value) {
            $permissions[$key]['participate'] = true;
            if($value == "Administrator") {
                $permissions[$key]['manageRecordings'] = true;
                $permissions[$key]['listActiveMeetings'] = true;
                $permissions[$key]['defaultRole'] = "moderator";
            } else if($value == "Anonymous") {
                $permissions[$key]['manageRecordings'] = false;
                $permissions[$key]['listActiveMeetings'] = false;
                $permissions[$key]['defaultRole'] = "none";
            } else {
                $permissions[$key]['manageRecordings'] = false;
                $permissions[$key]['listActiveMeetings'] = false;
                $permissions[$key]['defaultRole'] = "attendee";
            }

        }

        update_option( 'bbb_admin_panel_permissions', $permissions );

    }

    update_option( "bbb_admin_panel_plugin_version", BBB_ADMINISTRATION_PANEL_PLUGIN_VERSION );

    add_action( 'admin_notices', 'bbb_admin_panel_display_installation_ok' );

}

function bbb_admin_panel_update() {
    global $wpdb;

    $newColumnName = 'api_join_custom_parameters';
    $table_name = bbb_admin_panel_get_db_table_name();
    $row = $wpdb->get_results("
        SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = '$table_name' AND column_name = '$newColumnName'
    ");

    if(empty($row)){
        $wpdb->query("ALTER TABLE $table_name ADD $newColumnName TEXT NULL");
    }
}

function bbb_admin_panel_uninstall () {
    global $wpdb;

    //In case is deactivateing an overwritten version
    if( get_option('bbb_db_version') ) {
        $table_name_old = bbb_admin_panel_get_db_table_name() . "_old_meetingRooms";
        $wpdb->query("DROP TABLE IF EXISTS $table_name_old");
        delete_option('bbb_db_version');
        delete_option('mt_bbb_url');
        delete_option('mt_salt');
    }

    //Delete the options stored in the wordpress db
    delete_option('bbb_admin_panel_plugin_version');
    delete_option('bbb_admin_panel_url');
    delete_option('bbb_admin_panel_salt');
    delete_option('bbb_admin_panel_permissions');

    //Sets the name of the table
    $table_name = bbb_admin_panel_get_db_table_name();
    $wpdb->query("DROP TABLE IF EXISTS $table_name");

    $table_logs_name = bbb_admin_panel_get_db_table_name_logs();
    $wpdb->query("DROP TABLE IF EXISTS $table_logs_name");

}

//Creates the bigbluebutton tables in the wordpress database
function bbb_admin_panel_init_database() {
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    global $wpdb;

    //Sets the name of the table
    $table_name = bbb_admin_panel_get_db_table_name();
    $table_logs_name = bbb_admin_panel_get_db_table_name_logs();

    //Execute sql
    $sql = "CREATE TABLE " . $table_name . " (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    meetingID text NOT NULL,
    meetingName text NOT NULL,
    meetingVersion int NOT NULL,
    attendeePW text NOT NULL,
    moderatorPW text NOT NULL,
    waitForModerator BOOLEAN NOT NULL DEFAULT FALSE,
    recorded BOOLEAN NOT NULL DEFAULT FALSE,
    voiceBridge text NOT NULL,
    welcome text NOT NULL,
    api_join_custom_parameters TEXT NULL,
    UNIQUE KEY id (id)
    );";
    dbDelta($sql);

    // $sql = "INSERT INTO " . $table_name . " (meetingID, meetingName, meetingVersion, attendeePW, moderatorPW)
    // VALUES ('".bbb_admin_panel_generateToken()."','Demo meeting', '".time()."', 'ap', 'mp');";
    // dbDelta($sql);
    //
    // $sql = "INSERT INTO " . $table_name . " (meetingID, meetingName, meetingVersion, attendeePW, moderatorPW, recorded)
    // VALUES ('".bbb_admin_panel_generateToken()."','Demo meeting (recorded)', '".time()."', 'ap', 'mp', TRUE);";
    // dbDelta($sql);

    $sql = "CREATE TABLE " . $table_logs_name . " (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    meetingID text NOT NULL,
    recorded BOOLEAN NOT NULL DEFAULT FALSE,
    timestamp int NOT NULL,
    event text NOT NULL,
    UNIQUE KEY id (id)
    );";
    dbDelta($sql);

}

//Returns current plugin version.
function bbb_admin_panel_get_version() {
    if ( !function_exists( 'get_plugins' ) )
        require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
    $plugin_folder = get_plugins( '/' . plugin_basename( dirname( __FILE__ ) ) );
    $plugin_file = basename( ( __FILE__ ) );

    return $plugin_folder[$plugin_file]['Version'];
}


//================================================================================
//------------------------------Error Handler-------------------------------------
//================================================================================
function bbb_admin_panel_warning_handler($errno, $errstr) {
    //Do Nothing
}


//================================================================================
//---------------------------------ShortCode functions----------------------------
//================================================================================
//Inserts a bigbluebutton form on a post or page of the blog
function bbb_admin_panel_shorcode($args) {
    extract($args);

    return bbb_admin_panel_form($args);

}

function bbb_admin_panel_recordings_shortcode($args) {
    extract($args);

    return bbb_admin_panel_list_recordings((isset($args['title'])? $args['title']: null), $args);

}

function bbb_admin_panel_active_meetings_shortcode($args) {
  return bbb_admin_panel_list_active_meetings(false); // false => no tooltips of participants in public view
}

function bbb_admin_panel_room_status_shortcode($args) {
    $token = $args['token'] ? $args['token'] : null;
    $class = $args['class'] ? $args['class'] : null;
    $active = $args['active'] ? $args['active'] : null;
    $inactive = $args['inactive'] ? $args['inactive'] : null;
    $period = $args['period'] ? $args['period'] : null; // in seconds
    return bbb_admin_panel_room_status($token, $class, $active, $inactive, $period);
}

//================================================================================
//---------------------------------Widget-----------------------------------------
//================================================================================
//Inserts a bigbluebutton widget on the siderbar of the blog
function bbb_admin_panel_sidebar($args) {
    extract($args);

    $name = get_option('bbb_admin_panel_widget_title');
    echo $before_widget;
    echo $before_title.$name.$after_title;
    echo bbb_admin_panel_form($args, BBB_ADMINISTRATION_PANEL_FORM_IN_WIDGET);
    echo $after_widget;
}

//================================================================================
//Create the form called by the Shortcode and Widget functions
function bbb_admin_panel_form($args, $bigbluebutton_form_in_widget = false) {
    global $wpdb, $wp_version, $current_site, $current_user, $wp_roles;
    $table_name = bbb_admin_panel_get_db_table_name();
    $table_logs_name = bbb_admin_panel_get_db_table_name_logs();

    $token = isset($args['token']) ?$args['token']: null;
    $tokens = isset($args['tokens']) ?$args['tokens']: null;
    $submit = isset($args['submit']) ?$args['submit']: null;
    $customClass = isset($args['class']) ?$args['class']: null;

    //Initializes the variable that will collect the output
    $out = '';

    //Set the role for the current user if is logged in
    $role = null;
    if( $current_user->ID ) {
        $role = "unregistered";
        foreach($wp_roles->role_names as $_role => $Role) {
            if (array_key_exists($_role, $current_user->caps)) {
                $role = $_role;
                break;
            }
        }
    } else {
        $role = "anonymous";
    }

    //Read in existing option value from database
    $url_val = get_option('bbb_admin_panel_url');
    $salt_val = get_option('bbb_admin_panel_salt');
    //Read in existing permission values from database
    $permissions = get_option('bbb_admin_panel_permissions');

    //Gets all the meetings from wordpress database
    $listOfMeetings = $wpdb->get_results("SELECT meetingID, meetingName, meetingVersion, attendeePW, moderatorPW, voiceBridge, welcome FROM ".$table_name." ORDER BY meetingName");

    $dataSubmitted = false;
    $meetingExist = false;
    if( isset($_POST['SubmitForm']) ) { //The user has submitted his login information
        $dataSubmitted = true;
        $meetingExist = true;

        $meetingID = filter_input(INPUT_POST, 'meetingID', FILTER_SANITIZE_SPECIAL_CHARS);

        $sql = "SELECT * FROM ".$table_name." WHERE meetingID = %s";
        $found = $wpdb->get_row(
                $wpdb->prepare($sql, $meetingID)
        );
        if( $found ) {

            if( !$current_user->ID ) {
                if(isset($_POST['display_name']) && $_POST['display_name']) {
                    $name = htmlspecialchars(filter_input(INPUT_POST, 'display_name', FILTER_SANITIZE_SPECIAL_CHARS));
                } else {
                    $name = $role;
                }

                if( bbb_admin_panel_validate_defaultRole($role, 'none') ) {
                    $password = filter_input(INPUT_POST, 'pwd', FILTER_SANITIZE_SPECIAL_CHARS);
                } else {
                    $password = $permissions[$role]['defaultRole'] == 'none'? $found->moderatorPW: $found->attendeePW;
                }

            } else {
                if( $current_user->display_name != '' ) {
                    $name = $current_user->display_name;
                } else if( $current_user->user_firstname != '' || $current_user->user_lastname != '' ) {
                    $name = $current_user->user_firstname != ''? $current_user->user_firstname.' ': '';
                    $name .= $current_user->user_lastname != ''? $current_user->user_lastname.' ': '';
                } else if( $current_user->user_login != '') {
                    $name = $current_user->user_login;
                } else {
                    $name = $role;
                }
                if( bbb_admin_panel_validate_defaultRole($role, 'none') ) {
                    $password = filter_input(INPUT_POST, 'pwd', FILTER_SANITIZE_SPECIAL_CHARS);
                } else {
                    $password = $permissions[$role]['defaultRole'] == 'moderator'? $found->moderatorPW: $found->attendeePW;
                }

            }

            //Extra parameters
            $recorded = $found->recorded;
            if( $found->welcome ) {
                $welcome = html_entity_decode($found->welcome);
            } else {
                $welcome = (isset($args['welcome']))? html_entity_decode($args['welcome']): BBB_ADMINISTRATION_PANEL_STRING_WELCOME;
            }
            if( $recorded ) $welcome .= BBB_ADMINISTRATION_PANEL_STRING_MEETING_RECORDED;
            $duration = 0;
            if( $found->voiceBridge ) {
                $voiceBridge = $found->voiceBridge;
            } /*
               * I don't want to allow to set the voicebridge by shortcode, but by bbb settings page instead
               *
            else {
                $voiceBridge = (isset($args['voicebridge']))? html_entity_decode($args['voicebridge']): 0;
            }*/
            $logouturl = (is_ssl()? "https://": "http://") . $_SERVER['HTTP_HOST']  . $_SERVER['REQUEST_URI'];

            //Metadata for tagging recordings
            $metadata = Array(
                    'meta_origin' => 'WordPress',
                    'meta_originversion' => $wp_version,
                    'meta_origintag' => 'wp_plugin-bigbluebutton '.BBB_ADMINISTRATION_PANEL_PLUGIN_VERSION,
                    'meta_originservername' => home_url(),
                    'meta_originservercommonname' => get_bloginfo('name'),
                    'meta_originurl' => $logouturl
            );

            //Appending the voiceBridge key to the welcome message
            $welcome .= "<br><br>The voiceBridge of this Conference room is <b>".$voiceBridge."</b>.";

            $customParameters = bbb_admin_panel_parse_custom_parameters($found->api_join_custom_parameters);
            foreach($customParameters as $key => $customParam) {
                $metadata[$key] = $customParam;
            }

            //Call for creating meeting on the bigbluebutton server
            $response = BigBlueButtonAPI::createMeetingArray($name, $found->meetingID, $found->meetingName, $welcome, $found->moderatorPW, $found->attendeePW, $salt_val, $url_val, $logouturl, $recorded? 'true':'false', $duration, $voiceBridge, $metadata );

            //Analyzes the bigbluebutton server's response
            if(!$response || $response['returncode'] == 'FAILED' ) {//If the server is unreachable, or an error occured
                $out .= "Sorry an error occured while joining the meeting.";
                return $out;

            } else{ //The user can join the meeting, as it is valid
                if( !isset($response['messageKey']) || $response['messageKey'] == '' ) {
                    // The meeting was just created, insert the create event to the log
                    $rows_affected = $wpdb->insert( $table_logs_name, array( 'meetingID' => $found->meetingID, 'recorded' => $found->recorded, 'timestamp' => time(), 'event' => 'Create' ) );
                }

                $bigbluebutton_joinURL = BigBlueButtonAPI::getJoinURL($found->meetingID, $name, $password, $salt_val, $url_val, $customParameters );
                //If the meeting is already running or the moderator is trying to join or a viewer is trying to join and the
                //do not wait for moderator option is set to false then the user is immediately redirected to the meeting
                if ( (BigBlueButtonAPI::isMeetingRunning( $found->meetingID, $url_val, $salt_val ) && ($found->moderatorPW == $password || $found->attendeePW == $password ) )
                        || $response['moderatorPW'] == $password
                        || ($response['attendeePW'] == $password && !$found->waitForModerator)  ) {
                    //If the password submitted is correct then the user gets redirected
                    $out .= '<script type="text/javascript">window.location = "'.$bigbluebutton_joinURL.'";</script>'."\n";
                    return $out;
                }
                //If the viewer has the correct password, but the meeting has not yet started they have to wait
                //for the moderator to start the meeting
                else if ($found->attendeePW == $password) {
                    //Displays the javascript to automatically redirect the user when the meeting begins
                    $out .= bbb_admin_panel_display_redirect_script($bigbluebutton_joinURL, $found->meetingID, $found->meetingName, $name);
                    return $out;
                }
            }
        }
    }

    //If a valid meeting was found the login form is displayed
    if(sizeof($listOfMeetings) > 0) {
        //Alerts the user if the password they entered does not match
        //the meeting's password

        if($dataSubmitted && !$meetingExist) {
            $out .= "***".$meetingID." no longer exists.***";
        }
        else if($dataSubmitted) {
            $out .= "***Incorrect Password***";
        }

        if ( bbb_admin_panel_can_participate($role) ) {
            $out .= '
            <form id="bbb-join-form'.($bigbluebutton_form_in_widget?'-widget': '').'" class="bbb-join '.($customClass ? $customClass : '').'" name="form1" method="post" action="">';

            if(sizeof($listOfMeetings) > 1 && !$token ) {
                if( isset($tokens) && trim($tokens) != '' ) {
                    $tokens_array = explode(',', $tokens);
                    $where = "";
                    foreach ($tokens_array as $tokens_element) {
                        if( $where == "" )
                            $where .= " WHERE meetingID='".$tokens_element."'";
                        else
                            $where .= " OR meetingID='".$tokens_element."'";
                    }
                    $listOfMeetings = $wpdb->get_results("SELECT meetingID, meetingName, meetingVersion, attendeePW, moderatorPW FROM ".$table_name.$where." ORDER BY meetingName");
                }
                $out .= '
                <label>Meeting:</label>
                <select name="meetingID">';

                foreach ($listOfMeetings as $meeting) {
                    $out .= '
                    <option value="'.$meeting->meetingID.'">'.$meeting->meetingName.'</option>';
                }

                $out .= '
                </select>';
            } else if ($token) {
                $out .= '
                <input type="hidden" name="meetingID" id="meetingID" value="'.$token.'" />';

            } else {
                $meeting = reset($listOfMeetings);
                $out .= '
                <input type="hidden" name="meetingID" id="meetingID" value="'.$meeting->meetingID.'" />';

            }

            if( !$current_user->ID ) {
                $out .= '
                <label>Name:</label>
                <input type="text" id="name" name="display_name" size="10">';
            }
            if( bbb_admin_panel_validate_defaultRole($role, 'none') ) {
                $out .= '
                <label>Password:</label>
                <input type="password" name="pwd" size="10">';
            }
            $out .= '
            </table>';
            if(sizeof($listOfMeetings) > 1 && !$token ) {
                $out .= '

                <input type="submit" name="SubmitForm" value="'.($submit? $submit: 'Join').'">';
            } else if ($token) {
                foreach ($listOfMeetings as $meeting) {
                    if($meeting->meetingID == $token ) {
                        $out .= '
                <input type="submit" name="SubmitForm" value="'.($submit? $submit: 'Join '.$meeting->meetingName).'">';
                        break;
                    }
                }

                if($meeting->meetingID != $token ) {
                    $out .= '
                <div>Invalid meeting token</div>';
                }

            } else {
                $out .= '
                <input type="submit" name="SubmitForm" value="'.($submit? $submit: 'Join '.$meeting->meetingName).'">';

            }
            $out .= '
            </form>';

        } else {
            $out .= $role." users are not allowed to participate in meetings";

        }

    } else if($dataSubmitted) {
        //Alerts the user if the password they entered does not match
        //the meeting's password
        $out .= "***".$meetingID." no longer exists.***<br />";
        $out .= "No meeting rooms are currently available to join.";

    } else{
        $out .= "No meeting rooms are currently available to join.";

    }

    return $out;
}


//Displays the javascript that handles redirecting a user, when the meeting has started
//the meetingName is the meetingID
add_action( 'wp_ajax_nopriv_bbbadminpanel_action_display_redirect_script', 'bbbadminpanel_action_display_redirect_script' );
add_action( 'wp_ajax_bbbadminpanel_action_display_redirect_script', 'bbbadminpanel_action_display_redirect_script' );

function bbbadminpanel_action_display_redirect_script() {
	global $wpdb; // this is how you get access to the database

    $url_val = get_option('bbb_admin_panel_url');
    $salt_val = get_option('bbb_admin_panel_salt');
    $meetingID = filter_input(INPUT_GET, 'meetingID', FILTER_SANITIZE_URL);

    if (!$meetingID) {
        header("HTTP/1.0 400 Bad Request. [recordingID] parameter was not included in this query.");
        wp_die();
    }

    $info = BigBlueButtonAPI::getMeetingXML( $meetingID, $url_val, $salt_val );
    if (isset($info->running)) {
        echo json_encode([
            'running' => (string) $info->running
        ]);
    } else {
        echo json_encode([
            'error' => 'something happened with BBB connection'
        ]);
    }

	wp_die(); // this is required to terminate immediately and return a proper response
}

function bbb_admin_panel_display_redirect_script($bigbluebutton_joinURL, $meetingID, $meetingName, $name) {
    $out = '
    <script type="text/javascript">
        function bigbluebutton_ping() {
            jQuery.ajax({
                url: wp_ajax_tets_vars.ajaxurl,
                data: {
                    "action": "bbbadminpanel_action_display_redirect_script",
                    "meetingID": "'.urlencode($meetingID).'"
                },
                async : true,
                dataType : "json",
                success : function(response) {
                    if(response.running == "true") {
                        window.location = "'.$bigbluebutton_joinURL.'";
                    }
                },
                error : function(xmlHttpRequest, status, error) {
                    console.debug(xmlHttpRequest);
                }
            });

        }

        setInterval("bigbluebutton_ping()", 15000);
    </script>';

    $out .= '
    <table>
      <tbody>
        <tr>
          <td>
            Welcome '.$name.'!<br /><br />
            '.$meetingName.' session has not been started yet.<br /><br />
            <div align="center"><img src="' . BBB_ADMINISTRATION_PANEL_PLUGIN_URL . 'images/polling.gif" /></div><br />
            (Your browser will automatically refresh and join the meeting when it starts.)
          </td>
        </tr>
      </tbody>
    </table>';

    return $out;
}


//================================================================================
//---------------------------------bigbluebutton Page--------------------------------------
//================================================================================
//The main page where the user specifies the url of the bigbluebutton server and its salt
function bbb_admin_panel_general_options() {

    //Checks to see if the user has the sufficient persmissions and capabilities
    if (!current_user_can('manage_options'))
    {
        wp_die( __('You do not have sufficient permissions to access this page.') );
    }

    echo bbb_admin_panel_general_settings();
    /* If the bigbluebutton server url and salt are empty then it does not
     display the create meetings, and list meetings sections.*/
    $url_val = get_option('bbb_admin_panel_url');
    $salt_val = get_option('bbb_admin_panel_salt');
    if($url_val == '' || $salt_val == '') {
        $out .= '</div>';

    } else {
        echo bbb_admin_panel_permission_settings();

        echo bbb_admin_panel_create_meetings();

        echo bbb_admin_panel_upload_rooms();

        echo bbb_admin_panel_list_meetings();

        echo bbb_admin_panel_list_recordings('List of Recordings', null);

        echo bbb_admin_panel_list_active_meetings(true); // true => display participants in admin view
    }

}

//================================================================================
//------------------------------General Settings----------------------------------
//================================================================================
// The page allows the user specifies the url of the bigbluebutton server and its salt
function bbb_admin_panel_general_settings() {

    //Initializes the variable that will collect the output
    $out = '';

    //Displays the title of the page
    $out .= '<div class="wrap">';
    $out .= "<h2>BigBlueButton General Settings</h2>";

    $url_val = get_option('bbb_admin_panel_url');
    $salt_val = get_option('bbb_admin_panel_salt');

    //Obtains the meeting information of the meeting that is going to be terminated
    if( isset($_POST['SubmitSettings']) &&
            $_POST['SubmitSettings'] == 'Save Settings' &&
            isset( $_POST['nonce_settings']) &&
            wp_verify_nonce( $_POST['nonce_settings'], 'bbb_admin_panel_general_settings' )) {

        //Reads their posted value
        $url_val = filter_input(INPUT_POST, 'bbb_admin_panel_url', FILTER_SANITIZE_SPECIAL_CHARS);
        $salt_val = filter_input(INPUT_POST, 'bbb_admin_panel_salt', FILTER_SANITIZE_SPECIAL_CHARS);
        $newWidgetTitle = filter_input(INPUT_POST, 'bbb_admin_panel_widget_title', FILTER_SANITIZE_SPECIAL_CHARS);

        //
        if(strripos($url_val, "/bigbluebutton/") == false) {
            if(substr($url_val, -1) == "/") {
                $url_val .= "bigbluebutton/";
            }
            else{
                $url_val .= "/bigbluebutton/";
            }
        }

        // Save the posted value in the database
        update_option('bbb_admin_panel_url', $url_val );
        update_option('bbb_admin_panel_salt', $salt_val );
        update_option('bbb_admin_panel_widget_title', $newWidgetTitle);

        // Put an settings updated message on the screen
        $out .= '<div class="updated"><p><strong>Settings saved.</strong></p></div>';

    }

    if($url_val == "http://test-install.blindsidenetworks.com/bigbluebutton/" ) {
        $out .= '<div class="updated"><p><strong>You are using a test BigBlueButton server provided by <a href="http://blindsidenetworks.com/" target="_blank">Blindside Networks</a>. For more information on setting up your own BigBlueButton server see <i><a href="http://bigbluebutton.org/support" target="_blank">http://bigbluebutton.org/support.</a></i></strong></div>';
    }
    //Form to update the url of the bigbluebutton server, and it`s salt

    $widgetTitle = get_option('bbb_admin_panel_widget_title');

    $out .= '
    <form name="form1" method="post" action="">
    <p>BigBlueButton URL:<input type="text" name="bbb_admin_panel_url" value="'.$url_val.'" size="60"><br> eg. \'http://test-install.blindsidenetworks.com/bigbluebutton/\'
    </p>
    <p>BigBlueButton shared secret:<input type="text" name="bbb_admin_panel_salt" value="'.$salt_val.'" size="40"><br> It can be found in /var/lib/tomcat7/webapps/bigbluebutton/WEB-INF/classes/bigbluebutton.properties.<br>eg. \'8cd8ef52e8e101574e400365b55e11a6\'.
    </p>
    <p>Widget title:<input type="text" name="bbb_admin_panel_widget_title" value="'.$widgetTitle.'" size="25"><br> eg. BigBlueButton.
    </p>
    <p class="submit">
    <input type="submit" name="SubmitSettings" class="button-primary" value="Save Settings" />
    </p>
    <input type="hidden" name="nonce_settings" value="'.wp_create_nonce('bbb_admin_panel_general_settings').'" />
    </form>
    <hr />';

    return $out;

}

//================================================================================
//------------------------------Permisssion Settings----------------------------------
//================================================================================
// The page allows the user grants permissions for accessing meetings
function bbb_admin_panel_permission_settings() {
    global $wp_roles;
    $roles = $wp_roles->role_names;
    $roles['anonymous'] = 'Anonymous';

    //Initializes the variable that will collect the output
    $out = '';

    if( isset($_POST['SubmitPermissions']) &&
            filter_input(INPUT_POST, 'SubmitPermissions', FILTER_SANITIZE_SPECIAL_CHARS) == 'Save Permissions' &&
            isset( $_POST['nonce_permissions']) &&
            wp_verify_nonce( $_POST['nonce_permissions'], 'bbb_admin_panel_permission_settings' )) {

        foreach($roles as $key => $value) {
            if( !isset($_POST[$key.'-defaultRole']) ) {
                if( $value == "Administrator" ) {
                    $permissions[$key]['defaultRole'] = 'moderator';
                } else if ( $value == "Anonymous" ) {
                    $permissions[$key]['defaultRole'] = 'none';
                } else {
                    $permissions[$key]['defaultRole'] = 'attendee';
                }
            } else {
                $permissions[$key]['defaultRole'] = filter_input(INPUT_POST, $key.'-defaultRole', FILTER_SANITIZE_SPECIAL_CHARS);
            }

            if( !isset($_POST[$key.'-participate']) ) {
                $permissions[$key]['participate'] = false;
            } else {
                $permissions[$key]['participate'] = true;
            }

            if( !isset($_POST[$key.'-manageRecordings']) ) {
                $permissions[$key]['manageRecordings'] = false;
            } else {
                $permissions[$key]['manageRecordings'] = true;
            }

            if( !isset($_POST[$key.'-listActiveMeetings']) ) {
                $permissions[$key]['listActiveMeetings'] = false;
            } else {
                $permissions[$key]['listActiveMeetings'] = true;
            }
        }
        update_option( 'bbb_admin_panel_permissions', $permissions );

    } else {
        $permissions = get_option('bbb_admin_panel_permissions');

    }

    //Displays the title of the page
    $out .= "<h2>BigBlueButton Permission Settings</h2>";

    $out .= '</br>';

    $out .= '
    <form name="form1" method="post" action="">
    <table class="stats" cellspacing="5">
    <tr>
    <th class="hed" colspan="1">Role</td>
    <th class="hed" colspan="1">Manage Recordings</th>
    <th class="hed" colspan="1">Participate</th>
    <th class="hed" colspan="1">Join as Moderator</th>
    <th class="hed" colspan="1">Join as Attendee</th>
    <th class="hed" colspan="1">Join with Password</th>
    <th class="hed" colspan="1">List Active Meetings</th>
    </tr>';

    foreach($roles as $key => $value) {
        if (isset($permissions[$key]) && isset($permissions[$key]['manageRecordings']) && isset($permissions[$key]['participate']) &&
                isset($permissions[$key]['defaultRole']) && isset($permissions[$key]['listActiveMeetings'])) {
            $out .= '
            <tr>
            <td>'.$value.'</td>
            <td><input type="checkbox" name="'.$key.'-manageRecordings" '.($permissions[$key]['manageRecordings']?'checked="checked"': '').' /></td>
            <td><input type="checkbox" name="'.$key.'-participate" '.($permissions[$key]['participate']?'checked="checked"': '').' /></td>
            <td><input type="radio" name="'.$key.'-defaultRole" value="moderator" '.($permissions[$key]['defaultRole']=="moderator"?'checked="checked"': '').' /></td>
            <td><input type="radio" name="'.$key.'-defaultRole" value="attendee" '.($permissions[$key]['defaultRole']=="attendee"?'checked="checked"': '').' /></td>
            <td><input type="radio" name="'.$key.'-defaultRole" value="none" '.($permissions[$key]['defaultRole']=="none"?'checked="checked"': '').' /></td>
            <td><input type="checkbox" name="'.$key.'-listActiveMeetings" '.($permissions[$key]['listActiveMeetings']?'checked="checked"': '').' /></td>
            </tr>';
        }
    }

    $out .= '
    </table>
    <p class="submit"><input type="submit" name="SubmitPermissions" class="button-primary" value="Save Permissions" /></p>
    <input type="hidden" name="nonce_permissions" value="'.wp_create_nonce('bbb_admin_panel_permission_settings').'" />
    </form>
    <hr />';

    return $out;

}

//================================================================================
//-----------------------------Create a Meeting-----------------------------------
//================================================================================
//This page allows the user to create a meeting
function bbb_admin_panel_create_meetings() {
    global $wpdb;

    //Initializes the variable that will collect the output
    $out = '';

    //Displays the title of the page
    $out .= "<h2>Create a Meeting Room</h2>";

    $url_val = get_option('bbb_admin_panel_url');
    $salt_val = get_option('bbb_admin_panel_salt');

    //Obtains the meeting information of the meeting that is going to be created
    if (isset($_POST['SubmitCreate']) &&
            $_POST['SubmitCreate'] == 'Create' &&
            isset( $_POST['nonce_create_meetings']) &&
            wp_verify_nonce( $_POST['nonce_create_meetings'], 'bbb_admin_panel_create_meetings' )) {

        /// Reads the posted values
        $meetingName = filter_input(INPUT_POST, 'meetingName', FILTER_SANITIZE_SPECIAL_CHARS);
        $attendeePW = filter_input(INPUT_POST, 'attendeePW', FILTER_SANITIZE_SPECIAL_CHARS)? : bbb_admin_panel_generatePassword(6, 2);
        $moderatorPW = filter_input(INPUT_POST, 'moderatorPW', FILTER_SANITIZE_SPECIAL_CHARS)? : bbb_admin_panel_generatePassword(6, 2, $attendeePW);
        $voiceBridge = filter_input(INPUT_POST, 'voiceBridge', FILTER_SANITIZE_SPECIAL_CHARS)? : 0;
        $waitForModerator = (isset($_POST[ 'waitForModerator' ]) && $_POST[ 'waitForModerator' ] == 'True')? true: false;
        $recorded = (isset($_POST[ 'recorded' ]) && $_POST[ 'recorded' ] == 'True')? true: false;
        $welcome = htmlentities(stripslashes($_POST['welcome']));
        $apiJoinCustomParameters = htmlentities(stripslashes($_POST['api_join_custom_parameters']));
        $meetingVersion = time();
        /// Assign a random seed to generate unique ID on a BBB server
        $meetingID = bbb_admin_panel_generateToken();


        //Checks to see if the meeting name, attendee password or moderator password was left blank
        if($meetingName == '' || $attendeePW == '' || $moderatorPW == '' || $voiceBridge == '') {
            //If the meeting name was left blank, the user is prompted to fill it out
            $out .= '<div class="updated">
            <p>
            <strong>Meeting room name, Attendee password, Moderator password and Voicebridge must be filled.</strong>
            </p>
            </div>';

        } else {
            $alreadyExists = false;

            //Checks the meeting to be created to see if it already exists in wordpress database
            $table_name = bbb_admin_panel_get_db_table_name();
            $listOfMeetings = $wpdb->get_results("SELECT meetingID, meetingName FROM ".$table_name);

            foreach ($listOfMeetings as $meeting) {
                if($meeting->meetingName == $meetingName) {
                    $alreadyExists = true;
                    //Alerts the user to choose a different name
                    $out .= '<div class="updated">
                    <p>
                    <strong>'.$meetingName.' meeting room already exists. Please select a different name.</strong>
                    </p>
                    </div>';
                    break;
                }
            }

            //If the meeting doesn't exist in the wordpress database then create it
            if(!$alreadyExists) {
                $rows_affected = $wpdb->insert( $table_name, array(
                    'meetingID' => $meetingID,
                    'meetingName' => $meetingName,
                    'meetingVersion' => $meetingVersion,
                    'attendeePW' => $attendeePW,
                    'moderatorPW' => $moderatorPW,
                    'waitForModerator' => $waitForModerator? 1: 0,
                    'recorded' => $recorded? 1: 0,
                    'voiceBridge' => $voiceBridge,
                    'welcome' => $welcome,
                    'api_join_custom_parameters' => $apiJoinCustomParameters,
                ));

                $out .= '<div class="updated">
                <p>
                <strong>Meeting Room Created.</strong>
                </p>
                </div>';

            }

        }

    }

    //Form to create a meeting, the fields are the meeting name, and the optional fields are the attendee password and moderator password
    $out .= '
    <form name="form1" method="post" action="">
    <p>Meeting Room Name: <input type="text" name="meetingName" value="" size="20"></p>
    <p>Attendee Password: <input type="text" name="attendeePW" value="" size="20"></p>
    <p>Moderator Password: <input type="text" name="moderatorPW" value="" size="20"></p>
    <p>Voicebridge: <input type="number" name="voiceBridge" value="" min="10000" size="5"> (recommented 5 digits)</p>
    <p>Wait for moderator to start meeting: <input type="checkbox" name="waitForModerator" value="True" /></p>
    <p>Recorded meeting: <input type="checkbox" name="recorded" value="True" /></p>
    <p>Welcome message: <input type="text" name="welcome" value="" size="100"> (leave blank to default one)</p>
    <p>Join Custom Parameters: <input type="text" name="api_join_custom_parameters" value="" size="100">Custom parameters, separated by \'|\', i.e.: logo=foo|userdata-bbb_custom_style=bar</p>
    <p class="submit"><input type="submit" name="SubmitCreate" class="button-primary" value="Create" /></p>
    <input type="hidden" name="nonce_create_meetings" value="'.wp_create_nonce('bbb_admin_panel_create_meetings').'" />
    </form>
    <hr />';

    return $out;

}

add_action( 'wp_ajax_bbb_admin_panel_download_template_backup_file', 'bbb_admin_panel_download_template_backup_file' );

const BBB_ADMINISTRATION_PANEL_TABLE_ROOMS_COLUMN_NAMES = [
    'Meeting Room Name',
    'Meeting Token',
    'Attendee Password',
    'Moderator Password',
    'Wait for Moderator',
    'Recorded',
    'VoiceBridge',
    'Welcome Message',
    'Join Custom Parameters',
];

function bbb_admin_panel_download_template_backup_file() {

    $filename = "backup_template.csv";
    $array = [
        BBB_ADMINISTRATION_PANEL_TABLE_ROOMS_COLUMN_NAMES
    ];

    header('Content-Type: application/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="'.$filename.'";');

    // open the "output" stream
    // see http://www.php.net/manual/en/wrappers.php.php#refsect2-wrappers.php-unknown-unknown-unknown-descriptioq
    $f = fopen('php://output', 'w');
    foreach ($array as $line) {
        fputcsv($f, $line);
    }
    fclose($f);

    wp_die();
}

function bbb_admin_panel_upload_rooms() {
    global $wpdb;

    // @note FROM: https://wordpress.stackexchange.com/questions/4307/how-can-i-add-an-image-upload-field-directly-to-a-custom-write-panel/4413#4413

    //Initializes the variable that will collect the output
    $out = '';

    //Displays the title of the page
    $out .= "<h2>Restore database from backup CSV file</h2>";

    if(isset($_POST['xxxx_manual_save_flag']) &&
            isset( $_POST['nonce_upload_rooms']) &&
            wp_verify_nonce( $_POST['nonce_upload_rooms'], 'bbb_admin_panel_upload_rooms' )) {

        // HANDLE THE FILE UPLOAD
        $upload_feedback = false;

        // If the upload field has a file in it
        if(isset($_FILES['xxxx_image']) && ($_FILES['xxxx_image']['size'] > 0)) {
            // Get the type of the uploaded file. This is returned as "type/extension"
            $arr_file_type = wp_check_filetype(basename($_FILES['xxxx_image']['name']));
            $uploaded_file_type = $arr_file_type['type'];

            // Set an array containing a list of acceptable formats
            $allowed_file_types = array('text/csv');

            // If the uploaded file is the right format
            if(in_array($uploaded_file_type, $allowed_file_types)) {

                // Options array for the wp_handle_upload function. 'test_upload' => false
                $upload_overrides = array( 'test_form' => false );

                // Handle the upload using WP's wp_handle_upload function. Takes the posted file and an options array
                $uploaded_file = wp_handle_upload($_FILES['xxxx_image'], $upload_overrides);


                // If the wp_handle_upload call returned a local path for the image
                if(isset($uploaded_file['file'])) {

                    // The wp_insert_attachment function needs the literal system path, which was passed back from wp_handle_upload
                    $file_name_and_location = $uploaded_file['file'];

                    $overwrite_rooms = (isset($_POST[ 'overwrite_rooms' ]) && ($_POST[ 'overwrite_rooms' ] == 'True' || $_POST[ 'overwrite_rooms' ] == 'true'));

                    $row = 1;
                    $inserted = 0;
                    $updated = 0;
                    $emptyTokenRows = [];
                    $headers = BBB_ADMINISTRATION_PANEL_TABLE_ROOMS_COLUMN_NAMES;
                    $table_name = bbb_admin_panel_get_db_table_name();
                    $listOfMeetings = [];
                    foreach($wpdb->get_results("SELECT meetingID FROM ".$table_name) as $value) {
                        $listOfMeetings [] = $value->meetingID;
                    }

                    if (($gestor = fopen($file_name_and_location, "r")) !== FALSE) {
                        while (($data = fgetcsv($gestor)) !== FALSE) {

                            if ($row === 1) {
                                for ($c=0; $c < count($headers); $c++) {
                                    if ($data[$c] != $headers[$c]) {
                                        $upload_feedback = 'first row must be the headers. $c='.$c.'. "'.$data[$c].'" != "'.$headers[$c].'". Row:'.print_r($data, true).'. Headers: '.print_r($headers, true);
                                        break 2;
                                    }
                                }
                            } else {

                                $existsRoom = in_array($data[1], $listOfMeetings);

                                $meetingId = filter_var($data[1], FILTER_SANITIZE_SPECIAL_CHARS);
                                if ($meetingId) {
                                    $data = [
                                        'meetingID' => $meetingId,
                                        'meetingName' => filter_var($data[0], FILTER_SANITIZE_SPECIAL_CHARS),
                                        'meetingVersion' => time(),
                                        'attendeePW' => filter_var($data[2], FILTER_SANITIZE_SPECIAL_CHARS),
                                        'moderatorPW' => filter_var($data[3], FILTER_SANITIZE_SPECIAL_CHARS),
                                        'waitForModerator' => filter_var($data[4], FILTER_VALIDATE_BOOLEAN),
                                        'recorded' => filter_var($data[5], FILTER_VALIDATE_BOOLEAN),
                                        'voiceBridge' => filter_var($data[6], FILTER_SANITIZE_SPECIAL_CHARS),
                                        'welcome' => htmlentities(stripslashes($data[7])),
                                        'api_join_custom_parameters' => htmlentities(stripslashes($data[8])),
                                    ];
                                    if (!$existsRoom) {
                                        $inserted += $wpdb->insert( $table_name, $data);
                                    } elseif ($existsRoom && $overwrite_rooms) {
                                        $updated += $wpdb->update($table_name, $data, ['meetingID' => $meetingId]);
                                    }
                                } else {
                                    $emptyTokenRows [] = $row;
                                }

                            }
                            $row++;
                        }
                        fclose($gestor);
                    }

                    // Set the feedback flag to false, since the upload was successful
                    if (!$upload_feedback) {
                        $upload_feedback = '
                            Uploaded ok<br />
                            Readed '.($row-2).' rooms from CSV file.<br />
                            Added '.$inserted.' new rooms to databse.<br />
                            Updated '.$updated.' rooms from database (same Meeting Token).<br />
                        ';

                        if ($emptyTokenRows) {
                            $upload_feedback .= '<br />Error reading next row(s) because doesn\' set meeting token: ' . implode(', ', $emptyTokenRows) . '.<br /><br />';
                        }
                    }

                } else { // wp_handle_upload returned some kind of error. the return does contain error details, so you can use it here if you want.

                    $upload_feedback = 'There was a problem with your upload';
                    if (isset($uploaded_file ['error'])) {
                        $upload_feedback .= ': "' . $uploaded_file ['error'] . '"';
                    }

                }

            } else { // wrong file type

                $upload_feedback = 'Please upload only CSV files (received ' . $uploaded_file_type . ' type).';

            }

        } else { // No file was passed

            $upload_feedback = 'No file was passed.';
        }

        if ($upload_feedback) {

            $out .= '<p><strong>' . $upload_feedback . '</strong></p>';

        }

    } // End if manual save flag

    $out .= '<p>This feature is intended to bulk uploads, both update or create new rooms.</p>';
    $out .= '<p>The format must be the same as the CSV file downloaded from room tables (follow the next template file).</p>';
    $out .= '<p>Click on the button to download a template file ready to fill up and upload it:
        <a type="button"
            href="'.admin_url('admin-ajax.php'). '?action=bbb_admin_panel_download_template_backup_file&nonce_download_template_backup_file='.wp_create_nonce('bbb_admin_panel_download_template_backup_file').'"
            target="_blank"
            class="button-primary"
        >
            Download backup template file
        </a>
    </p>';

    $out .= '<form name="form_importing_bbb_rooms" enctype="multipart/form-data" method="post" action="">';
    $out .= '<p>Upload a backup file (CSV format): <input type="file" name="xxxx_image" id="xxxx_image" /></p>';
    // Put in a hidden flag. This helps differentiate between manual saves and auto-saves (in auto-saves, the file wouldn't be passed).
    $out .= '<input type="hidden" name="xxxx_manual_save_flag" value="true" />';
    $out .= '<p>Do you want to update the room if the Metting Token already exists in database? <input type="checkbox" name="overwrite_rooms" value="True" /><br />
             Note: Can not exists two rooms with same Meeting Token, then if some row from csv file has same Meeting Token than a row from database, it will be updated or discarded according the previous checkbox.<br />
             Hint: For boolean values, i.e. "Wait for Moderator" or "Recorded", set "yes" or "no".</p>';
    $out .= '<p class="submit"><input type="submit" name="SubmitCreate" class="button-primary" value="Import" /></p>';
    $out .= '<input type="hidden" name="nonce_upload_rooms" value="'.wp_create_nonce('bbb_admin_panel_upload_rooms').'" />';
    $out .= '</form>';

    $out .= "<hr>";

    return $out;
}

//================================================================================
//---------------------------------List Meetings----------------------------------
//================================================================================
// Displays all the meetings available in the bigbluebutton server
function bbb_admin_panel_list_meetings() {
    global $wpdb, $wp_version, $current_site, $current_user;
    $table_name = bbb_admin_panel_get_db_table_name();
    $table_logs_name = bbb_admin_panel_get_db_table_name_logs();

    //Initializes the variable that will collect the output
    $out = '';

    $url_val = get_option('bbb_admin_panel_url');
    $salt_val = get_option('bbb_admin_panel_salt');

    if( isset($_POST['SubmitList']) &&
        isset( $_POST['nonce_delete_room']) &&
        wp_verify_nonce( $_POST['nonce_delete_room'], 'bbb_admin_panel_list_meetings' )) { //Creates then joins the meeting. If any problems occur the error is displayed

        // Read the posted value and delete
        $meetingID = filter_input(INPUT_POST, 'meetingID', FILTER_SANITIZE_SPECIAL_CHARS);
        $sql = "SELECT * FROM ".$table_name." WHERE meetingID = %s";
        $found = $wpdb->get_row(
                $wpdb->prepare($sql, $meetingID)
        );

        if( $found ) {

            //---------------------------------------------------JOIN-------------------------------------------------
            if($_POST['SubmitList'] == 'Join') {
            	//Extra parameters
            	$duration = 0;
            	$logouturl = (is_ssl()? "https://": "http://") . $_SERVER['HTTP_HOST']  . $_SERVER['REQUEST_URI'];

            	//Metadata for tagging recordings
            	$metadata = array(
            			'meta_origin' => 'WordPress',
            			'meta_originversion' => $wp_version,
            			'meta_origintag' => 'wp_plugin-bbb_admin_panel_ '.BBB_ADMINISTRATION_PANEL_PLUGIN_VERSION,
            			'meta_originservername' => home_url(),
            			'meta_originservercommonname' => get_bloginfo('name'),
            			'meta_originurl' => $logouturl
            	);

                if( $found->welcome ) {
                    $welcome = htmlspecialchars_decode($found->welcome);
                } else {
            	    //Calls create meeting on the bigbluebutton server
            	    $welcome = BBB_ADMINISTRATION_PANEL_STRING_WELCOME;
                    if($found->recorded) {
                        $welcome .= BBB_ADMINISTRATION_PANEL_STRING_MEETING_RECORDED;
                    }
                }

                //Appending the voiceBridge key to the welcome message
                $welcome .= "<br><br>The voiceBridge of this Conference room is '<b>".$found->voiceBridge."</b>'.";

            	$response = BigBlueButtonAPI::createMeetingArray($current_user->display_name, $found->meetingID, $found->meetingName, $welcome, $found->moderatorPW, $found->attendeePW, $salt_val, $url_val, $logouturl, ($found->recorded? 'true':'false'), $duration, $found->voiceBridge, $metadata );

            	$createNew = false;
            	//Analyzes the bigbluebutton server's response
            	if(!$response) {//If the server is unreachable, then prompts the user of the necessary action
            		$out .= '<div class="updated"><p><strong>Unable to join the meeting. Please check the url of the bigbluebutton server AND check to see if the bigbluebutton server is running.</strong></p></div>';
            	}
            	else if( $response['returncode'] == 'FAILED' ) { //The meeting was not created
            		if($response['messageKey'] == 'idNotUnique') {
            			$createNew = true;
            		}
            		else if($response['messageKey'] == 'checksumError') {
            			$out .= '<div class="updated"><p><strong>A checksum error occured. Make sure you entered the correct salt.</strong></p></div>';
            		}
            		else{
            			$out .= '<div class="updated"><p><strong>'.$response['message'].'</strong></p></div>';
            		}
            	}
            	else{
            		if( !isset($response['messageKey']) || $response['messageKey'] == '' ) {
            			// The meeting was just created, insert the create event to the log
            			$rows_affected = $wpdb->insert( $table_logs_name, array( 'meetingID' => $found->meetingID, 'recorded' => $found->recorded, 'timestamp' => time(), 'event' => 'Create' ) );
                    }
                    
                    $customParameters = bbb_admin_panel_parse_custom_parameters($found->api_join_custom_parameters);

            		$bigbluebutton_joinURL = BigBlueButtonAPI::getJoinURL($found->meetingID, $current_user->display_name, $found->moderatorPW, $salt_val, $url_val, $customParameters);
            		$out .= '<script type="text/javascript">window.location = "'.$bigbluebutton_joinURL.'"; </script>'."\n";
            	}

            }
            //---------------------------------------------------END-------------------------------------------------
            else if($_POST['SubmitList'] == 'End' ) { //Obtains the meeting information of the meeting that is going to be terminated

            	//Calls endMeeting on the bigbluebutton server
            	$response = BigBlueButtonAPI::endMeeting($found->meetingID, $found->moderatorPW, $url_val, $salt_val );

            	//Analyzes the bigbluebutton server's response
            	if(!$response) {//If the server is unreachable, then prompts the user of the necessary action
            		$out .= '<div class="updated"><p><strong>Unable to terminate the meeting. Please check the url of the bigbluebutton server AND check to see if the bigbluebutton server is running.</strong></p></div>';
            	}
            	else if( $response['returncode'] == 'SUCCESS' ) { //The meeting was terminated
            	    $out .= '<div class="updated"><p><strong>'.$found->meetingName.' meeting has been terminated.</strong></p></div>';

            		//In case the meeting is created again it sets the meeting version to the time stamp. Therefore the meeting can be recreated before the 1 hour rule without any problems.
            		$meetingVersion = time();
            		$wpdb->update( $table_name, array( 'meetingVersion' => $meetingVersion), array( 'meetingID' => $found->meetingID ));

            	}
            	else{ //If the meeting was unable to be termindated
            		if($response['messageKey'] == 'checksumError') {
            			$out .= '<div class="updated"><p><strong>A checksum error occured. Make sure you entered the correct salt.</strong></p></div>';
            		}
            		else{
            			$out .= '<div class="updated"><p><strong>'.$response['message'].'</strong></p></div>';
            		}
            	}



            }
            //---------------------------------------------------DELETE-------------------------------------------------
            else if($_POST['SubmitList'] == 'Delete' ) { //Obtains the meeting information of the meeting that is going to be delete

            	//Calls endMeeting on the bigbluebutton server
            	$response = BigBlueButtonAPI::endMeeting($found->meetingID, $found->moderatorPW, $url_val, $salt_val );

            	//Analyzes the bigbluebutton server's response
            	if(!$response) {//If the server is unreachable, then prompts the user of the necessary action
            		$out .= '<div class="updated"><p><strong>Unable to delete the meeting. Please check the url of the bigbluebutton server AND check to see if the bigbluebutton server is running.</strong></p></div>';
            	}
            	else if( $response['returncode'] != 'SUCCESS' && $response['messageKey'] != 'notFound' ) { //If the meeting was unable to be deleted due to an error
            		if($response['messageKey'] == 'checksumError') {
            			$out .= '<div class="updated"><p><strong>A checksum error occured. Make sure you entered the correct salt.</strong></p></div>';
            		}
            		else{
            			$out .= '<div class="updated"><p><strong>'.$response['message'].'</strong></p></div>';
            		}
            	}
            	else { //The meeting was terminated
            	    $sql = "DELETE FROM ".$table_name." WHERE meetingID = %s";
            	    $wpdb->query(
            	            $wpdb->prepare($sql, $meetingID)
            	    );

            	    $out .= '<div class="updated"><p><strong>'.$found->meetingName.' meeting has been deleted.</strong></p></div>';
            	}

            }
        }
    }

    //Gets all the meetings from the wordpress db
    $listOfMeetings = $wpdb->get_results("SELECT * FROM ".$table_name." ORDER BY id");

    //Checks to see if there are no meetings in the wordpress db and if so alerts the user
    if(count($listOfMeetings) == 0) {
        $out .= '<div class="updated"><p><strong>There are no meeting rooms configured in by this plugin.</strong></p></div>';
        return $out;
    }

    //Displays the title of the page
    $out .= "<h2>List of Meeting Rooms</h2>";

    //Iinitiallizes the table
    $printed = false;
    //Displays the meetings in the wordpress database that have not been created yet. Avoids displaying
    //duplicate meetings, meaning if the same meeting already exists in the bigbluebutton server then it is
    //not displayed again in this for loop
    foreach ($listOfMeetings as $meeting) {
        $info = BigBlueButtonAPI::getMeetingInfoArray( $meeting->meetingID, $meeting->moderatorPW, $url_val, $salt_val);
        //Analyzes the bigbluebutton server's response
        if(!$info) {//If the server is unreachable, then prompts the user of the necessary action
            $out .= '<div class="updated"><p><strong>Unable to display the meetings. Please check the url of the bigbluebutton server AND check to see if the bigbluebutton server is running. Or the rooms are not set by this plugin.</strong></p></div>';
            return $out;
        } else if( $info['returncode'] == 'FAILED' && $info['messageKey'] != 'notFound' && $info['messageKey'] != 'invalidPassword') { /// If the meeting was unable to be deleted due to an error
            if($info['messageKey'] == 'checksumError') {
                $out .= '<div class="updated"><p><strong>A checksum error occured. Make sure you entered the correct salt.</strong></p></div>';
            }
            else{
                $out .= '<div class="updated"><p><strong>'.$info['message'].'</strong></p></div>';
            }
            return $out;
        } else if( $info['returncode'] == 'FAILED' && ($info['messageKey'] == 'notFound' || $info['messageKey'] != 'invalidPassword') ) { /// The meeting exists only in the wordpress db
            if(!$printed) {
                $out .= bbb_admin_panel_print_table_header();
                $printed = true;
            }
            $out .= '
            <tr>
            <td>'.$meeting->meetingName.'</td>
            <td>'.$meeting->meetingID.'</td>
            <td>'.$meeting->attendeePW.'</td>
            <td>'.$meeting->moderatorPW.'</td>
            <td>'.($meeting->waitForModerator? 'Yes': 'No').'</td>
            <td>'.($meeting->recorded? 'Yes': 'No').'</td>
            <td>'.$meeting->voiceBridge.'</td>
            <td>'.htmlspecialchars_decode($meeting->welcome).'</td>
            <td>'.htmlspecialchars_decode($meeting->api_join_custom_parameters).'</td>
            <td>
            <form name="form1" method="post" action="">
              <input type="hidden" name="nonce_delete_room" value="'.wp_create_nonce('bbb_admin_panel_list_meetings').'" />
              <input type="hidden" name="meetingID" value="'.$meeting->meetingID.'">
              <input type="submit" name="SubmitList" class="button-primary" value="Join" />&nbsp;
              <input type="submit" name="SubmitList" class="button-primary" value="Delete" onClick="return confirm(\'Are you sure you want to delete the meeting?\')" />
            </form>
            </td>
            </tr>';
        } else { /// The meeting exists in the bigbluebutton server

            if(!$printed) {
                $out .= bbb_admin_panel_print_table_header();
                $printed = true;
            }

            $out .= '
            <tr>
            <td>'.$meeting->meetingName.'</td>
            <td>'.$meeting->meetingID.'</td>
            <td>'.$meeting->attendeePW.'</td>
            <td>'.$meeting->moderatorPW.'</td>
            <td>'.($meeting->waitForModerator? 'Yes': 'No').'</td>
            <td>'.($meeting->recorded? 'Yes': 'No').'</td>
            <td>'.$meeting->voiceBridge.'</td>
            <td>'.htmlspecialchars_decode($meeting->welcome).'</td>
            <td>'.htmlspecialchars_decode($meeting->api_join_custom_parameters).'</td>';
            if( isset($info['hasBeenForciblyEnded']) && $info['hasBeenForciblyEnded']=='false') {
                $out .= '
                <td>
                <form name="form1" method="post" action="">
                  <input type="hidden" name="meetingID" value="'.$meeting->meetingID.'">
                  <input type="submit" name="SubmitList" class="button-primary" value="Join" />&nbsp;
                  <input type="submit" name="SubmitList" class="button-primary" value="End" onClick="return confirm(\'Are you sure you want to end the meeting?\')" />&nbsp;
                  <input type="submit" name="SubmitList" class="button-primary" value="Delete" onClick="return confirm(\'Are you sure you want to delete the meeting?\')" />
                  <input type="hidden" name="nonce_delete_room" value="'.wp_create_nonce('bbb_admin_panel_list_meetings').'" />
                </form>
                </td>';
            } else {
                $out .= '
                <td>
                <form name="form1" method="post" action="">
                  <input type="hidden" name="meetingID" value="'.$meeting->meetingID.'">
                  <!-- Meeting has ended and is temporarily unavailable. -->
                  <input type="submit" name="SubmitList" class="button-primary" value="Join" />&nbsp;
                  <input type="submit" name="SubmitList" class="button-primary" value="Delete" onClick="return confirm(\'Are you sure you want to delete the meeting?\')" />&nbsp;
                  <input type="hidden" name="nonce_delete_room" value="'.wp_create_nonce('bbb_admin_panel_list_meetings').'" />
                </form>
                </td>';
            }
            $out .= '	</tr>';
        }
    }

    $out .= '
    </tbody>
    </table>
    <script>
    jQuery(document).ready(function() {
      var list_meetings = jQuery("#bbb_print_table_header").DataTable( {
            fixedColumns:   {
                leftColumns: 1
            },
            responsive: true,
            rowReorder: true,
            "autofill": true,
            "dom": "lBfrtip",
            "buttons": [
                "copyHtml5",
                "excelHtml5",
                "csvHtml5",
                "pdfHtml5"
            ],
            "scrollX": true,
            "lengthMenu": [ 10, 25, 50, 100 ]
        }
      );
    })
    </script>
    </div><hr />';

    return $out;
}

//Begins the table of list meetings with the number of columns specified
function bbb_admin_panel_print_table_header() {
    return '
    <div>
    <table id="bbb_print_table_header" class="stats" cellspacing="0" width="100%">
      <thead>
        <tr>
          <th>Meeting Room Name</th>
          <th>Meeting Token</th>
          <th>Attendee Password</th>
          <th>Moderator Password</th>
          <th>Wait for Moderator</th>
          <th>Recorded</th>
          <th>VoiceBridge</th>
          <th>Welcome Message</th>
          <th>Join Custom Parameters</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>';
}

//================================================================================
//---------------------------------List Active Meetings----------------------------------
//================================================================================

add_action('wp_ajax_nopriv_bbbadminpanel_action_get_active_meetings', 'bbbadminpanel_action_get_active_meetings');
add_action('wp_ajax_bbbadminpanel_action_get_active_meetings', 'bbbadminpanel_action_get_active_meetings');

function bbbadminpanel_action_get_active_meetings() {

    $url_val = get_option('bbb_admin_panel_url');
    $salt_val = get_option('bbb_admin_panel_salt');
    $info = BigBlueButtonAPI::getMeetings( $url_val, $salt_val);
    $meetings = [];
    if ($info) {
        $xmlMeetings = simplexml_load_string ($info);
        foreach ($xmlMeetings as $meeting) {
            $meetings[] = [
                "meetingID"  => (string) $meeting->meetingID,
                "meetingName"  => (string) $meeting->meetingName,
                "voiceBridge"  => (string) $meeting->voiceBridge,
                "createDate"  => (string) $meeting->createDate,
                "participantCount" => (string) $meeting->participantCount,
            ];
        }
    }
    echo json_encode([
        'meeting' => $meetings
    ]);

	wp_die(); // this is required to terminate immediately and return a proper response
}

add_action('wp_ajax_bbbadminpanel_action_get_meeting_info', 'bbbadminpanel_action_get_meeting_info');

function bbbadminpanel_action_get_meeting_info() {

    $url_val = get_option('bbb_admin_panel_url');
    $salt_val = get_option('bbb_admin_panel_salt');
    $meetingID = filter_input(INPUT_GET, 'meetingId', FILTER_SANITIZE_URL);
    $meeting = bbb_admin_panel_wrap_simplexml_load_file( 
        BigBlueButtonAPI::getMeetingInfoURLWithoutModeratorPwUrl($meetingID, $url_val, $salt_val) 
    );
    echo json_encode($meeting);

	wp_die(); // this is required to terminate immediately and return a proper response
}

// Displays all the meetings running in the bigbluebutton server
function bbb_admin_panel_list_active_meetings($tooltipParticipants) {

    global $wpdb, $wp_version, $current_site;

    // check permissions
    if (!bbb_admin_panel_can_listActiveMeetings())
    {
        return "current user are not allowed to list active meetings";
    }

    //Displays the title of the page
    $out = "";

    $url_val = get_option('bbb_admin_panel_url');
    $salt_val = get_option('bbb_admin_panel_salt');

    $info = BigBlueButtonAPI::getMeetings( $url_val, $salt_val);
    $meetings = simplexml_load_string ($info ?? '');

    if(!$info)
    {
        //If the server is unreachable, then prompts the user of the necessary action
        $out .= '<div class="updated"><p><strong>Unable to display the meetings. Please check the url of the bigbluebutton server AND check to see if the bigbluebutton server is running.</strong></p></div>';
    }
    else if( $meetings['returncode'] == 'FAILED' && $meetings['messageKey'] != 'notFound' && $meetings['messageKey'] != 'invalidPassword')
    { /// If the meeting was unable to be deleted due to an error
        if($meetings['messageKey'] == 'checksumError')
        {
            $out .= '<div class="updated"><p><strong>A checksum error occured. Make sure you entered the correct salt.</strong></p></div>';
        }
        else{
            $out .= '<div class="updated"><p><strong>'.$meetings['message'].'</strong></p></div>';
        }
    }
    else
    { /// The meeting exists in the bigbluebutton server

        $out = "<h2>List of Active Meeting Rooms in BBB Server</h2>";
        $out .= "Click on the row to see the participants list";

        $out .= '
        </tbody>
        <div>
        <table id="activity_monitor" class="stats" cellspacing="0" width="100%">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Voice Bridge</th>
                    <th>Creation</th>
                    <th>Participants</th>
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Voice Bridge</th>
                    <th>Creation</th>
                    <th>Participants</th>
                </tr>
            </tfoot>
        </table>
        <script>

        jQuery(document).ready(function() {
            var table_activity_monitor = jQuery("#activity_monitor")
                .on( "error.dt", function ( e, settings, techNote, message ) {
                    console.log( "An error has been reported by DataTables: ", message );
                } )
                .DataTable( {
                    "autofill": true,
                    "dom": "lBfrtip",
                    "buttons": [
                        "copyHtml5",
                        "excelHtml5",
                        "csvHtml5",
                        "pdfHtml5",
                        {
                            text: "Reload table",
                            action: function () {
                                table_activity_monitor.ajax.reload(null, false);
                            }
                        }
                    ],
                    fixedColumns:   {
                        leftColumns: 1
                    },
                    responsive: true,
                    rowReorder: true,
                    "scrollX": true,
                    "lengthMenu": [ 10, 25, 50, 100 ],
                    "deferRender": true,
                    "ajax": {
                        "dataType": "json",
                        "url": wp_ajax_tets_vars.ajaxurl,
                        "cache": "false",
                        "dataSrc": "meeting",
                        "data": function ( d ) {
                            return jQuery.extend( {}, d, {
                                "action": "bbbadminpanel_action_get_active_meetings"
                            } );
                        }
                    },
                    "columns": [
                        { "data": "meetingID" },
                        { "data": "meetingName" },
                        { "data": "voiceBridge" },
                        { "data": "createDate" },
                        { "data": "participantCount" }
                    ],
                    "columnDefs": [
                        { targets: "_all", "defaultContent": "<i>Not set</i>", }
                    ],
                    "language": {
                        "emptyTable": "No active meetings in BBB server currently",
                    },
                } );

                // refresh table each 30 seconds
                setInterval( function () {
                    table_activity_monitor.ajax.reload( null, false ); // user paging is not reset on reload
                }, 30000 );';
                if ($tooltipParticipants) {
                    $out .= 
                    'jQuery("#activity_monitor tbody").on("click", "tr", function () {
                        var data = table_activity_monitor.row( this ).data();
                        jQuery.ajax({
                            "url": wp_ajax_tets_vars.ajaxurl,
                            "dataType": "json",
                            "cache": "false",
                            "dataSrc": "meeting",
                            "data": {
                                "action": "bbbadminpanel_action_get_meeting_info",
                                "meetingId": data.meetingID,
                            },
                            success: function(data) {
                                if (data.returncode === "SUCCESS") {
                                    const at = [];
                                    // antonp`s contribution
                                    // Have to check against data.attendees.attendee if Object or Array
                                    // typeof Array === "object" returns true so must use .constructor.name
                                    if (data.attendees.attendee.constructor.name === "Object") { // Only one attendee presented
                                        at.push({
                                            name: data.attendees.attendee.fullName,
                                            role: data.attendees.attendee.role,
                                        });
                                    } else { // data.attendees.attendee is an Array
                                        data.attendees.attendee.forEach(attendee => {
                                            at.push({
                                            name: attendee.fullName,
                                            role: attendee.role,
                                            });
                                        });
                                    }
                                    alert( 
                                        "Participants (# - Name - Role): \n"+
                                        // use join("\n") to avoid extra comma at the beginning of the line
                                        at.map((a, i) => (i+1)+": "+a.name+" ("+a.role.toLowerCase()+")").join("\n") 
                                    );
                                }

                            },
                            error: function(xhr) {
                                console.error(xhr);
                            } 
                        });
                    } );';
            }
            $out .= '
             
            }); // End JQuery ready

        </script>
        </div><hr />';
    }

    return $out;
}

function bbb_admin_panel_room_status($meetingId, $class, $activeWord, $inactiveWord, $period) {
    $url_val = get_option('bbb_admin_panel_url');
    $salt_val = get_option('bbb_admin_panel_salt');
    $info = bbb_admin_panel_get_meeting($meetingId);
    if (!$info || $info === 'false') {
        return '<strong>No meeting found for ID '.$meetingId.'</strong>';
    }

    return '
        <!-- Status displayed -->
        <span id="bbb_status_room_field" ' . ($class ? "class=\"$class\"" : '') . '>...</span>

        <!-- Javascript code -->
        <script>
            field = document.getElementById("bbb_status_room_field");
            function checkRoomStatus() {
                jQuery.ajax({
                    url: wp_ajax_tets_vars.ajaxurl,
                    data: {
                        "action": "bbbadminpanel_action_room_status_script",
                        "meetingId": "'.urlencode($meetingId).'"
                    },
                    async : true,
                    dataType : "json",
                    success : function(response) {
                        if(response.running) {
                            field.innerHTML = "' . ($activeWord ? $activeWord : 'Active') .'";
                        } else {
                            field.innerHTML = "' . ($inactiveWord ? $inactiveWord : 'Inactive') . '";
                        }
                        console.log("[bbb_admin_panel_room_status] Checked meeting ' . $meetingId . ' status: ", response.running);
                    },
                    error : function(xmlHttpRequest, status, error) {
                        console.error("[bbb_admin_panel_room_status]", xmlHttpRequest, status, error);
                    }
                });
            };
            var interval = window.setInterval(checkRoomStatus, ' . ($period ?? 1500) . ');
            checkRoomStatus(); // Run once now            
        </script>
    ';
}

add_action( 'wp_ajax_nopriv_bbbadminpanel_action_room_status_script', 'bbbadminpanel_action_room_status_script' );
add_action( 'wp_ajax_bbbadminpanel_action_room_status_script', 'bbbadminpanel_action_room_status_script' );

function bbbadminpanel_action_room_status_script() {

    $url_val = get_option('bbb_admin_panel_url');
    $salt_val = get_option('bbb_admin_panel_salt');
    $meetingID = filter_input(INPUT_GET, 'meetingId', FILTER_SANITIZE_URL);

    $info = BigBlueButtonAPI::getMeetingXML( $meetingID, $url_val, $salt_val );
    if (!$info || $info === 'false') {
        echo json_encode([
            'running' => false,
        ]);
    }
    echo json_encode([
        'running' => (string) $info->running === 'true' ? true : false,
    ]);

    wp_die(); // this is required to terminate immediately and return a proper response
}

//================================================================================
//---------------------------------List Recordings----------------------------------
//================================================================================
// Displays all the recordings available in the bigbluebutton server

add_action( 'wp_ajax_nopriv_bbbadminpanel_action_post_manage_recordings', 'bbbadminpanel_action_post_manage_recordings' );
add_action( 'wp_ajax_bbbadminpanel_action_post_manage_recordings', 'bbbadminpanel_action_post_manage_recordings' );

function bbbadminpanel_action_post_manage_recordings() {

    if (!isset($_REQUEST['nonce_active_meetings']) ||
            !wp_verify_nonce( $_REQUEST['nonce_active_meetings'], 'bbb_admin_panel_list_recordings' )) {
        die( 'Security check' );
    }

    $url_val = get_option('bbb_admin_panel_url');
    $salt_val = get_option('bbb_admin_panel_salt');
    $action = filter_input(INPUT_GET, 'recordingAction', FILTER_SANITIZE_URL);
    $recordingID = filter_input(INPUT_GET, 'recordingID', FILTER_SANITIZE_URL);

    if (!$recordingID) {
        header("HTTP/1.0 400 Bad Request. [recordingID] parameter was not included in this query.");
        wp_die();
    }

    switch ($action) {
        case "publish":
            $info = BigBlueButtonAPI::doPublishRecordings($recordingID, 'true', $url_val, $salt_val);
            break;
        case "unpublish":
            $info = BigBlueButtonAPI::doPublishRecordings($recordingID, 'false', $url_val, $salt_val);
            break;
        case "delete":
            $info = BigBlueButtonAPI::doDeleteRecordings($recordingID, $url_val, $salt_val);
            break;
        default:
            header("HTTP/1.0 400 Bad Request. [action] unknown.");
            wp_die();
    }

    $xmlResponse = simplexml_load_string ($info);
    echo json_encode($xmlResponse);

	wp_die(); // this is required to terminate immediately and return a proper response
}

function bbb_admin_panel_list_recordings($title,$args) {
    global $wpdb, $wp_roles, $current_user;
    $table_name = bbb_admin_panel_get_db_table_name();
    $table_logs_name = bbb_admin_panel_get_db_table_name_logs();

    $token = isset($args['token']) ?$args['token']: null;
    $tokens = isset($args['tokens']) ?$args['tokens']: null;
    $title_as_arg = isset($args['title']) ?$args['title']: null;

    //Initializes the variable that will collect the output
    $out = '';

    //Set the role for the current user if is logged in
    $role = null;
    if( $current_user->ID ) {
        $role = "unregistered";
        foreach($wp_roles->role_names as $_role => $Role) {
            if (array_key_exists($_role, $current_user->caps)) {
                $role = $_role;
                break;
            }
        }
    } else {
        $role = "anonymous";
    }

    $url_val = get_option('bbb_admin_panel_url');
    $salt_val = get_option('bbb_admin_panel_salt');

    if (isset($token) and trim($token) == 'only-current-wp') {
        $tokens = '';
        foreach ($wpdb->get_results("SELECT meetingID FROM ".$table_name." ORDER BY meetingName") as $meeting) {
            $tokens .= ',' . $meeting->meetingID;
        }
        if ($tokens != '') {
            $tokens = substr($tokens, 1);
        }
    } else if (isset($token) and trim($token) != '') {
        $tokens = $token;
    }

    $listOfRecordings = Array();
	$recordingsArray = BigBlueButtonAPI::getRecordingsArray($tokens, $url_val, $salt_val);

	if( $recordingsArray['returncode'] == 'SUCCESS' && !$recordingsArray['messageKey'] ) {
		$listOfRecordings = $recordingsArray['recordings'];
	}

    //Checks to see if there are no meetings in the wordpress db and if so alerts the user
    if(count($listOfRecordings) == 0) {
        $out .= '<div><p><strong>There are no recordings available. Or they are not generated from rooms setup by this plugin.</strong></p></div>';
        return $out;
    }

    //Displays the title of the page
    if($title)
        $out .= "<h2>".$title."</h2>";
    else if ($title_as_arg)
        $out .= "<h2>".$title_as_arg."</h2>";

    if ( bbb_admin_panel_can_manageRecordings($role) ) {
        $out .= '
        <script type="text/javascript">
            function actionCall(action, recordingid) {

                action = (typeof action == \'undefined\') ? \'publish\' : action;
                var nonce = "' . wp_create_nonce('bbb_admin_panel_list_recordings') . '";

                if (action == \'publish\' || (action == \'delete\' && confirm("Are you sure to delete this recording?"))) {
                    if (action == \'publish\') {
                        var el_a = document.getElementById(\'actionbar-publish-a-\'+ recordingid);
                        if (el_a) {
                            var el_img = document.getElementById(\'actionbar-publish-img-\'+ recordingid);
                            if (el_a.title == \'Hide\' ) {
                                action = \'unpublish\';
                                el_a.title = \'Show\';
                                el_img.src = \'' . BBB_ADMINISTRATION_PANEL_PLUGIN_URL . '/images/show.gif\';
                            } else {
                                action = \'publish\';
                                el_a.title = \'Hide\';
                                el_img.src = \'' . BBB_ADMINISTRATION_PANEL_PLUGIN_URL . '/images/hide.gif\';
                            }
                        }
                    } else {
                        // Removes the line from the table
                        jQuery(document.getElementById(\'actionbar-tr-\'+ recordingid)).remove();
                    }
                    jQuery.ajax({
                            url: wp_ajax_tets_vars.ajaxurl,
                            data: {
                                "action": "bbbadminpanel_action_post_manage_recordings",
                                "recordingAction": action,
                                "recordingID": recordingid,
                                "nonce_active_meetings": nonce,
                            },
                            async : false,
                            success : function(response) {
                                alert("deleted ok");
                            },
                            error : function(xmlHttpRequest, status, error) {
                                alert("error deleting");
                                console.debug(xmlHttpRequest);
                            }
                        });
                }
            }
        </script>';
    }


  //Print begining of the table
    $out .= '
    <div id="bbb-recordings-div" class="bbb-recordings">
    <table id="recording-list" class="stats" width="100%" cellspacing="0">
      <thead>
      <tr>
        <th class="hed">Recording</td>
        <th class="hed">Meeting Room Name</td>
        <th class="hed">Date</td>
        <th class="hed">Duration</td>
		<th class="hed">State</td>';
    if ( bbb_admin_panel_can_manageRecordings($role) ) {
        $out .= '
        <th class="hedextra">Toolbar</td>';
    }
    $out .= '
      </tr>
      </thead>
      <tbody>';
    foreach( $listOfRecordings as $recording) {
        if ( bbb_admin_panel_can_manageRecordings($role) || $recording['published'] == 'true') {
            /// Prepare playback recording links
            $type = '';
            foreach ( $recording['playbacks'] as $playback ) {
                if ($recording['published'] == 'true') {
                    $type .= '<a href="'.$playback['url'].'" target="_new">'.$playback['type'].'</a>&#32;';
                } else {
                    $type .= $playback['type'].'&#32;';
                }
            }

            /// Prepare duration
            $endTime = isset($recording['endTime'])? floatval($recording['endTime']):0;
            $endTime = $endTime - ($endTime % 1000);
            $startTime = isset($recording['startTime'])? floatval($recording['startTime']):0;
            $startTime = $startTime - ($startTime % 1000);
            $duration = intval(($endTime - $startTime) / 60000);

            /// Prepare date
            //Make sure the startTime is timestamp
            if( !is_numeric($recording['startTime']) ) {
                $date = new DateTime($recording['startTime']);
                $recording['startTime'] = date_timestamp_get($date);
            } else {
                $recording['startTime'] = ($recording['startTime'] - $recording['startTime'] % 1000) / 1000;
            }

            //Format the date
            //$formatedStartDate = gmdate("M d Y H:i:s", $recording['startTime']);
            $formatedStartDate = date_i18n( "M d Y H:i:s", $recording['startTime'], false );

            //Print detail
            $out .= '
            <tr id="actionbar-tr-'.$recording['recordID'].'">
              <td>'.$type.'</td>
              <td>'.$recording['meetingName'].'</td>
              <td>'.$formatedStartDate.'</td>
              <td>'.$duration.' min</td>
			  <td>'.$recording['state'].'</td>';

            /// Prepare actionbar if role is allowed to manage the recordings
            if ( bbb_admin_panel_can_manageRecordings($role) ) {
                $action = ($recording['published'] == 'true')? 'Hide': 'Show';
                $actionbar = "
                    <a id=\"actionbar-publish-a-".$recording['recordID']."\" title=\"".$action."\" href=\"#\">
                        <img id=\"actionbar-publish-img-".$recording['recordID']."\"
                            src=\"".BBB_ADMINISTRATION_PANEL_PLUGIN_URL."images/".strtolower($action).".gif\"
                            class=\"iconsmall\"
                            onClick=\"actionCall('publish', '".$recording['recordID']."'); return false;\"
                        />
                    </a>";
                $actionbar .= "
                    <a id=\"actionbar-delete-a-".$recording['recordID']."\" title=\"Delete\" href=\"#\">
                        <img id=\"actionbar-delete-img-".$recording['recordID']."\"
                            src=\"".BBB_ADMINISTRATION_PANEL_PLUGIN_URL."images/delete.gif\"
                            class=\"iconsmall\"
                            onClick=\"actionCall('delete', '".$recording['recordID']."'); return false;\"
                        />
                    </a>";
                $out .= '<td>'.$actionbar.'</td>';
            }

            $out .= '
            </tr>';
        }
        else {
            /// Prepare date
            //Make sure the startTime is timestamp
            if( !is_numeric($recording['startTime']) ) {
                $date = new DateTime($recording['startTime']);
                $recording['startTime'] = date_timestamp_get($date);
            } else {
                $recording['startTime'] = ($recording['startTime'] - $recording['startTime'] % 1000) / 1000;
            }

            //Format the date
            //$formatedStartDate = gmdate("M d Y H:i:s", $recording['startTime']);
            $formatedStartDate = date_i18n( "M d Y H:i:s", $recording['startTime'], false );

            //Print detail
            $out .= '
            <tr id="actionbar-tr-'.$recording['recordID'].'">
              <td>'.$recording['state'].'</td>
              <td>'.$recording['meetingName'].'</td>
              <td>'.$formatedStartDate.'</td>
              <td>-</td>
              <td>-</td>
            </tr>';
        }
    }

    //Print end of the table
    $out .= '  </tbody>
    </table>
    <script>
    jQuery(document).ready(function(){
      jQuery("#recording-list").DataTable({
        "autofill": true,
        "dom": "lBfrtip",
        "buttons": [
            "copyHtml5",
            "excelHtml5",
            "csvHtml5",
            "pdfHtml5"
        ],
        fixedColumns:   {
            leftColumns: 2
        },
        responsive: true,
        rowReorder: false,
        "scrollX": true,
        "lengthMenu": [ 10, 25, 50, 100 ]
      });
    })
    </script>
    </div>';

    return $out;
}

//================================================================================
//------------------------------- Helping functions ------------------------------
//================================================================================
//Validation methods
function bbb_admin_panel_can_participate($role) {
    $permissions = get_option('bbb_admin_panel_permissions');
    if( $role == 'unregistered' ) $role = 'anonymous';
    return ( isset($permissions[$role]['participate']) && $permissions[$role]['participate'] );

}

function bbb_admin_panel_can_manageRecordings($role) {
    $permissions = get_option('bbb_admin_panel_permissions');
    if( $role == 'unregistered' ) $role = 'anonymous';
    return ( isset($permissions[$role]['manageRecordings']) && $permissions[$role]['manageRecordings'] );

}

function bbb_admin_panel_can_listActiveMeetings() {
    global $current_user, $wp_roles;
    $permissions = get_option('bbb_admin_panel_permissions');

    //Set the role for the current user if is logged in
    $role = null;
    if( $current_user->ID ) {
      $role = "unregistered";
      foreach($wp_roles->role_names as $_role => $Role) {
          if (array_key_exists($_role, $current_user->caps)) {
              $role = $_role;
              break;
          }
      }
    } else {
      $role = "anonymous";
    }

    return ( isset($permissions[$role]['listActiveMeetings']) && $permissions[$role]['listActiveMeetings'] );
}

function bbb_admin_panel_validate_defaultRole($wp_role, $bbb_role) {
    $permissions = get_option('bbb_admin_panel_permissions');
    if( $wp_role == null || $wp_role == 'unregistered' || $wp_role == '' )
        $role = 'anonymous';
    else
        $role = $wp_role;
    return ( isset($permissions[$role]['defaultRole']) && $permissions[$role]['defaultRole'] == $bbb_role );
}

function bbb_admin_panel_generateToken($tokenLength=6) {
#    $token = '';
#
#    if(function_exists('openssl_random_pseudo_bytes')) {
#        $token .= bin2hex(openssl_random_pseudo_bytes($tokenLength));
#    } else {
#        //fallback to mt_rand if php < 5.3 or no openssl available
#        $characters = '0123456789abcdef';
#        $charactersLength = strlen($characters)-1;
#        $tokenLength *= 2;
#
#        //select some random characters
#        for ($i = 0; $i < $tokenLength; $i++) {
#            $token .= $characters[mt_rand(0, $charactersLength)];
#        }
#    }
#
#    return $token;
    if (function_exists('com_create_guid') === true) {
        return trim(com_create_guid(), '{}');
    }

    $data = openssl_random_pseudo_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function bbb_admin_panel_generatePassword($numAlpha=6, $numNonAlpha=2, $salt='') {
    $listAlpha = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $listNonAlpha = ',;:!?.$/*-+&@_+;./*&?$-!,';

    $pepper = '';
    do{
        $pepper = str_shuffle( substr(str_shuffle($listAlpha),0,$numAlpha) . substr(str_shuffle($listNonAlpha),0,$numNonAlpha) );
    } while($pepper == $salt);

    return $pepper;
}

/**
 * Convert duration in seconds to string Hours Min and Secs
 */
function bbb_admin_panel_secToDuration($duration) {
  $secs = $duration % 60;
  $duration = $duration / 60;
  $minutes = $duration % 60;
  $hours = $duration / 60;
  return $hours.' '.$minutes.' '.$secs;
}

function bbb_admin_panel_get_meeting($meetingId) {
    global $wpdb;
    $table_name = bbb_admin_panel_get_db_table_name();
    $rows = $wpdb->get_results("SELECT * FROM $table_name");
    if (count($rows) > 0) {
        return $rows [0];
    }
}

function bbb_admin_panel_parse_custom_parameters($apiJoinCustomParameters) {
    $metadata = [];
    foreach(explode('|', $apiJoinCustomParameters) as $customParam) {
        $pos = strpos($customParam, '=');
        if ($pos) {
            $key = substr($customParam, 0, $pos);
            $param = substr($customParam, $pos+1);
            $metadata[$key] = $param;
        }
    }
    return $metadata;
}
