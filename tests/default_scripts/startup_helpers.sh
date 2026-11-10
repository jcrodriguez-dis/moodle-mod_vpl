#!/bin/bash
# This file is part of VPL for Moodle.
# License GNU/GPL, see LICENSE.txt or http://www.gnu.org/licenses/gpl-2.0.html

function prepare_mocks {
    export MOCK_DIR="$CASE_DIR/mocks"
    mkdir -p "$MOCK_DIR/bin"
    local utility
    # Only shell utilities are inherited; installed language toolchains cannot affect these tests.
    for utility in awk base64 basename bash cat chmod cmp cp cut dirname find grep head ln \
            ls mkdir mv rm rmdir sed tail touch tr uname wc; do
        ln -s "$(command -v "$utility")" "$MOCK_DIR/bin/$utility"
    done
    cp "$TESTS_DIR/mock_tool.sh" "$MOCK_DIR/tool"
    chmod +x "$MOCK_DIR/tool"
    export PATH="$MOCK_DIR/bin"
    # NUnit discovery must not depend on libraries installed on the test host.
    function ls {
        case "$1" in
            /usr/lib/cli/nunit.framework*) return 2 ;;
            *) command ls "$@" ;;
        esac
    }
    export -f ls
    printf '1\n' > "$MOCK_DIR/next"
    MOCK_CALLS=0
}

function expect_call {
    local action=$1 program=$2
    shift 2
    MOCK_CALLS=$((MOCK_CALLS + 1))
    printf '%s\0' "$program" "$@" > "$MOCK_DIR/$MOCK_CALLS.args"
    printf '%s\n' "$action" > "$MOCK_DIR/$MOCK_CALLS.action"
    if [ ! -e "$MOCK_DIR/bin/$program" ]; then
        ln -s "$MOCK_DIR/tool" "$MOCK_DIR/bin/$program"
    fi
}

function expect_response {
    local filename=$1
    shift
    printf '%s\n' "$@" > "$MOCK_DIR/response-${filename##*/}"
}

function quote_response_filename {
    local filename=${1//\\/\\\\}
    filename=${filename//\"/\\\"}
    printf '"%s"' "$filename"
}

function expect_source_response {
    expect_response "$1" "$(quote_response_filename "$2")"
}

function get_startup_languages {
    local script language
    for script in "$SCRIPTS_DIR"/*_run.sh "$SCRIPTS_DIR/csharp_run-mono.sh"; do
        language=${script##*/}
        language=${language%_run.sh}
        case "$language" in
            all|default|command-line) continue ;;
            csharp_run-mono.sh) language=csharp_run-mono ;;
        esac
        printf '%s\n' "$language"
    done
}

function assert_mock_calls {
    if [ -f "$MOCK_DIR/failures" ]; then
        cat "$MOCK_DIR/failures" >&2
        exit 1
    fi
    assert_equal "$((MOCK_CALLS + 1))" "$(cat "$MOCK_DIR/next")"
}

