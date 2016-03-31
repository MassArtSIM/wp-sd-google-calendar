<?php
/*
Plugin Name: SD Google Calendar Combiner
Plugin URI: http://www.smartwaredesign.com/sd-google-calendar
Description: Build pages by combining Google Calendars
Author: Smartware Design LLC
Version: 1.0
*/

define( 'SDGC_VERSION', '1.0' );
define( 'SDGC_INC_PATH', dirname( __FILE__ ) . '/inc' );
define( 'SDGC_PATH', dirname( __FILE__ ) );
define( 'SDGC_FOLDER', basename(SDGC_PATH) );
define( 'SDGC_URL', plugins_url() . '/'. SDGC_FOLDER );
define( 'TEXTDOMAIN', 'sd-google-calendar' );

include_once( SDGC_INC_PATH . '/' . 'sd-google-calendar.class.php' );

$GoogleCalendars = new GoogleCalendars();
?>