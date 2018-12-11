<?php
/**
*   Class SSA_PDF
*   ---
*   Extends TCPDF class. Sets document headers footers, and some other bits.
*
*/

class SSA_PDF extends TCPDF
{

	var $bg_rgb = array(
		'red' 	=> 255,
		'green' => 255,
		'blue' 	=> 255 
	);

	//var $text_font = 'helvetica';
    var $text_font = 'dejavusans';
	var $text_hex = '#363636';
	var $link_hex = '#3333ff';

	var $text_rgb = array(
		'red' 	=> 40,
		'green' => 40,
		'blue' 	=> 40 
	);
	
	var $link_rgb = array(
		'red' 	=> 40,
		'green' => 40,
		'blue' 	=> 255 
	);
		
	var $displayDate = '';
	var $siteURL = '';
	var $siteTitle = '';
	
    var $current_topic_title = '';
    var $current_lecture_title = '';
    var $current_slide_title = '';
	
    var $add_footer = true;
    
	//--- custom header
	public function Header()
	{
		//set the bg colour
		$bMargin = $this->getBreakMargin(); 		//get the current page break margin
		$auto_page_break = $this->AutoPageBreak; 	//get current auto-page-break mode
		$this->SetAutoPageBreak(false, 0); 			//disable auto-page-break						
		
		$this->Rect	( 
			0,
			0,
			210,
			297,
			'F',
			array(),
			array( $this->bg_rgb['red'], $this->bg_rgb['green'], $this->bg_rgb['blue'] ) 
		);	
		
		$this->SetAutoPageBreak( $auto_page_break, $bMargin ); 	//restore auto-page-break status
		$this->setPageMark(); 									//set the starting point for the page content
		
		//make header content
		if ( '' != $this->current_topic_title ) {
            $headerContent = '<p style="font-family:' . $this->text_font . '; font-size:14px; color:' . $this->text_hex . '; line-height:20px;">' .$this->current_topic_title;
            if ( '' != $this->current_lecture_title ) {
                $headerContent .= ': ' .$this->current_lecture_title;
            }
            $headerContent .= '</p>';
		} else {
            $headerContent = '';
        }
        
        $this->writeHTMLCell(
			0,
			3,
			30, //PDF_MARGIN_LEFT,
			5,
			$headerContent,
			0,
			2,
			false,
			true,
			'L',
			false
		);
		
		$style = array( 'width' => 0.25, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'phase' => 1, 'color' => array($this->text_rgb['red'], $this->text_rgb['green'], $this->text_rgb['blue']) );
		$this->Line( 25, 17, 210, 17, $style );
	}
	
	
	//--- custom footer
	public function Footer ()
	{
		if ( $this->add_footer === true ) {
            $this->SetY( -15 ); //position 15 mm from bottom		
            $this->SetFont( $this->text_font , 'N', 16 );

            $this->SetTextColorArray	(
                //array( $this->text_rgb['red'], $this->text_rgb['green'], $this->text_rgb['blue'] ),
                array( 255, 255, 255 ),
                false
            );
            
            
            //$htmlStr = '<p style="text-align:right; color:#fff; font-size:20px; font-weight:300;">Created: ' . $this->displayDate . '</p>';
            //$this->Cell( 173, 10, 'Page '.$this->getAliasNumPage(), 0, false, 'R', 0, '', 0, false, 'T', 'M' );
            //$this->Cell( 173, 10, $htmlStr, 0, false, 'R', 0, '', 0, false, 'T', 'M' );
            //$this->Cell( 173, 10, 'Created: ' . $this->displayDate, 0, false, 'R', 0, '', 0, false, 'T', 'M' );
            
            
            
            $this->writeHTMLCell(
                100,
                40,
                100, //PDF_MARGIN_LEFT,
                280,
                '<div style="background-color:#004675;">Created: ' . $this->displayDate . '</div>',
                0,
                2,
                false,
                true,
                'C',
                false
            );
            
            
            //$style = array( 'width' => 0.25, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'phase' => 1, 'color' => array($this->text_rgb['red'], $this->text_rgb['green'], $this->text_rgb['blue']) );
            //$this->Line(20, 282, 188, 282, $style);
            
            $this->add_footer = false;
        }
        else
        {
            $this->SetY( -15 ); //position 15 mm from bottom		
            $this->SetFont( $this->text_font , 'N', 11 );
            $this->SetTextColorArray	(
                array( $this->text_rgb['red'], $this->text_rgb['green'], $this->text_rgb['blue'] ),
                false
            );
            
            $this->Cell( 173, 0, $this->getAliasNumPage(), 0, false, 'R', 0, '', 0, false, 'T', 'M' );
            //$style = array( 'width' => 0.25, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'phase' => 1, 'color' => array($this->text_rgb['red'], $this->text_rgb['green'], $this->text_rgb['blue']) );
            //$this->Line(25, 282, 210, 282, $style);
        }
	}
	
}

?>