function test_language_startup {
    local language=$1 program=${2:-} extension executable=vpl_execution
    local stem=${3:-main}
    local -a compiler_options=()
    prepare_mocks
    case "$language" in
        ada|ada-12)
            extension=adb
            local standard=-gnat2005
            [ "$language" != ada-12 ] || standard=-gnat2012
            expect_call executable:vpl_execution gnat make "$standard" -gnatW8 -q \
                -o vpl_execution "$stem.adb"
            expect_call run mock-runtime
            ;;
        c|c-11|c-99|c-ansi|cpp|cpp-11|cpp-14|cpp-17|cpp-20|cpp-98)
            extension=c
            program=gcc
            if [[ "$language" == cpp* ]]; then
                extension=cpp
                program=g++
            fi
            case "$language" in
                c-ansi) compiler_options=(-ansi -pedantic) ;;
                c-*) compiler_options=("-std=c${language#c-}" -pedantic) ;;
                cpp-*) compiler_options=("-std=c++${language#cpp-}" -pedantic) ;;
            esac
            expect_source_response .vpl_source_files "$stem.$extension"
            expect_call executable:vpl_execution "$program" "${compiler_options[@]}" \
                -fno-diagnostics-color -o vpl_execution -g @.vpl_source_files -lm -lutil
            expect_call run mock-runtime
            ;;
        asm)
            extension=asm
            expect_source_response .vpl_object_files "$stem.o"
            expect_call "file:$stem.o" nasm -f elf64 "$stem.asm"
            expect_call executable:vpl_execution ld -o vpl_execution @.vpl_object_files
            expect_call run mock-runtime
            ;;
        cobol)
            extension=cbl
            expect_call none cobc -c -g "$stem.cbl"
            expect_call executable:vpl_execution cobc -x -o vpl_execution -g "$stem.cbl"
            expect_call run mock-runtime
            ;;
        d|fortran)
            if [ "$language" == d ]; then
                extension=d
                expect_call executable:vpl_execution gdc -o vpl_execution -lm -lutil @.vpl_source_files
            else
                extension=f90
                expect_call executable:vpl_execution gfortran -o vpl_execution @.vpl_source_files
            fi
            expect_source_response .vpl_source_files "$stem.$extension"
            expect_call run mock-runtime
            ;;
        csharp|csharp-nunit|csharp_run-mono|fsharp|visualbasic)
            extension=cs
            local project=csproj
            case "$language" in
                fsharp) extension=fs; project=fsproj ;;
                visualbasic) extension=vb; project=vbproj ;;
            esac
            if [ "$language" == csharp_run-mono ] || [ "$program" == csc ] || [ "$program" == mcs ]; then
                program=${program:-csc}
                ln -s "$MOCK_DIR/tool" "$MOCK_DIR/bin/mono"
                [ "$program" != mcs ] || compiler_options=(-pkg:dotnet)
                expect_source_response .vpl_source_files "$stem.cs"
                expect_call file:output.exe "$program" "${compiler_options[@]}" \
                    -out:output.exe -lib:/usr/lib/mono @.vpl_source_files
                expect_call run mono output.exe
            else
                expect_call dotnet-version dotnet --version
                expect_call executable:bin/Debug/net8.0/default dotnet build -v=q "default.$project"
                expect_call run mock-runtime
            fi
            ;;
        erlang)
            extension=erl
            ln -s "$MOCK_DIR/tool" "$MOCK_DIR/bin/erl"
            expect_call none erlc "$stem.erl"
            expect_call run escript "$stem.erl"
            ;;
        go)
            extension=go
            expect_call executable:go_program go build -o go_program "$stem.go"
            expect_call run mock-runtime
            ;;
        haskell)
            extension=hs
            if [ "$program" == hugs ]; then
                ln -s "$MOCK_DIR/tool" "$MOCK_DIR/bin/hugs"
                expect_call run runhugs +98 "$stem.hs"
            else
                expect_call executable:vpl_execution ghc -v0 -o vpl_execution "$stem.hs"
                expect_call run mock-runtime
            fi
            ;;
        java)
            extension=java
            expect_call java-version java -version
            expect_call "file:$stem.class" javac -Xlint:deprecation -g "$stem.java"
            expect_call main-method javap "./$stem.class"
            expect_call run java -enableassertions "$stem"
            ;;
        groovy)
            extension=groovy
            expect_call none groovy -version
            expect_call groovy-class groovyc -g "$stem.groovy"
            expect_call run groovy "$stem"
            ;;
        kotlin)
            extension=kt
            expect_response .vpl_kotlin_command_line -include-runtime '-d vpl_execution.jar' \
                "$(quote_response_filename "$stem.kt")"
            expect_call file:vpl_execution.jar kotlinc @.vpl_kotlin_command_line
            expect_call run java -jar vpl_execution.jar
            ;;
        pascal)
            extension=pas
            if [ "$program" == gpc ]; then
                expect_source_response .vpl_source_files "$stem.pas"
                expect_call executable:vpl_execution gpc --automake -o vpl_execution @.vpl_source_files -lm
            else
                expect_call executable:vpl_execution fpc -ovpl_execution "$stem.pas"
            fi
            expect_call run mock-runtime
            ;;
        rust)
            extension=rs
            expect_call executable:vpl_execution rustc "$stem.rs" --crate-name vpl_execution
            expect_call run mock-runtime
            ;;
        verilog)
            extension=v
            expect_response .vpl_source_files "$stem.v"
            expect_call executable:vpl_execution iverilog -ovpl_execution -f.vpl_source_files
            expect_call run mock-runtime
            ;;
        vhdl)
            extension=vhdl
            if [ "$program" == gvhdl ]; then
                expect_call "executable:$stem" gvhdl "$stem.vhdl"
                expect_call run mock-runtime -q -cmd q
            else
                expect_call none ghdl -c "$stem.vhdl"
                expect_call top-entity ghdl --find-top
                expect_call none ghdl -a "$stem.vhdl"
                expect_call none ghdl -e main
                expect_call run ghdl -r main
            fi
            ;;
        clojure) extension=clj; expect_call run clojure "$stem.clj" ;;
        lisp) extension=lisp; expect_call run clisp "$stem.lisp" ;;
        lua) extension=lua; expect_call run lua "$stem.lua" ;;
        julia) extension=jl; expect_call run julia "$stem.jl" ;;
        ruby) extension=rb; expect_call run ruby "$stem.rb" ;;
        scheme)
            extension=scm
            expect_call run "${program:-racket}" -f "$stem.scm"
            ;;
        python|python-2|python-3)
            extension=py
            [ "$language" != python-2 ] || program=python2
            expect_call run "${program:-python3}" "$stem.py"
            ;;
        javascript)
            extension=js
            # command -pv also searches functions, independently of the host's default PATH.
            function npm { "$MOCK_DIR/bin/npm" "$@"; }
            export -f npm
            expect_call node-path npm root -g
            expect_call run "${program:-node}" "$stem.js"
            ;;
        typescript)
            extension=ts
            expect_call "file:$stem.js" tsc "$stem.ts"
            expect_call run "${program:-node}" "$stem.js"
            ;;
        perl)
            extension=perl
            expect_call none perl -c "$stem.perl"
            expect_call run perl -w "$stem.perl"
            ;;
        prolog)
            extension=pro
            expect_call none swipl -q -s "$stem.pro" -t halt
            expect_call run swipl -q -s "$stem.pro" -t vpl_hello
            ;;
        php)
            extension=php
            program=${program:-php}
            expect_call none "$program" -l "$stem.php"
            expect_call run "$program" -f "$stem.php"
            ;;
        r)
            extension=r
            ln -s "$MOCK_DIR/tool" "$MOCK_DIR/bin/R"
            expect_call run Rscript --vanilla "$stem.r"
            ;;
        scala)
            extension=scala
            expect_source_response .vpl_source_files "$stem.scala"
            expect_call none scalac @.vpl_source_files
            expect_call run scala -nocompdaemon "$stem"
            ;;
        sql)
            extension=sql
            expect_call input sqlite3 vpl.db
            ;;
        mips)
            extension=s
            expect_call mips spim -file "$stem.s"
            ;;
        minizinc)
            extension=mzn
            program=${program:-minizinc}
            [ "$program" != minizinc ] || compiler_options=(--solver Gecode)
            expect_call run "$program" "${compiler_options[@]}" "$stem.mzn"
            ;;
        matlab)
            extension=m
            if [ "$program" == octave ]; then
                ln -s "$MOCK_DIR/tool" "$MOCK_DIR/bin/octave"
                expect_call run octave-cli "$stem.m"
                expect_call none ps -o user= -o args=
                local poll
                for poll in {1..6}; do
                    expect_call none sleep 1s
                    expect_call none ps -o user= -o args=
                done
            else
                expect_call run matlab -nosplash
            fi
            ;;
        pseint)
            extension=psc
            expect_call run pseint --nouser "$stem.psc"
            ;;
        shell) extension=sh ;;
        make)
            extension=mk
            printf 'TARGET = main\n' > Makefile
            expect_call executable:main make -s
            expect_call run mock-runtime
            ;;
        html)
            extension=html
            executable=vpl_webexecution
            expect_call none php -i
            # The address is generated randomly; enqueue the server call after preparation.
            ;;
        html-browser-in-server)
            extension=html
            executable=vpl_wexecution
            expect_call run firefox-esr "file://$CASE_DIR/$stem.html"
            expect_call none sleep 10000
            ;;
        *)
            printf 'FAIL: missing startup test for <%s>\n' "$language" >&2
            exit 1
            ;;
    esac

    export MOCK_SOURCE="$stem.$extension" PRICE=expanded
    printf 'mock source\n' > "$MOCK_SOURCE"
    case "$language" in
        asm) printf 'bits 64\n' > "$MOCK_SOURCE" ;;
        shell) printf 'printf "mock program ran\\n"\n' > "$MOCK_SOURCE" ;;
    esac
    cp "$MOCK_SOURCE" "$MOCK_DIR/source"
    prepare_submission "$MOCK_SOURCE"
    local script="${language}_run.sh"
    [ "$language" != csharp_run-mono ] || script=csharp_run-mono.sh
    bash "$SCRIPTS_DIR/$script" batch -g
    assert_file "$MOCK_SOURCE"
    cmp "$MOCK_DIR/source" "$MOCK_SOURCE"
    if [ "$language" == matlab ] && [ "$program" != octave ]; then
        assert_file startup.m
        cmp "$MOCK_SOURCE" startup.m
    fi
    if [ "$language" == fsharp ]; then
        local xml_filename=$MOCK_SOURCE
        xml_filename=${xml_filename//&/\&amp;}
        xml_filename=${xml_filename//\"/\&quot;}
        if ! grep -Fq "<Compile Include=\"$xml_filename\" />" default.fsproj; then
            printf 'FAIL: generated F# project lost or failed to escape <%s>\n' "$MOCK_SOURCE" >&2
            exit 1
        fi
    fi
    assert_file "$executable"
    if [ ! -x "$executable" ]; then
        printf 'FAIL: <%s> is not executable\n' "$executable" >&2
        exit 1
    fi
    if [ "$stem" == 'literal[1]*' ]; then
        touch "literal1match.$extension" literal1match literal1match.class
    fi
    bash -n "$executable"
    if [ "$language" == html ]; then
        local address
        address=$(cat .vpl_localserveraddress)
        [[ "$address" =~ ^127\.[0-9]+\.[0-9]+\.[0-9]+:[0-9]+$ ]]
        expect_call run php -c .php.ini -S "$address" .router.php
        assert_file .router.php
        assert_file .php.ini
    fi
    # Intercept the hard-coded PSeInt path without creating anything outside the case directory.
    if [ "$language" == pseint ]; then
        bash -c 'script=$1; shift; function /pseint/bin/pseint { "$MOCK_DIR/bin/pseint" "$@"; }; source "$script"' _ \
            "$executable" > output
    else
        bash "$executable" > output
    fi
    assert_equal 'mock program ran' "$(cat output)"
    assert_mock_calls
}

function test_mock_rejects_arguments {
    prepare_mocks
    expect_call run mock-runtime expected
    if mock-runtime unexpected > output 2>&1; then
        printf 'FAIL: mock accepted incorrect arguments\n' >&2
        exit 1
    fi
    assert_file "$MOCK_DIR/failures"
    if (assert_mock_calls) > output 2>&1; then
        printf 'FAIL: failed mock call was not reported\n' >&2
        exit 1
    fi
}

function test_mock_rejects_response {
    prepare_mocks
    expect_response .vpl_source_files '"expected.c"'
    printf '"wrong.c"\n' > .vpl_source_files
    expect_call executable:vpl_execution gcc @.vpl_source_files
    if gcc @.vpl_source_files > output 2>&1; then
        printf 'FAIL: mock accepted incorrect response-file contents\n' >&2
        exit 1
    fi
    assert_file "$MOCK_DIR/failures"
    if [ -f vpl_execution ]; then
        printf 'FAIL: failed compiler mock generated an executable\n' >&2
        exit 1
    fi
}
