<?php
/*
Template Name: Edit Listing
*/
wp_enqueue_script( 'jquery-ui-sortable' );

$image_upload_limit = get_option( "image_upload_limit" );
$expiration_period = get_option( "expiration_period" );
$spaces_limit = get_option( "spaces_limit" );

$post_id = $_GET['id'];
$listing = get_post($post_id, ARRAY_A);

$seconds_per_period = $expiration_period * 24 * 60 * 60;
$expiration_time = strtotime($listing['post_modified_gmt']) + $seconds_per_period;
$expiration_days = round(($expiration_time - time()) / (24 * 60 * 60));


global $current_user, $wpdb;
get_currentuserinfo();

$args = array(
   'post_type' => 'attachment',
   'orderby' => 'menu_order',
   'order'            => 'ASC',
   'numberposts' => -1,
   'post_status' => null,
   'post_parent' => $listing['ID']
  );

$attachments = get_posts( $args );

if($listing['post_author'] == $current_user->ID || $current_user->ID == 1) {
	if(isset($_GET['delete'])) {
		wp_delete_post($_GET['delete'], 1);
		$success_msg = "Image Deleted.";
	}
	if ( isset($_POST['email']) ) {
		$encrypted_email = $_POST['email'];

		$decrypted = tsz_decrypt_email($encrypted_email);

		if(get_user_by("login", tsz_hash($decrypted)) || $current_user->ID == 1) {
			
			$listing = array_merge($listing, array(
				'post_type' => "listing",
				'post_status' => "publish", // auto publish as soon as user edits listing first time
				'post_title' => $_POST['title'],
				'post_content' => $_POST['description']
			));
			wp_update_post( $listing );
			update_post_meta($listing['ID'], "tsz_listing_address", $_POST['address']);

			$success_msg .= " Listing Saved.";

			if (!function_exists('wp_generate_attachment_metadata')){
                require_once(ABSPATH . "wp-admin" . '/includes/image.php');
                require_once(ABSPATH . "wp-admin" . '/includes/file.php');
                require_once(ABSPATH . "wp-admin" . '/includes/media.php');
            }

            if ($_FILES ) {
            	if(count($attachments) >= $image_upload_limit) {
				$error_msg = "You already have $image_upload_limit images.  Delete some before adding more.";
			} else {
	                foreach ($_FILES as $file => $array) {
	                    if ($_FILES[$file]['error'] !== UPLOAD_ERR_OK) {
	                        //$error_msg = "Upload Error: " . $_FILES[$file]['error'];
	                    } else {
	                    	$result = media_handle_upload( $file, $listing['ID'] ) or die("upload failed");
	                    	if ( is_wp_error($result) )
	                    		$error_msg = print_r($result, true);
	                    }
	                }   
	                
				}

	        }
	        
	        if(isset($_POST['image_order'])) {
	        	foreach($_POST['image_order'] as $order => $id) {
	        		wp_update_post( array( "ID" => $id, "menu_order" => $order) );
	        	}
	        }
	        $attachments = get_posts( $args );  // refresh list
        

	        if(isset($_POST['space_names'])) {
	        	$count = 1;
	        	$wpdb->suppress_errors = false;
	        	$spaces = $_POST['space_names'];

	        	foreach($spaces as $order => $name) {

	        		if($space = $wpdb->get_row("SELECT * FROM tsz_spaces WHERE listing_id = $listing[ID] AND ord = $order"))
	        			$wpdb->update( "tsz_spaces", 
	        				array("name" => $name),
	        				array("id" => $space->id));
	        		elseif($name != "")
	        			$wpdb->insert( "tsz_spaces", 
	        				array("name" => $name, "ord" => $count, "listing_id" => $listing['ID'])) or die("can't insert space");

	        		if($count >= $spaces_limit)
	        			break; // strict enforcment

	        		$count ++;
	        		
	        	}
	        }

			

		} else {
			$error_msg = "Incorrect Email.";
		}

	}
	$address = get_post_meta( $listing['ID'], "tsz_listing_address", true );


} else {
	$listing = array();
	$attachments = array();
	$error_msg = "You Can't Edit This Listing.";
}





get_header();

