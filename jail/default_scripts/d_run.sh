#!/bin/bash
# Default D language run script for VPL
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author  Daniel Ojeda Loisel
#         Juan Vega Rodriguez
#         Miguel Viera González

# @vpl_script_description Using gdc with math and util libs
# load common script and check programs
. common_script.sh
check_program gdc
if [ "$1" == "version" ] ; then
	echo "#!/bin/bash" > vpl_execution
	echo "gdc --version | head -n2" >> vpl_execution
	chmod +x vpl_execution
	exit
fi 
get_source_files d
#compile
gdc -o vpl_execution -lm -lutil $SOURCE_FILES
