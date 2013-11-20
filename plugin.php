<?php

/**
 * Plugin Name: FumbleBuK Attachment Download
 * Plugin URI: http://www.fumblebuk.com
 * Description: Download all attachments from the post into a zip file
 * Version: 0.1.00
 * Author: 
 * Author Email: 
 * License:
 * 
 *  Copyright 2013 FumbleBuK
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License, version 2, as 
 *  published by the Free Software Foundation.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *  
 */

class FBK_DownloadZipAttachments extends WP_Widget{

    /*--------------------------------------------*
	 * Constants
	 *--------------------------------------------*/
	const name = 'FBK Download Zip Attachments';
	const slug = 'download_zip_attachments';
	var $post_types = array('post');
    var $post_types_attachments = array('attachment');
	/**
	 * Constructor
	 */
	function __construct() {				
		add_action( 'init', array( &$this, 'init_download_zip_attachments' ) );        
        $widget_ops = array( 'classname' => 'download_zip_attach_widget', 'description' => __('Coloca un boton de descarga de attachments en el single', 'download_zip_attachments') ); // Widget Settings
        $control_ops = array( 'id_base' => 'download_zip_attach_widget'); // Widget Control Settings
        $this->WP_Widget( 'download_zip_attach_widget', self::name, $widget_ops, $control_ops );   
                
	}
  
    function widget($args,$instance){
        global $post;
        if(is_single()){
        	$title = apply_filters('widget_title', $instance['title']); // the widget title
    		echo $args['before_widget'];	    
    		if ( $title )
    		echo $args['before_title'] . $title . $args['after_title']; 	
    		$this->display_widget($widget);	
    	   echo $args['after_widget'];			
        }
	}  
    
    function display_widget($widget){
    	$this->download_metabox();		
	}
    
    
  	function init_download_zip_attachments() {
		// Setup localization
        
		load_plugin_textdomain( 'download_zip_attachments', false, dirname(plugin_basename(__FILE__)) . '/lang');
		// Load JavaScript and stylesheets
		$this->register_scripts_and_styles();
	
                // load in actions for content filters
                add_filter( 'the_content', array(&$this,'my_the_content_filter') );
	        /* add a CSS specifically for attachment icons */
                add_action( 'wp_print_styles', array(&$this,'my_enqueue_style') );

	
		if ( is_admin() ) {
			//this will run when in the WordPress admin
            add_action( 'add_meta_boxes', array(&$this,'meta_box'));
		} else {

		}
   
        add_action('wp_ajax_nopriv_download_zip_attachments', array( &$this, 'download_zip' ) );
        add_action('wp_ajax_download_zip_attachments', array( &$this, 'download_zip' ) );
        
	}

	private function register_scripts_and_styles() {
		if ( is_admin() ) {			
			$this->load_file( self::slug . '-admin-style', '/css/admin.css' );
		} else {			
			$this->load_file( self::slug . '-style', '/css/widget.css' );
		}   // end if/else
	} // end register_scripts_and_styles
	
	/**
	 * Helper function for registering and enqueueing scripts and styles.
	 *
	 * @name	The 	ID to register with WordPress
	 * @file_path		The path to the actual file
	 * @is_script		Optional argument for if the incoming file_path is a JavaScript source file.
	 */
	private function load_file( $name, $file_path, $is_script = false ) {

		$url = plugins_url($file_path, __FILE__);
		$file = plugin_dir_path(__FILE__) . $file_path;

		if( file_exists( $file ) ) {
			if( $is_script ) {
				wp_register_script( $name, $url, array('jquery') ); //depends on jquery
				wp_enqueue_script( $name );
			} else {
				wp_register_style( $name, $url );
				wp_enqueue_style( $name );
			} // end if
		} // end if

	} // end load_file
/**
 * This plugin adds attachment lists to the end of all posts
 * 
 * The code below was unabashedly lifted from http://theme.fm/2011/07/how-to-display-post-attachments-in-wordpress-945/
 */


/**
 * Filter the_content so we can append our list of post attachments before the post content gets displayed.
 */

private function all_attachments_as_zip($post_id, $blog) {
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

private function individual_attachments($attachments) {
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

    return $content;
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
			$content .= $this->all_attachments_as_zip($post->ID, get_current_blog_id());
			$content .= $this->individual_attachments($attachments);
		}
	}

	/* return the content or you’ll end up with an empty post! */
	return $content;
}

function my_enqueue_style() {
	wp_enqueue_style( 'post-attachemnts', get_stylesheet_directory_uri() . '/attachments.css', array(), null );
}

