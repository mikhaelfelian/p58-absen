	</div><!-- cotent-wrapper -->
	</div><!-- cotent -->
	</div><!-- site-content -->
	<footer class="shadow">
		<div class="footer-copyright">
			<div class="wrapper">
				<?php 
					$footer = str_replace('{{YEAR}}', date('Y'), $setting_aplikasi['footer_app']);
					echo html_entity_decode($footer);
				?>
			</div>
		</div>
	</footer>
<?=app_auth();?>
<script type="text/javascript">
	var base_url = "<?=$config->baseURL?>";
	var module_url = "<?=$module_url?>";
	var current_url = "<?=current_url()?>";
	var theme_url = "<?=$config->baseURL . '/public/themes/modern/builtin/'?>";
	let current_bootswatch_theme = "<?=$app_layout['bootswatch_theme']?>";
</script>
<script type="text/javascript" src="<?=$config->baseURL . 'public/vendors/jquery/jquery.min.js'?>"></script>
<script type="text/javascript" src="<?=$config->baseURL . 'public/vendors/overlayscrollbars/overlayscrollbars.browser.es6.min.js'?>"></script>
<script type="text/javascript" src="<?=$config->baseURL . 'public/vendors/bootstrap/js/bootstrap.bundle.min.js'?>"></script>
<script type="text/javascript" src="<?=$config->baseURL . 'public/vendors/bootbox/bootbox.min.js'?>"></script>
<script type="text/javascript" src="<?=$config->baseURL . 'public/vendors/sweetalert2/sweetalert2.min.js'?>"></script>
<script type="text/javascript" src="<?=$config->baseURL . 'public/vendors/js.cookie/js.cookie.min.js'?>"></script>
<script type="text/javascript" src="<?=$config->baseURL . 'public/themes/modern/builtin/js/functions.js?r='.time()?>"></script>
<script type="text/javascript" src="<?=$config->baseURL . 'public/themes/modern/builtin/js/site.js?r='.time()?>"></script>

<!-- Data Tables -->
<script type="text/javascript" src="<?=$config->baseURL . 'public/vendors/datatables/dist/js/jquery.dataTables.min.js?r='.time()?>"></script>
<script type="text/javascript" src="<?=$config->baseURL . 'public/vendors/datatables/dist/js/dataTables.bootstrap5.min.js?r='.time()?>"></script>
<!-- // Data Tables -->

<!-- Dynamic scripts -->
<?php
if (@$scripts) {
	foreach($scripts as $file) {
		if (is_array($file)) {
			if ($file['print']) {
				echo '<script data-type="dynamic-resource-head" type="text/javascript">' . $file['script'] . '</script>' . "\n";
			}
		} else {
			// Check if file already has query params
			$separator = (strpos($file, '?') === false) ? '?' : '&';
			echo '<script data-type="dynamic-resource-head" type="text/javascript" src="'.$file.$separator.'r='.time().'"></script>' . "\n";
		}
	}
}
?>
</body>
</html>