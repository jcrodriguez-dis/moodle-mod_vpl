#!/bin/bash
cat > vpl_execution << "ENDOFSCRIPT"
#!/bin/bash
echo "$1"
ENDOFSCRIPT
chmod +x vpl_execution

cat > program_sum << "ENDOFSCRIPT"
#!/bin/bash
let R=$1+$2
echo "$R"
ENDOFSCRIPT
chmod +x program_sum

cat > program_echo << "ENDOFSCRIPT"
#!/bin/bash
while [ "$1" != "" ] ; do
	echo -n "$1 "
	shift
done
echo
ENDOFSCRIPT
chmod +x program_echo
