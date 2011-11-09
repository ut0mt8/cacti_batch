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
include "api_auto.php";

// high level function
function getTreeId($treename)
{
    $treeId = db_fetch_cell("SELECT id, name FROM graph_tree WHERE name='$treename'");
    return $treeId;
}

function getGraphId($devicename,$graphname)
{
    $name = "$devicename $graphname";
    $deviceId = getHostId($devicename);
    if ($deviceId < 0 ) return -1;
    $deviceGraph = getHostGraphs($deviceId);
    foreach ($deviceGraph as $h) {
        if ($h['title_cache'] == $name ) return $h['lid'];
        if ($h['title_cache'] == $name." " ) return $h['lid'];
    }
    return -1;
}

function getGraphLikeId($devicename,$graphlike)
{

    $name = "$devicename $graphlike";
    $deviceId = getHostId($devicename);
    if ($deviceId < 0 ) return -1;
    $deviceGraph = getHostGraphs($deviceId);
    foreach ($deviceGraph as $h) {
        $pos = strpos($h['title_cache'],$name);
        if (!($pos === false)) return $h['lid'];
    }
    return -1;
}

function getDataSourceId($graphId)
{
    $dataSourceId = db_fetch_cell("SELECT DISTINCT data_template_rrd.local_data_id
            FROM graph_templates_item, data_template_rrd
            WHERE graph_templates_item.local_graph_id = " . $graphId .
            " AND graph_templates_item.task_item_id = data_template_rrd.id");
    return $dataSourceId;

}

function getHostNodeId($devicename)
{
    global $treeid;

    $hostNodeId = db_fetch_cell("select id from graph_tree_items where title = '$devicename' and graph_tree_id = $treeid" );
    return $hostNodeId;

}

function deleteDeviceInTree($hostname)
{
    $dev = db_fetch_row("select id, graph_tree_id from graph_tree_items where title='$hostname' ");
    $order_key = db_fetch_cell("select order_key from graph_tree_items where id=".$dev['id']." and graph_tree_id=".$dev['graph_tree_id']." " );
    if (preg_match("/^([0-9]{3})0+$/", $order_key , $matches)) {
        $search_key = $matches[1];
        db_execute("delete from graph_tree_items where order_key like '$search_key%%' and graph_tree_id=".$dev['graph_tree_id']);
    }
}

function addGraphTree($name,$gid,$rraid,$hostnodeid)
{
    global $treeid, $addGraphToHost;

    // fix garbage in name
    $name = preg_replace("/;/","",$name);

    $order_key = db_fetch_cell("select order_key from graph_tree_items where id=$hostnodeid and graph_tree_id = $treeid");

    if ( preg_match("/^([0-9]{3})0+$/", $order_key , $matches) == 1 ) { 
        $search_key = $matches[1];
    } else {
        echo "Warning : could not find order_key for host : $hostnodeid \n";
        return;
    }   

    // add the graph for the host header
    $graphgraphid = db_fetch_cell("select id from graph_tree_items where order_key like '$search_key%%' and local_graph_id = $gid and rra_id = $rraid and graph_tree_id = $treeid");
    // if ($addGraphToHost)
    //    if (!($graphgraphid > 0)) { echo "select id from graph_tree_items where order_key like '$search_key%%' and local_graph_id = $gid and rra_id = $rraid and graph_tree_id = $treeid \n"; addTreeNodeGraph($gid,$rraid,$hostnodeid,$treeid); }
    if ($addGraphToHost)
        if (!($graphgraphid > 0)) { addTreeNodeGraph($gid,$rraid,$hostnodeid,$treeid); }

    // add the header for the graph
    $graphnodeid = db_fetch_cell("select id from graph_tree_items where order_key like '$search_key%%' and title = '$name' and graph_tree_id = $treeid");
    //if (!($graphnodeid > 0)) { echo "header for graph $order_key , $search_key , $graphgraphid , $graphnodeid , $name,$gid,$rraid,$hostnodeid \n"; $graphnodeid = addTreeNodeHeader($name,$hostnodeid,$treeid); }
    if (!($graphnodeid > 0)) { $graphnodeid = addTreeNodeHeader($name,$hostnodeid,$treeid); }

    // add the graph for the header
    $order_key = db_fetch_cell("select order_key from graph_tree_items where id=$graphnodeid and graph_tree_id = $treeid");

    if ( preg_match("/^([0-9]{6})0+$/", $order_key , $matches)  == 1 ) { 
        $search_key = $matches[1];
    } else {
        echo "Warning : could not find order_key for graph : $graphnodeid \n";
        return;
    }  

    $graphgraphid = db_fetch_cell("select id from graph_tree_items where order_key like '$search_key%%' and local_graph_id = $gid and rra_id = $rraid and graph_tree_id = $treeid");
    // if (!($graphgraphid > 0)) { echo "graph for header order key: $order_key , search :  $search_key , graphgraph id : $graphgraphid , graphnodeid: $graphnodeid , name: $name, gid : $gid, $rraid,$hostnodeid \n"; addTreeNodeGraph($gid,$rraid,$graphnodeid,$treeid); }
    if (!($graphgraphid > 0)) { addTreeNodeGraph($gid,$rraid,$graphnodeid,$treeid); }

}


