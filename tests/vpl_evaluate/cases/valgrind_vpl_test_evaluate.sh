#!/bin/bash
command -v valgrind > /dev/null
if [ "$?" == "0" ] ; then
	grep -e "SUMMARY: 0 errors" "$VPLTESTERRORS" >/dev/null
	exit $?
else
	echo -n " not available"
	exit 0
fi
