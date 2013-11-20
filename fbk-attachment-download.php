<?php

/**
 * Plugin Name: FumbleBuK Attachment Download
 * Plugin URI: http://fumblebuk.com
 * Description: A brief description of the Plugin.
 * Version: 0.1.00
 * Author: FumbleBuK
 * Author URI: http://fumblebuk.com
 * License: TBD
 */

/**
 * This plugin adds attachment lists to the end of all posts
 * 
 * The code below was unabashedly lifted from http://theme.fm/2011/07/how-to-display-post-attachments-in-wordpress-945/
 */


/**
 * Filter the_content so we can append our list of post attachments before the post content gets displayed.
 */
add_filter( 'the_content', 'my_the_content_filter' );

function all_attachments_as_zip($post_id, $blog) {
    $str = <<<DOWNLOAD
	    <input class="button-primary" type="button" name="DownloadZip" id="DownloadZip" value="Download All" onclick="download_zip_attachments_();" />
	    <div class="download_zip_loading" style="display:none"></div>
	    <script type="text/javascript">
		function download_zip_attachments_(){
		  jQuery.ajax({
		    type: 'POST',
		    url: "/wp-admin/admin-ajax.php",
		    data: { 'action' : 'download_zip_attachments', 'Id': '{$post_id}' , 'blog': '{$blog}' },
		    beforeSend: function(){
			jQuery('.download_zip_loading').show();
		    },
		    success: function(data){

		      if(data != 'false'){
			window.location = data;
		      }else{
			alert('This post contains no attachments');
		      }
		      jQuery('.download_zip_loading').hide();
		    }
		  });
		}
	    </script>
DOWNLOAD;

    return $str;
}

function individual_attachments($attachments) {
    $content = '';
    $content .= '<ul class="post-attachments">';
    /* for each attachment, create a list item */
    foreach ( $attachments as $attachment ) {
	/* generate the class variable based on the attachment#s mime type in case we want to use CSS to style by file type */
	$class = "post-attachment mime-" . sanitize_title( $attachment->post_mime_type );
	/* return a hyper link that points directly to the attached file *not* its attachment page */
	$title = wp_get_attachment_link( $attachment->ID, false );
	$file_url = wp_get_attachment_url( $attachment->ID );
	$parts = parse_url($file_url);
	/* find the 'greater-than' symbol, replace it with the HTML5 link download="<filename>" attribute, then reintroduce the 'greater-than' symbol */
	$title = str_replace('>', ' download="' . basename($parts['path']) . '">', $title);;
	/* create the list item for each loop */
	$content .= '<li class="' . $class . '">' . $title . '</li>';
    }
    /* when all loops are finished, close the unordered list */
    $content .= '</ul>';
}

function my_the_content_filter( $content ) {

        /* declare global variable $post to store the current post while we’re in the loop */
        global $post;

	/* check to make sure we’re acting on a single post which is published */
	if ( is_single() && $post->post_type == 'post' && $post->post_status == 'publish' ) {
		/* get *all* attachments (posts_per_page set to zero) that are attached to (children of) the current post (their parent) */
		$attachments = get_posts( array(
			'post_type' => 'attachment',
			'posts_per_page' => 0,
			'post_parent' => $post->ID
		) );
 error_log(print_r($attachments,true));
		/* check to make sure the attachments array is *not* empty, otherwise don’t print the heading */
		if ( $attachments ) {
			/* append the unordered list heading */
			$content .= '<h3>Download Links:</h3>';
			$content .= all_attachments_as_zip($post->ID, get_current_blog_id());
			$content .= individual_attachments($attachments);
		}
	}

	/* return the content or you’ll end up with an empty post! */
	return $content;
}

/* add a CSS specifically for attachment icons */

add_action( 'wp_print_styles', 'my_enqueue_style' );

function my_enqueue_style() {
	wp_enqueue_style( 'post-attachemnts', get_stylesheet_directory_uri() . '/attachments.css', array(), null );
}

?>
