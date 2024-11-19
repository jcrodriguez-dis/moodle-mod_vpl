#!/bin/bash
grep -e 'regular expression compilation error' "$VPLTESTOUTPUT" >/dev/null