?>
<div id="main-content" class="main-content">
	<div id="primary" class="content-area">
		<div id="content" class="site-content" role="main">
			<header class="entry-header"><h1>Edit Your Listing</h1></header>
			<div class="entry-content">
				<?php if(isset($error_msg)): ?>
					<div class="message-response error"><?php echo $error_msg ?></div>
				<?php endif; ?>
				<?php if($success_msg): ?>
					<div class="message-response success"><?php echo $success_msg ?></div>
				<?php endif; ?>
				<form name="listingform" id="listingform" method="post" action="/edit-listing/?id=<?php echo $listing['ID'] ?>" enctype="multipart/form-data">
					<input type="hidden" name="email" id="email" />
					<p>
						<label for="title">Title<br />
						<input type="text" name="title" id="title" class="input" size="55" value="<?php echo stripslashes($listing['post_title']) ?>" /></label>
					</p>
					<p>
						<label for="description">Description (drag box open for more space!)<br />
						<textarea name="description" id="description" class="input"><?php echo stripslashes($listing['post_content']) ?></textarea>
					</p>
					<p>
						<label for="address">Cross Steets, City, State &amp; Zip (on one line)<br />
						<input type="text" name="address" id="address" class="input" size="55" value="<?php echo stripslashes($address) ?>" /></label>
					</p>
					<p>
					<?php $id = get_the_ID(); include "map.php"; ?>
					</p>
					<p>
						<label for="image">Add an Image (keeping saving up to <?php echo $image_upload_limit ?>)<br />
						<input type="file" name="image" id="image"></label>
					</p>
					<?php if ( $attachments ): ?>
					Drag and Drop to reorder images.  Click to delete.<br />
					<ul id="sortable" style="list-style-type: none; margin: 0; padding: 0;">
						<?php 
				        foreach ( $attachments as $attachment ) {
				        	echo "<li style='display: inline-block; margin: 4px 2px 4px 2px;'>";
				        	echo "<input type='hidden' name='image_order[]' value='" . $attachment->ID . "' />";
				        	echo "<a href='?id=" . $listing['ID'] . "&delete=" . $attachment->ID . "'>";
				        	echo wp_get_attachment_image( $attachment->ID, 'thumbnail' ) . "&nbsp;";
				        	echo "</a></li>";
				          } ?>
				    </ul>
					<?php endif; ?>

					<?php include_once( ABSPATH . 'wp-admin/includes/plugin.php' ); ?>


					<?php

					// move all this to res plugin
					 if(is_plugin_active("tsz_reservations/tsz_reservations.php")):

						$spaces = $wpdb->get_results("SELECT * FROM tsz_spaces WHERE listing_id = $listing[ID] ORDER BY ord"); ?>

					<p><h3>Reservations:</h3>You can keep track of reservations for up to <?php echo $spaces_limit ?> different rooms/spaces.  Each space needs a short, specific name ("the Blue Room", etc...) </p>
					<ul style="list-style-type: none; margin: 0; padding: 0;">
						<?php for($i = 1; $i <= $spaces_limit; $i++): ?>
							<li>
							<label for="space_name[<?php echo $i?>]">Space <?php echo $i ?> 
								<?php if(isset($spaces[$i - 1])): ?>: 
								<a href="/manage_reservation/?listing_id=<?php echo $listing['ID'] ?>&space=<?php echo $i ?>"> Manage Reservations</a>
								 or <a href="delete">Delete</a>

								<?php endif; ?><br />
							<input type="text" name="space_names[<?php echo $i ?>]" size="40" value="<?php if(isset($spaces[$i - 1])) echo $spaces[$i - 1]->name ?>" placeholder="name"></label>
						</li>

						<?php endfor; ?>

					</ul>
					<?php endif; // ?>


					<br class="clear" style="clear: both; padding-top: 20px;" />
					<p class="submit"><input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="Save" /> &nbsp;&nbsp;&nbsp;&nbsp; <a href="/listing/<?php echo $listing['post_name'] ?>" target="_blank"><b>View Listing</b></a><br />
					This listing will expire in <?php echo $expiration_days ?> days.  If you click save now it will be reset to <?php echo $expiration_period ?> days!  The listing will be destroyed if it's allowed to expire.</p>

						
				</form>
				<div style="margin-bottom: 30px">

				</div>
         

				
				<?php
				// Start the Loop.
				while ( have_posts() ) : the_post();
					the_content();
				endwhile;
			?>
			</div>

		</div><!-- #content -->
	</div><!-- #primary -->
	<?php get_sidebar( 'content' ); ?>
</div><!-- #main-content -->

<SCRIPT src="<?php echo plugins_url() ?>/tsz_listings/js/jquery.cookie.js"></SCRIPT>
<script type="text/javascript">
	jQuery("#listingform").submit(function( event ) {
		jQuery("#email").val(jQuery.cookie("email"));
	});

	  jQuery(function() {
	    jQuery( "#sortable" ).sortable();
	    jQuery( "#sortable" ).disableSelection();
	  });

</script>

<?php
get_sidebar();
get_footer();


