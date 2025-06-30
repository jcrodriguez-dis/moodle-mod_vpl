#!/bin/bash
if [ -s "$VPLTESTERRORS" ] ; then
    exit 1
fi
assertOutput "Grade :=>>73.50$"
assertOutput "Grade :=>>73.50$"
