// Patrol functionality
function initPatrolFunctionality() {
	let qrScanner = null;
	let currentCompanyId = null;
	let companyPatrolMap = (typeof assignedPatrols !== 'undefined' && assignedPatrols) ? assignedPatrols : {};
	let patrolOptions = [];
	let patrolStatusMap = {};
	let selectedPatrolId = null;
	let forcedScannerTargetId = null;
	let currentStep = 1;
	let scannedPatrolData = null;
	let activityCameraStream = null;
	let activityCameraFacingMode = 'environment'; // 'environment' or 'user'
	let activityPhotos = []; // Array to store multiple photos
	let nextPatrolInfo = null;
	
	// Debouncing for QR code scanning (prevent multiple rapid scans)
	let lastScannedCode = null;
	let lastScanTime = 0;
	const QR_SCAN_DEBOUNCE_MS = 2000; // Ignore same QR code for 2 seconds
	const stepperPills = $('#activity-stepper .step-pill');
	const qrInlineWrapper = $('#qr-inline-wrapper');
	const qrModalPlaceholder = $('#qr-modal-placeholder');
	const qrInlineHomeSlot = $('<div id="qr-inline-home-slot"></div>');
	const patrolAccordionCollapse = $('#patrol-progress-collapse');
	const patrolAccordionWrapper = $('#patrol-status-table-wrapper');
	const photoEvidenceCard = $('#photo-evidence-card');
	const floatingPhotoBtn = $('#btn-floating-photo');
	const scrollToPhotoBtn = $('#btn-scroll-to-photo');
	const descriptionField = $('#deskripsi_activity');
	const descriptionCollapse = $('#description-collapse');
	const flashControlWrapper = $('#flash-control-wrapper');
	const cameraStatusText = $('#camera-status-text');
	let flashMode = 'auto';
	let torchSupported = false;
	let cameraVideoTrack = null;
	
	// QR Scanner flash control
	const qrFlashControlWrapper = $('#qr-flash-control-wrapper');
	let qrFlashMode = 'auto';
	let qrTorchSupported = false;
	let qrCameraVideoTrack = null;
	let lastSuccessfulFacingMode = activityCameraFacingMode;
	let accordionCollapsedByCamera = false;
	let wasAccordionOpenBeforeCamera = false;
	let photoHighlightTimeout = null;
	let isQrInModal = false;
	let defaultQrStatusHtml = `
		<div class="alert alert-info mb-0">
			<i class="fas fa-search me-2"></i>
			<strong>Siap untuk memindai QR Patrol</strong><br>
			<small>Arahkan kamera ke QR code. Tekan tombol \"Mulai Scan\" jika kamera belum aktif.</small>
		</div>
	`;
	if (qrInlineWrapper.length) {
		qrInlineWrapper.after(qrInlineHomeSlot);
		$('#qr-scanning-status').html(defaultQrStatusHtml);
	}
	if (!$('#photos-preview-container').children().length) {
		$('#photos-preview-container').hide();
	}
	if (descriptionField.length && descriptionField.val().trim() && descriptionCollapse.length) {
		if (typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
			const descInstance = bootstrap.Collapse.getOrCreateInstance(descriptionCollapse[0], { toggle: false });
			descInstance.show();
		} else {
			descriptionCollapse.addClass('show');
		}
	}
	
	function focusPhotoCard() {
		if (!photoEvidenceCard.length) {
			return;
		}
		scrollIntoViewIfNeeded(photoEvidenceCard);
		photoEvidenceCard.addClass('photo-highlight');
		if (photoHighlightTimeout) {
			clearTimeout(photoHighlightTimeout);
		}
		photoHighlightTimeout = setTimeout(() => {
			photoEvidenceCard.removeClass('photo-highlight');
		}, 1500);
	}
	
	function collapsePatrolProgressForCamera() {
		if (!patrolAccordionWrapper.length || accordionCollapsedByCamera) {
			return;
		}
		wasAccordionOpenBeforeCamera = patrolAccordionCollapse.hasClass('show');
		if (patrolAccordionCollapse.length && typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
			const instance = bootstrap.Collapse.getOrCreateInstance(patrolAccordionCollapse[0], { toggle: false });
			instance.hide();
		} else {
			patrolAccordionCollapse.removeClass('show');
		}
		patrolAccordionWrapper.addClass('camera-hidden');
		accordionCollapsedByCamera = true;
	}
	
	function restorePatrolProgressAfterCamera() {
		if (!accordionCollapsedByCamera || !patrolAccordionWrapper.length) {
			return;
		}
		patrolAccordionWrapper.removeClass('camera-hidden');
		if (wasAccordionOpenBeforeCamera && patrolAccordionCollapse.length) {
			if (typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
				const instance = bootstrap.Collapse.getOrCreateInstance(patrolAccordionCollapse[0], { toggle: false });
				instance.show();
			} else {
				patrolAccordionCollapse.addClass('show');
			}
		}
		accordionCollapsedByCamera = false;
	}
	
	function hideFlashControl(message) {
		if (!flashControlWrapper.length) {
			return;
		}
		flashControlWrapper.hide();
		if (message) {
			$('#flash-support-text').text(message);
		}
	}
	
	function setupFlashControl() {
		if (!flashControlWrapper.length || !cameraVideoTrack) {
			return;
		}
		const capabilities = cameraVideoTrack.getCapabilities ? cameraVideoTrack.getCapabilities() : {};
		torchSupported = !!capabilities.torch;
		if (!torchSupported) {
			hideFlashControl('Lampu tidak tersedia di perangkat ini.');
			return;
		}
		flashControlWrapper.show();
		$('#flash-support-text').text('Sesuaikan lampu saat mengambil foto.');
		updateFlashButtons();
		applyFlashMode(flashMode);
	}
	
	function updateFlashButtons() {
		if (!flashControlWrapper.length) {
			return;
		}
		flashControlWrapper.find('.flash-toggle').removeClass('active');
		flashControlWrapper.find(`.flash-toggle[data-flash-mode="${flashMode}"]`).addClass('active');
	}
	
	function applyFlashMode(mode) {
		if (!cameraVideoTrack || !torchSupported) {
			return;
		}
		if (mode === 'auto') {
			cameraVideoTrack.applyConstraints({ advanced: [{ torch: false }] }).catch(() => {});
			return;
		}
		const torchOn = mode === 'on';
		cameraVideoTrack.applyConstraints({ advanced: [{ torch: torchOn }] }).catch(err => {
			console.warn('Failed to set torch:', err);
			Swal.fire('Info', 'Tidak dapat mengubah lampu di perangkat ini.', 'info');
		});
	}
	
	// QR Scanner Flash Control Functions
	function hideQRFlashControl(message) {
		if (!qrFlashControlWrapper.length) {
			return;
		}
		qrFlashControlWrapper.hide();
		if (message) {
			$('#qr-flash-support-text').text(message);
		}
	}
	
	function setupQRScannerFlashControl() {
		if (!qrFlashControlWrapper.length || !qrCameraVideoTrack) {
			return;
		}
		const capabilities = qrCameraVideoTrack.getCapabilities ? qrCameraVideoTrack.getCapabilities() : {};
		qrTorchSupported = !!capabilities.torch;
		if (!qrTorchSupported) {
			hideQRFlashControl('Lampu tidak tersedia di perangkat ini.');
			return;
		}
		qrFlashControlWrapper.show();
		$('#qr-flash-support-text').text('Sesuaikan lampu saat memindai QR code.');
		updateQRScannerFlashButtons();
		applyQRScannerFlashMode(qrFlashMode);
	}
	
	function updateQRScannerFlashButtons() {
		if (!qrFlashControlWrapper.length) {
			return;
		}
		qrFlashControlWrapper.find('.flash-toggle-qr').removeClass('active');
		qrFlashControlWrapper.find(`.flash-toggle-qr[data-flash-mode="${qrFlashMode}"]`).addClass('active');
	}
	
	function applyQRScannerFlashMode(mode) {
		if (!qrCameraVideoTrack || !qrTorchSupported) {
			return;
		}
		if (mode === 'auto') {
			qrCameraVideoTrack.applyConstraints({ advanced: [{ torch: false }] }).catch(() => {});
			return;
		}
		const torchOn = mode === 'on';
		qrCameraVideoTrack.applyConstraints({ advanced: [{ torch: torchOn }] }).catch(err => {
			console.warn('Failed to set QR scanner torch:', err);
		});
	}
	
	function getQRScannerVideoTrack() {
		if (!qrScanner) {
			return null;
		}
		// Try to get video track from qr-reader element
		const qrReaderElement = document.getElementById('qr-reader');
		if (!qrReaderElement) {
			return null;
		}
		const video = qrReaderElement.querySelector('video');
		if (!video || !video.srcObject) {
			return null;
		}
		const stream = video.srcObject;
		if (!stream || !stream.getVideoTracks || stream.getVideoTracks().length === 0) {
			return null;
		}
		return stream.getVideoTracks()[0];
	}
	
	function storePatrolOptionsForCompany(companyId, patrols) {
		if (!companyId) {
			return;
		}
		companyPatrolMap[companyId] = normalizePatrolArray(patrols);
	}
	
	function setPatrolOptionsForCompany(companyId) {
		if (companyId && companyPatrolMap[companyId]) {
			patrolOptions = normalizePatrolArray(companyPatrolMap[companyId]);
		} else {
			patrolOptions = [];
		}
		updatePatrolDropdown();
		initPatrolStatusMap(patrolOptions);
		renderPatrolStatusTable();
		togglePatrolUi(patrolOptions.length > 0);
		setNextPatrolInfo(getNextPendingPatrol());
	}

	function initPatrolStatusMap(patrols) {
		patrolStatusMap = {};
		if (!patrols || !patrols.length) {
			return;
		}
		patrols.forEach(function(patrol, index) {
			const formatted = formatPatrolForDisplay(patrol);
			if (!formatted || !formatted.id_patrol) {
				return;
			}
			const order = formatted.urutan != null ? formatted.urutan : (index + 1);
			patrolStatusMap[formatted.id_patrol] = {
				nama_patrol: formatted.nama_patrol || ('Patrol #' + (index + 1)),
				urutan: order,
				status: 'pending',
				last_scan: null
			};
		});
	}

	function renderPatrolTabs() {
		const nav = $('#patrol-tabs-nav');
		const placeholder = $('#patrol-tabs-placeholder');
		const detailBody = $('#patrol-tabs-detail-body');
		if (!nav.length) {
			return;
		}
		nav.empty();
		if (!patrolOptions.length) {
			if (placeholder.length) {
				placeholder.show();
			}
			if (detailBody.length) {
				detailBody.hide().empty();
			}
			return;
		}

		patrolOptions.forEach(function(patrol, idx) {
			const button = $('<button type="button" class="btn btn-outline-primary patrol-tab-button me-2 mb-2"></button>');
			button.attr('data-patrol-id', patrol.id_patrol);
			button.text(patrol.nama_patrol || ('Patrol #' + (idx + 1)));
			button.on('click', function() {
				selectPatrolForScan(patrol.id_patrol);
			});
			nav.append(button);
		});

		if (selectedPatrolId && findPatrolById(selectedPatrolId)) {
			selectPatrolForScan(selectedPatrolId);
		} else {
			selectPatrolForScan(patrolOptions[0].id_patrol);
		}
	}

	function selectPatrolForScan(patrolId) {
		if (!patrolId) {
			return;
		}
		selectedPatrolId = patrolId;
		// Tab section removed - just set the selected patrol ID
	}

	function updatePatrolDetailCard(patrolId) {
		const detailBody = $('#patrol-tabs-detail-body');
		const placeholder = $('#patrol-tabs-placeholder');
		if (!detailBody.length) {
			return;
		}
		const patrol = findPatrolById(patrolId);
		if (!patrol) {
			detailBody.hide().empty();
			if (placeholder.length) {
				placeholder.show();
			}
			return;
		}
		const statusObj = patrolStatusMap[patrol.id_patrol] || {};
		const isCompleted = statusObj.status === 'completed';
		const badge = isCompleted ? '<span class="badge bg-success">Selesai</span>' : '<span class="badge bg-secondary">Belum di-scan</span>';
		const lastScanText = statusObj.last_scan ? statusObj.last_scan : '-';
		const order = statusObj.urutan != null ? statusObj.urutan : (patrol.urutan != null ? patrol.urutan : '-');
		detailBody.html(
			'<h6 class="mb-2">' + (patrol.nama_patrol || 'Titik Patroli') + '</h6>' +
			'<p class="mb-1 text-muted">Urutan: ' + order + '</p>' +
			'<p class="mb-1 text-muted">Status: ' + badge + '</p>' +
			'<p class="mb-3 text-muted">Scan terakhir: ' + lastScanText + '</p>' +
			'<button type="button" class="btn btn-primary w-100" id="btn-scan-selected-patrol">' +
				'<i class="fas fa-qrcode me-2"></i>Scan ' + (patrol.nama_patrol || '') +
			'</button>'
		);
		detailBody.show();
		if (placeholder.length) {
			placeholder.hide();
		}
	}

	function renderPatrolStatusTable() {
		const tbody = $('#patrol-status-table tbody');
		if (!tbody.length) {
			return;
		}
		if (!patrolOptions.length) {
			tbody.html('<tr class="text-muted"><td colspan="5" class="text-center py-3">Belum ada data patroli untuk company ini.</td></tr>');
			return;
		}
		let rows = '';
		patrolOptions.forEach(function(patrol, idx) {
			const statusObj = patrolStatusMap[patrol.id_patrol] || {};
			const isCompleted = statusObj.status === 'completed';
			const badge = isCompleted
				? '<span class="badge bg-success">Selesai</span>'
				: '<span class="badge bg-secondary">Belum</span>';
			const lastScanText = statusObj.last_scan ? statusObj.last_scan : '-';
			const order = statusObj.urutan != null
				? statusObj.urutan
				: (patrol.urutan != null ? patrol.urutan : (idx + 1));
			
			// Button styling and state based on completion
			const buttonClass = isCompleted 
				? 'btn btn-sm btn-secondary w-100' 
				: 'btn btn-sm btn-outline-primary w-100 btn-scan-patrol-table';
			const buttonDisabled = isCompleted ? ' disabled' : '';
			const buttonIcon = isCompleted ? 'fas fa-check-circle me-1' : 'fas fa-qrcode me-1';
			const buttonText = isCompleted ? 'Selesai' : 'Scan';
			
			rows += '' +
				'<tr>' +
					'<td class="text-center">' + order + '</td>' +
					'<td>' +
						'<div class="fw-semibold">' + (patrol.nama_patrol || '-') + '</div>' +
						'<small class="text-muted">Barcode: ' + (patrol.barcode || '-') + '</small><br/>' +
						'' + badge + '<br/>' +
						'<small class="text-muted">Scan terakhir: ' + lastScanText + '</small>' +
					'</td>' +
					'<td class="text-center">' +
						'<button type="button" class="' + buttonClass + '" data-patrol-id="' + patrol.id_patrol + '"' + buttonDisabled + '>' +
							'<i class="' + buttonIcon + '"></i>' + buttonText +
						'</button>' +
					'</td>' +
				'</tr>';
		});
		tbody.html(rows);
		updatePatrolProgressBar();
	}

	function updatePatrolProgressBar() {
		const progressBar = $('#patrol-progress-bar');
		const progressPercent = $('#patrol-progress-percent');
		
		if (!progressBar.length || !progressPercent.length) {
			return;
		}
		
		if (!patrolOptions || !patrolOptions.length) {
			progressBar.css('width', '0%').attr('aria-valuenow', 0);
			progressPercent.text('0%');
			return;
		}
		
		const total = patrolOptions.length;
		let completed = 0;
		
		patrolOptions.forEach(function(patrol) {
			const statusObj = patrolStatusMap[patrol.id_patrol];
			if (statusObj && statusObj.status === 'completed') {
				completed++;
			}
		});
		
		const percentage = total > 0 ? Math.round((completed / total) * 100) : 0;
		
		progressBar.css('width', percentage + '%').attr('aria-valuenow', percentage);
		progressPercent.text(percentage + '%');
		
		// Change progress bar color based on completion
		progressBar.removeClass('bg-success bg-warning bg-danger');
		if (percentage === 100) {
			progressBar.addClass('bg-success');
		} else if (percentage >= 50) {
			progressBar.addClass('bg-primary');
		} else {
			progressBar.addClass('bg-warning');
		}
	}

	function markPatrolAsScanned(patrolId, options) {
		if (!patrolId) {
			return;
		}
		options = options || {};
		if (!patrolStatusMap[patrolId]) {
			const patrol = findPatrolById(patrolId);
			if (!patrol) {
				return;
			}
			const fallbackOrder = patrol.urutan != null ? patrol.urutan : null;
			patrolStatusMap[patrolId] = {
				nama_patrol: patrol.nama_patrol || '',
				urutan: fallbackOrder,
				status: 'pending',
				last_scan: null
			};
		}
		let timestamp = options.timestamp;
		if (!timestamp) {
			if (typeof moment !== 'undefined') {
				timestamp = moment().format('HH:mm:ss');
			} else {
				const now = new Date();
				const hours = String(now.getHours()).padStart(2, '0');
				const minutes = String(now.getMinutes()).padStart(2, '0');
				const seconds = String(now.getSeconds()).padStart(2, '0');
				timestamp = hours + ':' + minutes + ':' + seconds;
			}
		}
		patrolStatusMap[patrolId].status = 'completed';
		patrolStatusMap[patrolId].last_scan = timestamp;
		renderPatrolStatusTable();
		if (selectedPatrolId == patrolId) {
			updatePatrolDetailCard(patrolId);
		}
		setNextPatrolInfo(getNextPendingPatrol());
	}

	function getNextPendingPatrol() {
		if (!patrolOptions || !patrolOptions.length) {
			return null;
		}
		for (let i = 0; i < patrolOptions.length; i++) {
			const patrol = patrolOptions[i];
			const statusObj = patrolStatusMap[patrol.id_patrol];
			if (!statusObj || statusObj.status !== 'completed') {
				return patrol;
			}
		}
		return null;
	}

	function togglePatrolUi(hasData) {
		// Show patrol status table
		$('#patrol-status-table-wrapper').show();
	}

	function triggerScanForPatrol(patrolId) {
		if (!patrolId) {
			Swal.fire('Info', 'Pilih titik patroli terlebih dahulu.', 'info');
			return;
		}
		try {
			// Store as string for consistent comparison
			// NOTE: No sequence validation - users can scan any patrol in any order
			// IMPORTANT: This sets forcedScannerTargetId which will be preserved throughout the scan process
			// Even if user scans a different QR code, this patrol ID will be used
			forcedScannerTargetId = String(patrolId);
			selectPatrolForScan(patrolId);
			ensureInlineScanner(true);
		} catch (error) {
			console.error('Error starting scanner:', error);
			// Reset state on error
			forcedScannerTargetId = null;
			selectedPatrolId = null;
			// Stop any running scanner
			stopQRScanner().then(() => {
				Swal.fire({
					icon: 'error',
					title: 'Error',
					text: 'Gagal memulai scanner. Silakan coba lagi.'
				});
			});
		}
	}
	
	function findPatrolByBarcode(barcode) {
		if (!barcode || !patrolOptions || !patrolOptions.length) {
			return null;
		}
		const target = String(barcode).trim().toLowerCase();
		
		const result = patrolOptions.find(function(patrol) {
			const patrolBarcode = String(patrol.barcode || '').trim().toLowerCase();
			return patrolBarcode === target;
		}) || null;
		
		return result;
	}
	
	function findPatrolById(patrolId) {
		if (!patrolId || !patrolOptions || !patrolOptions.length) {
			return null;
		}
		return patrolOptions.find(function(patrol) {
			return String(patrol.id_patrol) === String(patrolId);
		}) || null;
	}
	
	function getSequenceNumber(patrolId) {
		if (!patrolId || !patrolOptions || !patrolOptions.length) {
			return null;
		}
		const index = patrolOptions.findIndex(function(patrol) {
			return String(patrol.id_patrol) === String(patrolId);
		});
		if (index === -1) {
			return null;
		}
		const patrol = patrolOptions[index];
		return patrol.urutan != null ? patrol.urutan : (index + 1);
	}
	
	function formatPatrolForDisplay(patrol) {
		if (!patrol) {
			return null;
		}
		const formatted = {
			id_patrol: patrol.id_patrol ?? patrol.idPatrol ?? null,
			nama_patrol: patrol.nama_patrol ?? patrol.namaPatrol ?? '',
			barcode: patrol.barcode ?? patrol.scanned_barcode ?? ''
		};
		formatted.urutan = patrol.urutan != null ? patrol.urutan : getSequenceNumber(formatted.id_patrol);
		return formatted;
	}
	
	function restartScannerWithDelay() {
		setTimeout(function() {
			if (isQrInModal && $('#qrScannerModal').is(':visible')) {
				startQRScanner();
			} else if (!isQrInModal && qrInlineWrapper.length) {
				startQRScanner();
			}
		}, 600);
	}
	
	function normalizePatrolArray(patrols) {
		if (!patrols) {
			return [];
		}
		if (Array.isArray(patrols)) {
			return patrols;
		}
		return Object.keys(patrols).map(function(key) {
			return patrols[key];
		});
	}
	
	if (companyPatrolMap && typeof companyPatrolMap === 'object') {
		Object.keys(companyPatrolMap).forEach(function(key) {
			companyPatrolMap[key] = normalizePatrolArray(companyPatrolMap[key]);
		});
	} else {
		companyPatrolMap = {};
	}

	function setStepper(step) {
		if (!stepperPills.length) {
			return;
		}
		stepperPills.removeClass('active');
		stepperPills.filter('[data-step="' + step + '"]').addClass('active');
	}

	function scrollIntoViewIfNeeded($target) {
		if (!$target || !$target.offset) {
			return;
		}
		$('html, body').stop().animate({
			scrollTop: $target.offset().top - 80
		}, 400);
	}

	function isScannerRunning() {
		if (!qrScanner) {
			return false;
		}
		if (typeof qrScanner.isScanning === 'function') {
			return qrScanner.isScanning();
		}
		return qrScanner.isScanning === true;
	}

	function stopQRScanner() {
		return new Promise(resolve => {
			try {
				// Reset QR flash control
				qrCameraVideoTrack = null;
				qrTorchSupported = false;
				hideQRFlashControl();
				
				if (!qrScanner) {
					resolve();
					return;
				}
				if (isScannerRunning()) {
					qrScanner.stop().then(() => {
						try {
							qrScanner.clear();
						} catch (e) {}
						qrScanner = null;
						resolve();
					}).catch(() => {
						qrScanner = null;
						resolve();
					});
				} else {
					try {
						qrScanner.clear();
					} catch (e) {}
					qrScanner = null;
					resolve();
				}
			} catch (e) {
				qrScanner = null;
				resolve();
			}
		});
	}

	function resetQrInlineStatus() {
		$('#qr-result').hide();
		$('#qr-scanning-status').html(defaultQrStatusHtml);
		// Reset flash control
		qrFlashMode = 'auto';
		qrCameraVideoTrack = null;
		qrTorchSupported = false;
		hideQRFlashControl();
	}

	function ensureInlineScanner(forceRestart = false) {
		showStep(1);
		if (qrInlineWrapper.length) {
			qrInlineWrapper.addClass('active');
			scrollIntoViewIfNeeded(qrInlineWrapper);
			setTimeout(() => {
				qrInlineWrapper.removeClass('active');
			}, 1500);
		}
		if (forceRestart) {
			stopQRScanner().then(() => {
				startQRScanner();
			});
		} else if (!isScannerRunning()) {
			startQRScanner();
		}
	}
	
	// Make ensureInlineScanner globally accessible for mobile-activity.js
	window.ensureInlineScanner = ensureInlineScanner;

	function moveScannerToModal() {
		if (!qrInlineWrapper.length || !qrModalPlaceholder.length) {
			return;
		}
		qrModalPlaceholder.empty().append(qrInlineWrapper.detach());
		isQrInModal = true;
		$('#qrScannerModal').modal('show');
	}

	function returnScannerToInline() {
		if (!qrInlineWrapper.length || !qrInlineHomeSlot.length) {
			return;
		}
		qrInlineHomeSlot.after(qrInlineWrapper.detach());
		isQrInModal = false;
		resetQrInlineStatus();
	}
	
	// Load patrol points when company is detected
	window.addEventListener('companyDetected', function(event) {
		currentCompanyId = event.detail.companyId;
		setPatrolOptionsForCompany(currentCompanyId);
		loadPatrolPoints(currentCompanyId);
	});
	
	// Also check if company is already detected on page load
	$(document).ready(function() {
		// Reset state variables on page load to prevent stuck state
		forcedScannerTargetId = null;
		selectedPatrolId = null;
		scannedPatrolData = null;
		currentStep = 1;
		
		setTimeout(function() {
			const idCompany = $('#id_company').val();
			if (idCompany && !currentCompanyId) {
				currentCompanyId = idCompany;
				setPatrolOptionsForCompany(currentCompanyId);
				loadPatrolPoints(currentCompanyId);
			}
		}, 1000);
	});
	
	// Step navigation
	$('#btn-proceed-to-step2').click(function() {
		showStep(2);
	});
	
	$('#btn-back-to-step1').click(function() {
		showStep(1);
	});
	
	$('#btn-proceed-to-step3').click(function() {
		validateStep2();
	});
	
	$('#btn-back-to-step2').click(function() {
		showStep(2);
	});
	
	// Show specific step
	function showStep(step) {
		$('[data-step-container]').hide();
		$('#step-' + step).show();
		currentStep = step;
		setStepper(step);
		scrollIntoViewIfNeeded($('#step-' + step));
		
		if (step === 3) {
			populateReviewContent();
		}
	}
	
	// Validate step 2 before proceeding
	function validateStep2() {
		const judul = $('#judul_activity').val();
		
		if (!judul.trim()) {
			Swal.fire('Error', 'Judul activity harus diisi', 'error');
			return;
		}
		
		showStep(3);
	}
	
	// Populate review content
	function populateReviewContent() {
		const judul = $('#judul_activity').val();
		const deskripsi = $('#deskripsi_activity').val();
		const foto = $('#foto_activity').val();
		
		let reviewHtml = `
			<div class="row">
				<div class="col-6"><strong>Titik Patroli:</strong></div>
				<div class="col-6">${scannedPatrolData ? scannedPatrolData.nama_patrol : 'N/A'}</div>
			</div>
			<div class="row">
				<div class="col-6"><strong>Company:</strong></div>
				<div class="col-6">${scannedPatrolData ? scannedPatrolData.nama_company : 'N/A'}</div>
			</div>
			<div class="row">
				<div class="col-6"><strong>Judul:</strong></div>
				<div class="col-6">${judul}</div>
			</div>
			<div class="row">
				<div class="col-6"><strong>Deskripsi:</strong></div>
				<div class="col-6">${deskripsi}</div>
			</div>
			<div class="row">
				<div class="col-6"><strong>Foto:</strong></div>
				<div class="col-6">${foto ? 'Ô£ô Sudah diambil' : 'Ô£ù Belum diambil'}</div>
			</div>
		`;
		
		$('#review-content').html(reviewHtml);
	}
	
	// Load patrol points for a company
	function loadPatrolPoints(companyId) {
		if (!companyId) {
			patrolOptions = [];
			updatePatrolDropdown();
			setNextPatrolInfo(null);
			// Reset state when no company
			forcedScannerTargetId = null;
			selectedPatrolId = null;
			return;
		}
		
		// Reset scanner target when loading new patrol points (e.g., after refresh)
		forcedScannerTargetId = null;
		selectedPatrolId = null;
		
		// Always fetch from database - don't use cached data
		// Clear patrolStatusMap before loading to ensure fresh state
		patrolStatusMap = {};
		
		// Build URL with cache-busting timestamp parameter
		const url = base_url + 'mobile-activity/getPatrolPoints/' + companyId + '?_t=' + Date.now();
		
		$.ajax({
			url: url,
			type: 'GET',
			dataType: 'json',
			cache: false, // Prevent browser caching
			success: function(response) {
				if (response.status === 'ok') {
					patrolOptions = response.data || [];
					storePatrolOptionsForCompany(companyId, patrolOptions);
					updatePatrolDropdown();
					
					// Initialize status map with 'pending' for all patrols first
					initPatrolStatusMap(patrolOptions);
					
					// Mark all scanned patrols today as completed BEFORE rendering table
					if (response.scanned_patrols_today && typeof response.scanned_patrols_today === 'object') {
						for (const patrolId in response.scanned_patrols_today) {
							if (response.scanned_patrols_today.hasOwnProperty(patrolId)) {
								const scanTime = response.scanned_patrols_today[patrolId];
								let formattedTime = null;
								
								if (scanTime) {
									if (typeof moment !== 'undefined') {
										formattedTime = moment(scanTime).format('HH:mm:ss');
									} else {
										// Parse datetime string and extract time
										const timeMatch = scanTime.match(/(\d{2}):(\d{2}):(\d{2})/);
										if (timeMatch) {
											formattedTime = timeMatch[0];
										} else {
											formattedTime = scanTime;
										}
									}
								}
								
								// Mark as scanned (this updates patrolStatusMap but doesn't render yet)
								const patrolIdStr = String(patrolId);
								if (!patrolStatusMap[patrolIdStr]) {
									const patrol = findPatrolById(patrolIdStr);
									if (patrol) {
										const fallbackOrder = patrol.urutan != null ? patrol.urutan : null;
										patrolStatusMap[patrolIdStr] = {
											nama_patrol: patrol.nama_patrol || '',
											urutan: fallbackOrder,
											status: 'pending',
											last_scan: null
										};
									}
								}
								if (patrolStatusMap[patrolIdStr]) {
									patrolStatusMap[patrolIdStr].status = 'completed';
									patrolStatusMap[patrolIdStr].last_scan = formattedTime;
								}
							}
						}
					}
					
					// Also mark last_scanned for backward compatibility
					if (response.last_scanned && response.last_scanned.id_patrol) {
						const lastPatrolId = String(response.last_scanned.id_patrol);
						// Only mark if not already marked by scanned_patrols_today
						if (!response.scanned_patrols_today || !response.scanned_patrols_today[lastPatrolId]) {
							let lastScanTime = null;
							if (response.last_scanned.scan_time) {
								if (typeof moment !== 'undefined') {
									lastScanTime = moment(response.last_scanned.scan_time).format('HH:mm:ss');
								} else {
									lastScanTime = response.last_scanned.scan_time;
								}
							}
							if (!patrolStatusMap[lastPatrolId]) {
								const patrol = findPatrolById(lastPatrolId);
								if (patrol) {
									const fallbackOrder = patrol.urutan != null ? patrol.urutan : null;
									patrolStatusMap[lastPatrolId] = {
										nama_patrol: patrol.nama_patrol || '',
										urutan: fallbackOrder,
										status: 'pending',
										last_scan: null
									};
								}
							}
							if (patrolStatusMap[lastPatrolId]) {
								patrolStatusMap[lastPatrolId].status = 'completed';
								patrolStatusMap[lastPatrolId].last_scan = lastScanTime;
							}
						}
					}
					
					// Now render table after all status updates are complete
					renderPatrolStatusTable();
					togglePatrolUi(patrolOptions.length > 0);
					
					const nextFromServer = response.next_patrol || getNextPendingPatrol();
					setNextPatrolInfo(nextFromServer);
				}
			},
			error: function() {
				// Error loading patrol points - silently fail
			}
		});
	}
	
	// Display next patrol info
	function displayNextPatrolInfo(nextPatrol) {
		if (nextPatrol) {
			const urutanText = nextPatrol.urutan ? `Urutan: ${nextPatrol.urutan}` : '';
			$('#next-patrol-name').html(`<strong>${nextPatrol.nama_patrol}</strong>`);
			$('#next-patrol-urutan').text(urutanText);
			$('#next-patrol-info').show();
		} else {
			hideNextPatrolInfo();
		}
	}
	
	// Hide next patrol info
	function hideNextPatrolInfo() {
		$('#next-patrol-info').hide();
		$('#next-patrol-name').empty();
		$('#next-patrol-urutan').empty();
	}
	
	function setNextPatrolInfo(nextPatrol) {
		const formattedPatrol = formatPatrolForDisplay(nextPatrol);
		nextPatrolInfo = formattedPatrol;
		if (formattedPatrol) {
			displayNextPatrolInfo(formattedPatrol);
		} else {
			hideNextPatrolInfo();
		}
	}
	
	function getNextPatrolAfter(patrolId) {
		if (!patrolOptions || patrolOptions.length === 0) {
			return null;
		}
		
		if (!patrolId) {
			return patrolOptions[0];
		}
		
		const index = patrolOptions.findIndex(function(patrol) {
			return String(patrol.id_patrol) === String(patrolId);
		});
		
		if (index === -1) {
			return null;
		}
		
		return patrolOptions[index + 1] || null;
	}
	
	// Update patrol dropdown
	function updatePatrolDropdown() {
		const select = $('#id_patrol');
		select.empty().append('<option value="">Pilih Titik Patroli</option>');
		
		patrolOptions.forEach(function(patrol) {
			select.append(`<option value="${patrol.id_patrol}" data-barcode="${patrol.barcode}">${patrol.nama_patrol}</option>`);
		});
	}
	
	// QR Scanner functionality
	$('#btn-start-inline-qr').click(function() {
		if (!currentCompanyId) {
			Swal.fire('Error', 'Company belum terdeteksi. Pastikan GPS aktif dan Anda berada di lokasi company.', 'error');
			return;
		}
		
		// DON'T reset forcedScannerTargetId if user already clicked a scan button
		// Only reset if this is a free scan (no specific patrol selected)
		// This preserves user's choice (e.g., patrol 18) when they click "Mulai Scan"
		if (!forcedScannerTargetId || forcedScannerTargetId === null || forcedScannerTargetId === '') {
			// Free scan mode - no specific patrol selected
			forcedScannerTargetId = null;
		}
		// If forcedScannerTargetId is set, keep it - user selected a specific patrol
		
		ensureInlineScanner(true);
	});

	$('#btn-retry-inline-qr').click(function() {
		if (!currentCompanyId) {
			Swal.fire('Error', 'Company belum terdeteksi. Pastikan GPS aktif dan Anda berada di lokasi company.', 'error');
			return;
		}
		ensureInlineScanner(true);
	});
	
	$('#btn-floating-photo, #btn-scroll-to-photo').click(function() {
		focusPhotoCard();
	});
	
	flashControlWrapper.find('.flash-toggle').on('click', function() {
		const mode = $(this).data('flash-mode');
		if (!mode) {
			return;
		}
		if (!torchSupported) {
			Swal.fire('Info', 'Lampu tidak tersedia di perangkat ini.', 'info');
			return;
		}
		flashMode = mode;
		updateFlashButtons();
		applyFlashMode(mode);
	});
	
	// QR Scanner flash control button handlers
	$(document).on('click', '.flash-toggle-qr', function() {
		const mode = $(this).data('flash-mode');
		if (!mode) {
			return;
		}
		if (!qrTorchSupported) {
			Swal.fire('Info', 'Lampu tidak tersedia di perangkat ini.', 'info');
			return;
		}
		qrFlashMode = mode;
		updateQRScannerFlashButtons();
		applyQRScannerFlashMode(mode);
	});
	
	function updateCameraStatus(message, variant = 'info') {
		if (!cameraStatusText.length) {
			return;
		}
		cameraStatusText.removeClass('text-success text-warning text-danger text-muted');
		if (!message) {
			cameraStatusText.text('');
			return;
		}
		let cls = 'text-muted';
		if (variant === 'success') {
			cls = 'text-success';
		} else if (variant === 'warning') {
			cls = 'text-warning';
		} else if (variant === 'error') {
			cls = 'text-danger';
		}
		cameraStatusText.addClass(cls).text(message);
	}

	$('#btn-manual-validation').click(function() {
		if (!currentCompanyId) {
			Swal.fire('Error', 'Company belum terdeteksi. Pastikan GPS aktif dan Anda berada di lokasi company.', 'error');
			return;
		}
		if (!patrolOptions || patrolOptions.length === 0) {
			Swal.fire('Info', 'Data patrol belum tersedia untuk company ini.', 'info');
			return;
		}

		// PRIORITY: If user already clicked a scan button (forcedScannerTargetId is set), use that
		// This respects user's explicit choice (e.g., patrol 18) instead of suggesting next patrol
		if (forcedScannerTargetId && forcedScannerTargetId !== null && forcedScannerTargetId !== '') {
			const selectedPatrol = findPatrolById(forcedScannerTargetId);
			if (selectedPatrol) {
				// User already selected a patrol via scan button - use that one
				handlePatrolDetection(selectedPatrol.barcode || '', { isTest: true, manualTrigger: true });
				return;
			}
		}

		// Validasi Manual: Suggest next patrol but allow any patrol to be scanned
		// Users can also click scan button on any patrol row to scan in any order
		let targetPatrol = null;
		if (nextPatrolInfo && nextPatrolInfo.id_patrol) {
			targetPatrol = findPatrolById(nextPatrolInfo.id_patrol);
		}
		if (!targetPatrol) {
			// If no next patrol, use first pending patrol
			for (let i = 0; i < patrolOptions.length; i++) {
				const patrol = patrolOptions[i];
				const statusObj = patrolStatusMap[patrol.id_patrol];
				if (!statusObj || statusObj.status !== 'completed') {
					targetPatrol = patrol;
					break;
				}
			}
		}
		if (!targetPatrol) {
			// Fallback to first patrol if all are completed
			targetPatrol = patrolOptions[0];
		}

		if (!targetPatrol) {
			Swal.fire('Info', 'Patrol belum tersedia.', 'info');
			return;
		}

		// Allow scanning any patrol - no sequence validation
		forcedScannerTargetId = String(targetPatrol.id_patrol);
		handlePatrolDetection(targetPatrol.barcode || '', { isTest: true, manualTrigger: true });
	});

	$('#btn-open-fullscreen-qr').click(function() {
		if (!currentCompanyId) {
			Swal.fire('Error', 'Company belum terdeteksi. Pastikan GPS aktif dan Anda berada di lokasi company.', 'error');
			return;
		}
		moveScannerToModal();
	});

	$('body').off('click', '.btn-scan-patrol-table');
	$('body').on('click', '.btn-scan-patrol-table', function() {
		const patrolId = $(this).attr('data-patrol-id');
		// Ensure patrolId is properly parsed (handle string/number conversion)
		if (patrolId) {
			triggerScanForPatrol(patrolId);
		}
	});

	$('body').off('click', '#btn-scan-selected-patrol');
	$('body').on('click', '#btn-scan-selected-patrol', function() {
		triggerScanForPatrol(selectedPatrolId);
	});
	
	// Handle modal opened event
	$(document).on('qrScannerModalOpened', function() {
		setTimeout(() => {
			if (isQrInModal) {
				startQRScanner();
			}
		}, 300);
	});
	
	// Handle modal shown event - use both events for better compatibility
	$('#qrScannerModal').on('shown.bs.modal', function() {
		// Delay to ensure modal is fully rendered
		setTimeout(() => {
			if (isQrInModal) {
				startQRScanner();
			}
		}, 300);
	});
	
	// Also handle when modal is about to be shown (for some mobile browsers)
	$('#qrScannerModal').on('show.bs.modal', function() {
		// Set status message based on whether a specific patrol is targeted
		if (forcedScannerTargetId) {
			const targetPatrol = findPatrolById(forcedScannerTargetId);
			if (targetPatrol) {
				$('#qr-scanning-status').html(`
					<div class="alert alert-info">
						<i class="fas fa-search me-2"></i>
						<strong>Mencari QR Code untuk:</strong>
						<br>
						<strong>${targetPatrol.nama_patrol || 'Patrol #' + forcedScannerTargetId}</strong>
						<br>
						<small>Arahkan kamera ke QR code patrol yang sesuai</small>
					</div>
				`);
			} else {
				$('#qr-scanning-status').html(`
					<div class="alert alert-info">
						<i class="fas fa-search me-2"></i>
						<strong>Mencari QR Code...</strong>
						<br>
						<small>Arahkan kamera ke QR code patrol</small>
					</div>
				`);
			}
		} else {
			// Reset status message for free scanning
			$('#qr-scanning-status').html(`
				<div class="alert alert-info">
					<i class="fas fa-search me-2"></i>
					<strong>Mencari QR Code...</strong>
					<br>
					<small>Arahkan kamera ke QR code patrol</small>
					<br>
					<small class="text-muted">Pastikan izin kamera sudah diberikan</small>
				</div>
			`);
		}
	});
	
	
	// Start QR scanner
	function startQRScanner() {
		// Check if Html5Qrcode is available
		if (typeof Html5Qrcode === 'undefined') {
			console.error('Html5Qrcode library not loaded');
			Swal.fire('Error', 'QR Scanner library tidak tersedia. Pastikan koneksi internet aktif untuk memuat library.', 'error');
			return;
		}
		
		// Check if qr-reader element exists
		const qrReaderElement = document.getElementById('qr-reader');
		if (!qrReaderElement) {
			console.error('qr-reader element not found');
			Swal.fire('Error', 'Elemen scanner tidak ditemukan', 'error');
			return;
		}
		
		// Clear the element before starting
		qrReaderElement.innerHTML = '';
		
		// Check if scanner is already running
		if (qrScanner && qrScanner.isScanning && qrScanner.isScanning()) {
			return;
		}
		
		// Clear existing scanner
		if (qrScanner) {
			try {
				qrScanner.clear();
			} catch(e) {
				// Error clearing scanner - silently fail
			}
		}
		
		// Mobile-friendly configuration
		const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
		
		const config = {
			fps: isMobile ? 10 : 30, // Lower FPS on mobile for better performance
			qrbox: function(viewfinderWidth, viewfinderHeight) {
				// Use 80% of viewfinder on mobile, 250px on desktop
				const minEdgePercentage = isMobile ? 0.8 : 0.3;
				const minEdgeSize = Math.min(viewfinderWidth, viewfinderHeight);
				const qrboxSize = Math.floor(minEdgeSize * minEdgePercentage);
				return {
					width: qrboxSize,
					height: qrboxSize
				};
			},
			aspectRatio: 1.0,
			rememberLastUsedCamera: true,
			supportedScanTypes: [Html5QrcodeScanType.SCAN_TYPE_CAMERA],
			showTorchButtonIfSupported: true,
			showZoomSliderIfSupported: !isMobile, // Disable zoom slider on mobile
			defaultZoomValueIfSupported: isMobile ? 1 : 2,
			useBarCodeDetectorIfSupported: true,
			verbose: true, // Enable verbose logging
			videoConstraints: isMobile ? {
				facingMode: "environment",
				width: { ideal: 1280 },
				height: { ideal: 720 }
			} : undefined
		};
		
		// Add manual capture button for when auto-decode fails
		let manualCaptureInterval = null;
		let lastCaptureTime = 0;
		
		qrScanner = new Html5Qrcode("qr-reader");
		
		// Wrap callbacks to ensure they're called
		const wrappedOnScanSuccess = function(decodedText, decodedResult) {
			onScanSuccess(decodedText, decodedResult);
		};
		
		const wrappedOnScanFailure = function(error) {
			// Don't log failures - they're too noisy during normal scanning
			onScanFailure(error);
		};
		
		// Try environment camera first, then user camera as fallback
		// On mobile, prioritize environment (back camera)
		const cameraConfigs = isMobile ? [
			{ facingMode: "environment" },
			"environment",
			{ facingMode: "user" },
			"user"
		] : [
			{ facingMode: "environment" },
			{ facingMode: "user" },
			"environment",
			"user"
		];
		
		let currentConfigIndex = 0;
		
		function tryStartScanner() {
			if (currentConfigIndex >= cameraConfigs.length) {
				// All camera configs failed - reset scanner state
				qrScanner = null;
				forcedScannerTargetId = null;
				
				let errorMsg = 'Tidak dapat mengakses kamera.';
				if (isMobile) {
					errorMsg += '<br><small>Pastikan:<br>1. Izin kamera sudah diberikan<br>2. Menggunakan HTTPS atau localhost<br>3. Browser mendukung kamera</small>';
				} else {
					errorMsg += ' Pastikan izin kamera diberikan.';
				}
				
				// Reset UI
				$('#qr-scanning-status').html(`
					<div class="alert alert-danger">
						<i class="fas fa-exclamation-triangle me-2"></i>
						<strong>Kamera tidak dapat diakses</strong>
						<br>
						<small>Silakan coba lagi atau gunakan tombol "Validasi Manual"</small>
					</div>
				`);
				
				Swal.fire({
					icon: 'error',
					title: 'Error Kamera',
					html: errorMsg
				});
				return;
			}
			
			const cameraConfig = cameraConfigs[currentConfigIndex];
			
			// Show loading message
			$('#qr-scanning-status').html(`
				<div class="alert alert-warning">
					<i class="fas fa-spinner fa-spin me-2"></i>
					<strong>Mengakses kamera...</strong>
					<br>
					<small>Mencoba konfigurasi kamera ${currentConfigIndex + 1}/${cameraConfigs.length}</small>
				</div>
			`);
			
			qrScanner.start(
				cameraConfig,
				config,
				wrappedOnScanSuccess,
				wrappedOnScanFailure
			).then(() => {
				// Hide loading, show scanning message (WhatsApp Web style - continuous scanning)
				$('#qr-scanning-status').html(`
					<div class="alert alert-info">
						<i class="fas fa-qrcode me-2"></i>
						<strong>Memindai QR Code...</strong>
						<br>
						<small>Arahkan kamera ke QR code patrol. QR code akan terdeteksi otomatis.</small>
					</div>
				`);
				
				// Setup flash control for QR scanner
				// Retry mechanism to get video track (may not be immediately available)
				let retryCount = 0;
				const maxRetries = 5;
				const setupFlashRetry = setInterval(() => {
					qrCameraVideoTrack = getQRScannerVideoTrack();
					if (qrCameraVideoTrack) {
						setupQRScannerFlashControl();
						clearInterval(setupFlashRetry);
					} else {
						retryCount++;
						if (retryCount >= maxRetries) {
							clearInterval(setupFlashRetry);
						}
					}
				}, 300); // Check every 300ms, up to 5 times
				
				// Add manual capture button handler
				$('#btn-manual-capture-qr').off('click').on('click', function() {
					captureAndDecodeQR();
				});
			}).catch(err => {
				console.error('Camera start error:', err);
				// Clean up failed scanner attempt
				if (qrScanner) {
					try {
						qrScanner.clear();
					} catch (e) {
						// Ignore cleanup errors
					}
				}
				currentConfigIndex++;
				// Try next config after a short delay
				setTimeout(() => {
					tryStartScanner();
				}, 500);
			});
		}
		
		tryStartScanner();
	}
	
	// Monitor for square detection (QR box overlay)
	function startSquareDetectionMonitor() {
		let lastCaptureTime = 0;
		const captureCooldown = 2000; // Don't capture more than once every 2 seconds
		let squareDetectedCount = 0;
		
		const checkInterval = setInterval(function() {
			if (!qrScanner || !qrScanner.isScanning || !qrScanner.isScanning()) {
				clearInterval(checkInterval);
				return;
			}
			
			// Look for the QR detection square overlay
			const qrReaderElement = document.getElementById('qr-reader');
			if (!qrReaderElement) {
				return;
			}
			
			// Html5Qrcode draws a canvas overlay with the detection square
			// Look for canvas elements or SVG elements that indicate detection
			const canvases = qrReaderElement.querySelectorAll('canvas');
			const svgs = qrReaderElement.querySelectorAll('svg');
			
			// Check if square is visible (detection box is drawn)
			let squareVisible = false;
			
			// Method 1: Check for canvas with drawing (detection overlay)
			canvases.forEach(function(canvas) {
				if (canvas.width > 0 && canvas.height > 0) {
					const ctx = canvas.getContext('2d');
					const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
					// Check if canvas has non-transparent pixels (square is drawn)
					for (let i = 3; i < imageData.data.length; i += 4) {
						if (imageData.data[i] > 0) { // Alpha channel > 0 means something is drawn
							squareVisible = true;
							break;
						}
					}
				}
			});
			
			// Method 2: Check for SVG elements (alternative detection indicator)
			if (svgs.length > 0) {
				squareVisible = true;
			}
			
			// Method 3: Check for specific Html5Qrcode detection classes/elements
			const detectionElements = qrReaderElement.querySelectorAll('[id*="qr"], [class*="qr"], [id*="scanner"], [class*="scanner"]');
			if (detectionElements.length > 1) { // More than just the video element
				squareVisible = true;
			}
			
			if (squareVisible) {
				squareDetectedCount++;
				
				// Only capture if enough time has passed since last capture
				const now = Date.now();
				if (now - lastCaptureTime > captureCooldown) {
					lastCaptureTime = now;
					
					// Update status
					$('#qr-scanning-status').html(`
						<div class="alert alert-warning">
							<i class="fas fa-spinner fa-spin me-2"></i>
							<strong>Square terdeteksi! Mengcapture gambar...</strong>
						</div>
					`);
					
					// Capture and decode
					setTimeout(function() {
						captureSquareRegion();
					}, 300); // Small delay to ensure square is stable
				}
			}
		}, 500); // Check every 500ms
	}
	
	// Capture the square region from video
	function captureSquareRegion() {
		const qrReaderElement = document.getElementById('qr-reader');
		if (!qrReaderElement) {
			return;
		}
		
		const video = qrReaderElement.querySelector('video');
		if (!video || video.readyState !== 4) {
			return;
		}
		
		// Get video dimensions
		const videoWidth = video.videoWidth;
		const videoHeight = video.videoHeight;
		
		// Calculate square region (center 80% of video, as per qrbox config)
		const squareSize = Math.min(videoWidth, videoHeight) * 0.8;
		const squareX = (videoWidth - squareSize) / 2;
		const squareY = (videoHeight - squareSize) / 2;
		
		// Create canvas to capture square region
		const canvas = document.createElement('canvas');
		canvas.width = squareSize;
		canvas.height = squareSize;
		const ctx = canvas.getContext('2d');
		
		// Draw only the square region from video
		ctx.drawImage(
			video,
			squareX, squareY, squareSize, squareSize, // Source: square region
			0, 0, squareSize, squareSize // Destination: full canvas
		);
		
		// Convert to blob and try to decode
		canvas.toBlob(function(blob) {
			if (!blob) {
				return;
			}
			
			const file = new File([blob], 'qr-square.jpg', { type: 'image/jpeg' });
			const imageDataUrl = canvas.toDataURL('image/jpeg');
			
			// Stop camera scanner first before file scan
			// Check if scanner is running (handle both function and property)
			let isScanning = false;
			if (qrScanner) {
				if (typeof qrScanner.isScanning === 'function') {
					isScanning = qrScanner.isScanning();
				} else if (qrScanner.isScanning === true) {
					isScanning = true;
				}
			}
			
			if (isScanning) {
				qrScanner.stop().then(() => {
					decodeCapturedFile(file, imageDataUrl);
				}).catch(err => {
					// Try to decode anyway with a new instance
					decodeCapturedFile(file, imageDataUrl, true);
				});
			} else {
				// Scanner not running, decode directly
				decodeCapturedFile(file, imageDataUrl);
			}
		}, 'image/jpeg', 0.95);
	}
	
	// Decode captured file (helper function)
	function decodeCapturedFile(file, imageDataUrl, restartScanner = true) {
		// Use Html5Qrcode static method or create new instance for file scanning
		if (typeof Html5Qrcode !== 'undefined' && Html5Qrcode.scanFile) {
			Html5Qrcode.scanFile(file, true)
				.then(decodedText => {
					// Restart scanner if needed
					if (restartScanner && $('#qrScannerModal').is(':visible')) {
						setTimeout(() => {
							startQRScanner();
						}, 500);
					}
					
					handlePatrolDetection(decodedText);
				})
				.catch(err => {
					// Decode failed - show error message
					Swal.fire({
						icon: 'error',
						title: 'Decode Gagal',
						text: 'Gagal membaca QR code dari gambar. Pastikan QR code jelas dan dalam kotak deteksi.',
						confirmButtonText: 'OK'
					});
					
					// Restart scanner if needed
					if (restartScanner && $('#qrScannerModal').is(':visible')) {
						setTimeout(() => {
							startQRScanner();
						}, 1000);
					}
				});
		} else {
			// Fallback: show error
			Swal.fire({
				icon: 'error',
				title: 'Error',
				text: 'Library QR scanner tidak tersedia.',
				confirmButtonText: 'OK'
			});
			
			// Restart scanner if needed
			if (restartScanner && $('#qrScannerModal').is(':visible')) {
				setTimeout(() => {
					startQRScanner();
				}, 1000);
			}
		}
	}
	
	// Manual capture and decode QR from video frame
	function captureAndDecodeQR() {
		// Find video element in qr-reader
		const qrReaderElement = document.getElementById('qr-reader');
		if (!qrReaderElement) {
			Swal.fire('Error', 'QR reader tidak ditemukan', 'error');
			return;
		}
		
		// Find video element
		const video = qrReaderElement.querySelector('video');
		if (!video || video.readyState !== 4) {
			Swal.fire('Error', 'Video tidak siap', 'error');
			return;
		}
		
		// Create canvas to capture frame
		const canvas = document.createElement('canvas');
		canvas.width = video.videoWidth;
		canvas.height = video.videoHeight;
		const ctx = canvas.getContext('2d');
		ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
		
		// Convert to blob
		canvas.toBlob(function(blob) {
			if (!blob) {
				Swal.fire('Error', 'Gagal membuat gambar', 'error');
				return;
			}
			
			// Create file from blob
			const file = new File([blob], 'captured-qr.jpg', { type: 'image/jpeg' });
			const imageDataUrl = canvas.toDataURL('image/jpeg');
			
			// Stop camera scanner first before file scan
			// Check if scanner is running (handle both function and property)
			let isScanning = false;
			if (qrScanner) {
				if (typeof qrScanner.isScanning === 'function') {
					isScanning = qrScanner.isScanning();
				} else if (qrScanner.isScanning === true) {
					isScanning = true;
				}
			}
			
			if (isScanning) {
				qrScanner.stop().then(() => {
					decodeCapturedFile(file, imageDataUrl);
				}).catch(err => {
					// Try to decode anyway with static method
					decodeCapturedFile(file, imageDataUrl, true);
				});
			} else {
				// Scanner not running, decode directly
				decodeCapturedFile(file, imageDataUrl);
			}
		}, 'image/jpeg', 0.95);
	}
	
	// QR scan success
	function onScanSuccess(decodedText, decodedResult) {
		// Debouncing: Prevent multiple rapid scans of the same QR code
		const now = Date.now();
		if (lastScannedCode === decodedText && (now - lastScanTime) < QR_SCAN_DEBOUNCE_MS) {
			// Same QR code scanned too soon, ignore
			return;
		}
		
		// Update debounce tracking
		lastScannedCode = decodedText;
		lastScanTime = now;
		
		// Show immediate feedback
		$('#qr-scanning-status').html(`
			<div class="alert alert-warning">
				<i class="fas fa-spinner fa-spin me-2"></i>
				<strong>QR Code terdeteksi!</strong>
				<br>
				<small>Memvalidasi dengan server...</small>
			</div>
		`);
		
		// Stop scanner temporarily to process the QR code
		// Then restart it automatically (like WhatsApp Web) for continuous scanning
		try {
			if (qrScanner && qrScanner.isScanning && qrScanner.isScanning()) {
				qrScanner.stop().then(() => {
					// Handle detection on client-side
					handlePatrolDetection(decodedText);
					
					// Auto-restart scanner after a short delay for continuous scanning (WhatsApp Web style)
					setTimeout(() => {
						// Only restart if we're still on step 1 and scanner is not running
						if (currentStep === 1 && !isScannerRunning()) {
							startQRScanner();
						}
					}, 1000);
				}).catch(err => {
					// Error stopping scanner - still process the detection
					handlePatrolDetection(decodedText);
				});
			} else {
				// Scanner not running, just handle detection
				handlePatrolDetection(decodedText);
			}
		} catch(e) {
			// Error in scanner stop - still process the detection
			handlePatrolDetection(decodedText);
		}
	}
	
	// QR scan failure
	function onScanFailure(error) {
		// Suppress all error logs - they're expected during scanning
		// Auto-capture disabled - user must click button manually
	}
	
	function handlePatrolDetection(barcode, options = {}) {
		const isTestMode = options.isTest === true;
		const manualTrigger = options.manualTrigger === true;
		
		// PRIORITY: If forcedScannerTargetId is set (user clicked a scan button), ALWAYS use it
		// This ensures user's choice (e.g., patrol 18) is respected, regardless of scanned barcode
		const buttonClicked = forcedScannerTargetId && forcedScannerTargetId !== null && forcedScannerTargetId !== '';
		
		// If button clicked OR manual trigger: completely bypass ALL QR validation
		// Use the selected patrol ID directly, ignore any scanned barcode
		if (buttonClicked || manualTrigger) {
			// Get the patrol ID to use - ALWAYS use forcedScannerTargetId if it's set
			const patrolIdToUse = buttonClicked ? String(forcedScannerTargetId) : (manualTrigger && forcedScannerTargetId ? String(forcedScannerTargetId) : null);
			
			if (!patrolIdToUse) {
				Swal.fire({
					icon: 'error',
					title: 'Error',
					text: 'Patrol ID tidak valid. Silakan pilih patrol terlebih dahulu.'
				});
				restartScannerWithDelay();
				return;
			}
			
			// Skip ALL QR validation and barcode matching
			// Just use button's patrol ID directly - IGNORE scanned barcode completely
			const buttonPatrol = findPatrolById(patrolIdToUse);
			if (!buttonPatrol) {
				Swal.fire({
					icon: 'error',
					title: 'Error',
					text: 'Patrol yang dipilih tidak ditemukan.'
				});
				restartScannerWithDelay();
				return;
			}
			
			// Use button's patrol ID directly, no validation - IGNORE scanned barcode
			selectPatrolForScan(patrolIdToUse);
			
			scannedPatrolData = Object.assign({}, buttonPatrol);
			if (!scannedPatrolData.nama_company && window.nearestCompany) {
				scannedPatrolData.nama_company = window.nearestCompany.nama_company || '';
			}
			
			$('#id_patrol').val(patrolIdToUse);
			// Use button's barcode if available, otherwise use scanned barcode (but patrol ID is from button)
			$('#scanned_barcode').val(buttonPatrol.barcode || barcode || '');
			$('#qr-code-text').text(barcode || buttonPatrol.barcode || '');
			$('#qr-result').show();
			
			$('#scanned-patrol-info').html(`
				<strong>${buttonPatrol.nama_patrol}</strong><br>
				<small>${scannedPatrolData.nama_company || (window.nearestCompany ? window.nearestCompany.nama_company : '')}</small>
			`);
			$('#qr-scan-result').show();
			
			// Auto-fill judul_activity with patrol name if field is empty
			const judulField = $('#judul_activity');
			if (judulField.length && (!judulField.val() || judulField.val().trim() === '')) {
				const patrolName = buttonPatrol.nama_patrol || '';
				if (patrolName) {
					judulField.val(patrolName);
				}
			}
			
			markPatrolAsScanned(patrolIdToUse);
			setNextPatrolInfo(getNextPendingPatrol());
			
			setTimeout(() => {
				showStep(2);
				// Ensure text is a string
				const patrolName = buttonPatrol && buttonPatrol.nama_patrol 
					? String(buttonPatrol.nama_patrol) 
					: 'Patrol siap diisi.';
				Swal.fire({
					icon: 'success',
					title: 'QR Terdeteksi',
					text: patrolName,
					timer: 1500,
					showConfirmButton: false
				});
			}, 200);
			
			// Keep forcedScannerTargetId for saving later - DO NOT reset it here
			// It will be reset after activity is saved or form is reset
			return;
		}
		
		// Normal flow (no button clicked, no forcedScannerTargetId): validate barcode and match to patrol
		if (!barcode) {
			Swal.fire('Info', 'Barcode tidak tersedia. Silakan lakukan scan ulang.', 'info');
			restartScannerWithDelay();
			return;
		}
		
		if (!currentCompanyId) {
			Swal.fire('Error', 'Company belum terdeteksi. Pastikan GPS aktif dan Anda berada di lokasi company.', 'error');
			return;
		}
		
		if (!patrolOptions || patrolOptions.length === 0) {
			Swal.fire('Info', 'Data patrol belum tersedia untuk company ini.', 'info');
			restartScannerWithDelay();
			return;
		}
		
		const matchedPatrol = findPatrolByBarcode(barcode);
		
		if (!matchedPatrol) {
			Swal.fire({
				icon: 'error',
				title: 'QR Tidak Valid',
				text: 'QR Code tidak dikenali untuk company ini.'
			});
			restartScannerWithDelay();
			return;
		}
		
		// Free scan (no button clicked): use normal QR matching
		// NOTE: No sequence validation - users can scan any patrol in any order
		selectPatrolForScan(matchedPatrol.id_patrol);
		
		scannedPatrolData = Object.assign({}, matchedPatrol);
		if (!scannedPatrolData.nama_company && window.nearestCompany) {
			scannedPatrolData.nama_company = window.nearestCompany.nama_company || '';
		}
		
		$('#id_patrol').val(matchedPatrol.id_patrol);
		$('#scanned_barcode').val(matchedPatrol.barcode);
		$('#qr-code-text').text(barcode);
		$('#qr-result').show();
		
		$('#scanned-patrol-info').html(`
			<strong>${matchedPatrol.nama_patrol}</strong><br>
			<small>${scannedPatrolData.nama_company || (window.nearestCompany ? window.nearestCompany.nama_company : '')}</small>
		`);
		$('#qr-scan-result').show();
		
		// Auto-fill judul_activity with patrol name if field is empty
		const judulField = $('#judul_activity');
		if (judulField.length && (!judulField.val() || judulField.val().trim() === '')) {
			const patrolName = matchedPatrol.nama_patrol || '';
			if (patrolName) {
				judulField.val(patrolName);
			}
		}
		
		markPatrolAsScanned(matchedPatrol.id_patrol);
		setNextPatrolInfo(getNextPendingPatrol());
		
		// Reset debounce tracking after successful scan
		lastScannedCode = null;
		lastScanTime = 0;
		
		setTimeout(() => {
			showStep(2);
			// Ensure text is a string
			const matchedPatrolName = matchedPatrol && matchedPatrol.nama_patrol 
				? String(matchedPatrol.nama_patrol) 
				: 'Patrol siap diisi.';
			Swal.fire({
				icon: 'success',
				title: isTestMode ? 'QR Terdeteksi' : 'QR Terdeteksi',
				text: matchedPatrolName,
				timer: 1500,
				showConfirmButton: false
			});
		}, 200);
	}
	
	// Helper function to reset scanner UI
	function resetScannerUI() {
		return stopQRScanner().then(() => {
			if (isQrInModal) {
				$('#qrScannerModal').modal('hide');
			} else {
				resetQrInlineStatus();
			}
		});
	}
	
	// Close modal and stop scanner
	$('#qrScannerModal').on('hidden.bs.modal', function() {
		returnScannerToInline();
		stopQRScanner();
	});
	
	// Save activity from step 3
	$('#btn-save-activity').click(function() {
		saveActivity();
	});
	
	// Save activity function
	function saveActivity() {
		const id_company = $('#id_company').val();
		// Use button's patrol ID if button was clicked, otherwise use form value
		const id_patrol = (forcedScannerTargetId && forcedScannerTargetId !== null) 
			? String(forcedScannerTargetId) 
			: $('#id_patrol').val();
		const scanned_barcode = $('#scanned_barcode').val();
		const judul_activity = $('#judul_activity').val();
		const deskripsi_activity = $('#deskripsi_activity').val();
		const foto = $('#foto_activity').val();
		
		// Get patrol requirement status from nearest company
		let isPatrolRequired = false;
		if (window.nearestCompany && window.nearestCompany.isPatrolRequired === 1) {
			isPatrolRequired = true;
		}
		
		if (!id_company) {
			Swal.fire('Error', 'Lokasi company tidak valid', 'error');
			return;
		}
		
		// Only require patrol if it's mandatory
		if (isPatrolRequired && !id_patrol) {
			Swal.fire('Error', 'Titik patroli harus dipilih', 'error');
			return;
		}
		
		if (!judul_activity.trim()) {
			Swal.fire('Error', 'Judul activity harus diisi', 'error');
			return;
		}
		
		// Disable button and show loading
		$('#btn-save-activity').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Mengambil GPS...');
		
		// Get fresh GPS location before saving
		getGPSLocation()
			.then(location => {
				// Reset error flag on successful GPS
				gpsErrorShown = false;
				gpsErrorShownTime = 0;
				// Save with fresh GPS location
				performSaveActivity(id_company, id_patrol, scanned_barcode, judul_activity, deskripsi_activity, foto, location);
			})
			.catch(err => {
				console.warn('GPS Error, using cached location:', err);
				// Use cached location if GPS fails - don't show error modal here
				// Error was already shown in btn-capture handler if applicable
				// Just proceed with cached location silently
				performSaveActivity(id_company, id_patrol, scanned_barcode, judul_activity, deskripsi_activity, foto, currentLocation);
			});
	}
	
	// Perform actual save with location
	function performSaveActivity(id_company, id_patrol, scanned_barcode, judul_activity, deskripsi_activity, foto, location) {
		const data = {
			id_company: id_company,
			id_patrol: id_patrol,
			barcode_scanned: scanned_barcode,
			judul_activity: judul_activity,
			deskripsi_activity: deskripsi_activity,
			foto: foto,
			location: location
		};
		
		const data_encoded = btoa(JSON.stringify(data));
		
		$('#btn-save-activity').html('<i class="fas fa-spinner fa-spin me-2"></i>Menyimpan...');
		
		$.ajax({
			url: base_url + 'mobile-activity/ajaxSaveActivity',
			type: 'POST',
			data: { data: data_encoded },
			dataType: 'json',
			success: function(response) {
				$('#btn-save-activity').prop('disabled', false).html('<i class="fas fa-save me-2"></i>Simpan Activity');
				
				if (response.status === 'ok') {
					Swal.fire({
						icon: 'success',
						title: 'Berhasil!',
						text: 'Activity berhasil disimpan',
						confirmButtonText: 'OK'
					}).then(() => {
						// Reset scanner UI, then reset form
						resetScannerUI().then(() => {
							// Stop activity camera as well
							stopActivityCamera();
							// Reset form and go back to step 1
							resetForm();
							showStep(1);
						});
					});
				} else {
					// Ensure message is a string, not an object
					let errorMessage = 'Gagal menyimpan activity';
					if (response.message) {
						if (typeof response.message === 'string') {
							errorMessage = response.message;
						} else if (typeof response.message === 'object') {
							errorMessage = JSON.stringify(response.message);
						}
					}
					Swal.fire('Error', errorMessage, 'error');
				}
			},
			error: function() {
				$('#btn-save-activity').prop('disabled', false).html('<i class="fas fa-save me-2"></i>Simpan Activity');
				Swal.fire('Error', 'Gagal menyimpan activity', 'error');
			}
		});
	}
	
	// Camera switch functionality
	$('#btn-switch-camera').click(function() {
		switchActivityCamera();
	});
	
	// Function to switch between front and back camera
	async function switchActivityCamera() {
		if (!activityCameraStream) {
			activityCameraFacingMode = activityCameraFacingMode === 'environment' ? 'user' : 'environment';
			try {
				await startActivityCamera({ suppressError: true });
			} catch (err) {
				activityCameraFacingMode = lastSuccessfulFacingMode;
			}
			return;
		}
		
		const previousMode = activityCameraFacingMode;
		const desiredMode = activityCameraFacingMode === 'environment' ? 'user' : 'environment';
		activityCameraFacingMode = desiredMode;
		updateCameraStatus('Mengganti kamera...', 'info');
		
		activityCameraStream.getTracks().forEach(track => track.stop());
		activityCameraStream = null;
		
		try {
			await startActivityCamera({ suppressError: true });
			const label = desiredMode === 'environment' ? 'Kamera belakang aktif' : 'Kamera depan aktif';
			updateCameraStatus(label, 'success');
		} catch (err) {
			console.warn('Switch camera failed, reverting to previous camera', err);
			activityCameraFacingMode = previousMode;
			updateCameraStatus('Kamera depan tidak tersedia di perangkat ini. Menggunakan kamera belakang.', 'warning');
			try {
				await startActivityCamera({ suppressError: true });
			} catch (fallbackErr) {
				console.error('Failed to restore previous camera', fallbackErr);
				updateCameraStatus('Kamera tidak dapat digunakan.', 'error');
			}
		}
	}
	
	// Function to start camera for activity
	async function startActivityCamera(options = {}) {
		const { suppressError = false } = options;
		try {
			const constraints = {
				video: {
					facingMode: activityCameraFacingMode
				}
			};
			
			const stream = await navigator.mediaDevices.getUserMedia(constraints);
			activityCameraStream = stream;
			
			const video = document.getElementById('my_camera');
			if (video) {
				video.srcObject = stream;
				video.play();
			}
			
			cameraVideoTrack = stream.getVideoTracks()[0];
			lastSuccessfulFacingMode = activityCameraFacingMode;
			const label = activityCameraFacingMode === 'environment' ? 'Kamera belakang aktif' : 'Kamera depan aktif';
			updateCameraStatus(label, 'success');
			setupFlashControl();
		} catch (err) {
			console.error('Error accessing camera:', err);
			if (!suppressError) {
				Swal.fire('Error', 'Tidak dapat mengakses kamera. Pastikan izin kamera diberikan.', 'error');
			}
			restorePatrolProgressAfterCamera();
			updateCameraStatus('Kamera tidak bisa diakses.', 'error');
			hideFlashControl('Lampu tidak tersedia.');
			throw err;
		}
	}
	
	// Stop camera stream
	function stopActivityCamera() {
		if (activityCameraStream) {
			activityCameraStream.getTracks().forEach(track => track.stop());
			activityCameraStream = null;
		}
		cameraVideoTrack = null;
		hideFlashControl();
		restorePatrolProgressAfterCamera();
	}
	
	// Handle camera button clicks
	$('#btn-open-camera').click(function() {
		$('#camera-container').show();
		$('#btn-open-camera').hide();
		startActivityCamera().catch(() => {
			$('#camera-container').hide();
			$('#btn-open-camera').show();
		});
		collapsePatrolProgressForCamera();
		focusPhotoCard();
	});
	
	$('#btn-open-gallery').click(function() {
		$('#gallery-file-input').trigger('click');
		focusPhotoCard();
	});
	
	$('#gallery-file-input').on('change', function(e) {
		const files = e.target.files;
		if (!files || !files.length) {
			return;
		}
		const file = files[0];
		const galleryBtn = $('#btn-open-gallery');
		galleryBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Mengambil Foto...');
		addGalleryPhoto(file).then(() => {
			galleryBtn.prop('disabled', false).html('<i class="fas fa-image me-2"></i>Pilih dari Galeri');
		}).catch(() => {
			galleryBtn.prop('disabled', false).html('<i class="fas fa-image me-2"></i>Pilih dari Galeri');
			Swal.fire('Error', 'Gagal memuat foto dari galeri.', 'error');
		}).finally(() => {
			$('#gallery-file-input').val('');
		});
	});
	
	// Initialize currentLocation variable to avoid undefined errors
	let currentLocation = null;
	
	// Track GPS error display to prevent repeated modals
	let gpsErrorShown = false;
	let gpsErrorShownTime = 0;
	const GPS_ERROR_DEBOUNCE_MS = 5000; // Only show error once per 5 seconds
	
	$('#btn-capture').click(function() {
		const button = $('#btn-capture');
		
		// Show loading while getting GPS
		button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Mengambil Lokasi GPS...');
		
		// Request fresh GPS location
		getGPSLocation()
			.then(location => {
				capturePhotoWithLocation(location);
				button.prop('disabled', false).html('<i class="fas fa-camera me-2"></i>Ambil Foto');
			})
			.catch(err => {
				console.error('GPS Error:', err);
				// Use cached location if GPS fails, or null if no cached location
				if (currentLocation) {
					capturePhotoWithLocation(currentLocation);
				} else {
					// Only show error modal if not shown recently (debounce)
					const now = Date.now();
					if (!gpsErrorShown || (now - gpsErrorShownTime) > GPS_ERROR_DEBOUNCE_MS) {
						gpsErrorShown = true;
						gpsErrorShownTime = now;
						Swal.fire({
							icon: 'warning',
							title: 'GPS Tidak Tersedia',
							text: 'Tidak dapat mendapatkan lokasi GPS. Foto akan disimpan tanpa koordinat.',
							confirmButtonText: 'OK'
						});
					}
					// Capture photo with null location (function should handle this)
					capturePhotoWithLocation(null);
				}
				button.prop('disabled', false).html('<i class="fas fa-camera me-2"></i>Ambil Foto');
			});
	});
	
	// Function to get GPS location
	function getGPSLocation() {
		return new Promise((resolve, reject) => {
			if (!navigator.geolocation) {
				reject(new Error('GPS tidak tersedia'));
				return;
			}
			
			const options = {
				enableHighAccuracy: true,
				timeout: 10000,
				maximumAge: 0
			};
			
			navigator.geolocation.getCurrentPosition(
				position => {
					// Reset error flag on successful GPS
					gpsErrorShown = false;
					gpsErrorShownTime = 0;
					const location = {
						lat: position.coords.latitude,
						lng: position.coords.longitude,
						accuracy: position.coords.accuracy
					};
					currentLocation = location;
					resolve(location);
				},
				error => {
					reject(error);
				},
				options
			);
		});
	}
	
	// Function to capture photo with GPS location
	function capturePhotoWithLocation(location) {
		const video = document.getElementById('my_camera');
		const canvas = document.createElement('canvas');
		canvas.width = video.videoWidth;
		canvas.height = video.videoHeight;
		
		const ctx = canvas.getContext('2d');
		ctx.drawImage(video, 0, 0);
		
		const imageData = canvas.toDataURL('image/jpeg');
		
		// Get location
		let lat = location && location.lat ? location.lat : null;
		let lon = location && location.lng ? location.lng : null;
		
		// Create photo object
		const photoData = {
			file_name: 'photo_' + Date.now() + '.jpg',
			image: imageData,
			lat: lat,
			lon: lon
		};
		
		// Add to photos array
		activityPhotos.push(photoData);
		
		// Update hidden field with JSON
		$('#foto_activity').val(JSON.stringify(activityPhotos));
		
		// Show photo in preview
		addPhotoToPreview(photoData, activityPhotos.length - 1);
		
		// Stop camera and hide camera container
		stopActivityCamera();
		$('#camera-container').hide();
		
		// Show "Buka Kamera" button again for taking more photos
		$('#btn-open-camera').show();
		$('#btn-open-camera').html('<i class="fas fa-camera me-2"></i>Ambil Foto Lagi');
		
		// Show photos preview
		$('#photos-preview-container').show();
	}
	
	// Function to pause camera (keep stream alive)
	function pauseActivityCamera() {
		const video = document.getElementById('my_camera');
		if (video && activityCameraStream) {
			video.pause();
			// Keep the stream alive but pause video
		}
	}
	
	// Function to resume camera
	function resumeActivityCamera() {
		const video = document.getElementById('my_camera');
		if (video && activityCameraStream) {
			video.play();
		}
	}
	
	// Add photo to preview
	function addPhotoToPreview(photoData, index) {
		const photoHtml = `
			<div class="photo-item mb-2" data-index="${index}">
				<div class="card">
					<div class="card-body p-2">
						<div class="row align-items-center">
							<div class="col-3">
								<img src="${photoData.image}" class="img-fluid rounded" alt="Photo ${index + 1}">
							</div>
							<div class="col-6">
								<small class="text-muted d-block">${photoData.file_name}</small>
								<small class="text-muted d-block">Lat: ${photoData.lat || 'N/A'}</small>
								<small class="text-muted d-block">Lon: ${photoData.lon || 'N/A'}</small>
							</div>
							<div class="col-3 text-end">
								<button type="button" class="btn btn-danger btn-sm remove-photo" data-index="${index}">
									<i class="fas fa-trash"></i>
								</button>
							</div>
						</div>
					</div>
				</div>
			</div>
		`;
		
		$('#photos-preview-container').append(photoHtml);
		
		// Handle remove button
		$('#photos-preview-container .remove-photo').off('click').on('click', function() {
			const indexToRemove = $(this).data('index');
			activityPhotos.splice(indexToRemove, 1);
			$('#foto_activity').val(JSON.stringify(activityPhotos));
			
			// Rebuild preview
			$('#photos-preview-container').empty();
			activityPhotos.forEach(function(photo, idx) {
				addPhotoToPreview(photo, idx);
			});
			
			// Update button text if no photos
			if (activityPhotos.length === 0) {
				$('#btn-open-camera').html('<i class="fas fa-camera me-2"></i>Buka Kamera');
				$('#photos-preview-container').hide();
			}
		});
	}
	
	// Reset form
	function resetForm() {
		$('#form-activity')[0].reset();
		$('#qr-scan-result').hide();
		$('#id_patrol').val('');
		$('#scanned_barcode').val('');
		scannedPatrolData = null;
		$('#photos-preview-container').empty();
		$('#photos-preview-container').hide();
		activityPhotos = [];
		$('#camera-container').hide();
		$('#btn-open-camera').show();
		$('#btn-open-camera').html('<i class="fas fa-camera me-2"></i>Buka Kamera');
		$('#foto_activity').val('');
		stopActivityCamera();
		// Clear forced scanner target when form is reset
		forcedScannerTargetId = null;
	}
	
	function addGalleryPhoto(file) {
		return new Promise((resolve, reject) => {
			const reader = new FileReader();
			reader.onload = function(evt) {
				const imageData = evt.target.result;
				const photoData = {
					file_name: file.name || ('gallery_' + Date.now() + '.jpg'),
					image: imageData,
					lat: currentLocation && currentLocation.lat ? currentLocation.lat : (currentLocation && currentLocation.coords ? currentLocation.coords.latitude : null),
					lon: currentLocation && currentLocation.lng ? currentLocation.lng : (currentLocation && currentLocation.coords ? currentLocation.coords.longitude : null)
				};
				activityPhotos.push(photoData);
				$('#foto_activity').val(JSON.stringify(activityPhotos));
				addPhotoToPreview(photoData, activityPhotos.length - 1);
				$('#photos-preview-container').show();
				resolve();
			};
			reader.onerror = reject;
			reader.readAsDataURL(file);
		});
	}
}
