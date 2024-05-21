#!/bin/bash
cat > vpl_execution << "ENDOFSCRIPT"
#!/bin/bash
read A
echo "$A"
exit $A
ENDOFSCRIPT
chmod +x vpl_execution
