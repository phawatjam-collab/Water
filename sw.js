/**
 * Service Worker: ระบบประปาหมู่บ้านวังยาง (PWA)
 * ช่วยให้สามารถติดตั้งแอปบนหน้าจอมือถือ (Add to Home Screen) และรองรับการทำงานขณะสัญญาณเน็ตหลุด
 */

const CACHE_NAME = 'wangyang-pwa-v1';
const STATIC_ASSETS = [
  './',
  './manifest.json',
  './style.css',
  './assets/icon-192.png',
  './assets/icon-512.png'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(STATIC_ASSETS);
    }).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.map((key) => {
          if (key !== CACHE_NAME) {
            return caches.delete(key);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// Network-First with Cache Fallback for dynamic requests
self.addEventListener('fetch', (event) => {
  if (event.request.method !== 'GET') return;

  const url = new URL(event.request.url);

  // Static assets: Stale-While-Revalidate or Cache-First
  if (url.pathname.endsWith('.css') || url.pathname.endsWith('.png') || url.pathname.endsWith('.json') || url.pathname.endsWith('.svg')) {
    event.respondWith(
      caches.match(event.request).then((cached) => {
        if (cached) return cached;
        return fetch(event.request).then((networkRes) => {
          if (networkRes.status === 200) {
            const resClone = networkRes.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(event.request, resClone));
          }
          return networkRes;
        });
      })
    );
    return;
  }

  // HTML and API: Network First, fallback to cached or offline
  event.respondWith(
    fetch(event.request)
      .then((networkRes) => {
        return networkRes;
      })
      .catch(async () => {
        const cached = await caches.match(event.request);
        if (cached) return cached;
        // If navigation request failed, try to return root cache
        if (event.request.mode === 'navigate') {
          return caches.match('./meter_reading') || caches.match('./') || new Response(
            `<div style="font-family: sans-serif; text-align: center; padding: 40px;">
              <h2>📴 ออฟไลน์</h2>
              <p>สัญญาณอินเทอร์เน็ตหลุดชั่วคราว ข้อมูลที่บันทึกล่าสุดจะถูกเก็บไว้ กรุณาลองใหม่อีกครั้งเมื่อมีสัญญาณ</p>
            </div>`,
            { headers: { 'Content-Type': 'text/html; charset=utf-8' } }
          );
        }
      })
  );
});
