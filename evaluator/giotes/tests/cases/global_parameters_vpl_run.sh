#!/bin/bash
cat > b.sh << 'ENDOFSCRIPT'
#!/bin/bash
read A
echo "$A"3
exit 3
ENDOFSCRIPT
chmod +x b.sh

cat > a.sh << 'ENDOFSCRIPT'
#!/bin/bash
read A
echo "$A"2"$1""$2"
exit $A
exit 3
ENDOFSCRIPT
chmod +x a.sh

cat > vpl_execution << 'ENDOFSCRIPT'
#!/bin/bash
read A
echo "$A"1
exit 3
ENDOFSCRIPT
chmod +x vpl_execution
