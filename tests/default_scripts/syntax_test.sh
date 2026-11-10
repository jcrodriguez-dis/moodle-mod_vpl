#!/bin/bash
# This file is part of VPL for Moodle.
# License GNU/GPL, see LICENSE.txt or http://www.gnu.org/licenses/gpl-2.0.html

for script_file in "$SCRIPTS_DIR"/*.sh; do
    run_case "${script_file##*/}: Bash syntax" bash -n "$script_file"
done
