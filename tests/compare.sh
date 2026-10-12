#!/usr/bin/env bash
#
# Compare two snapshots taken by tests/snapshot.sh. Prints a unified diff and
# exits non-zero when anything differs. Screenshots are compared by eye, not here.
#
#   tests/compare.sh baseline pr-a

set -euo pipefail

HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
A="$HERE/snapshots/${1:?usage: tests/compare.sh <a> <b>}"
B="$HERE/snapshots/${2:?usage: tests/compare.sh <a> <b>}"

for dir in "$A" "$B"; do
    if [[ ! -d "$dir" ]]; then
        echo "no snapshot: $dir" >&2
        exit 2
    fi
done

if diff -ru --exclude=screens "$A" "$B"; then
    echo "identical: $(basename "$A") == $(basename "$B") ($(ls "$A" | grep -vc screens) files)"
else
    exit 1
fi