// Example : JunMem, CiscoCpu
function graphValue($devicename,$name)
{
    global $template_graph;
    $template_id = $template_graph[$name];

    $hostId = getHostId($devicename);
    if ($hostId < 0 ) return -1; //the device does not exist not cool :)

    $gid = getGraphId($devicename,$name);
    // we do not found the graph so graph it
    if ($gid < 0 ) $gid = addGraphCG($template_id,$hostId,$devicename." ".$name);

    $rraid = getDataSourceId($gid);
    $hostnodeid = getHostNodeId($devicename);

    if ( $hostnodeid > 0 ) addGraphTree($name,$gid,$rraid,$hostnodeid);

    return 0;
}

function graphNAvol($devicename,$volume)
{
    global $template_graph;
    global $snmp_qr;
    global $snmp_qt;
    $template_id = $template_graph['NAvol'];
    $snmp_q = $snmp_qr['NAvol'];
    $qt = $snmp_qt['NAvol'];

    $hostId = getHostId($devicename);
    if ($hostId < 0 ) return -1; //the device does not exist not cool :)

    // volume file
    $gid = getGraphId($devicename,"disk ".$volume);
    // we do not found the graph so graph it
    if ($gid < 0 ) $gid = addGraphDS($template_id,$hostId,$devicename." disk ".$volume,$snmp_q,"dfMountedOn",$qt,$volume);

    $rraid = getDataSourceId($gid);
    $hostnodeid = getHostNodeId($devicename);

    if ( $hostnodeid > 0 ) addGraphTree("disk ".$volume,$gid,$rraid,$hostnodeid);

    return 0;
}

function graphNcpu($devicename,$cpu)
{
    global $template_graph;
    global $snmp_qr;
    global $snmp_qt;
    $template_id = $template_graph['Ncpu'];
    $snmp_q = $snmp_qr['Ncpu'];
    $qt = $snmp_qt['Ncpu'];

    $hostId = getHostId($devicename);
    if ($hostId < 0 ) return -1; //the device does not exist not cool :)

    // volume file
    $gid = getGraphId($devicename,"cpu ".$cpu);
    // we do not found the graph so graph it
    if ($gid < 0 ) $gid = addGraphDS($template_id,$hostId,$devicename." cpu ".$cpu,$snmp_q,"hrProcessorFrwID",$qt,$cpu);

    $rraid = getDataSourceId($gid);
    $hostnodeid = getHostNodeId($devicename);

    if ( $hostnodeid > 0 ) addGraphTree("cpu ".$cpu,$gid,$rraid,$hostnodeid);

    return 0;
}

function graphHD($devicename,$partname)
{
    global $template_graph;
    global $snmp_qr;
    global $snmp_qt;
    $template_id = $template_graph['HD'];
    $snmp_q = $snmp_qr['HD'];
    $qt = $snmp_qt['HD'];

    $hostId = getHostId($devicename);
    if ($hostId < 0 ) return -1; //the device does not exist not cool :)

    $gid = getGraphId($devicename,"disk ".$partname);
    // we do not found the graph so graph it
    if ($gid < 0 ) $gid = addGraphDS($template_id,$hostId,$devicename." disk ".$partname,$snmp_q,"hrStorageDescr",$qt,$partname);

    $rraid = getDataSourceId($gid);
    $hostnodeid = getHostNodeId($devicename);

    if ( $hostnodeid > 0 ) addGraphTree("disk ".$partname,$gid,$rraid,$hostnodeid);

    return 0;
}

