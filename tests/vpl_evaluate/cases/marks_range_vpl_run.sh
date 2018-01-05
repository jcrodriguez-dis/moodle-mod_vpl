#!/bin/bash
echo "export VPL_GRADEMIN=50" >> vpl_environment.sh
echo "export VPL_GRADEMAX=100" >> vpl_environment.sh
cat > vpl_execution << ENDOFSCRIPT
#!/bin/bash
echo -n "match"
ENDOFSCRIPT
chmod +x vpl_execution