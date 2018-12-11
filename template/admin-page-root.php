<?php

$ops = get_option('iclpdf_options');

$pdf_cover_title_text = isset( $ops['pdf_cover_title_text'] ) ? $ops['pdf_cover_title_text'] : '';
$pdf_cover_image_url = isset( $ops['pdf_cover_image_url'] ) ? $ops['pdf_cover_image_url'] : '';

$feedback = '';

if ( isset( $_POST['update_pdf_settings'] ) ) 
{
    $pdf_cover_title_text = sanitize_text_field( $_POST['pdf_cover_title_text'] );
    $pdf_cover_image_url = sanitize_text_field( $_POST['pdf_cover_image_url'] );
    
    if ( ! is_array( $ops ) ) {
        $ops = array();
    }
    $ops['pdf_cover_title_text'] = $pdf_cover_title_text;
    $ops['pdf_cover_image_url'] = $pdf_cover_image_url;
    
    update_option( 'iclpdf_options', $ops );
    $feedback = 'Settings saved.';
}

if ( $feedback ) {
    echo '<div class="notice updated"><p>' .$feedback. '</p></div>';
}




//$apages = iclpdf_get_additional_pages();
//print_r($apages);



?>

<div class="wrap">
    <h1><?php echo IPDF_MENU_NAME; ?></h1>
    <hr>
    
    <p>The following are options for the PDF document that is downloadable from the front-end of the site.</p>
    
    <form method="post" action="">
        
        <table style="width:90%">
            <tr>
                <td style="width:180px;"><label for="pdf_cover_title_text">PDF Cover Title:</label></td>
                <td><input type="text" name="pdf_cover_title_text" value="<?php echo $pdf_cover_title_text; ?>" id="pdf_cover_title_text" class="long-input" /></td>
            </tr>
            <tr>
                <td><label for="pdf_cover_image_url">Cover Image URL:</label></td>
                <td><input type="text" name="pdf_cover_image_url" value="<?php echo $pdf_cover_image_url; ?>" id="pdf_cover_image_url" class="long-input" /></td>
            </tr>
        </table>
        
        
        <br><br>
        <input type="submit" name="update_pdf_settings" value="Save Settings" class="button-primary" />
        
    
    </form>




    
</div>


<style>
.long-input {
    width:  80% !important;
    max-width:  600px;
}
</style>