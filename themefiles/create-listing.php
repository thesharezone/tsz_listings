<?php
/*
Template Name: Create Listing
*/

$details = tsz_key_details();


if ( isset($_POST['email']) ) {
	$image = new Securimage();
    if ($image->check($_POST['captcha_code']) == true) {
		$encrypted_email = $_POST['email'];

		$decrypted = tsz_decrypt_email($encrypted_email);
		$recrypted = tsz_encrypt_email($decrypted);

		if(get_user_by("login", tsz_hash($decrypted))) {
			$error_msg = "User already exists, we only allow one listing per email address at this time.";
		} else {
			$result = register_new_user(tsz_hash($decrypted), $recrypted);
			if ( !is_wp_error($result) ) {
				//wp_update_user( array( 'ID' => $result, 'role' => "contributor" ) );
				$listing = array(
					'post_type' => "listing",
					'post_status' => "pending",
					'post_title' => $_POST['title'],
					'post_content' => $_POST['description'],
					'post_author' => $result
				);
				$listing_id = wp_insert_post( $listing );
				add_post_meta($listing_id, "tsz_listing_address", $_POST['address'], 1);
				$success_msg = "Your listing has been created.  Your password and link to login to edit your listing have been sent to the email address you entered.";

			} else {
				$error_msg = print_r($result, true);
			}
		}
	} else {
      	$error_msg = "Captcha code did not match";
    }

}

get_header();

?>
<div id="main-content" class="main-content">
	<div id="primary" class="content-area">
		<div id="content" class="site-content" role="main">
			<header class="entry-header"><h1>Create Your Listing</h1></header>
			<div class="entry-content">
				<?php if(isset($error_msg)): ?>
					<div class="message-response error"><?php echo $error_msg ?></div>
				<?php endif; ?>
				<?php if($success_msg): ?>
					<div class="message-response success"><?php echo $success_msg ?></div>
				<?php else: ?>
				<p>Need some ideas?  Check out the <a href="<?php echo get_permalink(get_sample_listing()->ID) ?>" traget="_blank">sample listing</a>.</p>
				<form name="listingform" id="listingform" method="post">
					<p>
						<label for="title">Title<br />
						<input type="text" name="title" id="title" class="input" size="55" value="<?php echo $_POST['title'] ?>" /></label>
					</p>
					<p>
						<label for="description">Description (drag box open for more space)<br />
						<textarea name="description" id="description" class="input"><?php echo $_POST['description'] ?></textarea>
					</p>
					<p>
						<label for="address">Cross Steets, City, State &amp; Zip (this will be show to the public do don't use exact address!)<br />
						<input type="text" name="address" id="address" class="input" size="55" value="<?php echo $_POST['address'] ?>" /></label>
					</p>
					<p>
						<label for="email">E-mail Address<br />
						<input type="text" name="email" id="email" class="input" size="55" /></label>
					</p>
					<p>A random, secure password will be e-mailed to you.</p>
					<p>
						<?php echo Securimage::getCaptchaHtml() ?>
					</p>
					<br class="clear" />
					<p class="submit"><input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="List It!" /></p>
				</form>
				<?php endif; ?>
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
		if(jQuery("#email").val() == "") {
			alert("You must enter an email address.");
			event.preventDefault();
		}
		var data = cryptEmail(event, "email", "<?php echo to_hex($details['rsa']['n']) ?>", "<?php echo to_hex($details['rsa']['e']) ?>");
		return true;
	});

</script>

<?php
get_sidebar();
get_footer();
