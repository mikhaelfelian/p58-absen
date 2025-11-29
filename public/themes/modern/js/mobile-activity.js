/**
*
*	App Name	: Aplikasi Absensi Online	
*
*	Author		: Agus Prawoto Hadi
*
*	Website		: https://jagowebdev.com
*
*	Year		: 2024
*
*/

// Mobile Activity Home JavaScript
// This file contains all JavaScript related to mobile-activity-home.php

(function() {
	// Check if we're on the mobile-activity-home page
	// Only run if required DOM elements exist
	if (!document.getElementById('company-detecting') && !document.getElementById('manual-company-select')) {
		return; // Not on mobile-activity-home page
	}

	// Track current step
	var currentStep = 1;

	// Function to calculate distance between two coordinates
	function getDistance(lat1, lon1, lat2, lon2) {
		const R = 6371; // Radius of Earth in kilometers
		const dLat = (lat2 - lat1) * Math.PI / 180;
		const dLon = (lon2 - lon1) * Math.PI / 180;
		const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
			Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
			Math.sin(dLon / 2) * Math.sin(dLon / 2);
		const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
		const distance = R * c;
		return distance; // in kilometers
	}

	// Manual Company Selection Functions (attached to window for global access)
	window.populateManualCompanyOptions = function () {
		var select = document.getElementById('manual-company-select');
		if (!select) {
			console.error('Manual company select element not found');
			return;
		}
		
		if (typeof assignedCompanies === 'undefined') {
			console.error('assignedCompanies is undefined');
			return;
		}
		
		if (!Array.isArray(assignedCompanies)) {
			console.error('assignedCompanies is not an array:', typeof assignedCompanies);
			return;
		}
		
		if (assignedCompanies.length === 0) {
			console.warn('No companies assigned to user');
			return;
		}
		
		console.log('Populating dropdown with', assignedCompanies.length, 'companies');
		
		// Clear existing options except the first one
		select.innerHTML = '<option value="">-- Pilih Perusahaan --</option>';
		
		// Populate with assigned companies
		for (var i = 0; i < assignedCompanies.length; i++) {
			var company = assignedCompanies[i];
			
			// Handle both object and array formats - access properties directly
			var companyId = null;
			var companyName = null;
			
			// Try to get id_company
			if (company.id_company !== undefined && company.id_company !== null) {
				companyId = company.id_company;
			}
			
			// Try to get nama_company
			if (company.nama_company !== undefined && company.nama_company !== null) {
				companyName = company.nama_company;
			}
			
			if (!companyId) {
				console.warn('Company at index', i, 'has no id_company. Full object:', JSON.stringify(company));
				continue;
			}
			
			var option = document.createElement('option');
			option.value = companyId;
			option.textContent = companyName || 'Perusahaan #' + companyId;
			select.appendChild(option);
			console.log('Added company option:', companyId, '-', companyName);
		}
		
		console.log('Dropdown populated with', select.options.length - 1, 'companies');
	};

	window.showManualCompanySelector = function () {
		var manualSelect = document.getElementById('manual-company-select');
		if (manualSelect) {
			window.populateManualCompanyOptions();
		}

		var manualTabTrigger = document.querySelector('[data-bs-target="#manual-detect-tab"]');
		if (manualTabTrigger && typeof bootstrap !== 'undefined') {
			var tab = bootstrap.Tab.getOrCreateInstance(manualTabTrigger);
			tab.show();
		}
	};

	window.selectCompanyManually = function (companyId) {
		console.log('selectCompanyManually called with companyId:', companyId);
		
		if (!companyId) {
			console.error('No company ID provided');
			if (typeof Swal !== 'undefined') {
				Swal.fire({
					icon: 'error',
					title: 'Error',
					text: 'ID perusahaan tidak valid.',
					confirmButtonText: 'OK'
				});
			}
			return;
		}
		
		if (typeof assignedCompanies === 'undefined') {
			console.error('assignedCompanies is undefined');
			if (typeof Swal !== 'undefined') {
				Swal.fire({
					icon: 'error',
					title: 'Error',
					text: 'Data perusahaan tidak tersedia. Silakan refresh halaman.',
					confirmButtonText: 'OK'
				});
			}
			return;
		}

		// Find the selected company
		var selectedCompany = null;
		for (var i = 0; i < assignedCompanies.length; i++) {
			var company = assignedCompanies[i];
			// Handle both object and array formats
			var companyIdToCompare = company.id_company;
			if (companyIdToCompare == companyId) {
				selectedCompany = company;
				break;
			}
		}

		if (!selectedCompany) {
			console.error('Company not found for ID:', companyId);
			if (typeof Swal !== 'undefined') {
				Swal.fire({
					icon: 'error',
					title: 'Error',
					text: 'Perusahaan tidak ditemukan.',
					confirmButtonText: 'OK'
				});
			}
			return;
		}
		
		console.log('Selected company:', selectedCompany);

		var companyNotFoundEl = document.getElementById('company-not-found');
		if (companyNotFoundEl) {
			companyNotFoundEl.style.display = 'none';
		}

		// Show company detected with manual badge
		var companyDetectedEl = document.getElementById('company-detected');
		if (companyDetectedEl) {
			companyDetectedEl.style.display = 'block';
		}
		
		var detectedCompanyNameEl = document.getElementById('detected-company-name');
		if (detectedCompanyNameEl) {
			detectedCompanyNameEl.innerHTML = selectedCompany.nama_company + ' <span class="badge bg-warning text-dark">Manual</span>';
		}
		
		var detectedCompanyDistanceEl = document.getElementById('detected-company-distance');
		if (detectedCompanyDistanceEl) {
			detectedCompanyDistanceEl.textContent = 'Dipilih secara manual';
		}
		
		var settingBadge = document.getElementById('detected-company-setting');
		if (settingBadge) {
			if (selectedCompany.nama_setting) {
				settingBadge.textContent = selectedCompany.nama_setting;
				settingBadge.style.display = 'inline-block';
			} else {
				settingBadge.style.display = 'none';
			}
		}

		// Set hidden field
		var idCompanyEl = document.getElementById('id_company');
		if (idCompanyEl) {
			idCompanyEl.value = selectedCompany.id_company;
		}

		// Store selected company globally
		window.nearestCompany = selectedCompany;

		// Dispatch event for main-mobile.js to set currentCompanyId
		if (typeof window.dispatchEvent !== 'undefined') {
			const event = new CustomEvent('companyDetected', {
				detail: { companyId: selectedCompany.id_company, company: selectedCompany }
			});
			window.dispatchEvent(event);
		}

		// Check if patrol is required
		var isPatrolRequired = selectedCompany.isPatrolRequired === 1;

		// If patrol is NOT required, skip step 1 and go to step 2
		if (!isPatrolRequired) {
			// Hide step 1, show step 2
			if (typeof jQuery !== 'undefined') {
				jQuery('#step-1').hide();
				jQuery('#step-2').show();
			}
			currentStep = 2;
		} else {
			// Show step 1 for QR scanning
			if (typeof jQuery !== 'undefined') {
				jQuery('#step-1').show();
				jQuery('#step-2').hide();
			}
			currentStep = 1;
			
			// Auto-start QR scanner when patrol is required
			// Wait a bit for DOM to be ready and then start scanner
			setTimeout(function() {
				if (typeof window.initPatrolFunctionality !== 'undefined' && typeof jQuery !== 'undefined') {
					// Check if ensureInlineScanner function exists (from main-mobile.js)
					if (typeof window.ensureInlineScanner === 'function') {
						window.ensureInlineScanner(true);
					} else {
						// Fallback: trigger the button click programmatically
						var btnStartQr = document.getElementById('btn-start-inline-qr');
						if (btnStartQr) {
							btnStartQr.click();
						}
					}
				}
			}, 500);
		}
		
		// Switch to auto-detect tab to show the result
		var autoTabTrigger = document.querySelector('[data-bs-target="#auto-detect-tab"]');
		if (autoTabTrigger && typeof bootstrap !== 'undefined') {
			var tab = bootstrap.Tab.getOrCreateInstance(autoTabTrigger);
			tab.show();
		}
		
		console.log('Company selection completed successfully');
		
		// Show success message
		if (typeof Swal !== 'undefined') {
			Swal.fire({
				icon: 'success',
				title: 'Perusahaan Dipilih',
				text: 'Perusahaan ' + selectedCompany.nama_company + ' telah dipilih.',
				confirmButtonText: 'OK',
				timer: 2000,
				timerProgressBar: true
			});
		}
	}

	// Auto-detect company based on GPS
	if (navigator.geolocation && typeof assignedCompanies !== 'undefined') {
		navigator.geolocation.getCurrentPosition(function (position) {
			var userLat = position.coords.latitude;
			var userLon = position.coords.longitude;

			// Store location globally
			window.currentLocation = {
				coords: {
					latitude: userLat,
					longitude: userLon
				}
			};

			// Find nearest company within radius
			var nearestCompany = null;
			var minDistance = Infinity;

			for (var i = 0; i < assignedCompanies.length; i++) {
				var company = assignedCompanies[i];

				// Validate coordinates - skip if invalid
				var companyLat = parseFloat(company.latitude);
				var companyLon = parseFloat(company.longitude);

				if (isNaN(companyLat) || isNaN(companyLon) || companyLat === 0 || companyLon === 0) {
					// Skip companies with invalid coordinates
					continue;
				}

				// Validate and parse radius_nilai with default fallback
				var radiusNilai = parseFloat(company.radius_nilai);
				if (isNaN(radiusNilai) || radiusNilai === null || radiusNilai === undefined) {
					// Default to 1000km if radius is missing
					radiusNilai = 1000;
				}

				// Validate radius_satuan with default fallback
				var radiusSatuan = company.radius_satuan;
				if (!radiusSatuan || (radiusSatuan !== 'm' && radiusSatuan !== 'km')) {
					// Default to 'km' if unit is missing or invalid
					radiusSatuan = 'km';
				}

				// Convert radius to kilometers
				var radiusKm = radiusSatuan === 'm' ? radiusNilai / 1000 : radiusNilai;

				// Calculate distance
				var distance = getDistance(userLat, userLon, companyLat, companyLon);

				// Check if within radius
				if (distance <= radiusKm && distance < minDistance) {
					minDistance = distance;
					nearestCompany = company;
				}
			}

			// Hide detecting spinner
			var companyDetectingEl = document.getElementById('company-detecting');
			if (companyDetectingEl) {
				companyDetectingEl.style.display = 'none';
			}

			if (nearestCompany) {
				// Company detected!
				var companyDetectedEl = document.getElementById('company-detected');
				if (companyDetectedEl) {
					companyDetectedEl.style.display = 'block';
				}
				
				var detectedCompanyNameEl = document.getElementById('detected-company-name');
				if (detectedCompanyNameEl) {
					detectedCompanyNameEl.textContent = nearestCompany.nama_company;
				}
				
				var settingBadge = document.getElementById('detected-company-setting');
				if (settingBadge) {
					if (nearestCompany.nama_setting) {
						settingBadge.textContent = nearestCompany.nama_setting;
						settingBadge.style.display = 'inline-block';
					} else {
						settingBadge.style.display = 'none';
					}
				}

				var distanceText = minDistance < 1
					? Math.round(minDistance * 1000) + ' meter dari lokasi company'
					: minDistance.toFixed(2) + ' km dari lokasi company';
				
				var detectedCompanyDistanceEl = document.getElementById('detected-company-distance');
				if (detectedCompanyDistanceEl) {
					detectedCompanyDistanceEl.textContent = 'Anda berada ' + distanceText;
				}

				// Set hidden field
				var idCompanyEl = document.getElementById('id_company');
				if (idCompanyEl) {
					idCompanyEl.value = nearestCompany.id_company;
				}

				// Store nearest company globally
				window.nearestCompany = nearestCompany;

				// Dispatch event for main-mobile.js to set currentCompanyId
				if (typeof window.dispatchEvent !== 'undefined') {
					const event = new CustomEvent('companyDetected', {
						detail: { companyId: nearestCompany.id_company, company: nearestCompany }
					});
					window.dispatchEvent(event);
				}

				// Check if patrol is required
				// isPatrolRequired is already set on backend (company mode + user requirement)
				var isPatrolRequired = nearestCompany.isPatrolRequired === 1;

				// If patrol is NOT required, skip step 1 and go to step 2
				if (!isPatrolRequired) {
					// Hide step 1, show step 2
					if (typeof jQuery !== 'undefined') {
						jQuery('#step-1').hide();
						jQuery('#step-2').show();
					}
					currentStep = 2;
				} else {
					// Show step 1 for QR scanning
					if (typeof jQuery !== 'undefined') {
						jQuery('#step-1').show();
						jQuery('#step-2').hide();
					}
					currentStep = 1;
					
					// Auto-start QR scanner when patrol is required
					// Wait a bit for DOM to be ready and then start scanner
					setTimeout(function() {
						if (typeof window.initPatrolFunctionality !== 'undefined' && typeof jQuery !== 'undefined') {
							// Check if ensureInlineScanner function exists (from main-mobile.js)
							if (typeof window.ensureInlineScanner === 'function') {
								window.ensureInlineScanner(true);
							} else {
								// Fallback: trigger the button click programmatically
								var btnStartQr = document.getElementById('btn-start-inline-qr');
								if (btnStartQr) {
									btnStartQr.click();
								}
							}
						}
					}, 500);
				}
			} else {
				// No company found within radius
				var companyNotFoundEl = document.getElementById('company-not-found');
				if (companyNotFoundEl) {
					companyNotFoundEl.style.display = 'block';
				}
				
				if (window.showManualCompanySelector) {
					window.showManualCompanySelector();
				}

				// Disable submit button
				var submitBtn = document.getElementById('btn-submit');
				if (submitBtn) {
					submitBtn.disabled = true;
					submitBtn.style.opacity = '0.5';
				}
			}
		}, function (error) {
			// GPS error
			var companyDetectingEl = document.getElementById('company-detecting');
			if (companyDetectingEl) {
				companyDetectingEl.style.display = 'none';
			}
			
			var companyNotFoundEl = document.getElementById('company-not-found');
			if (companyNotFoundEl) {
				companyNotFoundEl.style.display = 'block';
				var alertDiv = companyNotFoundEl.querySelector('.alert');
				if (alertDiv) {
					alertDiv.innerHTML =
						'<i class="fas fa-exclamation-triangle me-2"></i>' +
						'<strong>Gagal mendapatkan lokasi GPS!</strong><br>' +
						'<small>Pastikan GPS/Location diaktifkan di browser Anda.</small>';
				}
			}
			
			if (window.showManualCompanySelector) {
				window.showManualCompanySelector();
			}
		}, {
			enableHighAccuracy: true,
			timeout: 10000,
			maximumAge: 0
		});
	}

	// Wait for jQuery to be available
	(function checkJQuery() {
		if (typeof jQuery === 'undefined') {
			setTimeout(checkJQuery, 50);
			return;
		}

		// jQuery is loaded, now run our code
		var currentLocation = null;

		// Get current location
		if (navigator.geolocation) {
			navigator.geolocation.getCurrentPosition(function (position) {
				currentLocation = {
					coords: {
						latitude: position.coords.latitude,
						longitude: position.coords.longitude
					}
				};
			}, function (error) {
				// Error getting location - silently fail
			});
		}

		// Camera handling is done in main-mobile.js
		// DO NOT add camera code here to avoid conflicts

		// QR Scanner button click handler
		var btnScanQr = document.getElementById('btn-scan-qr');
		if (btnScanQr) {
			jQuery(btnScanQr).on('click', function () {
				jQuery('#qrScannerModal').modal('show');
				// QR scanner will be initialized by main-mobile.js modal events
			});
		}

		// Manual Company Selection Event Handlers
		jQuery(document).ready(function() {
			// Populate manual company options on page load
			if (window.populateManualCompanyOptions) {
				window.populateManualCompanyOptions();
			}
			
			// Tab change event listener - populate dropdown when manual tab is shown
			var companyDetectionTabs = jQuery('#company-detection-tabs button[data-bs-toggle="pill"]');
			if (companyDetectionTabs.length) {
				companyDetectionTabs.on('shown.bs.tab', function (e) {
					var targetTab = jQuery(e.target).data('bs-target');
					console.log('Tab shown:', targetTab);
					if (targetTab === '#manual-detect-tab') {
						// Manual tab is now active, hide loading spinner and populate the dropdown
						jQuery('#company-detecting').hide();
						console.log('Manual tab shown, populating dropdown...');
						if (window.populateManualCompanyOptions) {
							window.populateManualCompanyOptions();
						} else {
							console.error('populateManualCompanyOptions function not found');
						}
					}
				});
			}
			
			// Also handle direct click on manual tab button (fallback)
			var manualTabBtn = jQuery('button[data-bs-target="#manual-detect-tab"]');
			if (manualTabBtn.length) {
				manualTabBtn.on('click', function() {
					console.log('Manual tab button clicked');
					// Hide loading spinner immediately when user clicks manual tab
					jQuery('#company-detecting').hide();
					// Small delay to ensure tab is shown before populating
					setTimeout(function() {
						console.log('Populating dropdown after tab click...');
						if (window.populateManualCompanyOptions) {
							window.populateManualCompanyOptions();
						} else {
							console.error('populateManualCompanyOptions function not found');
						}
					}, 100);
				});
			}
			
			// Ensure tabs are always clickable - prevent any overlay from blocking
			var companyTabsButtons = jQuery('#company-detection-tabs button');
			if (companyTabsButtons.length) {
				companyTabsButtons.css({
					'pointer-events': 'auto',
					'z-index': '1000',
					'position': 'relative'
				});
			}
			
			// Also populate on page load if manual tab is already visible (for debugging)
			setTimeout(function() {
				var manualTab = jQuery('#manual-detect-tab');
				if (manualTab.length && (manualTab.hasClass('active') || manualTab.hasClass('show'))) {
					console.log('Manual tab is active on page load, populating...');
					if (window.populateManualCompanyOptions) {
						window.populateManualCompanyOptions();
					}
				}
			}, 500);
			
			// Manual company selection dropdown change handler
			var manualCompanySelect = jQuery('#manual-company-select');
			if (manualCompanySelect.length) {
				manualCompanySelect.on('change', function () {
					var selectedValue = jQuery(this).val();
					var confirmBtn = jQuery('#btn-confirm-manual-company');
					console.log('Dropdown changed, selected value:', selectedValue);
					if (selectedValue && selectedValue !== '') {
						confirmBtn.prop('disabled', false);
						console.log('Button enabled');
					} else {
						confirmBtn.prop('disabled', true);
						console.log('Button disabled');
					}
				});
			}
			
			// Fallback: If dropdown has no options but companies exist, try to populate on focus
			if (manualCompanySelect.length) {
				manualCompanySelect.on('focus', function() {
					var select = jQuery(this);
					if (select.find('option').length <= 1 && typeof window.populateManualCompanyOptions === 'function') {
						console.log('Dropdown focused but empty, attempting to populate...');
						window.populateManualCompanyOptions();
					}
				});
			}
			
			// Manual company confirmation button click handler
			var btnConfirmManualCompany = jQuery('#btn-confirm-manual-company');
			if (btnConfirmManualCompany.length) {
				btnConfirmManualCompany.on('click', function () {
					var selectedCompanyId = jQuery('#manual-company-select').val();
					console.log('Button clicked, selected company ID:', selectedCompanyId);
					
					if (!selectedCompanyId || selectedCompanyId === '') {
						// Show error message if no company selected
						if (typeof Swal !== 'undefined') {
							Swal.fire({
								icon: 'warning',
								title: 'Pilih Perusahaan',
								text: 'Silakan pilih perusahaan terlebih dahulu dari dropdown.',
								confirmButtonText: 'OK'
							});
						} else {
							alert('Silakan pilih perusahaan terlebih dahulu dari dropdown.');
						}
						return;
					}
					
					if (window.selectCompanyManually) {
						window.selectCompanyManually(selectedCompanyId);
					} else {
						console.error('selectCompanyManually function not found');
						if (typeof Swal !== 'undefined') {
							Swal.fire({
								icon: 'error',
								title: 'Error',
								text: 'Fungsi pemilihan perusahaan tidak tersedia. Silakan refresh halaman.',
								confirmButtonText: 'OK'
							});
						}
					}
				});
			}
		});

		function showAlert(type, message) {
			var alertClass = type == 'error' ? 'alert-danger' : 'alert-success';
			var icon = type == 'error' ? 'fa-exclamation-circle' : 'fa-check-circle';

			var html = '<div class="alert ' + alertClass + ' alert-dismissible fade show" role="alert">';
			html += '<i class="fas ' + icon + ' me-2"></i>';
			html += Array.isArray(message) ? message.join('<br>') : message;
			html += '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
			html += '</div>';

			var alertContainer = jQuery('#alert-container');
			if (alertContainer.length) {
				alertContainer.html(html);
			}
		}

		// Update live time
		setInterval(function () {
			var now = new Date();
			var hours = String(now.getHours()).padStart(2, '0');
			var minutes = String(now.getMinutes()).padStart(2, '0');
			var seconds = String(now.getSeconds()).padStart(2, '0');
			var liveJam = document.getElementById('live-jam');
			if (liveJam) {
				liveJam.textContent = hours + ':' + minutes + ':' + seconds;
			}
		}, 1000);
	})();
})();
