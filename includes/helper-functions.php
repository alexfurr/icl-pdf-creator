<?php


/**
*   Over-ride default shortcode processing
*   ---
*/
function iclpdf_replace_shortcode_handlers ()
{
    // Remove all shortcode handlers
    remove_all_shortcodes();
    
    // Re-register specific shortcodes with new handlers
    // -----
    add_shortcode( 'ek-question', 'iclpdf_shortcode_ek_question' );
    add_shortcode( 'ek-quiz', 'iclpdf_shortcode_ek_quiz' );
    add_shortcode( 'h5p', 'iclpdf_shortcode_h5p' );
    // -----
}


/**
*   Shortcode [ek-question]
*/
function iclpdf_shortcode_ek_question ( $atts, $content = null ) 
{
	$html = '';
	
	
	$html.='<table style="border:2px solid #ccc; padding:20px;">';
	$html.='<tr>';
	$html.='<td style="background-color:#f7f7f7;">';
	$atts = shortcode_atts( 
		array(
			'id'		=> '',
			), 
		$atts
	);	
	
	$questionID = (int) $atts['id'];
	$qType = get_post_meta($questionID, 'qType', true);


	$html.= apply_filters('the_content', get_post_field('post_content', $questionID));
	
	
	$html.='</td></tr><tr>';
	$html.='<td>';
	switch ($qType)
	{
		case "singleResponse":
		case "multiResponse":
		
			$alphabet = range('a', 'z');
		
			$responseOptions = get_post_meta($questionID, "responseOptions", true);
			
			$i=0;
			foreach ($responseOptions as $optionInfo)
			{
				$optionValue = $optionInfo['optionValue'];
				$html.=$alphabet[$i].') '.$optionValue.'<br/>';
				$i++;
			}			
			
		break;

		
		default:
		
		
		break;
	}
	
	
	$html.='</td>';
	$html.='</tr>';
	$html.='</table><br/><br/>';
	
	
	
    return $html;
}


/**
*   Shortcode [ek-quiz]
*/
function iclpdf_shortcode_ek_quiz ( $atts, $content = null ) 
{
	
    return '{QTL-QUIZ over-ride}';
}


/**
*   Shortcode [h5p]
*/
function iclpdf_shortcode_h5p ( $atts, $content = null ) 
{
	
	
	$siteURL = home_url();
	$html = '';
	
	$html.='<table style="border:2px solid #ccc; padding:20px;">';
	$html.='<tr>';
	$html.='<td style="background-color:#f7f7f7;">';
	$html.='This is an interactive learning object.';
	
	$html.='</td></tr><tr>';
	$html.='<td>';	
	
	
	$html.='<br/><a href="'.$siteURL.'">';
	$html.='<img width="100px" height="100px" src = "'.IPDF_PLUGIN_DIR.'/images/h5p_item.png"><br/><br/>';	
	$html.='Click here to visit the website to view this activity</a><br/>';	
	$html.='</td></tr></table>';
	return $html;

}





/**
*
*/
function iclpdf_get_additional_pages ( $query_args = null ) 
{
    $content = array();
    
    $default_args = array(
        'orderby' 					=> 'menu_order',
        'order' 					=> 'ASC', 
        'posts_per_page' 			=> -1,
        'post_type' 				=> array( 'page' ),
        'post_status'				=> 'publish',
        'suppress_filters'			=> false,
        'no_found_rows'				=> true,
        'update_post_meta_cache'	=> true,
        'update_post_term_cache'	=> true,
    );
    
    $query_args = empty( $query_args ) ? array() : $query_args;
    
    // Run Hook - Extensions modify query
    $query_args = apply_filters( 'iclpdf_pre_get_additional_pages', $query_args ); 
    
    $args 	    = wp_parse_args( $query_args, $default_args );
    $Q          = new WP_Query;
    $results    = $Q->query( $args );
    
    $results = ! is_array( $results ) ? array() : $results;
    foreach ( $results as $r ) {
        if ( strpos( $r->post_content , '[topics-tree]', 0 ) === false && strpos( $r->post_content , '[topics]', 0 ) === false ) {
            $content[ $r->ID ] = $r;
            $content[ $r->ID ]->post_content = str_replace( '[icl-pdf]', '', $r->post_content );
        }
    }
    return $content;
}




/**
*
*/
function iclpdf_get_content () 
{
    iclpdf_cache_content();
    $content = array();
    
    $L1 = getTopics();
    foreach ( $L1 as $i => $topic ) {
        $content[ $i ] = $topic;
        $L2 = getTopicSessions( $topic->ID );
        $content[ $i ]->sessions = is_array( $L2 ) ? $L2 : array();
        
        foreach ( $content[ $i ]->sessions as $j => $session ) {
            $L3 = getSessionPages( $session->ID );
            $content[ $i ]->sessions[ $j ]->slides = is_array( $L3 ) ? $L3 : array();
        }
    }
    return $content;
}


/**
*
*/
function iclpdf_cache_content ( $query_args = null ) 
{
    $default_args = array(
        'orderby' 					=> 'menu_order',
        'order' 					=> 'ASC', 
        'posts_per_page' 			=> -1,
        'post_type' 				=> array( 'imperial_topic', 'topic_session', 'session_page' ),
        'post_status'				=> 'publish',
        'suppress_filters'			=> false,
        'no_found_rows'				=> true,
        'update_post_meta_cache'	=> true,
        'update_post_term_cache'	=> true,
    );
    
    $query_args = empty( $query_args ) ? array() : $query_args;
    
    // Run Hook - Extensions modify query
    $query_args = apply_filters( 'iclpdf_pre_get_content', $query_args ); 
    
    $args 	    = wp_parse_args( $query_args, $default_args );
    $Q          = new WP_Query;
    $content    = $Q->query( $args );
    
    return ! is_array( $content ) ? array() : $content;
}





/**
*
*/
function SSAPDF_hex2RGB ( $hexStr, $returnAsString = false, $seperator = ',' )
{
	$hexStr = preg_replace( "/[^0-9A-Fa-f]/", '', $hexStr ); // Gets a proper hex string
	$rgbArray = array();
	
	if (strlen($hexStr) == 6) { //If a proper hex code, convert using bitwise operation. No overhead... faster
		$colorVal = hexdec($hexStr);
		$rgbArray['red'] = 0xFF & ($colorVal >> 0x10);
		$rgbArray['green'] = 0xFF & ($colorVal >> 0x8);
		$rgbArray['blue'] = 0xFF & $colorVal;
	} elseif (strlen($hexStr) == 3) { //if shorthand notation, need some string manipulations
		$rgbArray['red'] = hexdec(str_repeat(substr($hexStr, 0, 1), 2));
		$rgbArray['green'] = hexdec(str_repeat(substr($hexStr, 1, 1), 2));
		$rgbArray['blue'] = hexdec(str_repeat(substr($hexStr, 2, 1), 2));
	} else {
		return false; //Invalid hex color code
	}
	return $returnAsString ? implode($seperator, $rgbArray) : $rgbArray; // returns the rgb string or the associative array
}


/**
*
*/
function SSAPDF_getPageDepth ( $id )
{
	return count( get_post_ancestors( $id ) );
}


?>