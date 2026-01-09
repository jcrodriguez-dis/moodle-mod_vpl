#!/bin/bash
echo "export VPL_VARIATION=V1" >> vpl_environment.sh
cat > vpl_execution << "ENDOFSCRIPT"
#!/bin/bash
read A
[ "$A" == "1k" ] && printf "%.0sA" {1..1024}
[ "$A" == "4k" ] && printf "%.0sB" {1..4096}
[ "$A" == "9999" ] && printf "%.0sC" {1..9999}
[ "$A" == "16k" ] && printf "%.0sD" {1..16384}

ENDOFSCRIPT
chmod +x vpl_execution
