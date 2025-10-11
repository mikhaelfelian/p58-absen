<div class="card">
	<div class="card-header">
		<h5 class="card-title">
		<?php
		if (empty($title)) {
			echo 'Error';
		} else {
			echo $title;
		}		
		?></h5>
	</div>
	
	<div class="card-body">
		<?php
		helper('admin/html');
		if (!empty($message)) {
			if (is_string($message)) {
				$message = ['status' => 'error', 'message' => $message];
			}
			show_message($message);
		}
		?>
	</div>
</div>