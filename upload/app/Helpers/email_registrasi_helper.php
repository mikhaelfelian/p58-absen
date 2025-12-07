<?php
function email_head() {
	
	return '
	<link rel="preconnect" href="https://fonts.gstatic.com">
	<link href="https://fonts.googleapis.com/css2?family=Nunito+Sans" rel="stylesheet">
	<style>
body{
	font-family: "Nunito Sans", "open sans", "segoe ui", arial;
	font-size: 16px;
}
h1 {
	font-size: 22px;
}
.logo-container {
	text-align:center;
}
.logo {
	display: inline-block;
	margin: auto;
}
.wrapper {
	max-width: 650px;
	margin: auto;
	margin-top: 20px;
}
.button {
	text-decoration:none;
	display:inline-block;
	margin-bottom:0;
	font-weight:normal;
	text-align:center;
	vertical-align:middle;
	background-image:none;
	border:1px solid transparent;
	white-space:nowrap;
	padding:7px 15px;
	line-height:1.5384616;
	background-color:#0277bd;
	border-color:#0277bd;
	color:#FFFFFF;
}
.button span {
	font-family:arial,helvetica,sans-serif;
	font-size: 16px;
	color:#FFFFFF;
}
p {
	font-size: 16px;
}
.alert {
    display: inline-block;
    margin-bottom: 0;
    font-weight: normal;
    text-align: left;
    vertical-align: middle;
    background-image: none;
    border: 1px solid transparent;
    padding: 7px 15px;
    line-height: 1.5384616;
    background-color: #ffb4b4;
    border-color: #ff9c9c;
	color: #c34949;
	font-size: 16px;
}
</style>
';
}

function email_resendlink_content() {

$config = new \Config\EmailConfig;

	return '
<html>
<head>'. email_head() .'</head>
<body>
<div class="wrapper">
	<div class="logo-container">
		<img class="logo" alt="logo" src="cid:logo_text"/>
	</div>
	<h1>Link Aktivasi Akun</h1>
	<p>
	Hi {{NAME}},  kami mengirim email ini karena kami mendapat permintaan kirim ulang link aktivasi akun, silakan klik tombol berikut untuk mengaktifkan akun Anda:
	</p>
	<p>
		<a class="button" href="{{url}}" target="_blank" >
		<span style="">Aktifkan Akun Saya</span></a>
	</p>
	<p>
	Jika tombol tersebut tidak berfungsi, silakan copy paste link berikut ini ke browser Anda:<br/><a href="{{url}}" target="_blank" >{{url}}</a></p>
	<p>
	Jika Anda merasa tidak melakukan permintaan ini, mohon abaikan email ini.
	</p>
	<p>Jika ada pertanyaan mengenai email ini, silakan kontak:<br/>
	<a href="mailto:'.$config->emailSupport.'" target="_blank">'.$config->emailSupport.'</a></p>
	<p>Regards,<br/>Berkah Mitra Abadi Team</p>
</div>
</body>
</html>
';
}

function email_registration_content() {

$config = new \Config\EmailConfig;
	
	return '
<html>
<head>'. email_head() .'</head>
<body>
<div class="wrapper">
	<div class="logo-container">
		<img class="logo" alt="logo" src="cid:logo_text"/>
	</div>
	<h1>Link Aktivasi Akun</h1>
	<p>Hi, {{NAME}}, Anda baru saja mendaftar di aplikasi PHP Admin Template Berkah Mitra Abadi. Untuk menyelesaikan proses pendaftaran, konfirmasi alamat email Anda dengan mengklik tombol berikut ini:</p>
	<p>
		<a class="button" href="{{url}}" target="_blank" >
		<span>Ya, konfirmasi alamat email saya</span></a>
	</p>
	<p>
	Jika tombol tersebut tidak berfungsi, silakan copy dan paste link berikut ini ke browser anda<br/>
	<a href="{{url}}" target="_blank">{{url}}</a>
	</p>
	<p>
	Jika Anda tidak merasa melakukan pendaftaran, mohon abaikan email ini.</p>
	<p>Jika ada pertanyaan lebih lanjut mengenai email ini, silakan kontak kami di:<br/>
	<a href="mailto:'.$config->emailSupport.'" target="_blank">'.$config->emailSupport.'</a></p>
	<p>Regards,<br/>Berkah Mitra Abadi Team</p>
</div>
</body>
</html>
';
}

