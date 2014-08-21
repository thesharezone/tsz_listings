<?php if(isset($error_msg)): ?>
	<div class="message-response error"><?php echo $error_msg ?></div>
<?php endif; ?>
<?php if($success_msg): ?>
	<div class="message-response success"><?php echo $success_msg ?></div>
<?php endif; ?>