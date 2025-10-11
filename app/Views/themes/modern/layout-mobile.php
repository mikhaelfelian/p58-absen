<!DOCTYPE HTML>
<html lang="en">
<head>
<title>PRESENSI</title>
<meta name="descrition" content="Presensi"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="mobile-web-app-capable" content="yes" />
<link rel="manifest" href="manifest.json"/>
<link rel="shortcut icon" href="<?=$config->baseURL . 'public/images/favicon.png?r='.time()?>" />

<link rel="stylesheet" type="text/css" href="<?=$config->baseURL . 'public/vendors/fontawesome/css/all.css'?>"/>
<link rel="stylesheet" type="text/css" href="<?=$config->baseURL . 'public/vendors/bootstrap/css/bootstrap.min.css?r='.time()?>"/>
<link rel="stylesheet" type="text/css" href="<?=$config->baseURL . 'public/vendors/bootswatch/cosmo/bootstrap.min.css?r='.time()?>"/>
<link rel="stylesheet" type="text/css" href="<?=$config->baseURL . 'public/vendors/bootstrap-icons/bootstrap-icons.css?r='.time()?>"/>
<link rel="stylesheet" type="text/css" href="<?=$config->baseURL . 'public/vendors/sweetalert2/sweetalert2.min.css?r='.time()?>"/>
<link rel="stylesheet" type="text/css" href="<?=$config->baseURL . 'public/vendors/flatpickr/dist/flatpickr.min.css?r='.time()?>"/>
<link rel="stylesheet" type="text/css" href="<?=$config->baseURL . 'public/themes/modern/builtin/css/placeholder.css?r='.time()?>"/>
<link rel="stylesheet" type="text/css" href="<?=$config->baseURL . 'public/vendors/overlayscrollbars/jquery.overlayScrollbars.min.css?r='.time()?>"/>
<link rel="stylesheet" type="text/css" href="<?=$config->baseURL . 'public/vendors/pace/pace-theme-default.css?r='.time()?>"/>
<link rel="stylesheet" type="text/css" href="<?=$config->baseURL . 'public/themes/modern/builtin/css/layout-mobile.css?r='.time()?>"/>
<link rel="stylesheet" type="text/css" href="<?=$config->baseURL . 'public/themes/modern/builtin/css/layout-mobile-panel.css?r='.time()?>"/>

<!-- Data Tables -->
<link rel="stylesheet" type="text/css" href="<?=$config->baseURL . 'public/vendors/datatables/dist/css/dataTables.bootstrap5.min.css?r='.time()?>"/>
<!-- // Data Tables -->

<link rel="stylesheet" id="style-switch" type="text/css" href="<?=$config->baseURL . 'public/themes/modern/builtin/css/color-schemes/'.$app_layout['color_scheme'].'.css?r='.time()?>"/>
<link rel="stylesheet" id="style-switch-sidebar" type="text/css" href="<?=$config->baseURL . 'public/themes/modern/builtin/css/color-schemes/'.$app_layout['sidebar_color'].'-sidebar.css?r='.time()?>"/>
<link rel="stylesheet" id="font-switch" type="text/css" href="<?=$config->baseURL . 'public/themes/modern/builtin/css/fonts/'.$app_layout['font_family'].'.css?r='.time()?>"/>
<link rel="stylesheet" id="font-size-switch" type="text/css" href="<?=$config->baseURL . 'public/themes/modern/builtin/css/fonts/font-size-'.$app_layout['font_size'].'.css?r='.time()?>"/>
<link rel="stylesheet" id="logo-background-color-switch" type="text/css" href="<?=$config->baseURL . 'public/themes/modern/builtin/css/color-schemes/'.$app_layout['logo_background_color'].'-logo-background.css?r='.time()?>"/>

