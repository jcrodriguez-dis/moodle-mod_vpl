#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running C# language
# Copyright (C) 2019 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# @vpl_script_description Using dotnet for F#
# load common script and check programs
. common_script.sh

function get_project {
	get_source_files $1 NOERROR
	if [ "${#SOURCE_FILES_ARRAY[@]}" -eq 0 ] ; then
		local DOTNET_VERSION=$(dotnet --version | cut -d. -f1,2)
		get_source_files fs
		# IMPORTANT: Use your own .fsproj
		local FILES_CONF=
		local file_name=
		for file_name in "${SOURCE_FILES_ARRAY[@]}" ; do
			file_name=${file_name//&/\&amp;}
			file_name=${file_name//</\&lt;}
			file_name=${file_name//>/\&gt;}
			file_name=${file_name//\"/\&quot;}
			file_name=${file_name//\'/\&apos;}
			FILES_CONF=$(printf '        <Compile Include="%s" />\n%s\n' "$file_name" "$FILES_CONF")
		done
		local config_filename=default.$1
		cat >> $config_filename << END_CONFIG
<Project Sdk="Microsoft.NET.Sdk">
	<PropertyGroup>
		<OutputType>Exe</OutputType>
		<TargetFramework>net$DOTNET_VERSION</TargetFramework>
	</PropertyGroup>
	<ItemGroup>$FILES_CONF</ItemGroup>
</Project>
END_CONFIG
	fi
	get_source_files $1
	PROJECT_FILENAME=""
	local file_name
	for file_name in "${SOURCE_FILES_ARRAY[@]}"
	do
		if [ "$PROJECT_FILENAME" == "" ] ; then
			PROJECT_FILENAME="$file_name"
		else
			echo "Warning: more than one $2 project file found using: '$PROJECT_FILENAME'"
			break
		fi
	done
	PROJECT_DIRECTORY=$(dirname "$PROJECT_FILENAME")
	local filename_with_extension=$(basename "$PROJECT_FILENAME")
	PROJECT_NAME="${filename_with_extension%.*}"
}


check_program dotnet

VPL_ERROR_FILE=.vpl_execution.error
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
get_project fsproj F#
dotnet build -v=q "$PROJECT_FILENAME" &> $VPL_ERROR_FILE
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
			printf '%q\n' "./$EXE_FILENAME"
		} >  vpl_execution
		chmod +x vpl_execution
	else
		echo "Error: execution file not found"
	fi
else
	cat $VPL_ERROR_FILE
fi
rm -f $VPL_ERROR_FILE
apply_run_mode
