#!/bin/bash
if [ -s "$VPLTESTERRORS" ] ; then
    exit 1
fi
grep -e 'Global timeout' "$VPLTESTOUTPUT" >/dev/null
if [ $? -ne 0 ] ; then
    exit 1
fi
grep -e 'Program timeout' "$VPLTESTOUTPUT" >/dev/null
