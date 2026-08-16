#!/bin/bash
if [ -s "$VPLTESTERRORS" ] ; then
    exit 1
fi
assertOutput "(line:2) unknow parameter"
assertOutputFalse "(line:3)"
assertOutputFalse "(line:4)"
assertOutput "(line:5) text out of parameter or comment"
