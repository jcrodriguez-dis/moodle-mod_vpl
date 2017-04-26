#!/bin/bash
cat > vpl_execution << "ENDOFSCRIPT"
#!/bin/bash
read A
echo "$A"
ENDOFSCRIPT
chmod +x vpl_execution
