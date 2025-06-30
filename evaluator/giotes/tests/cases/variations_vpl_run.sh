#!/bin/bash
echo "export VPL_VARIATION=V1" >> vpl_environment.sh
cat > vpl_execution << "ENDOFSCRIPT"
#!/bin/bash
read A
echo "$A"
ENDOFSCRIPT
chmod +x vpl_execution
