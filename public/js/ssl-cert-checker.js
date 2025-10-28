/**
 * SSL Certificate Auto-Detection and Installation Guide
 *
 * This script automatically detects if the user is accessing the site
 * without a valid SSL certificate and prompts them to install it.
 *
 * Include this in your main layout or app.js
 */

(function () {
	'use strict';

	// Configuration
	const config = {
		certificateUrl: '/rootCA.pem',
		installGuideUrl: '/install-certificate.html',
		checkOnLoad: true,
		showBanner: true,
		autoRedirect: false,
		checkInterval: null, // Set to milliseconds to periodically check (e.g., 60000 for 1 minute)
	};

	/**
	 * Check if the connection is secure
	 */
	function isSecureConnection() {
		return window.location.protocol === 'https:';
	}

	/**
	 * Check if accessing via localhost or IP
	 */
	function isLocalAccess() {
		const hostname = window.location.hostname;
		return (
			hostname === 'localhost' ||
			hostname === '127.0.0.1' ||
			hostname.match(/^192\.168\.\d{1,3}\.\d{1,3}$/) ||
			hostname.match(/^10\.\d{1,3}\.\d{1,3}\.\d{1,3}$/)
		);
	}

	/**
	 * Create and show a warning banner
	 */
	function showSecurityBanner() {
		// Check if banner already exists
		if (document.getElementById('ssl-warning-banner')) {
			return;
		}

		const banner = document.createElement('div');
		banner.id = 'ssl-warning-banner';
		banner.innerHTML = `
            <style>
                #ssl-warning-banner {
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
                    color: #78350f;
                    padding: 16px 20px;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                    z-index: 999999;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    animation: slideDown 0.4s ease-out;
                }

                @keyframes slideDown {
                    from {
                        transform: translateY(-100%);
                        opacity: 0;
                    }
                    to {
                        transform: translateY(0);
                        opacity: 1;
                    }
                }

                #ssl-warning-banner .banner-content {
                    display: flex;
                    align-items: center;
                    gap: 16px;
                    flex: 1;
                }

                #ssl-warning-banner .banner-icon {
                    font-size: 28px;
                    flex-shrink: 0;
                }

                #ssl-warning-banner .banner-text {
                    flex: 1;
                }

                #ssl-warning-banner .banner-title {
                    font-weight: 700;
                    font-size: 16px;
                    margin-bottom: 4px;
                }

                #ssl-warning-banner .banner-subtitle {
                    font-size: 14px;
                    opacity: 0.9;
                }

                #ssl-warning-banner .banner-actions {
                    display: flex;
                    gap: 12px;
                    align-items: center;
                }

                #ssl-warning-banner .banner-btn {
                    padding: 10px 20px;
                    border: none;
                    border-radius: 6px;
                    font-weight: 600;
                    font-size: 14px;
                    cursor: pointer;
                    transition: all 0.3s;
                    text-decoration: none;
                    display: inline-block;
                }

                #ssl-warning-banner .banner-btn-primary {
                    background: #78350f;
                    color: #fef3c7;
                }

                #ssl-warning-banner .banner-btn-primary:hover {
                    background: #92400e;
                    transform: translateY(-2px);
                    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
                }

                #ssl-warning-banner .banner-btn-secondary {
                    background: rgba(255, 255, 255, 0.3);
                    color: #78350f;
                }

                #ssl-warning-banner .banner-btn-secondary:hover {
                    background: rgba(255, 255, 255, 0.5);
                }

                #ssl-warning-banner .banner-close {
                    background: none;
                    border: none;
                    color: #78350f;
                    font-size: 24px;
                    cursor: pointer;
                    padding: 4px 8px;
                    margin-left: 12px;
                    opacity: 0.7;
                    transition: opacity 0.3s;
                }

                #ssl-warning-banner .banner-close:hover {
                    opacity: 1;
                }

                @media (max-width: 768px) {
                    #ssl-warning-banner {
                        flex-direction: column;
                        align-items: stretch;
                        padding: 16px;
                    }

                    #ssl-warning-banner .banner-content {
                        margin-bottom: 12px;
                    }

                    #ssl-warning-banner .banner-actions {
                        width: 100%;
                        flex-direction: column;
                    }

                    #ssl-warning-banner .banner-btn {
                        width: 100%;
                    }

                    #ssl-warning-banner .banner-close {
                        position: absolute;
                        top: 12px;
                        right: 12px;
                        margin: 0;
                    }
                }
            </style>
            <div class="banner-content">
                <div class="banner-icon">🔓</div>
                <div class="banner-text">
                    <div class="banner-title">Unsecured Connection Detected</div>
                    <div class="banner-subtitle">Install the security certificate to access the system safely</div>
                </div>
            </div>
            <div class="banner-actions">
                <button class="banner-btn banner-btn-primary" onclick="window.SSLCertChecker.oneClickInstall()">
                    🚀 One-Click Install
                </button>
                <a href="${config.installGuideUrl}" class="banner-btn banner-btn-secondary" target="_blank">
                    � Manual Guide
                </a>
                <button class="banner-btn banner-btn-secondary" onclick="document.getElementById('ssl-warning-banner').style.display='none'">
                    Remind Later
                </button>
            </div>
            <button class="banner-close" onclick="document.getElementById('ssl-warning-banner').remove()">×</button>
        `;

		document.body.insertBefore(banner, document.body.firstChild);

		// Add padding to body to prevent content from being hidden
		const originalPaddingTop = window.getComputedStyle(document.body).paddingTop;
		const bannerHeight = banner.offsetHeight;
		document.body.style.paddingTop = `${parseInt(originalPaddingTop || 0) + bannerHeight}px`;

		// Store that we've shown the banner in this session
		sessionStorage.setItem('ssl-banner-shown', 'true');
	}

	/**
	 * Show a modal prompt for certificate installation
	 */
	function showCertificateModal() {
		// Create modal only if it doesn't exist
		if (document.getElementById('ssl-cert-modal')) {
			return;
		}

		const modal = document.createElement('div');
		modal.id = 'ssl-cert-modal';
		modal.innerHTML = `
            <style>
                #ssl-cert-modal {
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(0, 0, 0, 0.7);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 9999999;
                    animation: fadeIn 0.3s;
                    padding: 20px;
                }

                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }

                #ssl-cert-modal .modal-content {
                    background: white;
                    border-radius: 12px;
                    padding: 32px;
                    max-width: 500px;
                    width: 100%;
                    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
                    animation: slideUp 0.3s;
                }

                @keyframes slideUp {
                    from {
                        transform: translateY(20px);
                        opacity: 0;
                    }
                    to {
                        transform: translateY(0);
                        opacity: 1;
                    }
                }

                #ssl-cert-modal .modal-header {
                    text-align: center;
                    margin-bottom: 24px;
                }

                #ssl-cert-modal .modal-icon {
                    font-size: 64px;
                    margin-bottom: 16px;
                }

                #ssl-cert-modal .modal-title {
                    font-size: 24px;
                    font-weight: 700;
                    color: #1f2937;
                    margin-bottom: 8px;
                }

                #ssl-cert-modal .modal-subtitle {
                    font-size: 16px;
                    color: #6b7280;
                }

                #ssl-cert-modal .modal-body {
                    margin-bottom: 24px;
                    color: #4b5563;
                    line-height: 1.6;
                }

                #ssl-cert-modal .modal-actions {
                    display: flex;
                    gap: 12px;
                }

                #ssl-cert-modal .modal-btn {
                    flex: 1;
                    padding: 14px 24px;
                    border: none;
                    border-radius: 8px;
                    font-weight: 600;
                    font-size: 16px;
                    cursor: pointer;
                    transition: all 0.3s;
                }

                #ssl-cert-modal .modal-btn-primary {
                    background: #667eea;
                    color: white;
                }

                #ssl-cert-modal .modal-btn-primary:hover {
                    background: #5568d3;
                }

                #ssl-cert-modal .modal-btn-secondary {
                    background: #e5e7eb;
                    color: #374151;
                }

                #ssl-cert-modal .modal-btn-secondary:hover {
                    background: #d1d5db;
                }
            </style>
            <div class="modal-content">
                <div class="modal-header">
                    <div class="modal-icon">🔒</div>
                    <div class="modal-title">Security Certificate Required</div>
                    <div class="modal-subtitle">Install to continue securely</div>
                </div>
                <div class="modal-body">
                    <p>This system requires a security certificate to be installed on your device. This ensures all your data is transmitted securely.</p>
                    <p style="margin-top: 12px;"><strong>Click the button below for automatic installation - it only takes 2 minutes!</strong></p>
                </div>
                <div class="modal-actions">
                    <button class="modal-btn modal-btn-primary" onclick="window.SSLCertChecker.oneClickInstall()">
                        🚀 One-Click Install
                    </button>
                    <button class="modal-btn modal-btn-secondary" onclick="window.open('${config.installGuideUrl}', '_blank')">
                        📋 Manual Guide
                    </button>
                    <button class="modal-btn modal-btn-secondary" onclick="document.getElementById('ssl-cert-modal').remove()">
                        Later
                    </button>
                </div>
            </div>
        `;

		document.body.appendChild(modal);

		// Close on background click
		modal.addEventListener('click', function (e) {
			if (e.target === modal) {
				modal.remove();
			}
		});
	}

	/**
	 * Attempt to redirect to HTTPS
	 */
	function redirectToHttps() {
		if (window.location.protocol === 'http:') {
			const httpsUrl = window.location.href.replace('http://', 'https://');
			window.location.href = httpsUrl;
		}
	}

	/**
	 * One-Click Installation Function
	 */
	function oneClickInstall() {
		const serverUrl = window.location.origin;
		const batchUrl = `${serverUrl}/install-certificate.bat`;

		// Show instructions
		const message = `One-Click Installation:

1. The installer will download now
2. Find "install-certificate.bat" in your Downloads folder
3. Double-click the file
4. Click "Yes" when Windows asks for permission
5. Wait for "Installation Complete" message
6. Restart your browser

Click OK to download the installer.`;

		if (confirm(message)) {
			// Download batch file
			const link = document.createElement('a');
			link.href = batchUrl;
			link.download = 'install-certificate.bat';
			document.body.appendChild(link);
			link.click();
			document.body.removeChild(link);

			// Show follow-up
			setTimeout(() => {
				const followUp = `✅ Installer downloaded!

Next Steps:
1. Open your Downloads folder
2. Double-click "install-certificate.bat"
3. Follow the prompts
4. Restart your browser after installation

The installer will handle everything automatically!`;

				alert(followUp);
			}, 1000);
		}
	}

	/**
	 * Main check function
	 */
	function checkCertificate() {
		// Don't show on HTTPS or if already shown in this session
		if (isSecureConnection()) {
			console.log('✅ Secure connection detected');
			return;
		}

		// Only show for local network access
		if (!isLocalAccess()) {
			console.log('ℹ️ Not a local network access');
			return;
		}

		console.warn('⚠️ Unsecured connection detected on local network');

		// Show banner if enabled and not shown in this session
		if (config.showBanner && !sessionStorage.getItem('ssl-banner-shown')) {
			showSecurityBanner();
		}

		// Auto-redirect if enabled
		if (config.autoRedirect) {
			setTimeout(redirectToHttps, 3000);
		}
	}

	/**
	 * Initialize the certificate checker
	 */
	function init() {
		// Check on load
		if (config.checkOnLoad) {
			// Wait for DOM to be ready
			if (document.readyState === 'loading') {
				document.addEventListener('DOMContentLoaded', checkCertificate);
			} else {
				checkCertificate();
			}
		}

		// Set up periodic checking if configured
		if (config.checkInterval) {
			setInterval(checkCertificate, config.checkInterval);
		}
	}

	// Public API
	window.SSLCertChecker = {
		check: checkCertificate,
		showBanner: showSecurityBanner,
		showModal: showCertificateModal,
		redirectToHttps: redirectToHttps,
		isSecure: isSecureConnection,
		oneClickInstall: oneClickInstall,
		config: config,
	};

	// Auto-initialize
	init();
})();
