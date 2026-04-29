#!/bin/bash
# ─────────────────────────────────────────────────────────────
# generate-assets.sh — genera logos y favicons desde los SVGs
#
# Prerequisito (Mac):
#   brew install librsvg          # para rsvg-convert
#   brew install imagemagick      # para convert (favicon.ico)
#
# Uso:
#   chmod +x scripts/generate-assets.sh
#   ./scripts/generate-assets.sh
# ─────────────────────────────────────────────────────────────
set -euo pipefail

SRC_ICON="scripts/src/logo-icon.svg"        # solo el ícono (sin texto)
SRC_FULL="scripts/src/logo-full.svg"        # ícono + "VIDA ISLEÑA"
OUT_PUB="public"
OUT_IMG="public/images"

mkdir -p "$OUT_IMG" "scripts/src"

# ── Verificar fuentes ─────────────────────────────────────────
if [[ ! -f "$SRC_ICON" ]]; then
  echo "❌  Falta $SRC_ICON — copiá el SVG del ícono ahí"
  exit 1
fi
if [[ ! -f "$SRC_FULL" ]]; then
  echo "❌  Falta $SRC_FULL — copiá el SVG completo (ícono + Vida Isleña) ahí"
  exit 1
fi

echo "🎨  Generando logos y favicons..."

# ── Logo completo (nav) ───────────────────────────────────────
rsvg-convert -h 72 "$SRC_FULL" > "$OUT_IMG/logo.png"
echo "✔  logo.png (72px alto)"

# ── Solo ícono ────────────────────────────────────────────────
rsvg-convert -w 512 -h 512 "$SRC_ICON" > "$OUT_IMG/logo-icon.png"
echo "✔  logo-icon.png (512×512)"

# ── Favicons ──────────────────────────────────────────────────
rsvg-convert -w 16  -h 16  "$SRC_ICON" > "$OUT_PUB/favicon-16x16.png"
rsvg-convert -w 32  -h 32  "$SRC_ICON" > "$OUT_PUB/favicon-32x32.png"
rsvg-convert -w 180 -h 180 "$SRC_ICON" > "$OUT_PUB/apple-touch-icon.png"
rsvg-convert -w 192 -h 192 "$SRC_ICON" > "$OUT_PUB/android-chrome-192x192.png"
rsvg-convert -w 512 -h 512 "$SRC_ICON" > "$OUT_PUB/android-chrome-512x512.png"
echo "✔  favicons PNG"

# ── favicon.ico (multi-size: 16 + 32 dentro del .ico) ─────────
convert \
  "$OUT_PUB/favicon-16x16.png" \
  "$OUT_PUB/favicon-32x32.png" \
  "$OUT_PUB/favicon.ico"
echo "✔  favicon.ico"

echo ""
echo "✅  Listo. Archivos generados:"
echo "   public/images/logo.png"
echo "   public/images/logo-icon.png"
echo "   public/favicon.ico, favicon-16x16.png, favicon-32x32.png"
echo "   public/apple-touch-icon.png"
echo "   public/android-chrome-192x192.png"
echo "   public/android-chrome-512x512.png"
echo ""
echo "👉  Próximo paso: git add public/ && git commit && git push"
