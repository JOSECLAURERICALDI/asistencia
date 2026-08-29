const http = require('http');
const fs = require('fs');
const path = require('path');

const PORT = 9000;
const PUBLIC = path.join(__dirname, 'dist', 'spa');

const MIME = {
  '.html': 'text/html; charset=utf-8',
  '.js': 'text/javascript; charset=utf-8',
  '.css': 'text/css; charset=utf-8',
  '.json': 'application/json',
  '.png': 'image/png',
  '.jpg': 'image/jpeg',
  '.ico': 'image/x-icon',
};

http.createServer((req, res) => {
  let url = req.url.split('?')[0].split('#')[0];
  let filePath = path.join(PUBLIC, url);

  if (fs.existsSync(filePath) && fs.statSync(filePath).isFile()) {
    res.writeHead(200, {
      'Content-Type': MIME[path.extname(filePath)] || 'application/octet-stream',
      'Cache-Control': 'no-cache, no-store, must-revalidate',
      'Access-Control-Allow-Origin': '*',
    });
    return fs.createReadStream(filePath).pipe(res);
  }

  // Fallback to index.html for SPA routes (only if no extension)
  if (!path.extname(url)) {
    const indexPath = path.join(PUBLIC, 'index.html');
    if (fs.existsSync(indexPath)) {
      res.writeHead(200, {
        'Content-Type': 'text/html; charset=utf-8',
        'Cache-Control': 'no-cache, no-store, must-revalidate',
        'Access-Control-Allow-Origin': '*',
      });
      return fs.createReadStream(indexPath).pipe(res);
    }
  }

  // 404 for missing static assets
  res.writeHead(404, { 'Content-Type': 'text/plain' });
  res.end('404 Not Found');
}).listen(PORT, () => {
  console.log(`SPA Server running at http://localhost:${PORT}`);
});
