#!/bin/bash

# Overwrite vpl_execution with the shebang line
echo "#!/bin/bash" > vpl_execution

# Append the command to run the Python solution
echo "$PROGRAM \"solution.py\" \$@" >> vpl_execution

# Make vpl_execution executable
chmod +x vpl_execution