function email_recovery_content() 
{
	$config = new \Config\EmailConfig;
	
	return '
<html>
<head>'. email_head() .'</head>
<body>
<div class="wrapper">
	<div class="logo-container">
		<img class="logo" alt="logo" src="cid:logo_text"/>
	</div>
	<h1>Reset Password</h1>
	<p>
	Hi, kami mengirim email ini karena kami mendapat permintaan reset password, silakan klik tombol berikut untuk membuat password baru:
	</p>
	<p>
		<a class="button" href="{{url}}" target="_blank" >
		<span style="">Reset Password Saya</span></a>
	</p>
	<p>
	Jika tombol tersebut tidak berfungsi, silakan copy paste link berikut ini ke browser Anda:<br/><a href="{{url}}" target="_blank" >{{url}}</a></p>
	<p>
	Jika Anda merasa tidak melakukan permintaan ini, mohon abaikan email ini.
	</p>
	<p>Jika ada pertanyaan mengenai email ini, silakan kontak:<br/>
	<a href="mailto:'.$config->emailSupport.'" target="_blank">'.$config->emailSupport.'</a></p>
	<p>Regards,<br/>Berkah Mitra Abadi Team</p>
</div>
</body>
</html>';
}

function email_activity_report_content($activity_data, $company_name, $user_name, $patrol_info = null) {
	$config = new \Config\EmailConfig;
	$appConfig = new \Config\App;

	// Get identity name
	$identitas_nama = 'PT. Berkah Mitra Abadi';
	try {
		$db = \Config\Database::connect();
		$row = $db->query("SELECT nama FROM identitas LIMIT 1")->getRowArray();
		if ($row && !empty($row['nama'])) {
			$identitas_nama = $row['nama'];
		}
	} catch (\Exception $e) {}

	// Logo handling, fallback to example URL if not found
	$defaultBaseUrl = isset($appConfig->baseURL) ? rtrim($appConfig->baseURL, '/') : ((isset($_SERVER['REQUEST_SCHEME']) && isset($_SERVER['HTTP_HOST'])) ? $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] : 'http://localhost');	
	$logo_path = $defaultBaseUrl . '/public/images/Absen_7.png';
	try {
		$db = \Config\Database::connect();
		$q = $db->query("SELECT value FROM setting WHERE type='app' AND param='logo_app' LIMIT 1");
		$res = $q->getRowArray();
		if ($res && !empty($res['value'])) {
			$logo_path = $defaultBaseUrl . '/public/images/' . $res['value'];
		}
	} catch (\Exception $e) {}

	// Get fields and sanitize
	$tanggal = isset($activity_data['tanggal']) ? $activity_data['tanggal'] : date('Y-m-d');
	$waktu   = isset($activity_data['waktu']) ? $activity_data['waktu'] : date('H:i:s');
	$judul   = isset($activity_data['judul_activity']) ? htmlspecialchars($activity_data['judul_activity']) : '-';
	$deskripsi = isset($activity_data['deskripsi_activity']) ? nl2br(htmlspecialchars($activity_data['deskripsi_activity'])) : '-';

	// Show patrol info if available
	$patrol_rows = '';
	if ($patrol_info && !empty($patrol_info)) {
		$pname   = isset($patrol_info->nama_patrol) ? htmlspecialchars($patrol_info->nama_patrol) : '';
		$pcode   = isset($patrol_info->barcode_scanned) ? htmlspecialchars($patrol_info->barcode_scanned) : '';
		$pscan   = isset($patrol_info->scan_time) ? htmlspecialchars($patrol_info->scan_time) : '';

		if ($pname) $patrol_rows .= '<tr><td style="padding:10px 14px; width:150px; font-weight:600; color:#1a73e8;">Titik Patroli</td><td style="padding:10px 14px;">'.$pname.'</td></tr>';
		if ($pcode) $patrol_rows .= '<tr><td style="padding:10px 14px; width:150px; font-weight:600; color:#1a73e8;">Barcode</td><td style="padding:10px 14px;">'.$pcode.'</td></tr>';
		if ($pscan) $patrol_rows .= '<tr><td style="padding:10px 14px; width:150px; font-weight:600; color:#1a73e8;">Waktu Scan</td><td style="padding:10px 14px;">'.$pscan.'</td></tr>';
	}

	// GPS info
	$gps_has = !empty($activity_data['latitude']) && !empty($activity_data['longitude']);
	$lat = $gps_has ? htmlspecialchars($activity_data['latitude']) : '';
	$lon = $gps_has ? htmlspecialchars($activity_data['longitude']) : '';
	$gps_url = $gps_has ? 'https://www.google.com/maps?q=' . $lat . ',' . $lon : '#';
	$gps_content = $gps_has ? "$lat, $lon" : "-";

	// Format support contact
	$email_support = !empty($config->emailSupport) ? $config->emailSupport : 'support@mail.com';

	return '<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Laporan Aktivitas</title>
