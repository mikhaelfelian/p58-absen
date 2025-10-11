<!DOCTYPE HTML>
<html lang="en">
<title><?=$site_title?></title>
<meta name="descrition" content="<?=$site_desc?>"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="shortcut icon" href="<?=$config->baseURL . 'public/images/favicon.png?r='.time()?>" />
<link rel="stylesheet" type="text/css" href="<?=$config->baseURL . 'public/vendors/bootstrap/css/bootstrap.min.css?r='.time()?>"/>
<link rel="stylesheet" type="text/css" href="<?=$config->baseURL . 'public/themes/modern/builtin/css/bootstrap-custom.css?r=' . time()?>"/>
<link rel="stylesheet" type="text/css" href="<?=$config->baseURL . 'public/vendors/fontawesome/css/all.css?r='.time()?>"/>
<link rel="stylesheet" type="text/css" href="<?=$config->baseURL . 'public/themes/modern/css/register.css?r='.time()?>"/>
<link rel="stylesheet" type="text/css" href="<?=$config->baseURL . 'public/vendors/pace/pace-theme-default.css?r='.time()?>"/>

<?php
if (@$styles) {
	foreach($styles as $file) {
		echo '<link rel="stylesheet" type="text/css" href="'.$file.'?r='.time().'"/>';
	}
}

?>
<script type="text/javascript" src="<?=$config->baseURL . 'public/vendors/jquery/jquery.min.js?r='.time()?>"></script>
<script type="text/javascript" src="<?=$config->baseURL . 'public/vendors/bootstrap/js/bootstrap.min.js?r='.time()?>"></script>
<script type="text/javascript" src="<?=$config->baseURL . 'public/vendors/bootbox/bootbox.min.js'?>"></script>
<script type="text/javascript" src="<?=$config->baseURL . 'public/vendors/pace/pace.min.js?r='.time()?>"></script>
<script type="text/javascript">
	var base_url = "<?=$config->baseURL?>";
</script>
<?php

if (@$scripts) {
	foreach($scripts as $file) {
		echo '<script type="text/javascript" src="'.$file.'?r='.time().'"/></script>';
	}
}

?>
</html>
<body>
	<div class="background"></div>
	<div class="backdrop"></div>
	<div class="card-container ms-3 me-3" <?=@$style?>>
		<?php
		$this->renderSection('content')
		?>
		<div class="copyright">
			<?php $footer = $setting_aplikasi['footer_login'] ? str_replace( '{{YEAR}}', date('Y'), html_entity_decode($setting_aplikasi['footer_login']) ) : '';
			echo $footer;
			?>
		</div>
	</div><!-- login container -->
</body>
</html>
		