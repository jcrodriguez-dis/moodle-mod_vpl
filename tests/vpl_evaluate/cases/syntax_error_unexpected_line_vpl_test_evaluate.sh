#!/bin/bash
if [ -s "$VPLTESTERRORS" ] ; then
    exit 1
fi
assertOutput "(line:2) unknow parameter"
assertOutputFalse "(line:3)"
assertOutput "(line:4) text out of parameter or comment"
