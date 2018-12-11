<?php


function iclpdf_make_pdf () 
{
    if ( wp_doing_ajax() ) {
        iclpdf_replace_shortcode_handlers();
    }
    
    
    //print_r( $_POST );
    $info = $_POST['info'];
    
    $user_notes = array();
    if ( class_exists('ekNotesDB') ) {
        if ( $info['include_user_notes'] == 'true' ) {
            $u_notes = ekNotesDB::getUserNotes( get_current_user_id() );
            if ( is_array( $u_notes ) ) {
                foreach ( $u_notes as $note ) {
                    $user_notes[ $note['postID'] ] = $note;
                }
            }
        }
    }
    
    $content = iclpdf_get_content();
    $pdfFilename = iclpdf_build_pdf_file( $content, $user_notes );
    
    echo '<div class="iclpdf-frontend-feedback">';
    iclpdf_drawFrontendFeedback( $pdfFilename );
	echo '</div>';
    
    die();
}





 

function iclpdf_build_pdf_file ( $content, $user_notes = array() ) 
{
    $ops = get_option('iclpdf_options');
   
    //$img_file = IPDF_PLUGIN_URL . '/images/cover-1.jpg';
	

    $img_file = ! empty( $ops['pdf_cover_image_url'] ) ? $ops['pdf_cover_image_url'] : IPDF_PLUGIN_URL . '/images/cover-1.jpg';
    //if($img_file=="")
	//{
	//	$img_file = IPDF_PLUGIN_URL . '/images/cover-1.jpg';
	//}
	
    //$side_img_file = IPDF_PLUGIN_URL . '/images/side-1.jpg';
    $side_img_file = ! empty( $ops['pdf_cover_image_url'] ) ? $ops['pdf_cover_image_url'] : IPDF_PLUGIN_URL . '/images/side-1.jpg';
    //if($side_img_file=="")
	//{
	//	$side_img_file = IPDF_PLUGIN_URL . '/images/side-1.jpg';
	//}
	
    //$displayTitle = get_bloginfo('name');
    $displayTitle = ! empty( $ops['pdf_cover_title_text'] ) ? $ops['pdf_cover_title_text'] : get_bloginfo('name');
	//if($displayTitle=="")
	//{
	//	$displayTitle= get_bloginfo('name');
	//}


   $displayTitle = html_entity_decode( $displayTitle, ENT_COMPAT, 'UTF-8' );
	
	//set up some default style options
	$bg_rgb = array(
		'red' 	=> 255,
		'green' => 255,
		'blue' 	=> 255 
	);

	//$text_font = 'helvetica';
	$text_font = 'dejavusans';
    
    $text_hex = '#363636';
	$link_hex = '#336699';
    
    $text_rgb = SSAPDF_hex2RGB( $text_hex );
	$link_rgb = SSAPDF_hex2RGB( $link_hex );
	
	//make a display creation date
	$utsNow = strtotime('now');
	$displayDate = date( 'jS F Y', $utsNow );
	
	//grab the site info
	$siteURL = get_bloginfo('url');
	$siteTitle = get_bloginfo( 'name', 'raw' );
    
    //clean up site title to use as filename
	$pdfFileName = preg_replace( "/&#?[a-z0-9]{2,8};/i", "", $siteTitle );
	$pdfFileName = str_replace( " ", "-",  $pdfFileName );
	$pdfFileName = str_replace( "/", "_",  $pdfFileName );
	
	$pdfFileName = preg_replace('!\.pdf$!i', '', $pdfFileName );
	$pdfFileName = $pdfFileName . ".pdf";
    
    
    //--- init TCPDF ---------------------------------------
	$pdf = new SSA_PDF( PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false );
    
	//pass style settings into the class vars
	$pdf->bg_rgb = $bg_rgb;
	
	$pdf->text_font = $text_font;
	$pdf->text_hex = $text_hex;
	$pdf->link_hex = $link_hex;

	$pdf->text_rgb = $text_rgb;
	$pdf->link_rgb = $link_rgb;
	
	$pdf->displayDate = $displayDate;
	$pdf->siteURL = $siteURL;
	$pdf->siteTitle = $displayTitle;
	
	// set document information
	$pdf->SetCreator('SSA-PDF(TC)');
	$pdf->SetAuthor('');
	$pdf->SetTitle( $pdfFileName );
	$pdf->SetSubject('');
	$pdf->SetKeywords('');

	// set header data
	$pdf->SetHeaderMargin( PDF_MARGIN_HEADER );
    //$pdf->SetHeaderMargin( 140 );
    
	// set footer data
	$pdf->SetFooterMargin( PDF_MARGIN_FOOTER );
	// set default monospaced font
	$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
	// set margins
	//$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetMargins( 30, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    
	// set auto page breaks
	$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
	// set image scale factor
	$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
	
	// set default font subsetting mode
	//$pdf->setFontSubsetting( true );

	// Set font
	// dejavusans is a UTF-8 Unicode font, if you only need to
	// print standard ASCII chars, you can use core fonts like
	// helvetica or times to reduce file size.
	$pdf->SetFont( 'dejavusans', '', 11, '', true, true );
	
	$pdf->SetTextColorArray	(
		array( $text_rgb['red'], $text_rgb['green'], $text_rgb['blue'] ),
		false
	);
    
    
    //--- build the contents ---------------------------------------
    $cssStr  = '';
    
    //set some extra document css 
    $cssStr .= '<style type="text/css"> ';
    $cssStr .= '.pageBreak { page-break-after: always; } ';
    $cssStr .= '* { font-family:' . $text_font . ';	} ';
    $cssStr .= 'a { color:' . $link_hex . '; } ';
    $cssStr .= 'a:visited { color:' . $link_hex . '; } ';
    $cssStr .= ' </style>';
    
    
    // Cover page and background image
    // ---
    $pdf->setPrintHeader( false );    // remove default header
    //$pdf->setPrintFooter( false );    // remove default header
    $pdf->AddPage();
    
    $bMargin = $pdf->getBreakMargin();              // get the current page break margin
    $auto_page_break = $pdf->getAutoPageBreak();    // get current auto-page-break mode
    $pdf->SetAutoPageBreak(false, 0);               // disable auto-page-break
    
    // set bacground image
    //$img_file = IPDF_PLUGIN_URL . '/images/cover-1.jpg';
    $pdf->Image($img_file, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);
    
    $pdf->SetAutoPageBreak( $auto_page_break, $bMargin ); // restore auto-page-break status
    $pdf->setPageMark();    // set the starting point for the page content
    
    //$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
    
    // set margin for front page
    $pdf->SetMargins( 0, PDF_MARGIN_TOP, 0);
    //#004675
    $htmlStr = '&nbsp;<br />&nbsp;<br /><div style="width:auto; background-color:#004675;"><table style="width:100%;"><tr><td style="width:30px;"></td><td style="width:700px;"><br/><h1 style="font-size:56px; font-weight:300; text-align:left; text-decoration:none; color:#fff;">' . $displayTitle . '</h1><br/></td></tr></table></div>';
    //$htmlStr = '&nbsp;<br />&nbsp;<br />&nbsp;<br /><div style="width:100%; background-color:#f00;">&nbsp;<br /><h1 style="font-size:56px; font-weight:300; text-align:left; text-decoration:none;color:#fff;">' . $displayTitle . '</h1></div>';
	//$htmlStr .= '<p style="text-align:right; color:#fff; font-size:20px; font-weight:300;">&nbsp;<br />&nbsp;<br />&nbsp;<br />&nbsp;<br />&nbsp;<br />&nbsp;<br />&nbsp;<br />&nbsp;<br />&nbsp;<br />&nbsp;<br />&nbsp;<br />&nbsp;<br />&nbsp;<br />&nbsp;<br />&nbsp;<br />&nbsp;<br />&nbsp;<br />&nbsp;<br />&nbsp;<br />&nbsp;<br />&nbsp;<br />Created: ' . $displayDate . '</p>';
    //$htmlStr .= '<p style="text-align:right;"><a href="' . $siteURL . '">' . $siteURL . '</a></p>';
    
    $pdf->writeHTML	( $cssStr . $htmlStr, true, false, false, false, '' );
    
    
    //set margin back to original
    //$pdf->SetMargins( 30, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    //$htmlStr = '&nbsp;<br />&nbsp;<br />&nbsp;<br />&nbsp;<br /><h1 style="font-size:56px; font-weight:300; text-align:left; text-decoration:none;color:#fff;">' . $displayTitle . '</h1>';
    //$pdf->writeHTML	( $cssStr . $htmlStr, true, false, false, false, '' );
    
    
    $pdf->setPrintHeader( true );
    $pdf->setPrintFooter( true ); 
    
    //set margin back to original
    $pdf->SetMargins( 30, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    
    
    // Add the course info pages
    $infopages = iclpdf_get_additional_pages();
    
    $root_pages = array();
    $sub_pages = array();
    foreach( $infopages as $page ) {
        if ( $page->post_parent ) {
            $sub_pages[] = $page;
        } else {
            $root_pages[] = $page;
        }
    }
    
    
    //foreach( $infopages as $page ) {
    foreach( $root_pages as $page ) {
        
        $htmlStr = '<h1 style="font-size:28px; font-weight:300;">' . $page->post_title . '</h1>';
        $IPcontent = apply_filters( 'the_content', $page->post_content );
        $htmlStr .= $IPcontent;
        
        
        $depth = $page->post_parent ? 1 : 0;
        
        $pdf->AddPage();				
        $pdf->Bookmark( html_entity_decode( $page->post_title, ENT_COMPAT, 'UTF-8'), $depth, 0, '', 'B', array($link_rgb['red'], $link_rgb['green'], $link_rgb['blue']), 0, '#TOC' );
        
        $bMargin = $pdf->getBreakMargin();              // get the current page break margin
        $auto_page_break = $pdf->getAutoPageBreak();    // get current auto-page-break mode
        $pdf->SetAutoPageBreak(false, 0);               // disable auto-page-break
        //$img_file = IPDF_PLUGIN_URL . '/images/side-1.jpg';
        $pdf->Image($side_img_file, 0, 0, 25, 297, '', '', '', false, 300, '', false, false, 0); // set bacground image
        $pdf->SetAutoPageBreak( $auto_page_break, $bMargin ); // restore auto-page-break status
        $pdf->setPageMark();    // set the starting point for the page content
        
        $pdf->writeHTML	( $cssStr . $htmlStr, true, false, false, false, '' );
        
        
        foreach( $sub_pages as $subpage ) {
            if ( $subpage->post_parent == $page->ID ) {
                
                $htmlStr = '<h1 style="font-size:28px; font-weight:300;">' . $subpage->post_title . '</h1>';
                $IPcontent = apply_filters( 'the_content', $subpage->post_content );
                $htmlStr .= $IPcontent;
                
                $depth = $subpage->post_parent ? 1 : 0;
                
                $pdf->AddPage();				
                $pdf->Bookmark( html_entity_decode( $subpage->post_title, ENT_COMPAT, 'UTF-8'), $depth, 0, '', 'B', array($link_rgb['red'], $link_rgb['green'], $link_rgb['blue']), 0, '#TOC' );
                
                $bMargin = $pdf->getBreakMargin();              // get the current page break margin
                $auto_page_break = $pdf->getAutoPageBreak();    // get current auto-page-break mode
                $pdf->SetAutoPageBreak(false, 0);               // disable auto-page-break
                //$img_file = IPDF_PLUGIN_URL . '/images/side-1.jpg';
                $pdf->Image($side_img_file, 0, 0, 25, 297, '', '', '', false, 300, '', false, false, 0); // set bacground image
                $pdf->SetAutoPageBreak( $auto_page_break, $bMargin ); // restore auto-page-break status
                $pdf->setPageMark();    // set the starting point for the page content
                
                $pdf->writeHTML	( $cssStr . $htmlStr, true, false, false, false, '' );
                    
            
        
            }
        }
        
    }
    
    
    
    $pagecount = 1;
    
    foreach ( $content as $i => $topic ) {
        
        $htmlStr = '<h1 style="font-size:40px; font-weight:300;">' . $topic->post_title . '</h1>';
        $htmlStr .= '';
        $htmlStr .= '';
        
        $depth = 0;
        $pdf->current_topic_title = $topic->post_title;
        $pdf->AddPage();				
        $pdf->Bookmark( html_entity_decode( $topic->post_title, ENT_COMPAT, 'UTF-8' ), $depth, 0, '', 'B', array($link_rgb['red'], $link_rgb['green'], $link_rgb['blue']), 0, '#TOC' );
        //$pdf->setPrintHeader( false );    // remove default header
        //$pdf->setPrintFooter( false );    // remove default header        
        
        $bMargin = $pdf->getBreakMargin();              // get the current page break margin
        $auto_page_break = $pdf->getAutoPageBreak();    // get current auto-page-break mode
        $pdf->SetAutoPageBreak(false, 0);               // disable auto-page-break
        
        //$img_file = IPDF_PLUGIN_URL . '/images/lectures.jpg';
        //$pdf->Image($side_img_file, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0); // set bacground image
        $pdf->SetAutoPageBreak( $auto_page_break, $bMargin ); // restore auto-page-break status
        $pdf->setPageMark();    // set the starting point for the page content
        
        $pdf->writeHTML	( $cssStr . $htmlStr, true, false, false, false, '' );
        
        foreach ( $topic->sessions as $j => $session ) {
            
            $SEScontent = apply_filters( 'the_content', $session->post_content );
            $htmlStr = '<h1 style="font-size:45px; font-weight:300;">' . $session->post_title . '</h1>';
            //$htmlStr .= '<p>learning outcomes etc..</p>';
            $htmlStr .= $SEScontent;
            
            $depth = 1;
            $pdf->current_lecture_title = $session->post_title;
            $pdf->AddPage();				
            $pdf->Bookmark( html_entity_decode( $session->post_title, ENT_COMPAT, 'UTF-8'), $depth, 0, '', 'B', array($link_rgb['red'], $link_rgb['green'], $link_rgb['blue']), 0, '#TOC' );
            
            $bMargin = $pdf->getBreakMargin();              // get the current page break margin
            $auto_page_break = $pdf->getAutoPageBreak();    // get current auto-page-break mode
            $pdf->SetAutoPageBreak(false, 0);               // disable auto-page-break
            //$side_img_file = IPDF_PLUGIN_URL . '/images/side-1.jpg';
            $pdf->Image($side_img_file, 0, 0, 25, 297, '', '', '', false, 300, '', false, false, 0); // set bacground image
            $pdf->SetAutoPageBreak( $auto_page_break, $bMargin ); // restore auto-page-break status
            $pdf->setPageMark();    // set the starting point for the page content
            
            $pdf->writeHTML	( $cssStr . $htmlStr, true, false, false, false, '' );
        
            foreach ( $session->slides as $k => $slide ) {
                
                $PRcontent = apply_filters( 'the_content', $slide->post_content ); 
                $htmlStr = '<h1>' . $slide->post_title . '</h1>';
                //$htmlStr .= '<p>slide content...</p>';
                $htmlStr .= $PRcontent;
                
                // Add user's notes
                if ( array_key_exists( $slide->ID, $user_notes ) ) {
                    $htmlStr .= '<div style="border:1px solid #050; background-color:#ccffcc; padding:10px;"><h2>My Notes<h2>' .apply_filters( 'the_content', $user_notes[ $slide->ID ]['noteContent'] ). '</div>';
                }
                
                $depth = 2;
                $pdf->current_slide_title = $slide->post_title;
                $pdf->AddPage();				
                $pdf->Bookmark( html_entity_decode( $slide->post_title, ENT_COMPAT, 'UTF-8'), $depth, 0, '', 'B', array($link_rgb['red'], $link_rgb['green'], $link_rgb['blue']), 0, '#TOC' );
                
                $bMargin = $pdf->getBreakMargin();              // get the current page break margin
                $auto_page_break = $pdf->getAutoPageBreak();    // get current auto-page-break mode
                $pdf->SetAutoPageBreak(false, 0);               // disable auto-page-break
                //$img_file = IPDF_PLUGIN_URL . '/images/side-1.jpg';
                $pdf->Image($side_img_file, 0, 0, 25, 297, '', '', '', false, 300, '', false, false, 0); // set bacground image
                $pdf->SetAutoPageBreak( $auto_page_break, $bMargin ); // restore auto-page-break status
                $pdf->setPageMark();    // set the starting point for the page content
                
                $pdf->writeHTML	( $cssStr . $htmlStr, true, false, false, false, '' );
        
                $pagecount += 1;
            }
        
            $pagecount += 1;
        }
        
        $pagecount += 1;
    }
    
    
    // ToC
    $pdf->setPrintHeader( false );
    $pdf->addTOCPage();
    $pdf->SetFont( $text_font, 'B', 16);
    $pdf->MultiCell(0, 16, 'Table of Contents', 0, 'L', 0, 1, '', '', true, 0);

    $pdf->SetFont( $text_font, '', 11);
    $insertAt = 2;
    $pdf->addTOC( $insertAt, $text_font, '.', 'TOC', 'B', array( $link_rgb['red'], $link_rgb['green'], $link_rgb['blue'] ));
    $pdf->endTOCPage();
    
 
    
    //--- output the PDF ---------------------------------------	
    $WPuploads = wp_upload_dir();
    $basePath = $WPuploads['basedir'];
    if ( ! file_exists( $basePath ) ) {
        mkdir( $basePath, 0777, true );
    }		
    
    //temp set server limit and timeout
    ini_set("memory_limit", "1024M");
    ini_set("max_execution_time", "600");
    ini_set("allow_url_fopen", "1");
    
    //output PDF document.
    $pdf->Output( $basePath . '/' . $pdfFileName, 'F');
    
    return $pdfFileName;
    //return 'testname.pdf';
}







function iclpdf_drawFrontendFeedback ( $pdfFileName )
{
	$blogID     = get_current_blog_id();
	$siteTitle  = get_bloginfo( 'name', 'raw' );
	$WPuploads  = wp_upload_dir();
	$basePath   = $WPuploads['basedir'];
	$baseURL    = $WPuploads['baseurl'];
	//$pluginFolder = plugins_url('', __FILE__);
    
	$fileLocal  = $basePath . '/' . $pdfFileName;
	$fileURL    = $baseURL . '/' . $pdfFileName;
	
	//check protocol and correct if needed
	$secureProtocol = ( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ) ? true : false;
	if ( $secureProtocol ) {
		$fileURL = preg_replace('!^http://!i', 'https://', $fileURL );
	}
	
	
	echo '<h3>Your PDF is ready!</h3>';
	//echo '<a href="' . $fileURL . '" id="forceDownloadLink" class="iclpdf-link">Download</a>';
    echo '<a href="' . $fileURL . '" class="iclpdf-link" target="_blank">Download</a>';
	echo '&nbsp;<a id="previewLink" class="iclpdf-link" target="_blank" href="' . $fileURL . '">Preview</a> ';
	//echo '<span>( Preview is only available in some browsers. )</span>';
	
	echo '<div id="previewDownload"></div>';
		
	echo '<hr />';
	echo '<p><em>Problems downloading? Here is a direct link to the PDF file.<br/>';
	echo 'Right-click it and choose \'save target\': </em> <a href="' . $fileURL . '" target="blank">' . $pdfFileName . '</a></p>';
	
	echo '<div id="forceDownload"></div>';
	?>
		<script type="text/javascript">
			var SSAPDF = {};
					
			SSAPDF.addForceFrame = function ( file ) {
				jQuery('#forceDownload').empty().append('<iframe id="forceDownloadFrame" name="forceDownloadFrame" src="<?php echo IPDF_PLUGIN_URL; ?>/download.php?pdf=loc' + file + '" style="display:none;"></iframe>');
			};
			
			SSAPDF.addPreviewFrame = function ( url ) {
				console.log(url);
				jQuery('#previewDownload').empty().append('<iframe style="width:100%; height:840px; border:1px solid #aaa;" id="previewFrame" name="previewFrame" src="' + url + '"></iframe>');
			};
			
			jQuery(document).ready( function () {				
				jQuery('#forceDownloadLink').click( function ( e ) {
					SSAPDF.addForceFrame( '<?php echo $fileLocal; ?>' );
					e.preventDefault();
				});
				
				jQuery('#previewLink').click( function ( e ) {
					SSAPDF.addPreviewFrame( '<?php echo $fileURL; ?>' );
					jQuery(this).text('Refresh Preview');
					e.preventDefault();
				});
			});
		</script>	
<?php
}
?>