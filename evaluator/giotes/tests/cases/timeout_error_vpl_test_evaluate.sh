#!/bin/bash
if [ -s "$VPLTESTERRORS" ] ; then
    exit 1
fi
assertOutput 'Global timeout'
assertOutput 'Program timeout'
