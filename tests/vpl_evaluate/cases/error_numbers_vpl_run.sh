#!/bin/bash
cat > vpl_execution << ENDOFSCRIPT
#!/bin/bash
echo -n "3 4 5.5 8"
ENDOFSCRIPT
chmod +x vpl_execution