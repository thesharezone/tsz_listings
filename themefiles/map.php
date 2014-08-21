		<?php if(get_option( "google_maps_api_key" )): ?>
			<iframe
			  width="470"
			  height="300"
			  frameborder="0" style="border:0"
			  src="https://www.google.com/maps/embed/v1/place?key=<?php echo get_option( "google_maps_api_key" ); ?>&zoom=11&q=<?php echo urlencode($address) ?>">
			</iframe>
		<?php else: ?>
			<b>Location: <?php echo $address ?></b>
		<?php endif; ?>