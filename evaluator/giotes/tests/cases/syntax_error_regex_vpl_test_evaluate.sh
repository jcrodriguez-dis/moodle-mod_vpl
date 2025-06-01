#!/bin/bash
if [ -s "$VPLTESTERRORS" ] ; then
    exit 1
fi
assertOutput 'invalid flag in regex output'
