#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running C# language
# Copyright (C) 2019 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
# Contrbution Frank Neumann <frank.neumann@htw-berlin.de>

# @vpl_script_description Using dotnet, csc or mcs with Nunit
# Load common script and check programs
. common_script.sh

function get_project {
    get_source_files $1 NOERROR
    if [ "$SOURCE_FILES" == "" ] ; then
        DOTNET_VERSION=$(dotnet --version | grep -o "^...") 

        # Check if NUnit is available and get the version
        check_nunit

        # Create the default .csproj file
        cat > default.$1 << END_CONFIG
<Project Sdk="Microsoft.NET.Sdk">
    <PropertyGroup>
        <OutputType>Exe</OutputType>
        <TargetFramework>net$DOTNET_VERSION</TargetFramework>
        <ImplicitUsings>enable</ImplicitUsings>
        <Nullable>enable</Nullable>
    </PropertyGroup>
END_CONFIG

        if [ "$NUNIT_VERSION" != "" ]; then
            # Add NUnit packages if NUnit is available
            cat >> default.$1 << END_CONFIG
    <ItemGroup>
        <PackageReference Include="nunit" Version="$NUNIT_VERSION" />
        <PackageReference Include="NUnitLite" Version="$NUNIT_VERSION" />
    </ItemGroup>
END_CONFIG
        fi

        # Close the project tag
        echo "</Project>" >> default.$1
    fi
    get_source_files $1
    PROJECT_FILENAME=""
    local file_name
    local SIFS=$IFS
    IFS=$'\n'
    for file_name in $SOURCE_FILES
    do
        if [ "$PROJECT_FILENAME" == "" ] ; then
            PROJECT_FILENAME="$file_name"
        else
            echo "Warning: more than one $2 project file found using: '$PROJECT_FILENAME'"
            break
        fi
    done
    IFS=$SIFS
    PROJECT_DIRECTORY=$(dirname "$PROJECT_FILENAME")
    local filename_with_extension=$(basename "$PROJECT_FILENAME")
    PROJECT_NAME="${filename_with_extension%.*}"
}

function check_nunit {
    # Attempt to find NUnit and detect version
    NUNITLIBFILE=$(ls /usr/lib/cli/nunit.framework*/nunit.framework.dll 2>/dev/null | tail -n 1)
    if [ -f "$NUNITLIBFILE" ]; then
        echo "NUnit found at $NUNITLIBFILE"
        # Extract version number from the directory name
        if [[ "$NUNITLIBFILE" =~ nunit\.framework([0-9.]+) ]]; then
            NUNIT_VERSION="${BASH_REMATCH[1]}"
        else
            NUNIT_VERSION="3.14.0" # Default to 3.14.0 if unable to determine version
        fi
        export NUNITLIB="-r:$NUNITLIBFILE"
    else
        echo "Warning: NUnit not found. Skipping NUnit-related compilation."
        export NUNITLIB=""
        NUNIT_VERSION=""
    fi
}

check_program dotnet csc mcs
if [ "$PROGRAM" == "dotnet" ] ; then
    export DOTNET_CLI_TELEMETRY_OPTOUT=1
    export DOTNET_RUNNING_IN_CONTAINER=1
    export DOTNET_EnableWriteXorExecute=0
    export DOTNET_NOLOGO=1
    if [ "$1" == "version" ] ; then
        {
            cat common_script.sh
            echo "export DOTNET_CLI_TELEMETRY_OPTOUT=1"
            echo "export DOTNET_RUNNING_IN_CONTAINER=1" 
            echo "export DOTNET_EnableWriteXorExecute=0"
            echo "export DOTNET_NOLOGO=1"
            echo "dotnet --version"
        } >  vpl_execution
        chmod +x vpl_execution
        exit
    fi
    get_project csproj C#
    dotnet build -v=q "$PROJECT_FILENAME"
    if [ "$?" == "0" ] ; then
        if [ "$PROJECT_DIRECTORY" == "" ] ; then
            EXE_FILENAME=$(ls "bin/Debug/"*"/$PROJECT_NAME")
        else
            EXE_FILENAME=$(ls "$PROJECT_DIRECTORY/bin/Debug/"*"/$PROJECT_NAME")
        fi
        if [ "$?" == "0" ] ; then
            {
                cat common_script.sh
                echo "export DOTNET_CLI_TELEMETRY_OPTOUT=1"
                echo "export DOTNET_RUNNING_IN_CONTAINER=1" 
                echo "export DOTNET_EnableWriteXorExecute=0"
                echo "export DOTNET_NOLOGO=1"
                echo "./\"$EXE_FILENAME\""
            } >  vpl_execution
            chmod +x vpl_execution
            grep -E -q "System\.Windows\.Forms" $EXE_FILENAME*
            if [ "$?" = "0" ] ; then
                mv vpl_execution vpl_wexecution
            fi

        else
            echo "Error: execution file not found"
        fi
    fi
else
    check_program mono
    check_program csc mcs
    if [ "$1" == "version" ] ; then
        get_program_version --version
    fi 
    [ "$PROGRAM" == "mcs" ] && export PKGDOTNET="-pkg:dotnet"
    get_source_files cs
    OUTPUTFILE=output.exe
    # Generate file with source files
    generate_file_of_files .vpl_source_files
    # Detect NUnit
    check_nunit
    # Compile
    export MONO_ENV_OPTIONS=--gc=sgen
    EXECUTABLE=false
    $PROGRAM $PKGDOTNET $NUNITLIB -out:$OUTPUTFILE -lib:/usr/lib/mono @.vpl_source_files &>.vpl_compilation_message
    if [ -f $OUTPUTFILE ] ; then
        EXECUTABLE=true
    else
        # Try to compile as dll
        OUTPUTFILE=output.dll
        if [ "$NUNITLIB" != "" ] ; then
            $PROGRAM $PKGDOTNET $NUNITLIB -out:$OUTPUTFILE -target:library -lib:/usr/lib/mono @.vpl_source_files &> /dev/null
        fi
    fi
    rm .vpl_source_files
    if [ -f $OUTPUTFILE ] ; then
        cat common_script.sh > vpl_execution
        chmod +x vpl_execution
        echo "export MONO_ENV_OPTIONS=--gc=sgen" >> vpl_execution
        # Detect NUnit in the compiled output
        grep -E "nunit\.framework" $OUTPUTFILE &>/dev/null
        if [ "$?" -eq "0" ] ; then
            echo "nunit-console -nologo $OUTPUTFILE" >> vpl_execution
        fi
        if [ "$EXECUTABLE" == "true" ] ; then
            echo "mono $OUTPUTFILE \$@" >> vpl_execution
            grep -E "System\.Windows\.Forms" $OUTPUTFILE &>/dev/null
            if [ "$?" -eq "0" ] ; then
                mv vpl_execution vpl_wexecution
            fi
        fi
    else
        cat .vpl_compilation_message
    fi
fi
