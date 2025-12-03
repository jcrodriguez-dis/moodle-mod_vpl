#!/bin/bash
# This file is part of VPL for Moodle
# SPRESAI evaluate script for VPL
# Copyright (C) 2025 onwards Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jc.rodriguezdelpino@ulpgc.es>

# Load VPL environment vars.

. ./common_script.sh
# Check required programs
check_program python3
(
    cat vpl_environment.sh
    echo
    echo "source /opt/pyenvs/global/bin/activate"
    echo "python3 spresai/evaluator.py"
) > vpl_execution
chmod +x vpl_execution
