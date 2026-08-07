#!/usr/bin/env bash
# install-hooks.sh — Install git hooks dari scripts/hooks/ ke .git/hooks/
#
# Cara pakai:
#   bash scripts/install-hooks.sh

set -euo pipefail

HOOKS_SRC="scripts/hooks"
HOOKS_DST=".git/hooks"

if [ ! -d "$HOOKS_DST" ]; then
    echo "ERROR: Folder .git/hooks tidak ditemukan. Pastikan ini adalah git repository."
    exit 1
fi

for hook in "$HOOKS_SRC"/*; do
    name=$(basename "$hook")
    dest="$HOOKS_DST/$name"
    cp "$hook" "$dest"
    chmod +x "$dest"
    echo "✅ Hook installed: $dest"
done

echo ""
echo "Git hooks berhasil diinstall dari $HOOKS_SRC/ ke $HOOKS_DST/"
