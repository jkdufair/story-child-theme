<?php
/**
 * Prints a gallery. Overrides the default WordPress [gallery] shortcode output.
 * Customized by jkdufair to use WordPress' thumbnail selection for proper responsive
 * and retina selection
 *
 * @param string  $output the output
 * @param array   $attr   shortcode attributes that set the gallery options
 * @return string         the gallery markup
 */
function pexeto_print_gallery( $output, $attr ) {
    global $post, $pexeto_page, $pexeto_scripts, $pexeto;
    $add_class = '';

    //check if masonry layout is enabled
    $is_masonry = pexeto_option( 'qg_masonry_'.$post->post_type );

    //calculate the number of columns
    $columns = isset( $attr['columns'] ) && intval( $attr['columns'] ) ?
             $attr['columns'] : 3;

    $full_width_custom = false;
    if(is_page_template('template-full-custom.php') && pexeto_option('qg_masonry_fullpage_layout')=='full'){
        $full_width_custom = true;
        $add_class.=' qg-full qg-full-col-'.$columns;
        $is_masonry = false;
    }

    $gallery_settings = array(
        'qg_thumbnail_height' => pexeto_option( 'qg_thumbnail_height_'.$post->post_type ),
        'qg_masonry' => $is_masonry
    );

    if($is_masonry===true){
        $pexeto_scripts['masonry'] = true;
    }

    //get the gallery container layout
    $layout = isset( $pexeto_page['blog_layout'] ) ?
			$pexeto_page['blog_layout'] :  $pexeto_page['layout'];

    if ( empty( $layout ) ) {
        $layout='full';
    }
    $image_size = pexeto_get_image_size_options( $columns, 'quick_gallery', $layout);
    $image_width = $image_size['width'];

    //get the image height
    if ( $layout == 'twocolumn' || $layout == 'threecolumn' ) {
        //when it is a narrow column in the blog, make the image square
        $image_height = $image_width;
    }elseif ( $is_masonry ) {
        //masonry is enabled, set the height to be dynamic depending on the
        //original image ratio
        $add_class.= ' page-masonry';
        $image_height = '';
    }else {
        //masonry is disabled, get the default image height settings
			
        if(isset($attr['thumbnail_height'])){
            $image_height = intval($attr['thumbnail_height']);
        }else{
            $image_height = $gallery_settings['qg_thumbnail_height'];
        }

        if($full_width_custom){
            //full-width custom page, displaying the gallery in a full-width layout
            //set the image size to be a bit bigger
            $add_vals=array(1=>350, 2=>300, 3=>200, 4=>150);
            $add_val = isset($add_vals[$columns]) ? $add_vals[$columns] : 100;

            $image_height = $image_height * ($image_width+$add_val) / $image_width;
            $image_width += $add_val;
        }
			
    }

    $section_id = $full_width_custom ? ' id="'.pexeto_generate_section_id().'"':'';
    $html = '<div class="quick-gallery'.$add_class.'"'.$section_id.'>';

    $attachments = pexeto_get_gallery_attachments( $attr, $post->ID );


    if ( empty( $attachments ) ) {
        return '';
    }

    if ( is_feed() ) {
        //return standard list of images in a feed
        $html = "\n";
        foreach ( $attachments as $att_id => $attachment ) {
            $html .= wp_get_attachment_link( $att_id, 'thumbnail', true ) . "\n";
        }
        return $html;
    }
    $pexeto->gallery_count++;

    foreach ( $attachments as $attachment ) {
        $img =  wp_get_attachment_image_src($attachment->ID, 'full');
        $imgurl = wp_get_attachment_image_src($attachment->ID, 'large')[0];
        $caption = get_post_field( 'post_excerpt', $attachment->ID );
        $add_class = $caption ? '' : ' qg-no-title';

        $html .= '<div class="qg-img'.$add_class.'" data-defwidth="'.$image_width.'"><a href="'
             .$img[0].'" data-rel="lightbox[group-'.$post->ID.$pexeto->gallery_count.']" title="'
             .htmlspecialchars( $attachment->post_content ).'" ><img src="'
             .$imgurl.'" alt="'.esc_attr(get_post_meta($attachment->ID, '_wp_attachment_image_alt', true)).'"/>';
        $html.='<div class="qg-overlay"><span class="icon-circle"><span class="pg-icon lightbox-icon"></span></span>';
        if ( $caption ) {
            $html.='<span class="qg-title">'.$caption.'</span>';
        }
        $html.='</div></a></div>';
    }

    $html .='<div class="clear"></div></div>';

    return $html;
}   
?>