#!/usr/bin/env bash
# Déploiement FTP Hostinger — neuro-odyssee.com
# Envoie uniquement les fichiers modifiés (lftp mirror -R ne transfère que ce qui diffère).
#
# EXCLUSIONS DE SÉCURITÉ (ne jamais écraser sur le serveur) :
#   - config.php (payment/ + api/) : configuration serveur, gitignorée
#   - data/      : ÉTAT DU SERVEUR rempli par l'admin PHP (incident 2026-08-04) — JAMAIS déployé
#   - .git/ .claude/ tasks/ : artefacts de dev
set -euo pipefail
cd "$(dirname "$0")"

if [[ ! -f .deploy.env ]]; then
  echo "❌ .deploy.env introuvable (identifiants FTP)." >&2; exit 1
fi
source .deploy.env

: "${FTP_HOST:?FTP_HOST manquant dans .deploy.env}"
: "${FTP_USER:?FTP_USER manquant dans .deploy.env}"
: "${FTP_PASS:?FTP_PASS manquant dans .deploy.env}"
FTP_PORT="${FTP_PORT:-21}"
FTP_REMOTE_DIR="${FTP_REMOTE_DIR:-public_html}"

DRY=""
[[ "${1:-}" == "--dry-run" ]] && DRY="--dry-run" && echo "🧪 DRY-RUN (aucun fichier envoyé)"

echo "→ Déploiement vers ${FTP_REMOTE_DIR} sur ${FTP_HOST} ..."

lftp -u "${FTP_USER},${FTP_PASS}" "ftp://${FTP_HOST}:${FTP_PORT}" <<EOF
set ftp:ssl-allow no
set net:timeout 15
set net:max-retries 3
mirror -R --verbose ${DRY} \
  --exclude-glob .git/ \
  --exclude-glob .claude/ \
  --exclude-glob tasks/ \
  --exclude-glob data/ \
  --exclude config.php \
  --exclude .deploy.env \
  --exclude deploy.sh \
  --exclude-glob *.DS_Store \
  ./ ${FTP_REMOTE_DIR}
bye
EOF

echo "✅ Déployé sur ${FTP_HOST}:${FTP_REMOTE_DIR}"
