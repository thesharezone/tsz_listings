<?php
/*
Template Name: Login
*/



$details = tsz_key_details();

if(isset($_GET['logout'])) {
	wp_logout();
	wp_redirect("/login");
} elseif ( isset($_POST['log']) ) {
	sleep(5);
	$encrypted_email = $_POST['log'];

	$_POST['log'] = tsz_decrypt_email($encrypted_email);

	if($user = wp_signon()) {
		if(!is_wp_error($user)) {

			if($user->ID == 1) {
				wp_redirect(admin_url());
			} else {
				$listings = get_posts(array('author' => $user->ID, 'post_type' => 'listing', 'post_status' => 'any'));
				wp_redirect("/edit-listing/" . $listings[0]->ID);
			}

		} else {
			$error_msg = "Email or password not recognized.";
		}

	} 

}

if(is_user_logged_in()) {
	global $current_user;
	get_currentuserinfo();
	$listings = get_posts(array('author' => $current_user->ID, 'post_type' => 'listing', 'post_status' => 'any'));
	$listing = reset($listings);
}

get_header();

?>
<div id="main-content" class="main-content">
	<div id="primary" class="content-area">
		<div id="content" class="site-content" role="main">
			<header class="entry-header"><h1 class="entry-title">Authentication</h1></header>
			<div class="entry-content">
				<?php if(isset($error_msg)): ?>
					<div class="message-response error"><?php echo $error_msg ?></div>
				<?php endif; ?>
				<?php if(is_user_logged_in()): ?>
					<p>
						Your are already logged in.
					</p>
					<p>

						<a href="/edit-listing/<?php echo $listing->ID ?>">Edit Your Listing</a> or <a href="?logout=1">Log Out</a>
					</p>
				<?php else: ?>
				<form name="listingform" id="listingform" method="post">
					<p>
						<label for="email">E-mail Address<br />
						<input type="text" name="log" id="email" class="input" size="40" /></label>
					</p>
					<p>
						<label for="email">Password<br />
						<input type="password" name="pwd" id="password" class="input" size="40" /></label>
					</p>
					<br class="clear" />
					<p class="submit"><input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="Login" /></p>
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

<SCRIPT src="<?php echo get_template_directory_uri() ?>/js/jsbn.js"></SCRIPT><!--needed for rsa math operations-->
<SCRIPT src="<?php echo get_template_directory_uri() ?>/js/prng4.js"></SCRIPT><!--needed for rsa key generation-->
<SCRIPT src="<?php echo get_template_directory_uri() ?>/js/rng.js"></SCRIPT><!--needed for rsa key generation-->
<SCRIPT src="<?php echo get_template_directory_uri() ?>/js/rsa.js"></SCRIPT><!--needed for rsa en-/decryption-->
<SCRIPT src="<?php echo get_template_directory_uri() ?>/js/sha1.js"></SCRIPT>
<SCRIPT src="<?php echo plugins_url() ?>/tsz_listings/js/jquery.cookie.js"></SCRIPT>
<script type="text/javascript">

	jQuery("#listingform").submit(function( event ) {
		data = cryptEmail(event, "email", '<?php echo to_hex($details['rsa']['n']) ?>', '<?php echo to_hex($details['rsa']['e']) ?>');
		if(data)
			jQuery.cookie('email', data, { path: '/' });

	});

</script>

<?php
get_sidebar();
get_footer();
