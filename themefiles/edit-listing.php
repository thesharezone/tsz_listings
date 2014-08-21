<?php
/*
Template Name: Edit Listing
*/


$listing = get_post($wp->query_vars['listing-id'], ARRAY_A);


global $current_user, $wpdb;
get_currentuserinfo();


if($listing['post_author'] == $current_user->ID || $current_user->ID == 1) {
	wp_enqueue_script( 'jquery-ui-sortable' );

	$image_upload_limit = get_option( "image_upload_limit" );
	$expiration_period = get_option( "expiration_period" );
	
	$seconds_per_period = $expiration_period * 24 * 60 * 60;
	if($listing['post_modified'])
		$expiration_time = strtotime($listing['post_modified']) + $seconds_per_period;
	else 
		$expiration_time = strtotime($listing['post_date']) + $seconds_per_period;

	$expiration_days = round(($expiration_time - time()) / (24 * 60 * 60));

	$address = get_post_meta( $listing['ID'], "tsz_listing_address", true );

	$args = array(
	   'post_type' => 'attachment',
	   'orderby' => 'menu_order',
	   'order'            => 'ASC',
	   'numberposts' => -1,
	   'post_status' => null,
	   'post_parent' => $listing['ID']
	  );

	$attachments = get_posts( $args );


	if(isset($wp->query_vars['delete-image'])) {
		wp_delete_post($wp->query_vars['delete-image'], 1);
		$success_msg = "Image Deleted.";
	}

	do_action('tsz_edit_listing_load', $listing);



	//// FORM POSTED
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
			update_post_meta($listing['ID'], "tsz_listing_btc_address", $_POST['btc_address']);

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


	        do_action('tsz_edit_listing_post', $listing);
        



			

		} else {
			$error_msg = "Incorrect Email.";
		}

	}
	


} else { // clear data and set error
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
				<?php include "messages.php" ?>
				<form name="listingform" id="listingform" method="post" action="/edit-listing/<?php echo $listing['ID'] ?>" enctype="multipart/form-data">
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
					<?php include "map.php"; ?>
					</p>
					<p>
						<label for="btc_address">Bitcoin Address (optional, for receiving payments and reviews)<br />
						<input type="text" name="btc_address" id="btc_address" class="input" size="55" value="<?php echo stripslashes($btc_address) ?>" /></label>
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
				        	echo "<a href='/edit-listing/" . $listing['ID'] . "/delete-image/" . $attachment->ID . "'>";
				        	echo wp_get_attachment_image( $attachment->ID, 'thumbnail' ) . "&nbsp;";
				        	echo "</a></li>";
				          } ?>
				    </ul>
					<?php endif; ?>


					<?php do_action('tsz_edit_listing_form', $listing['ID']); ?>


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