<?php
if (@$styles) {
	foreach($styles as $file) {
		if (is_array($file)) {
			if (strpos($file['file'], 'flatpickr') !== false) {
				continue;
			}
			if ($file['print']) {
				echo '<style type="text/css">' . $file['css'] . '</style>';
			} else {
				echo '<link rel="stylesheet" data-type="dynamic-resource-head" type="text/css" href="'.$file['file'].'?r='.time().'"/>' . "\n";
			}
		} else {
			if (strpos($file, 'flatpickr') !== false) {
				continue;
			}
			echo '<link rel="stylesheet" data-type="dynamic-resource-head" type="text/css" href="'.$file.'?r='.time().'"/>' . "\n";
		}
	}
}
?>
</head>
<body class="bg-primary">
	<div class="page-container" id="page-container">
		<header>
		<nav class="navbar py-2 px-0" style="height:55px">
			<div style="width:100%" class="d-flex justify-content-between align-items-center">
				<a id="btn-menu-mobile" class="nav-link nav-menu-mobile px-4 fs-5" data-bs-toggle="offcanvas" href="#offcanvasExample" role="button" aria-controls="offcanvasExample"><i class="fa fa-bars"></i></a>
				<div class="nav-right-panel me-4">
					<div role="button" id="user-menu-nav-header">
						<img src="<?=base_url() . 'public/images/default_avatar.png'?>" role="button" style="width:32px;height:32px;border-radius:50%"/>
						<span id="user-detail" style="display:none"><?=json_encode($user)?></span>
					</div>
				</div>
			</div>
			
		</nav>
	</header>
		<div id="page-content">
		<?php
		$this->renderSection('content');
		?>
		</div>
	</div> <!-- Page Container -->
	<?php
	$nama_module = $_SESSION['web']['nama_module'];
	$active_home = $nama_module == 'presensi-home' ? 'active' : '';
	$active_riwayat = $nama_module == 'presensi-riwayat' ? 'active' : '';
	$uri = service('uri');
	$home_active = strpos($uri->getSegment(1), 'mobile-presensi-home') !== false ? 'active' : '';
	$riwayat_active = strpos($uri->getSegment(1), 'mobile-presensi-riwayat') !== false ? 'active' : '';
	?>
	<nav class="navbar bg-light navbar-footer navbar-expand fixed-bottom shadow">
		<ul class="navbar-nav nav-justified w-100">
			<li class="nav-item bg-light">
				<a href="<?=base_url()?>mobile-presensi-home" class="nav-link <?=$active_home?> link-spa d-flex align-items-center flex-column nav-footer-home <?=$home_active?>" style="padding:10px" data-placeholder="presensi-home">
					<i class="bi bi-houses"></i>
					<span style="font-size:10px">Home</span>
				</a>
			</li>
			<li class="nav-item bg-light">
				<div class="nav-item-center-container">
					<a href="#" id="btn-presensi" class="bg-info">
						<i class="bi bi-plus-circle"></i>
					</a>
				</div>
			</li>
			<li class="nav-item bg-light">
				<a href="<?=base_url()?>mobile-presensi-riwayat" class="nav-link <?=$active_riwayat?> link-spa d-flex align-items-center flex-column <?=$riwayat_active?>" data-placeholder="presensi-riwayat" style="padding:10px">
					<i class="bi bi-clock-history"></i>
					<span style="font-size:10px">Riwayat</span>
				</a>
			</li>
		</ul>
	 </nav>
		
	<div class="sidebar-mobile offcanvas offcanvas-start" tabindex="-1" id="offcanvasExample" style="width:280px" aria-labelledby="offcanvasExampleLabel">
		<div class="offcanvas-header">
			<h5 class="offcanvas-title" id="offcanvasExampleLabel"> <img src="<?=base_url() . '/public/images/' . $setting_aplikasi['logo_login']?>"/></h5>
			<button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
		</div>
		<div class="offcanvas-body sidebar-body">
			<div class="img-profile">
				<?php
				$file = $user['avatar'];
				
				if ($user['avatar']) {
					$path = ROOTPATH . '/public/images/user/' . $file;
					if (!file_exists($path)) {
						$file = 'default.png';
					}
					
				} else {
					$file = 'default.png';
				}
				?>
				<div class="avatar-profile">
					<img class="rounded-circle" src="<?=base_url() . '/public/images/user/' . $file?>"/>
				</div>
				<div id="profil-user-sidebar">
					<p class="mb-0 mt-3"><?=$user['nama']?></p>
					<p class="mb-0"><?=$data_setelah_nama_user?></p>
				</div>
			</div>
			<nav class="mt-3">
				<ul class="nav nav-pills flex-column">
					<?php
					if (key_exists(46, $_SESSION['user']['all_permission'])) {
						?>
						<li class="nav-item">
							<a class="nav-link link-dark py-3 px-3 link-dashboard" href="<?=base_url() . '/dashboard'?>">
								<i class="fas fa-tachometer-alt me-2"></i>Dashboard
							</a>
						</li>
					<?php
					}
					?>
					<li class="nav-item">
						<a class="nav-link link-dark py-3 px-3" href="<?=base_url() . 'presensi-rekap'?>">
							<i class="fas fa-list-check me-2"></i>Rekap Presensi
						</a>
					</li>
					<li class="nav-item">
						<a class="nav-link link-dark py-3 px-3" href="<?=base_url() . 'presensi-riwayat'?>">
							<i class="far fa-calendar me-2"></i>Riwayat Presensi
						</a>
					</li>
				</ul>
			</nav>
		</div>
	</div>


