#!/bin/bash
# This file is part of VPL for Moodle.
# License GNU/GPL, see LICENSE.txt or http://www.gnu.org/licenses/gpl-2.0.html

set -e

function fail {
    printf 'FAIL: mock %s: %s\n' "${0##*/}" "$*" >&2
    printf '%s\n' "$*" >> "$MOCK_DIR/failures"
    exit 1
}

next=$(cat "$MOCK_DIR/next")
expected="$MOCK_DIR/$next"
printf '%s\0' "${0##*/}" "$@" > "$MOCK_DIR/actual"
if [ ! -f "$expected.args" ] || ! cmp -s "$expected.args" "$MOCK_DIR/actual"; then
    printf 'Actual call:' >&2
    printf ' %q' "${0##*/}" "$@" >&2
    printf '\n' >&2
    if [ -f "$expected.args" ]; then
        mapfile -d '' -t arguments < "$expected.args"
        printf 'Expected call:' >&2
        printf ' %q' "${arguments[@]}" >&2
        printf '\n' >&2
    fi
    fail "unexpected call number $next"
fi
printf '%s\n' "$((next + 1))" > "$MOCK_DIR/next"

for argument in "$@"; do
    case "$argument" in
        @*) response=${argument#@} ;;
        -f.vpl_source_files) response=${argument#-f} ;;
        *) continue ;;
    esac
    if ! cmp -s "$response" "$MOCK_DIR/response-${response##*/}"; then
        fail "incorrect response file $response"
    fi
done

action=$(cat "$expected.action")
case "$action" in
    executable:*)
        output=${action#executable:}
        mkdir -p "$(dirname "$output")"
        printf '#!/bin/bash\nexec mock-runtime "$@"\n' > "$output"
        chmod +x "$output"
        ;;
    file:*) touch "${action#file:}" ;;
    java-version) printf 'openjdk version "17.0.1"\n' >&2 ;;
    main-method) printf '  public static void main(java.lang.String[]);\n' ;;
    groovy-class)
        printf '\001\000\004main\001\000\026([Ljava/lang/String;)' > "${MOCK_SOURCE%.*}.class"
        ;;
    dotnet-version) printf '8.0.0\n' ;;
    top-entity) printf 'main\n' ;;
    node-path) printf 'mock-node-path\n' ;;
    mips)
        printf 'SPIM banner\n%.0s' {1..5}
        printf 'mock program ran\n'
        ;;
    input)
        cat > "$MOCK_DIR/input"
        cmp -s "$MOCK_SOURCE" "$MOCK_DIR/input" || fail 'incorrect standard input'
        printf 'mock program ran\n'
        ;;
    run) printf 'mock program ran\n' ;;
    none) ;;
    *) fail "unknown mock action $action" ;;
esac
