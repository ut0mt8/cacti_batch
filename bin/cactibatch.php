//!/usr/bin/php 
<?php
/*
   +-------------------------------------------------------------------------+
   | RMZ - 2011   raph AT futomaki.net                                       |
   +-------------------------------------------------------------------------+
   | This program is free software; you can redistribute it and/or           |
   | modify it under the terms of the GNU General Public License             |
   | as published by the Free Software Foundation; either version 2          |
   | of the License, or (at your option) any later version.                  |
   |                                                                         |
   | This program is distributed in the hope that it will be useful,         |
   | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
   | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
   | GNU General Public License for more details.                            |
   +-------------------------------------------------------------------------+
   | cacti: a php-based graphing solution                                    |
   +-------------------------------------------------------------------------+
 */

/* do NOT run this script through a web browser */
if (!isset($_SERVER["argv"][0])) {
    die("<br><strong>This script is only meant to run at the command line.</strong>");
}

$no_http_headers = true;
include "/home/cacti/cactibatch/conf/cactibatch.conf";
include "/home/cacti/cactibatch/lib/api_batch.php";
$confdir = "/home/cacti/cactibatch/conf/";

function processDevice($devicename,$ip,$comm) {
    
    global $template_host, $treeid, $community, $snmp_ver, $createTree;

    if ((isset($comm)) && ($comm != "")) $community = $comm;
    $hostId = getHostId($devicename);

    # device does not existe try to create it.
    if ($hostId < 0 ) $hostId = addDevice($template_host,$devicename,$ip,$community,$snmp_ver);

    if ($hostId < 0 ) return -1;  # ?

    $hostnodeid = getHostNodeId($devicename);
    if ((!($hostnodeid > 0 ))&&($createTree)) addTreeNodeHeader($devicename,0,$treeid);

    createDeviceGraphs($devicename,$ip,$comm);

    return 0;
}

function processView($devicename,$graphname) {
    
    global $template_host, $treeid, $community, $snmp_ver; 

    if ((isset($comm)) && ($comm != "")) $community = $comm;

    $hostId = getHostId($devicename);
    if ($hostId < 0 ) return -1;  # misconfig ?!
    
    $gid = getGraphLikeId($devicename,$graphname);
    if ($gid < 0 ) return -1; # misconfig ?!

    $rraid = getDataSourceId($gid);
    if ($rraid < 0 ) return -1; #  ???

    # add the graph for the host header
        $graphgraphid = db_fetch_cell("select id from graph_tree_items where local_graph_id = $gid and rra_id = $rraid and graph_tree_id = $treeid");
        if (!($graphgraphid > 0)) { addTreeNodeGraph($gid,$rraid,0,$treeid); }

    return 0;
}


// main 

global $treeid;
chdir($confdir);

// search for something to delete
$files = glob("*.lst");
$newdevices = array();
$newtrees = array();

foreach ($files as $filename) {
    $lines = file ($filename);
    $treename = preg_replace('/\.lst/', "", $filename);
    $newtrees[$treename] = $filename;

    foreach ($lines as $line_num => $line) {
        if (preg_match("/^(#|\s*$)/",$line)) continue;
        $linepart = explode(";", $line);
        if ((!isset($linepart[0])) || (!isset($linepart[1])) || (!isset($linepart[2]))) {
            echo "Syntax Error in $filename\n";
            exit(1);
        }
        $newdevices[$linepart[1]] = $linepart[0];
    }
}
$olddevices = getHosts();
foreach ($olddevices as $host_id => $host_name) {
    if (!array_key_exists($host_name, $newdevices)) {
        $desc = getHostDescName($host_id);
        if ($verbose) echo "Deleting device $host_name $desc $host_id\n";
        deleteDeviceInTree($desc);
        deleteDevice($host_id);
    }
}
// view tree
$files = glob("*.view");
foreach ($files as $filename) {
    $lines = file ($filename);
    $treename = preg_replace('/\.view/', "", $filename);
    $newtrees[$treename] = $filename;
}
// static tree
$files = glob("*.static");
foreach ($files as $filename) {
    $lines = file ($filename);
    $treename = preg_replace('/\.static/', "", $filename);
    $newtrees[$treename] = $filename;
}

// search for tree to delete
$oldtrees = getTrees();
foreach ($oldtrees as $tree_id => $tree_name) {
    if (!array_key_exists($tree_name, $newtrees)) {
        if ($verbose) echo "Deleting tree $tree_name $tree_id\n";
        deleteTree($tree_id);
    }
}

// main loop
$files = glob("*.lst");

// search for adding
foreach ($files as $filename) {
    global $graphing,$ifIndexType,$excludeIF,$includeIF,$excludeIFnoip,$excludePart,$includePart,$snmp_ver;
    $graphing = array();
    unset($ifIndexType);
    unset($excludeIF);
    unset($includeIF);
    unset($excludeIFnoip);
    unset($excludePart);
    unset($includePart);

    $treename = preg_replace('/\.lst/', "", $filename);
    include "$treename.cnf";

    $treeId = getTreeId($treename);
    if (!($treeId > 0 )) $treeId = addTree($treename,"a");

    if (!($treeId > 0 )) return -1; # §?

    $treeid = $treeId;
    $lines = file ($filename);

    print "== $treename \n";

    foreach ($lines as $line_num => $line) {
        if (preg_match("/^#/",$line)) continue;
        $linepart = explode(";", $line);
        if ((!isset($linepart[0])) || (!isset($linepart[1])) || (!isset($linepart[2]))) {
            echo "Syntax Error in $filename\n";
            exit(1);
        }
        $comm = preg_replace("/\n/","",$linepart[2]);
        processDevice($linepart[0],$linepart[1],$comm);
    }
}

// main loop for view files
$files = glob("*.view");

foreach ($files as $filename) {

    $treename = preg_replace('/\.view/', "", $filename);
    $treeId = getTreeId($treename);
    if (!($treeId > 0 )) $treeId = addTree($treename,"a");

    if (!($treeId > 0 )) return -1; # §?

    $treeid = $treeId;
    $lines = file ($filename);

    print "=- $treename \n";

    foreach ($lines as $line_num => $line) {
        if (preg_match("/^#/",$line)) continue;
        $linepart = explode(";", $line);
        if ((!isset($linepart[0])) || (!isset($linepart[1]))) {
            echo "Syntax Error in $filename\n";
            exit(1);
        }
        $comm = preg_replace("/\n/","",$linepart[2]);
        processView($linepart[0],$linepart[1]);
    }
}


// main loop for static files
$files = glob("*.static");

foreach ($files as $filename) {

    $treename = preg_replace('/\.static/', "", $filename);
    $treeId = getTreeId($treename);

    print "=> $treename \n";

    if (!($treeId > 0 )) $treeId = addTree($treename,"a");
    if (!($treeId > 0 )) return -1; # §?

}

exit(0);

?>