<script type="text/javascript">
	let base_url = "<?=$config->baseURL?>";
	let module_url = "<?=$module_url?>";
	let current_url = "<?=current_url()?>";
	let theme_url = "<?=$config->baseURL . '/public/themes/modern/builtin/'?>";
	<?php
	if (!empty($setting_kasir)) {
		echo 'let setting_kasir = ' . json_encode($setting_kasir);
	}
	?>
</script>
<?=app_auth();?>
<script type="text/javascript" src="<?=$config->baseURL . 'public/vendors/jquery/jquery.min.js'?>"></script>
<script type="text/javascript" src="<?=$config->baseURL . 'public/vendors/bootstrap/js/bootstrap.bundle.min.js'?>"></script>
<script type="text/javascript" src="<?=$config->baseURL . 'public/vendors/jquery.select2/js/select2.full.min.js'?>"></script>
<script type="text/javascript" src="<?=$config->baseURL . 'public/vendors/bootbox/bootbox.min.js'?>"></script>
<script type="text/javascript" src="<?=$config->baseURL . 'public/vendors/sweetalert2/sweetalert2.min.js'?>"></script>
<script type="text/javascript" src="<?=$config->baseURL . 'public/themes/modern/builtin/js/functions.js?r='.time()?>"></script>
<script type="text/javascript" src="<?=$config->baseURL . 'public/vendors/overlayscrollbars/jquery.overlayScrollbars.min.js'?>"></script>
<script type="text/javascript" src="<?=$config->baseURL . 'public/vendors/pace/pace.min.js?r='.time()?>"></script>
<script type="text/javascript" src="<?=$config->baseURL . 'public/vendors/flatpickr/dist/flatpickr.js?r='.time()?>"></script>
<script type="text/javascript" src="<?=$config->baseURL . 'public/vendors/webcamjs/webcam.js?r='.time()?>"></script>
<script type="text/javascript" src="<?=$config->baseURL . 'public/vendors/moment/moment.min.js?r='.time()?>"></script>
<script type="text/javascript" src="<?=$config->baseURL . 'public/vendors/flatpickr/dist/l10n/id.js?r='.time()?>"></script>
<script type="text/javascript" src="<?=$config->baseURL . 'public/vendors/filesaver/FileSaver.js?r='.time()?>"></script>
<script type="text/javascript" src="<?=$config->baseURL . 'public/themes/modern/js/main-mobile.js?r='.time()?>"></script>

<!-- Data Tables -->
<script type="text/javascript" src="<?=$config->baseURL . 'public/vendors/datatables/dist/js/jquery.dataTables.min.js?r='.time()?>"></script>
<script type="text/javascript" src="<?=$config->baseURL . 'public/vendors/datatables/dist/js/dataTables.bootstrap5.min.js?r='.time()?>"></script>
<!-- // Data Tables -->

<?php
if (@$scripts) {
	foreach($scripts as $file) {
		if (is_array($file)) {
			
			if (strpos($file['script'], 'flatpickr') !== false) {
				continue;
			}
			
			$attr = '';
			if (key_exists('attr', $file)) {
				foreach ($file['attr'] as $attr_name => $attr_value) {
					$attr .= $attr_name . '="' . $attr_value . '"';
				}					
			}
				
			if (@$file['print']) {
				echo '<script type="text/javascript" data-type="dynamic-resource-head" ' . $attr . '>' . $file['script'] . '</script>' . "\n";
			} else {
				echo '<script type="text/javascript" data-type="dynamic-resource-head" ' . $attr . ' src="'.$file['script'].'?r='.time().'"></script>' . "\n";
			}
		} else {
			if (strpos($file, 'flatpickr') !== false) {
				continue;
			}
			echo '<script type="text/javascript" data-type="dynamic-resource-head" src="'.$file.'?r='.time().'"></script>' . "\n";
		}
	}
}
?>
<span id="setting-presensi" style="display:none"><?=json_encode($setting_presensi)?></span>
</body>
</html>