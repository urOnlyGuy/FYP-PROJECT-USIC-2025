// service-worker.js
// PWA Service Worker for offline functionality

const CACHE_NAME = 'uptm-info-center-v1';
const urlsToCache = [
  '/',
  '/index.php',
  '/student/dashboard.php',
  '/pages/login.php',
  '/assets/icons/icon-192x192.png',
  '/assets/icons/icon-512x512.png',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
  'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css'
];

// Install event - cache resources
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('Opened cache');
        return cache.addAll(urlsToCache);
      })
      .catch((error) => {
        console.log('Cache installation failed:', error);
      })
  );
  self.skipWaiting();
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME) {
            console.log('Deleting old cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
  self.clients.claim();
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', (event) => {
    // Skip chrome extensions and non-http(s) requests
    if (!event.request.url.startsWith('http')) {
      return;
    }
  
    event.respondWith(
      caches.match(event.request)
        .then((response) => {
          // Cache hit - return response
          if (response) {
            return response;
          }
  
          // Clone the request
          const fetchRequest = event.request.clone();
  
          return fetch(fetchRequest).then((response) => {
            // Check if valid response
            if (!response || response.status !== 200 || response.type !== 'basic') {
              return response;
            }
  
            // Clone the response
            const responseToCache = response.clone();
  
            // Cache the fetched response
            caches.open(CACHE_NAME)
              .then((cache) => {
                // Only cache http(s) requests
                if (event.request.url.startsWith('http')) {
                  cache.put(event.request, responseToCache);
                }
              });

          return response;
        }).catch(() => {
          // If both cache and network fail, show offline page
          return caches.match('/offline.html');
        });
      })
  );
});

// Push notification event (for future OneSignal integration)
self.addEventListener('push', (event) => {
  const options = {
    body: event.data ? event.data.text() : 'New announcement available',
    icon: '/assets/icons/icon-192x192.png',
    badge: '/assets/icons/icon-72x72.png',
    vibrate: [100, 50, 100],
    data: {
      dateOfArrival: Date.now(),
      primaryKey: 1
    }
  };

  event.waitUntil(
    self.registration.showNotification('USIC-UPTM Info Center', options)
  );
});

// Notification click event
self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  event.waitUntil(
    clients.openWindow('/student/dashboard.php')
  );
});