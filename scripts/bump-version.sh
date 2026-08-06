#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PLUGIN_FILE="$ROOT_DIR/tebuto-online-terminbuchung/tebuto-plugin.php"
README_FILE="$ROOT_DIR/tebuto-online-terminbuchung/readme.txt"
PACKAGE_FILE="$ROOT_DIR/package.json"

usage() {
	cat <<'EOF'
Usage:
  ./scripts/bump-version.sh <version>       Set an explicit version (e.g. 2.2.0)
  ./scripts/bump-version.sh patch           Bump patch version (2.1.0 -> 2.1.1)
  ./scripts/bump-version.sh minor           Bump minor version (2.1.0 -> 2.2.0)
  ./scripts/bump-version.sh major           Bump major version (2.1.0 -> 3.0.0)
  ./scripts/bump-version.sh --check         Verify all version fields are in sync
  ./scripts/bump-version.sh --check --release-tag <tag>  Also verify GitHub release tag
  ./scripts/bump-version.sh --print-version            Print the current synced version

Updates:
  - tebuto-online-terminbuchung/tebuto-plugin.php (Version header + TEBUTO_VERSION)
  - tebuto-online-terminbuchung/readme.txt (Stable tag + changelog/upgrade notice stubs)
  - package.json
EOF
}

is_valid_version() {
	[[ "$1" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]
}

get_header_version() {
	perl -ne 'print $1 if /\* Version:\s*([0-9]+\.[0-9]+\.[0-9]+)/' "$PLUGIN_FILE"
}

get_constant_version() {
	perl -ne "print \$1 if /define\\(\\s*'TEBUTO_VERSION'\\s*,\\s*'([0-9]+\\.[0-9]+\\.[0-9]+)'\\s*\\)/" "$PLUGIN_FILE"
}

get_stable_tag() {
	perl -ne 'print $1 if /^Stable tag:\s*([0-9]+\.[0-9]+\.[0-9]+)/' "$README_FILE"
}

get_package_version() {
	node -p "require('$PACKAGE_FILE').version"
}

read_current_version() {
	local header constant stable package

	header=$(get_header_version)
	constant=$(get_constant_version)
	stable=$(get_stable_tag)
	package=$(get_package_version)

	if [ "$header" != "$constant" ] || [ "$header" != "$stable" ] || [ "$header" != "$package" ]; then
		echo "Error: version fields are out of sync:" >&2
		echo "  tebuto-plugin.php header: $header" >&2
		echo "  tebuto-plugin.php constant: $constant" >&2
		echo "  readme.txt stable tag: $stable" >&2
		echo "  package.json: $package" >&2
		echo "Fix with ./scripts/bump-version.sh <version>" >&2
		exit 1
	fi

	echo "$header"
}

bump_semver() {
	local version="$1"
	local part="$2"
	local major minor patch

	IFS='.' read -r major minor patch <<< "$version"

	case "$part" in
		patch) patch=$((patch + 1)) ;;
		minor)
			minor=$((minor + 1))
			patch=0
			;;
		major)
			major=$((major + 1))
			minor=0
			patch=0
			;;
		*)
			echo "Error: invalid semver part '$part'" >&2
			exit 1
			;;
	esac

	echo "${major}.${minor}.${patch}"
}

update_plugin_file() {
	local new_version="$1"

	perl -pi -e "s/(\* Version:\s*)[0-9]+\.[0-9]+\.[0-9]+/\${1}$new_version/" "$PLUGIN_FILE"
	perl -pi -e "s/(define\\(\\s*'TEBUTO_VERSION'\\s*,\\s*')[0-9]+\.[0-9]+\.[0-9]+(')/\${1}$new_version\${2}/" "$PLUGIN_FILE"
}

update_readme_file() {
	local new_version="$1"

	perl -pi -e "s/^(Stable tag:\s*)\K[0-9]+\.[0-9]+\.[0-9]+/$new_version/" "$README_FILE"

	if ! grep -Fq "= $new_version =" "$README_FILE"; then
		perl -i -pe '
			if (/^== Changelog ==$/) {
				$in_changelog = 1;
			} elsif ($in_changelog && /^= [0-9]/ && !$inserted_changelog) {
				$_ = "= $ENV{NEW_VERSION} =\n* TODO: Add changelog entries\n\n" . $_;
				$inserted_changelog = 1;
			}
			if (/^== Upgrade Notice ==$/) {
				$in_upgrade = 1;
			} elsif ($in_upgrade && /^= [0-9]/ && !$inserted_upgrade) {
				$_ = "= $ENV{NEW_VERSION} =\nTODO: Add upgrade notice\n\n" . $_;
				$inserted_upgrade = 1;
			}
		' "$README_FILE"
	fi
}

update_package_file() {
	local new_version="$1"

	node <<EOF
const fs = require('fs');
const path = '$PACKAGE_FILE';
const pkg = JSON.parse(fs.readFileSync(path, 'utf8'));
pkg.version = '$new_version';
fs.writeFileSync(path, JSON.stringify(pkg, null, 2) + '\n');
EOF
}

check_versions() {
	local release_tag="${1:-}"
	local header constant stable package

	header=$(get_header_version)
	constant=$(get_constant_version)
	stable=$(get_stable_tag)
	package=$(get_package_version)

	if [ "$header" != "$constant" ] || [ "$header" != "$stable" ] || [ "$header" != "$package" ]; then
		echo "Version mismatch:" >&2
		echo "  tebuto-plugin.php header: $header" >&2
		echo "  tebuto-plugin.php constant: $constant" >&2
		echo "  readme.txt stable tag: $stable" >&2
		echo "  package.json: $package" >&2
		exit 1
	fi

	if [ -n "$release_tag" ]; then
		release_tag="${release_tag#v}"
		if [ "$header" != "$release_tag" ]; then
			echo "Release tag $release_tag does not match plugin version $header" >&2
			exit 1
		fi
	fi

	echo "All version fields are in sync at $header"
}

if [ $# -eq 0 ]; then
	usage
	exit 1
fi

if [ "$1" = "--print-version" ]; then
	get_constant_version
	exit 0
fi

if [ "$1" = "--check" ]; then
	shift
	release_tag=""
	if [ "${1:-}" = "--release-tag" ]; then
		shift
		release_tag="${1:-}"
		if [ -z "$release_tag" ]; then
			echo "Error: --release-tag requires a value" >&2
			exit 1
		fi
	fi
	check_versions "$release_tag"
	exit 0
fi

if [ "$1" = "-h" ] || [ "$1" = "--help" ]; then
	usage
	exit 0
fi

new_version=""

case "$1" in
	patch|minor|major)
		current_version=$(read_current_version)
		new_version=$(bump_semver "$current_version" "$1")
		;;
	*)
		new_version="$1"
		new_version="${new_version#v}"
		if ! is_valid_version "$new_version"; then
			echo "Error: '$new_version' is not a valid semver version (expected X.Y.Z)" >&2
			exit 1
		fi
		;;
esac

current_version=$(get_header_version)

if [ "$current_version" = "$new_version" ] \
	&& [ "$(get_constant_version)" = "$new_version" ] \
	&& [ "$(get_stable_tag)" = "$new_version" ] \
	&& [ "$(get_package_version)" = "$new_version" ]; then
	echo "Version is already $new_version"
	exit 0
fi

export NEW_VERSION="$new_version"

echo "Bumping version: $current_version -> $new_version"

update_plugin_file "$new_version"
update_readme_file "$new_version"
update_package_file "$new_version"
check_versions

echo "Done. Review readme.txt changelog and upgrade notice stubs before releasing."