/* creates a compressed zip file */
public function create_zip($files = array(),$destination = '',$overwrite = false) {
	//if the zip file already exists and overwrite is false, return false
	if(file_exists($destination) && !$overwrite) { return false; }
	//vars
	$valid_files = array();
	//if files were passed in...
	if(is_array($files)) {
		//cycle through each file
		foreach($files as $file) {
			//make sure the file exists
			if(file_exists($file)) {
				$valid_files[] = $file;
			}
		}
	}
	//if we have good files...
	if(count($valid_files)) {
		//create the archive
		$zip = new ZipArchive();
		if($zip->open($destination,$overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE) !== true) {
			return false;
		}
		//add the files
		foreach($valid_files as $file) {
			$zip->addFile($file,basename($file));
		}
		//debug
		//echo 'The zip archive contains ',$zip->numFiles,' files with a status of ',$zip->status;
		
		//close the zip -- done!
		$zip->close();
		
		//check to make sure the file exists
		return file_exists($destination);
	}
	else
	{
		return false;
	}
}

        public function forceDownload($archiveName,$exit = true) {
                if(ini_get('zlib.output_compression')) {
                        ini_set('zlib.output_compression', 'Off');
                }

                // Security checks
                if( $archiveName == "" ) {
                        echo "<html><title>Public Photo Directory - Download </title><body><BR><B>ERROR:</B> The download file was NOT SPECIFIED.</body></html>";
                        exit;
                }
                elseif ( ! file_exists( $archiveName ) ) {
                        echo "<html><title>Public Photo Directory - Download </title><body><BR><B>ERROR:</B> File not found.</body></html>";
                        exit;
                }

                ob_clean();
                header("Pragma: public");
                header("Expires: 0");
                header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                header("Cache-Control: private",false);
                header("Content-Type: application/zip");
                header("Content-Disposition: attachment; filename=".basename($archiveName).";" );
                header("Content-Transfer-Encoding: binary");
                header("Content-Length: ".filesize($archiveName));
                readfile("$archiveName");
                if($exit)
                    exit;
        }


    
    function download_zip(){        
        $files_to_zip = array();// create files array
        //run a query
        $post_id = $_POST["Id"];
        $blog_id = $_POST["blog"];
        $args = array(
            //'post_type' => $this->post_types_attachments,
            'post_type' => 'attachment',
            'posts_per_page' => -1,
            //'numberposts' => null,
            'post_status' => 'any',
            'post_parent' => $post_id
        );

        error_log(print_r($args,true));
        switch_to_blog( $blog_id );
        $attachments = get_posts($args);
        error_log(print_r($attachments, true));
        if ($attachments) {
            //print_r($attachments);
            foreach ($attachments as $attachment) {
              $files_to_zip [] = get_attached_file( $attachment->ID ); // populate files array
            }
	
            error_log(print_r($files_to_zip, true));
            $uploads = wp_upload_dir(); 
            $tmp_location = $uploads['path'];
            $tmp_location_url = $uploads['url'];
            $FileName = sanitize_title(get_the_title($post_id)).".zip";
error_log("Dir: " . $tmp_location . "   File: " . $FileName);
            $zipFileName = $tmp_location.'/'.$FileName;        
            ob_clean();
            $this->create_zip($files_to_zip, $zipFileName, true); 
            echo plugins_url('download.php', __FILE__)."?File=".$FileName."&blog=".$blog_id;
        }else{
            echo 'false';
        }
        exit;
    }
  
   function meta_box() {  
        for($i=0; $i<count($this->post_types); $i++){
            add_meta_box( 'download_metabox', __('Descargar Attachments', 'download_zip_attachments'), array(&$this,'download_metabox'), $this->post_types[$i], 'side', 'high' );
        }        
    }
        
    function download_metabox(){ ?>
        <input class="button-primary" type="button" name="DownloadZip" id="DownloadZip" value="<?php echo __('Descargar Zip', 'download_zip_attachments') ?>" onclick="download_zip_attachments_();" />
        <div class="download_zip_loading" style="display:none"></div>
        <script type="text/javascript">
            function download_zip_attachments_(){    
              jQuery.ajax({
                type: 'POST',
                url: "/wp-admin/admin-ajax.php",
                data: { action : 'download_zip_attachments',Id:<?php echo get_the_id(); ?> },
                beforeSend: function(){
                    jQuery('.download_zip_loading').show();
                },
                success: function(data){                  
                 
                  if(data != 'false'){
                    window.location = data;
                  }else{
                    alert('<?php echo __('Este post no contiene attachments', 'download_zip_attachments'); ?>');
                  }
                  jQuery('.download_zip_loading').hide();
                }
              });
            }
        </script>
    <?php 
    }
} // end class
new FBK_DownloadZipAttachments();

add_action('widgets_init', create_function('', 'return register_widget("FBK_DownloadZipAttachments");'));
