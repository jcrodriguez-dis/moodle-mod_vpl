#!/bin/bash
# This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
# Script for running C# language
# Copyright (C) 2019 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>

# @vpl_script_description Using dotnet for VisualBasic
# load common script and check programs
. common_script.sh

function get_project {
	get_source_files $1 NOERROR
	if [ "$SOURCE_FILES" == "" ] ; then
		DOTNET_VERSION=$(dotnet --version | grep -o "^...")
		local config_filename=default.$1
		cat >> $config_filename << END_CONFIG
<Project Sdk="Microsoft.NET.Sdk">
  <PropertyGroup>
    <OutputType>Exe</OutputType>
    <RootNamespace>vpl</RootNamespace>
    <TargetFramework>net$DOTNET_VERSION</TargetFramework>
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

check_program dotnet
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
get_project vbproj VisualBasic
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
	else
		echo "Error: execution file not found"
	fi
fi
