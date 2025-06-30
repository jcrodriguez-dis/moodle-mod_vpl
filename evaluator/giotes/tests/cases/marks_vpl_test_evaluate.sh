#!/bin/bash
if [ -s "$VPLTESTERRORS" ] ; then
    exit 1
fi
assertOutput "Reduction 1 (-1.5)"
assertOutput "Reduction 2 (-5)"
assertOutput "Grade :=>> 3.50$"
