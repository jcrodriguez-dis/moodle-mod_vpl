#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running C# language
# Copyright (C) 2019 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# @vpl_script_description Using dotnet, csc or mcs
# load common script and check programs
. common_script.sh

function get_project {
	get_source_files $1 NOERROR
	if [ "$SOURCE_FILES" == "" ] ; then
		DOTNET_VERSION=$(dotnet --version | grep -o "^...")
		cat > default.$1 << END_CONFIG
<Project Sdk="Microsoft.NET.Sdk">
	<PropertyGroup>
		<OutputType>Exe</OutputType>
		<TargetFramework>net$DOTNET_VERSION</TargetFramework>
		<ImplicitUsings>enable</ImplicitUsings>
		<Nullable>disable</Nullable>
	</PropertyGroup>
</Project>
END_CONFIG
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
			if [ "$?" = "0" ]	; then
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
	NUNITLIBFILE=$(ls /usr/lib/cli/nunit.framework*/nunit.framework.dll | tail -n 1)
	[ -f "$NUNITLIBFILE" ] && export NUNITLIB="-r:$NUNITLIBFILE"
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
			PROGRAM $PKGDOTNET $NUNITLIB -out:$OUTPUTFILE -target:library -lib:/usr/lib/mono @.vpl_source_files &> /dev/null
		fi
	fi
	rm .vpl_source_files
	if [ -f $OUTPUTFILE ] ; then
		cat common_script.sh > vpl_execution
		chmod +x vpl_execution
		echo "export MONO_ENV_OPTIONS=--gc=sgen" >> vpl_execution
		# Detect NUnit
		grep -E "nunit\.framework" $OUTPUTFILE &>/dev/null
		if [ "$?" -eq "0" ]	; then
			echo "nunit-console -nologo $OUTPUTFILE" >> vpl_execution
		fi
		if [ "$EXECUTABLE" == "true" ] ; then
			echo "mono $OUTPUTFILE \$@" >> vpl_execution
			grep -E "System\.Windows\.Forms" $OUTPUTFILE &>/dev/null
			if [ "$?" -eq "0" ]	; then
				mv vpl_execution vpl_wexecution
			fi
		fi
	else
		cat .vpl_compilation_message
	fi
fi