function graphIF($devicename,$ip,$comm,$ifindex,$ifindextype,$ifname,$ifalias)
{
    global $verbose;
    global $template_graph;
    global $snmp_qr;
    global $snmp_qt;
    $template_id = $template_graph['IF'];
    $snmp_q = $snmp_qr['IF'];
    $qt_int = $snmp_qt['IF'];
    $qt_int64 = $snmp_qt['IF64'];

    $hostId = getHostId($devicename);
    if ($hostId < 0 ) return -1; //the device does not exist not cool :)

    if ($ifalias != "") {
        $nifalias = preg_replace("/[^A-Za-z0-9_\-\.\s\s+]/","",$ifalias);
        $graphName = $devicename." if ".$nifalias;
        $gid = getGraphId($devicename,"if ".$nifalias);
    } else {
        $nifname = preg_replace("/[^A-Za-z0-9_\-\.\s\s+]/","",$ifname);
        $graphName = $devicename." if ".$nifname;
        $gid = getGraphId($devicename,"if ".$nifname);
    }

    // we do not found the graph so graph it
    if ($gid < 0 ) { 
        $c64 = @snmp2_get($ip, $comm, ".1.3.6.1.2.1.31.1.1.1.6.".$ifindex);
        if ( $c64 && preg_match("/Counter64/",$c64) ) {
            if ($verbose) echo "Adding interface graph with 64 bits counters\n";
            $gid = addGraphDS($template_id,$hostId,$graphName,$snmp_q,$ifindextype,$qt_int64,$ifname);
        }
        else {
            if ($verbose) echo "Adding interface graph with 32 bits counters\n";
            $gid = addGraphDS($template_id,$hostId,$graphName,$snmp_q,$ifindextype,$qt_int,$ifname);
        }   
    }   
    $rraid = getDataSourceId($gid);
    $hostnodeid = getHostNodeId($devicename);

    if ( $hostnodeid > 0 ) 
        if ($ifalias != "") 
            addGraphTree("if ".$nifalias,$gid,$rraid,$hostnodeid);
        else 
            addGraphTree("if ".$nifname,$gid,$rraid,$hostnodeid);
            
    return 0;
}

function graphNsIF($devicename,$ip,$comm,$ifindex,$ifname,$ifalias)
{
    global $verbose;
    global $template_graph;
    global $snmp_qr;
    global $snmp_qt;
    $template_id = $template_graph['NsIF'];
    $snmp_q = $snmp_qr['NsIF'];
    $qt = $snmp_qt['NsIF'];

    $hostId = getHostId($devicename);
    if ($hostId < 0 ) return -1; //the device does not exist not cool :)

    $gid = getGraphId($devicename,"ns if ".$ifname);

    // we do not found the graph so graph it
    if ($gid < 0 ) $gid = addGraphDS($template_id,$hostId,$devicename." ns if ".$ifname." ".$ifalias,$snmp_q,"nsIfName",$qt,$ifname);

    $rraid = getDataSourceId($gid);
    $hostnodeid = getHostNodeId($devicename);

    if ( $hostnodeid > 0 ) addGraphTree("ns if ".$ifname." ".$ifalias,$gid,$rraid,$hostnodeid);

    return 0;
}

function graphPKT($devicename,$ip,$comm,$ifindex,$ifindextype,$ifname,$ifalias)
{
    global $verbose;
    global $template_graph;
    global $snmp_qr;
    global $snmp_qt;
    $template_id = $template_graph['PKT'];
    $snmp_q = $snmp_qr['PKT'];
    $qt = $snmp_qt['PKT'];

    $hostId = getHostId($devicename);
    if ($hostId < 0 ) return -1; //the device does not exist not cool :)

    if ($ifalias != "")
        $gid = getGraphId($devicename,"pkt ".$ifname." ".$ifalias);
    else
        $gid = getGraphId($devicename,"pkt ".$ifname);

    // we do not found the graph so graph it
    if ($gid < 0 ) $gid = addGraphDS($template_id,$hostId,$devicename." pkt ".$ifname." ".$ifalias,$snmp_q,$ifindextype,$qt,$ifname);

    $rraid = getDataSourceId($gid);
    $hostnodeid = getHostNodeId($devicename);

    if ( $hostnodeid > 0 ) addGraphTree("pkt ".$ifname." ".$ifalias,$gid,$rraid,$hostnodeid);

    return 0;
}

