<?php
/* 
Plugin Name: 	ICL PDF Maker
Description: 	 
Version: 		0.1.1
License: 		GPL2
Text Domain: 	ipdf
GitHub Plugin URI: https://github.com/ImperialCollegeLondon/icl-pdf-creator
*/

if ( ! defined( 'ABSPATH' ) ) { // Prevent direct access
	die();
}


define(	'IPDF_PLUGIN_DIR', dirname( __FILE__ ) );

// TCPDF Library


if (!defined("PDF_CREATOR") )
{
	
	
	
	include_once( IPDF_PLUGIN_DIR . '/lib/tcpdf/config/tcpdf_config.php' );
	include_once( IPDF_PLUGIN_DIR . '/lib/tcpdf/tcpdf.php' );

}

// Extension class for TCPDF Library
include_once( IPDF_PLUGIN_DIR . '/includes/extend-class-tcpdf.php' );

// Plugin
include_once( IPDF_PLUGIN_DIR . '/config.php' );
include_once( IPDF_PLUGIN_DIR . '/includes/helper-functions.php' );
include_once( IPDF_PLUGIN_DIR . '/includes/class-ipdf.php' );
include_once( IPDF_PLUGIN_DIR . '/includes/ajax.php' );


IPDF::initialize();

?>