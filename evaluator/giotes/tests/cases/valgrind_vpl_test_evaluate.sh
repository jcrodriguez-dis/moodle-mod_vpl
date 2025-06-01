#!/bin/bash
command -v valgrind > /dev/null
if [ "$?" == "0" ] ; then
	assertErrors "SUMMARY: 0 errors"
else
	echo -n " not available"
	exit 0
fi
