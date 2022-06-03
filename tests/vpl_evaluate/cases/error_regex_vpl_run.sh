#!/bin/bash
cat > vpl_execution << ENDOFSCRIPT
#!/bin/bash
echo "Line 1 regular expression test"
echo "Line 2 regular expression test"
ENDOFSCRIPT
chmod +x vpl_execution