<?php
if(isset($_REQUEST['File']) && !empty($_REQUEST['File'])){
    define('WP_USE_THEMES', false);
    require('../../../wp-load.php');    
    $c = new FBK_DownloadZipAttachments;
    switch_to_blog( $_REQUEST['blog'] );
    $uploads = wp_upload_dir(); 
    $tmp_location = $uploads['path']."/".$_REQUEST['File'];
    echo $tmp_location;
    $c->create_zip();
    $c->forceDownload($tmp_location,false);     
    unlink($tmp_location); 
    exit;
}
