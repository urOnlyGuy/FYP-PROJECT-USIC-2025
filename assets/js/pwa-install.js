// pwa-install.js
// Handle PWA installation prompt

let deferredPrompt;
let installButton;

// Check if app is already installed
function isAppInstalled() {
  return window.matchMedia('(display-mode: standalone)').matches || 
         window.navigator.standalone === true;
}

// Initialize PWA
window.addEventListener('load', () => {
  // Register service worker
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/service-worker.js')
      .then((registration) => {
        console.log('ServiceWorker registered:', registration.scope);
      })
      .catch((error) => {
        console.log('ServiceWorker registration failed:', error);
      });
  }

  // Create install button if not installed
  if (!isAppInstalled()) {
    createInstallButton();
  } else {
    console.log('App is already installed');
  }
});

// Listen for beforeinstallprompt event
window.addEventListener('beforeinstallprompt', (e) => {
  // Prevent default browser install prompt
  e.preventDefault();
  
  // Store the event for later use
  deferredPrompt = e;
  
  // Show custom install button
  showInstallButton();
});

// Create install button
function createInstallButton() {
  // Check if button already exists
  if (document.getElementById('pwa-install-btn')) return;

  // Create button container
  const container = document.createElement('div');
  container.id = 'pwa-install-container';
  container.style.cssText = `
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 1000;
    display: none;
  `;

  // Create button
  installButton = document.createElement('button');
  installButton.id = 'pwa-install-btn';
  installButton.innerHTML = `
    <i class="bi bi-download"></i> Install App
  `;
  installButton.className = 'btn btn-primary btn-lg shadow';
  installButton.style.cssText = `
    border-radius: 50px;
    padding: 12px 24px;
  `;

  // Add click handler
  installButton.addEventListener('click', handleInstallClick);

  // Add dismiss button
  const dismissBtn = document.createElement('button');
  dismissBtn.innerHTML = '×';
  dismissBtn.className = 'btn btn-sm btn-light ms-2';
  dismissBtn.style.cssText = `
    border-radius: 50%;
    width: 30px;
    height: 30px;
    padding: 0;
  `;
  dismissBtn.addEventListener('click', hideInstallButton);

  container.appendChild(installButton);
  container.appendChild(dismissBtn);
  document.body.appendChild(container);
}

// Show install button
function showInstallButton() {
  const container = document.getElementById('pwa-install-container');
  if (container) {
    container.style.display = 'block';
  }
}

// Hide install button
function hideInstallButton() {
  const container = document.getElementById('pwa-install-container');
  if (container) {
    container.style.display = 'none';
  }
  // Remember user dismissed the prompt
  localStorage.setItem('pwa-install-dismissed', 'true');
}

// Handle install button click
async function handleInstallClick() {
  if (!deferredPrompt) {
    console.log('Install prompt not available');
    return;
  }

  // Show install prompt
  deferredPrompt.prompt();

  // Wait for user response
  const { outcome } = await deferredPrompt.userChoice;
  console.log(`User response: ${outcome}`);

  if (outcome === 'accepted') {
    console.log('App installed successfully');
    hideInstallButton();
  }

  // Clear the prompt
  deferredPrompt = null;
}

// Listen for successful app install
window.addEventListener('appinstalled', () => {
  console.log('App installed');
  hideInstallButton();
  
  // Show success message
  if (typeof bootstrap !== 'undefined') {
    const toast = document.createElement('div');
    toast.className = 'toast position-fixed bottom-0 end-0 m-3';
    toast.innerHTML = `
      <div class="toast-header">
        <strong class="me-auto">✅ App Installed</strong>
        <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
      </div>
      <div class="toast-body">
        UPTM Info Center has been added to your home screen!
      </div>
    `;
    document.body.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
  }
});

// Check if dismissed before
if (localStorage.getItem('pwa-install-dismissed') !== 'true') {
  // Show button after 5 seconds if not dismissed
  setTimeout(() => {
    if (!isAppInstalled() && deferredPrompt) {
      showInstallButton();
    }
  }, 5000);
}