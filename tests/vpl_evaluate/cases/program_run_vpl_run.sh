#!/bin/bash
cat > vpl_execution << "ENDOFSCRIPT"
#!/bin/bash
read A
echo "$A"
exit $A
done
ENDOFSCRIPT
chmod +x vpl_execution
cat > program_to_run1 << ENDOFSCRIPT
#!/bin/bash
echo -n "OK"
ENDOFSCRIPT
chmod +x program_to_run1
cat > program_to_run2 << ENDOFSCRIPT
#!/bin/bash
echo -n "BAD"
exit 1
ENDOFSCRIPT
chmod +x program_to_run2