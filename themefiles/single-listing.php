<?php


get_header(); ?>

	<div id="primary" class="content-area">
		<div id="content" class="site-content" role="main">
			<?php
				// Start the Loop.
				while ( have_posts() ): 
					the_post();

					if ( isset($_POST['email']) ) {
						$image = new Securimage();
					    if ($image->check($_POST['captcha_code']) == true) {
					    	if(!is_email($_POST['email'])) {
					    		$error_msg = "Invalid Email Address.";
					    	} elseif($_POST['message'] == "") {
					    		$error_msg = "Message Is Blank.";
					    	} else {
					    		$poster = get_user_by("id", get_the_author_meta('ID'));

							$decrypted = tsz_decrypt_email($poster->user_email);

							$headers = 'From: TheShare.Zone <' . $_POST['email'] . '>' . "\r\n" .
    								'Reply-To: ' . $_POST['email'] . "\r\n";
								
							$message = stripslashes($_POST['message']). "\r\n\r\n" . $headers;

							if(wp_mail( $decrypted, 'Inquiry about your listing', $message, $headers ))
								$success_msg = "Message Sent!";
							else
								$error_msg = "Your message could not be sent, please try again later.";
					    	}
					    } else {
					      	$error_msg = "Captcha code did not match";
					    }
					}

				 ?>

				<div class="entry-content">

					<div style="width:470px;">
						<?php first_image_tag(get_the_ID(), 'medium') ?>
						<p style="font-weight: bold; margin-top: 10px"><?php echo strip_tags(get_the_title()) ?></p>
					</div>
					<?php $post_id = get_the_ID(); $address = get_post_meta(get_the_ID(), "tsz_listing_address", true); include "map.php"; ?>

					<div style="width:470px; margin-bottom: 40px;">
						<?php echo nl2br(strip_tags(get_the_content())) ?>

						<?php do_action('tsz_single_listing_display', $listing['ID']); ?>
						
						<?php foreach(get_attachments(get_the_ID()) as $attachment): ?>
							<?php echo wp_get_attachment_image( $attachment->ID, "medium" ); ?>
						<?php endforeach; ?>



						<a name="messageForm"></a>
						<form method="post" style="margin-top: 24px">
							<?php if(isset($error_msg)): ?>
								<div class="message-response error"><?php echo $error_msg ?></div>
							<?php endif; ?>
							<?php if(isset($success_msg)): ?>
								<div class="message-response success"><?php echo $success_msg ?></div>
							<?php else: ?>
							<p>
								<label for="description">Message (drag box open for more space)<br />
								<textarea name="message" id="message" class="input"><?php echo stripslashes($_POST['message']) ?></textarea>
							</p>
							<p>
								<label for="email">E-mail Address<br />
								<input type="text" name="email" id="email" class="input" size="55" value="<?php echo $_POST['email'] ?>" /></label>
							</p>
							<p>
								<?php echo Securimage::getCaptchaHtml() ?>
							</p>
							<br class="clear" />
							<p class="submit"><input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="Send" /></p>
							<?php endif; ?>
						</form>
					</div>

				</div><!-- .entry-content -->


			<?php endwhile; ?>
		</div><!-- #content -->
	</div><!-- #primary -->
	<?php if(isset($error_msg) || isset($success_msg)): ?>
	<script type="text/javascript">
		location.hash = "#messageForm";
	</script>
	<?php endif; ?>

<?php
get_sidebar( 'content' );
get_sidebar();
get_footer();
