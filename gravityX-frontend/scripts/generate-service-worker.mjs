import { createHash } from 'node:crypto';
import { readdir, readFile, writeFile } from 'node:fs/promises';
import { resolve, relative, sep } from 'node:path';

const outputDirectory = resolve('dist');
const serviceWorkerName = 'sw.js';

const collectFiles = async (directory) => {
  const entries = await readdir(directory, { withFileTypes: true });
  const files = await Promise.all(entries.map(async (entry) => {
    const path = resolve(directory, entry.name);

    if (entry.isDirectory()) {
      return collectFiles(path);
    }

    return entry.isFile() ? [path] : [];
  }));

  return files.flat();
};

const files = (await collectFiles(outputDirectory))
  .filter((file) => relative(outputDirectory, file) !== serviceWorkerName)
  .sort();

const precacheUrls = files.map((file) => `/${relative(outputDirectory, file).split(sep).join('/')}`);
const cacheFingerprint = createHash('sha256');

for (const file of files) {
  cacheFingerprint.update(relative(outputDirectory, file));
  cacheFingerprint.update(await readFile(file));
}

const cacheName = `gravityx-app-shell-${Date.now()}-${cacheFingerprint.digest('hex').slice(0, 16)}`;
const serviceWorker = `const CACHE_NAME = ${JSON.stringify(cacheName)};
const CACHE_PREFIX = 'gravityx-app-shell-';
// Remove caches created before the GravityX rename when this worker activates.
const LEGACY_CACHE_PREFIX = 'gravityly-app-shell-';
const PRECACHE_URLS = ${JSON.stringify(precacheUrls, null, 2)};
const INDEX_URL = '/index.html';

const getCurrentCache = () => caches.open(CACHE_NAME);

const getCachedAsset = async (request) => {
  const currentCache = await getCurrentCache();
  const currentResponse = await currentCache.match(request);

  if (currentResponse) return currentResponse;

  const cacheNames = await caches.keys();

  for (const cacheName of cacheNames) {
    if (!cacheName.startsWith(CACHE_PREFIX) || cacheName === CACHE_NAME) continue;

    const cachedResponse = await (await caches.open(cacheName)).match(request);

    if (cachedResponse) return cachedResponse;
  }

  return undefined;
};

self.addEventListener('install', (event) => {
  event.waitUntil(getCurrentCache().then((cache) => cache.addAll(PRECACHE_URLS)));
});

self.addEventListener('activate', (event) => {
  event.waitUntil((async () => {
    const allCacheNames = await caches.keys();
    const cacheNames = allCacheNames
      .filter((name) => name.startsWith(CACHE_PREFIX))
      .sort();
    const staleCacheNames = cacheNames.slice(0, -2);
    const legacyCacheNames = allCacheNames
      .filter((name) => name.startsWith(LEGACY_CACHE_PREFIX));

    await Promise.all([...staleCacheNames, ...legacyCacheNames]
      .map((name) => caches.delete(name)));
    await self.clients.claim();
  })());
});

self.addEventListener('message', (event) => {
  if (event.data?.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});

self.addEventListener('fetch', (event) => {
  const { request } = event;

  if (request.method !== 'GET') return;

  const url = new URL(request.url);

  if (url.origin !== self.location.origin || url.pathname.startsWith('/api/')) return;

  if (request.mode === 'navigate') {
    event.respondWith(fetch(request).catch(async () => {
      const cache = await getCurrentCache();
      return cache.match(INDEX_URL);
    }));
    return;
  }

  if (!PRECACHE_URLS.includes(url.pathname) && !url.pathname.startsWith('/assets/')) return;

  event.respondWith(getCachedAsset(request).then((cachedResponse) => (
    cachedResponse || fetch(request)
  )));
});
`;

await writeFile(resolve(outputDirectory, serviceWorkerName), serviceWorker);
