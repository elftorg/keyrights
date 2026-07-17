#!/usr/bin/env bash

set -euo pipefail

if [[ $# -ne 1 ]]; then
    echo "Usage: $0 VERSION" >&2
    exit 64
fi

version="$1"
if [[ ! "$version" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    echo "ERROR: VERSION must be in the form X.Y.Z" >&2
    exit 64
fi

root_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
dist_dir="$root_dir/dist"
archive_name="drdroid.keyrights-${version}.tar.gz"

module_version="$(php -r 'include $argv[1]; echo $arModuleVersion["VERSION"];' "$root_dir/install/version.php")"
if [[ "$module_version" != "$version" ]]; then
    echo "ERROR: requested version $version does not match install/version.php ($module_version)" >&2
    exit 1
fi

mkdir -p "$dist_dir"
rm -f "$dist_dir/$archive_name" "$dist_dir/SHA256SUMS"

build_dir="$(mktemp -d "${TMPDIR:-/tmp}/drdroid.keyrights-release.XXXXXX")"
trap 'rm -rf "$build_dir"' EXIT
package_dir="$build_dir/drdroid.keyrights"
mkdir -p "$package_dir"

# The archive is unpacked directly into /bitrix/modules/. Keep one module
# directory at the archive root and omit repository/development-only content.
tar \
    -C "$root_dir" \
    --exclude='./.git' \
    --exclude='./.github' \
    --exclude='./dist' \
    --exclude='./docs' \
    --exclude='./release' \
    --exclude='./scripts' \
    --exclude='./tests' \
    --exclude='./install/components/drdroid/keyrights/node_modules' \
    --exclude='./vendor/bin' \
    -cf - . | tar -C "$package_dir" -xf -

for required_file in \
    install/index.php \
    install/version.php \
    include.php \
    vendor/autoload.php \
    install/components/drdroid/keyrights/static/js/bundle.js; do
    if [[ ! -f "$package_dir/$required_file" ]]; then
        echo "ERROR: package is missing $required_file" >&2
        exit 1
    fi
done

archive_path="$dist_dir/$archive_name"
tar \
    --sort=name \
    --mtime='UTC 1970-01-01' \
    --owner=0 \
    --group=0 \
    --numeric-owner \
    -C "$build_dir" \
    -czf "$archive_path" \
    drdroid.keyrights

archive_roots="$(tar -tzf "$archive_path" | awk -F/ 'NF {print $1}' | sort -u)"
if [[ "$archive_roots" != "drdroid.keyrights" ]]; then
    echo "ERROR: archive must have exactly one root directory: drdroid.keyrights/" >&2
    exit 1
fi

if tar -tzf "$archive_path" | grep -Eq '^drdroid\.keyrights/(\.git|\.github|dist|docs|release|scripts|tests)(/|$)'; then
    echo "ERROR: archive contains repository or development-only files" >&2
    exit 1
fi

(
    cd "$dist_dir"
    sha256sum "$archive_name" > SHA256SUMS
    sha256sum -c SHA256SUMS
)

echo "Created $archive_path"
echo "Created $dist_dir/SHA256SUMS"
