#!/usr/bin/env bash
#
# Builds the current working copy and deploys it to Hostinger (zero downtime).
# Used by GitHub Actions (.github/workflows/deploy.yml) and can be run from a developer PC:
#
#   DEPLOY_HOST=72.60.93.54 DEPLOY_PORT=65002 DEPLOY_USER=u771984404 \
#   DEPLOY_PATH=domains/totymes.com/staging-app DEPLOY_KEY=~/.ssh/totymes_deploy deploy/deploy.sh
#
# Needs: git, npm, tar, ssh, scp. On the server: deploy/activate.sh does the rest.
# Shipped files = tracked + new untracked files (not ignored), minus tests/CI, plus public/build.

set -euo pipefail

: "${DEPLOY_HOST:?}" "${DEPLOY_PORT:?}" "${DEPLOY_USER:?}" "${DEPLOY_PATH:?}"
KEY_OPT=()
[ -n "${DEPLOY_KEY:-}" ] && KEY_OPT=(-i "${DEPLOY_KEY/#\~/$HOME}" -o IdentitiesOnly=yes)
SSH=(ssh -p "$DEPLOY_PORT" "${KEY_OPT[@]}" -o BatchMode=yes -o StrictHostKeyChecking=accept-new)
SCP=(scp -q -P "$DEPLOY_PORT" "${KEY_OPT[@]}" -o BatchMode=yes -o StrictHostKeyChecking=accept-new)
TARGET="$DEPLOY_USER@$DEPLOY_HOST"

cd "$(dirname "$0")/.."

[ "${SKIP_BUILD:-0}" = 1 ] || npm run build

REL="$(date -u +%Y%m%d%H%M%S)"
WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT

{ git ls-files -co --exclude-standard | grep -v -E '^(tests/|\.github/|database/database\.sqlite$|\.env$)'; find public/build -type f; } \
    | sort -u > "$WORK/files.txt"
tar --force-local -czf "$WORK/release.tar.gz" -T "$WORK/files.txt" 2>/dev/null || tar -czf "$WORK/release.tar.gz" -T "$WORK/files.txt"

"${SSH[@]}" "$TARGET" "mkdir -p ~/$DEPLOY_PATH/releases/$REL ~/$DEPLOY_PATH/shared"
"${SCP[@]}" "$WORK/release.tar.gz" "$TARGET:$DEPLOY_PATH/releases/$REL.tar.gz"
"${SSH[@]}" "$TARGET" "cd ~/$DEPLOY_PATH/releases && tar -xzf $REL.tar.gz -C $REL && rm $REL.tar.gz \
    && bash $REL/deploy/activate.sh \$HOME/$DEPLOY_PATH \$HOME/$DEPLOY_PATH/releases/$REL"