<style>
    body {
        margin: 0;
        padding: 0;
        background: #f4f6f8;
        font-family: Arial, Helvetica, sans-serif;
        color: #333;
    }
    .container {
        max-width: 600px;
        margin: 30px auto;
        background: #ffffff;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 18px rgba(0,0,0,0.08);
        border: 1px solid #e6edf3;
    }
    .header {
        padding: 25px 30px;
        background: #ffffff;
        border-bottom: 1px solid #eef2f6;
    }
    .logo {
        max-height: 55px;
    }
    h1 {
        margin: 0;
        padding: 0;
        font-size: 24px;
        font-weight: 700;
        color: #1a73e8;
    }
    .content {
        padding: 25px 30px;
        font-size: 15px;
        line-height: 1.6;
        color: #444;
    }
    .label {
        font-weight: 700;
        color: #1a73e8;
        margin: 20px 0 8px 0;
        font-size: 14px;
        letter-spacing: 0.03em;
    }
    .footer {
        text-align: center;
        padding: 28px 30px;
        font-size: 13px;
        color: #888;
        border-top: 1px solid #eef2f6;
    }
    a.btn {
        background:#1a73e8;
        color:#fff !important;
        padding:8px 18px;
        font-size:13px;
        font-weight:600;
        border-radius:5px;
        text-decoration:none;
        display:inline-block;
    }
    table.detail-activity, table.detail-gps, table.detail-foto, table.detail-patroli {
        width:100%; background:#f7fbff; border:1px solid #e4eaf1; border-radius:8px; border-collapse:separate; margin-bottom:14px;
    }
    table.detail-activity td,
    table.detail-gps td,
    table.detail-foto td,
    table.detail-patroli td {
        padding:10px 14px;
        font-size:14px;
    }