function createDeviceGraphs($devicename,$ip,$comm)
{
    global $verbose,$graphing,$ifIndexType,$excludeIF,$includeIF,$excludeIFnoip,$excludePart,$includePart; 

    $hostId = getHostId($devicename);
    if ($hostId < 0 ) return -1; //the device does not exist not cool :)

    if ($verbose) echo "Processing $devicename\n";

    // for each Graph defined in graphing array call the appropriate funcion
    // see .cnf file
    foreach ($graphing as $graphname => $graphvalue) {
        if ($graphname == "Ncpu") continue; // handled separatly
        if ($graphname == "NAvol") continue; // handled separatly
        if ($graphname == "HD") continue; // handled separatly
        if ($graphname == "IF") continue; // handled separatly
        if ($graphname == "NsIF") continue; // handled separatly
        if ($graphname == "PKT") continue; // handled separatly
        if ($graphvalue) graphValue($devicename,$graphname);
    }

    // graph all cpu on server 
    if (isset($graphing['Ncpu'])) {
        $cpus = getSNMPNcpu($hostId);
        while (list($field, $values) = each ($cpus))
        {
            if (!isset($values['hrProcessorFrwID'])) continue;
            $cpu = $values['hrProcessorFrwID'];
            graphNcpu($devicename,$cpu);
        }
    }

    // graph all volume on netapp
    if (isset($graphing['NAvol'])) {
        $parts = getSNMPNAvol($hostId);
        while (list($field, $values) = each ($parts))
        {
            if (!isset($values['dfMountedOn'])) continue;
            $vol = $values['dfMountedOn'];
            if (preg_match('/\.snapshot/',$vol)) continue; // dont graph these part
                graphNAvol($devicename,$vol);
        }
    }

    // graph all the part on the disk
    if (isset($graphing['HD'])) {
        $parts = getSNMPpart($hostId);
        while (list($field, $values) = each ($parts))
        {
            if (!isset($values['hrStorageDescr'])) continue;
            $part = $values['hrStorageDescr'];
            // graph only part in includePart
            if (isset($includePart))
                if (preg_match($includePart,$values['hrStorageDescr'])) graphHD($devicename,$part);
            // and basic one
            if (preg_match('/\//',$part)) {
                if (preg_match($excludePart,$part)) continue; // dont graph these part
                graphHD($devicename,$part);
            }
        }
    }

    // graph netscreen ifs 
    if (isset($graphing['NsIF'])) {
        $ifs = getSNMPNsif($hostId);
        while (list($field, $values) = each ($ifs))
        {
            if (!isset($values['nsIfFlowIfIdx'])) continue;
            if (!isset($values['nsIfName'])) continue;
            if (!isset($values['nsIfStatus'])) continue;
            if (($values['nsIfStatus'] == 1)||($values['nsIfStatus'] == "3")) {  // graph only int marked up
                if (isset($excludeIF))
                    if (preg_match($excludeIF,$values['nsIfName'])) continue; // dont graph excluded interfaces
                if (isset($includeIF))
                    if (!(preg_match($includeIF,$values['nsIfName']))) continue; // dont graph interfaces not in
                graphNsIF($devicename,$ip,$comm,$values['nsIfFlowIfIdx'],$values['nsIfName'],"");
            }
        }
    }

    // graph ifs 
    if (isset($graphing['IF'])) {
        $ifs = getSNMPif($hostId);
        while (list($field, $values) = each ($ifs))
        {
            if (!isset($ifIndexType)) { $ifIndexType = "ifDescr"; }  
            if (!isset($values['ifOperStatus'])) continue;
            if (!isset($values['ifDescr'])) continue;
            if (!isset($values['ifIndex'])) continue;
            if (!isset($values['ifIP']) && $excludeIFnoip ) continue;
            if (($values['ifOperStatus'] == 1)||($values['ifOperStatus'] == "Up")) {  // graph only int marked up
                if (isset($excludeIF))
                    if (preg_match($excludeIF,$values['ifDescr'])) continue; // dont graph excluded interfaces
                if (isset($includeIF))
                    if (!(preg_match($includeIF,$values['ifDescr']))) continue; // dont graph interfaces not in
                if (isset($values['ifAlias'])&&($values['ifAlias']!="")) { //the alias is set use it
                    graphIF($devicename,$ip,$comm,$values['ifIndex'],$ifIndexType,$values[$ifIndexType],$values['ifAlias']);
                }
                else {   
                    graphIF($devicename,$ip,$comm,$values['ifIndex'],$ifIndexType,$values[$ifIndexType],"");
                }
            }
        }
    }

    // graph pkt 
    if (isset($graphing['PKT'])) {
        $ifs = getSNMPif($hostId);
        while (list($field, $values) = each ($ifs))
        {
            if (!isset($ifIndexType)) { $ifIndexType = "ifDescr"; }  
            if (!isset($values['ifOperStatus'])) continue;
            if (!isset($values['ifDescr'])) continue;
            if (!isset($values['ifIndex'])) continue;
            if (!isset($values['ifIP']) && $excludeIFnoip ) continue;
            if (($values['ifOperStatus'] == 1)||($values['ifOperStatus'] == "Up")) {  // graph only int marked up
                if (isset($excludeIF))
                    if (preg_match($excludeIF,$values['ifDescr'])) continue; // dont graph excluded interfaces
                if (isset($includeIF))
                    if (!(preg_match($includeIF,$values['ifDescr']))) continue; // dont graph interfaces not in
                if (isset($values['ifAlias'])&&($values['ifAlias']!="")) { // the alias is set use it
                    graphPKT($devicename,$ip,$comm,$values['ifIndex'],$ifIndexType,$values[$ifIndexType],$values['ifAlias']);
                }
                else {   
                    graphPKT($devicename,$ip,$comm,$values['ifIndex'],$ifIndexType,$values[$ifIndexType],"");
                }
            }
        }
    }
}


?>
