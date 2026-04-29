/**
 * generate-pngs.mjs
 * Convierte los SVG fuente en todos los PNG/ICO necesarios.
 * Uso: node scripts/generate-pngs.mjs
 * Requiere: npm install sharp  (en el proyecto o global)
 */
import sharp from 'sharp';
import { readFileSync, writeFileSync } from 'fs';
import { resolve, dirname } from 'path';
import { fileURLToPath } from 'url';

const __dir = dirname(fileURLToPath(import.meta.url));
const root  = resolve(__dir, '..');

const ICON = readFileSync(resolve(__dir, 'src/logo-icon.svg'));
const FULL = readFileSync(resolve(__dir, 'src/logo-full.svg'));

const sizes = [
  // favicon
  { src: ICON, w: 16,  h: 16,  out: 'public/favicon-16x16.png' },
  { src: ICON, w: 32,  h: 32,  out: 'public/favicon-32x32.png' },
  // apple
  { src: ICON, w: 180, h: 180, out: 'public/apple-touch-icon.png' },
  // android / PWA
  { src: ICON, w: 192, h: 192, out: 'public/android-chrome-192x192.png' },
  { src: ICON, w: 512, h: 512, out: 'public/android-chrome-512x512.png' },
  // logo para nav (full: icono + texto)
  { src: FULL, w: 320, h: 60,  out: 'public/images/logo-full.png' },
  // logo icono solo (grande)
  { src: ICON, w: 512, h: 512, out: 'public/images/logo-icon.png' },
];

for (const { src, w, h, out } of sizes) {
  const dest = resolve(root, out);
  await sharp(src).resize(w, h).png().toFile(dest);
  console.log(`✔  ${out}  (${w}×${h})`);
}

// favicon.ico: incrusta 16 y 32 en un .ico simple (PNG embebido en ICO)
// Sharp no genera .ico nativamente; usamos el PNG de 32px renombrado como fallback
// o generamos un ICO básico a mano (4 bytes header + 2 imágenes)
const ico16 = readFileSync(resolve(root, 'public/favicon-16x16.png'));
const ico32 = readFileSync(resolve(root, 'public/favicon-32x32.png'));
writeFileSync(resolve(root, 'public/favicon.ico'), buildIco([ico16, ico32]));
console.log('✔  public/favicon.ico  (16+32)');

console.log('\n✅ Listo. Todos los assets generados.');

/**
 * Construye un .ico mínimo con una o más imágenes PNG embebidas.
 * Spec: https://en.wikipedia.org/wiki/ICO_(file_format)
 */
function buildIco(pngs) {
  const n = pngs.length;
  const headerSize = 6 + 16 * n;          // ICONDIR + n ICONDIRENTRY
  let offset = headerSize;

  // ICONDIR
  const header = Buffer.alloc(6);
  header.writeUInt16LE(0, 0);              // reserved
  header.writeUInt16LE(1, 2);              // type: 1 = ICO
  header.writeUInt16LE(n, 4);              // image count

  const entries = [];
  for (const png of pngs) {
    // Leer dimensiones del chunk IHDR del PNG (bytes 16-23)
    const w = png.readUInt32BE(16);
    const h = png.readUInt32BE(20);

    const entry = Buffer.alloc(16);
    entry.writeUInt8(w >= 256 ? 0 : w, 0);   // width  (0 = 256)
    entry.writeUInt8(h >= 256 ? 0 : h, 1);   // height (0 = 256)
    entry.writeUInt8(0, 2);                   // color count
    entry.writeUInt8(0, 3);                   // reserved
    entry.writeUInt16LE(1, 4);                // color planes
    entry.writeUInt16LE(32, 6);               // bits per pixel
    entry.writeUInt32LE(png.length, 8);       // size
    entry.writeUInt32LE(offset, 12);          // offset
    entries.push(entry);
    offset += png.length;
  }

  return Buffer.concat([header, ...entries, ...pngs]);
}