</style>
</head>
<body>
<div class="container">
    <!-- Header -->
    <div class="header">
        <img src="'.htmlspecialchars($logo_path).'" class="logo" alt="Logo Perusahaan">
    </div>
    <!-- Body -->
    <div class="content">
        <h1>Laporan Aktivitas</h1>
        <p>
            Yth. Bapak/Ibu,<br><br>
            Bersama ini kami sampaikan ringkasan aktivitas yang telah tercatat pada sistem operasional perusahaan. 
            Informasi berikut disusun secara otomatis untuk memastikan proses pemantauan kegiatan berjalan dengan baik 
            serta mendukung kelancaran operasional di lingkungan kerja.
        </p>
        <!-- DETAIL AKTIVITAS -->
        <div class="label">Detail Aktivitas</div>
        <table class="detail-activity">
            <tr>
                <td style="width:150px; font-weight:600; color:#1a73e8;">Judul</td>
                <td>' . $judul . '</td>
            </tr>
            <tr>
                <td style="font-weight:600; color:#1a73e8;">Deskripsi</td>
                <td>' . $deskripsi . '</td>
            </tr>
            <tr>
                <td style="font-weight:600; color:#1a73e8;">Tanggal</td>
                <td>' . htmlspecialchars($tanggal) . ' ' . htmlspecialchars($waktu) . '</td>
            </tr>
            <tr>
                <td style="font-weight:600; color:#1a73e8;">Perusahaan</td>
                <td>' . htmlspecialchars($company_name) . '</td>
            </tr>
            <tr>
                <td style="font-weight:600; color:#1a73e8;">Karyawan</td>
                <td>' . htmlspecialchars($user_name) . '</td>
            </tr>' 
            . ($patrol_rows != '' ? $patrol_rows : '') .
        '</table>'

        // GPS Section
        .($gps_has ? ('<div class="label">Lokasi GPS</div>
        <table class="detail-gps">
            <tr>
                <td style="width:150px; font-weight:600; color:#1a73e8;">Koordinat</td>
                <td>' . $gps_content . '<br><br><a href="' . htmlspecialchars($gps_url) . '" class="btn" target="_blank">Lihat di Google Maps</a></td>
            </tr>
        </table>') : '')

        // Foto Section
        . '<div class="label">Dokumentasi Foto</div>
        <table class="detail-foto">
            <tr>
                <td>
                    Dokumentasi foto terkait aktivitas tersebut telah disertakan sebagai lampiran pada email ini 
                    untuk keperluan verifikasi serta kebutuhan arsip.
                </td>
            </tr>
        </table>'

        // Contact info
        .'<p style="margin-top:20px;">
            Apabila Bapak/Ibu memerlukan informasi tambahan terkait laporan ini, 
            silakan menghubungi kami melalui alamat email berikut:<br>
            <strong><a href="mailto:'.htmlspecialchars($email_support).'" style="color:#1a73e8;">'.htmlspecialchars($email_support).'</a></strong>
        </p>
    </div>
    <!-- Footer -->
    <div class="footer">
        Hormat kami,<br><strong>' . htmlspecialchars($identitas_nama) . '</strong>
        </div>
    </div>
</body>
</html>';
}

function email_patrol_recap_content($presensi_data, $activities_list, $company_name, $user_name) {
	$config = new \Config\EmailConfig;
	$appConfig = new \Config\App;

	// Get identity name
	$identitas_nama = 'PT. Berkah Mitra Abadi';
	try {
		$db = \Config\Database::connect();
		$row = $db->query("SELECT nama FROM identitas LIMIT 1")->getRowArray();
		if ($row && !empty($row['nama'])) {
			$identitas_nama = $row['nama'];
		}
	} catch (\Exception $e) {}

	// Logo handling, fallback to example URL if not found
	$defaultBaseUrl = isset($appConfig->baseURL) ? rtrim($appConfig->baseURL, '/') : ((isset($_SERVER['REQUEST_SCHEME']) && isset($_SERVER['HTTP_HOST'])) ? $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] : 'http://localhost');	
	$logo_path = $defaultBaseUrl . '/public/images/Absen_7.png';
	try {
		$db = \Config\Database::connect();
		$q = $db->query("SELECT value FROM setting WHERE type='app' AND param='logo_app' LIMIT 1");
		$res = $q->getRowArray();
		if ($res && !empty($res['value'])) {
			$logo_path = $defaultBaseUrl . '/public/images/' . $res['value'];
		}
	} catch (\Exception $e) {}

	// Format dates for header
	$tgl_masuk = isset($presensi_data['tgl_masuk']) ? $presensi_data['tgl_masuk'] : '';
	$tgl_keluar = isset($presensi_data['tgl_keluar']) ? $presensi_data['tgl_keluar'] : '';
	
	$start_date_formatted = '';
	$end_date_formatted = '';
	if ($tgl_masuk) {
		$start_date_formatted = date('d/m/Y', strtotime($tgl_masuk));
	}
	if ($tgl_keluar) {
		$end_date_formatted = date('d/m/Y', strtotime($tgl_keluar));
	}
	
	$period_text = $user_name;
	if ($start_date_formatted && $end_date_formatted) {
		$period_text .= ' - ' . $start_date_formatted . ' - ' . $end_date_formatted;
	} elseif ($start_date_formatted) {
		$period_text .= ' - ' . $start_date_formatted;
	}

	// Build table rows
	$table_rows = '';
	$row_number = 1;
	
	if (!empty($activities_list) && is_array($activities_list)) {
		foreach ($activities_list as $activity) {
			// Activity title and description
			$judul = isset($activity['judul_activity']) ? htmlspecialchars($activity['judul_activity']) : '-';
			$deskripsi = isset($activity['deskripsi_activity']) ? htmlspecialchars($activity['deskripsi_activity']) : '';
			$aktifitas_text = $judul;
			if (!empty($deskripsi)) {
				$aktifitas_text .= ' - ' . $deskripsi;
			}
			
			// Format date and time: DD/MM/YYYY HH:MM
			$tanggal = isset($activity['tanggal']) ? $activity['tanggal'] : '';
			$waktu = isset($activity['waktu']) ? $activity['waktu'] : '';
			$tanggal_formatted = '-';
			if ($tanggal && $waktu) {
				$datetime = $tanggal . ' ' . $waktu;
				$timestamp = strtotime($datetime);
				if ($timestamp !== false) {
					$tanggal_formatted = date('d/m/Y H:i', $timestamp);
				}
			}
			
			// GPS coordinates button
			$koordinat_button = '-';
			$latitude = isset($activity['latitude']) ? $activity['latitude'] : null;
			$longitude = isset($activity['longitude']) ? $activity['longitude'] : null;
			if ($latitude && $longitude) {
				$gps_url = 'https://www.google.com/maps?q=' . htmlspecialchars($latitude) . ',' . htmlspecialchars($longitude);
				$koordinat_button = '<a href="' . htmlspecialchars($gps_url) . '" target="_blank" style="color:#1a73e8; text-decoration:underline;">Klik disini</a>';
			}
			
			$table_rows .= '<tr>';
			$table_rows .= '<td style="padding:10px 14px; border:1px solid #000; text-align:center;">' . $row_number . '</td>';
			$table_rows .= '<td style="padding:10px 14px; border:1px solid #000;">' . $aktifitas_text . '</td>';
			$table_rows .= '<td style="padding:10px 14px; border:1px solid #000;">' . $tanggal_formatted . '</td>';
			$table_rows .= '<td style="padding:10px 14px; border:1px solid #000;">' . $koordinat_button . '</td>';
			$table_rows .= '</tr>';
			
			$row_number++;
		}
	}
	
	// If no activities, show empty row
	if (empty($table_rows)) {
		$table_rows = '<tr><td colspan="4" style="padding:10px 14px; border:1px solid #000; text-align:center;">Tidak ada aktivitas patroli</td></tr>';
	}

	// Format support contact
	$email_support = !empty($config->emailSupport) ? $config->emailSupport : 'support@mail.com';

	return '<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Rekap Aktifitas Patroli</title>
<style>
    body {
        margin: 0;
        padding: 0;
        background: #f4f6f8;
        font-family: Arial, Helvetica, sans-serif;
        color: #333;
    }
    .container {
        max-width: 600px;
        margin: 30px auto;
        background: #ffffff;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 18px rgba(0,0,0,0.08);
        border: 1px solid #e6edf3;
    }
    .header {
        padding: 25px 30px;
        background: #ffffff;
        border-bottom: 1px solid #eef2f6;
    }
    .logo {
        max-height: 55px;
    }
    h1 {
        margin: 0;
        padding: 0;
        font-size: 24px;
        font-weight: 700;
        color: #1a73e8;
        text-align: center;
    }
    .sub-header {
        text-align: left;
        margin-top: 10px;
        font-size: 16px;
        color: #666;
    }
    .content {
        padding: 25px 30px;
        font-size: 15px;
        line-height: 1.6;
        color: #444;
    }
    .footer {
        text-align: center;
        padding: 28px 30px;
        font-size: 13px;
        color: #888;
        border-top: 1px solid #eef2f6;
    }
    table.patrol-recap {
        width: 100%;
        border-collapse: collapse;
        border: 1px solid #000;
        margin: 20px 0;
    }
    table.patrol-recap th {
        padding: 10px 14px;
        border: 1px solid #000;
        background-color: #f0f0f0;
        font-weight: 700;
        text-align: left;
    }
    table.patrol-recap td {
        padding: 10px 14px;
        border: 1px solid #000;
        font-size: 14px;
    }
</style>
</head>
<body>
<div class="container">
    <!-- Header -->
    <div class="header">
        <img src="'.htmlspecialchars($logo_path).'" class="logo" alt="Logo Perusahaan">
        <h1>Rekap Aktifitas Patroli</h1>
        <div class="sub-header">' . htmlspecialchars($period_text) . '</div>
    </div>
    <!-- Body -->
    <div class="content">
        <table class="patrol-recap">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Aktifitas</th>
                    <th>Tanggal</th>
                    <th>Koordinat</th>
                </tr>
            </thead>
            <tbody>
                ' . $table_rows . '
            </tbody>
        </table>
        <p style="margin-top:20px;">
            Apabila Bapak/Ibu memerlukan informasi tambahan terkait laporan ini, 
            silakan menghubungi kami melalui alamat email berikut:<br>
            <strong><a href="mailto:'.htmlspecialchars($email_support).'" style="color:#1a73e8;">'.htmlspecialchars($email_support).'</a></strong>
        </p>
    </div>
    <!-- Footer -->
    <div class="footer">
        Hormat kami,<br><strong>' . htmlspecialchars($identitas_nama) . '</strong>
    </div>
</div>
</body>
</html>';
}