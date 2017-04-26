#!/bin/bash
if [ -s "$VPLTESTERRORS" ] ; then
    exit 1
fi
ret=0
grep -e "Grade :=>> 0$" "$VPLTESTOUTPUT" >/dev/null
if [ "$?" != "0" ] ; then
    echo -n " g"
	ret=1
fi
grep -e "^Fail_message_1$" "$VPLTESTOUTPUT" >/dev/null
if [ "$?" != "0" ] ; then
    echo -n " f1"
	ret=1
fi
grep -e "^Fail_message_2$" "$VPLTESTOUTPUT" >/dev/null
if [ "$?" != "0" ] ; then
    echo -n " f2"
	ret=1
fi
grep -e "^Fail_message_4$" "$VPLTESTOUTPUT" >/dev/null
if [ "$?" != "0" ] ; then
    echo -n " f3"
	ret=1
fi
grep -c -e "Program output" "$VPLTESTOUTPUT" | grep -e "^1$" > /dev/null
if [ "$?" != "0" ] ; then
    echo -n " ne"
	ret=1
fi
exit $ret
