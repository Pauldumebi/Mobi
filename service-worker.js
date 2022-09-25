const appName = "9ijakids-mobi";
const assets = [
    "/",
    // "index.html",
    // "info.html",
    "style.css",
    // "app.js",
    "images/background.png",
    "images/logo.png",
    "images/glo.png",
    "images/mtn.svg",
];

self.addEventListener("install", installEvent => {
    installEvent.waitUntil(
        caches.open(appName).then(cache => {
            cache.addAll(assets)
        })
    )
});

self.addEventListener("fetch", fetchEvent => {
    fetchEvent.respondWith(
        caches.match(fetchEvent.request).then(res => {
            return res || fetch(fetchEvent.request)
        })
    )
});