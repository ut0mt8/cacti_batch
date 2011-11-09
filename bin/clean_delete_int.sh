#!/bin/sh

[ $# -ne 1 ] && echo "Usage $0 <host-id>" && exit 1

~/bin/cacticli.php  --host-id $1 --list-snmp-if | grep up | awk '{ print $2 }' | sort > /tmp/up_$1
~/bin/cacticli.php  --host-id $1 --list-graphs | grep if | awk '{ print $5 }' | sort > /tmp/graphed_$1
~/bin/cacticli.php  --host-id $1 --list-graphs | grep if | sort > /tmp/graphedid_$1

while read f ; do
        g=""
        g=`grep "$f" /tmp/up_$1`
        if [ "$g" == "" ] ;then
                id=`grep "$f " /tmp/graphedid_$1 | awk '{ print $1 }'`
                if [ "$id" != "" ]; then
                        ~/bin/cacticli.php  --delete-graph $id --host-id $1
                fi
        fi
done < /tmp/graphed_$1

rm -f /tmp/graphed_$1 /tmp/up_$1 /tmp/graphedid_$1
