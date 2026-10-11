# Shared helpers for verify-kibbles prove scripts. Source after ROOT is set.
# Pest Browser + Http::preventStrayRequests() cannot render while public/hot
# points at Vite: Inertia SSR hits http://[::1]:5173/__inertia_ssr and fails.
# Stash the hot file so Pest uses public/build/manifest.json. Restore after.

stash_vite_hot() {
  : "${ROOT:?ROOT must be set before stash_vite_hot}"
  VERIFY_HOT_FILE="$ROOT/public/hot"
  VERIFY_HOT_STASH=""
  if [[ -f "$VERIFY_HOT_FILE" ]]; then
    VERIFY_HOT_STASH="$(mktemp /tmp/kibbles-verify-hot.XXXXXX)"
    mv "$VERIFY_HOT_FILE" "$VERIFY_HOT_STASH"
    echo "vite_hot_stashed=$VERIFY_HOT_STASH"
  else
    echo "vite_hot_absent"
  fi
}

restore_vite_hot() {
  if [[ -n "${VERIFY_HOT_STASH:-}" && -f "$VERIFY_HOT_STASH" ]]; then
    mv -f "$VERIFY_HOT_STASH" "$ROOT/public/hot"
    echo "vite_hot_restored"
  fi
}
