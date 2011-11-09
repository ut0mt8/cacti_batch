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

include_once("/home/cacti/site/include/global.php");
include_once($config["base_path"]."/lib/utility.php");
include_once($config["base_path"]."/lib/api_data_source.php");
include_once($config["base_path"]."/lib/api_graph.php");
include_once($config["base_path"]."/lib/snmp.php");
include_once($config["base_path"]."/lib/data_query.php");
include_once($config["base_path"]."/lib/api_device.php");
include_once($config["base_path"]."/lib/data_query.php");
include_once($config["base_path"]."/lib/sort.php");
include_once($config["base_path"]."/lib/template.php");
include_once($config["base_path"].'/lib/api_tree.php');
include_once($config["base_path"].'/lib/tree.php');

// display function
function displayGraphTemplates()
{
    $templates = getGraphTemplates();
    echo "Known Graph Templates:\n\n";
    while (list($id, $name) = each ($templates))
    {
        echo $id . "\t" . $name . "\n";
    }
    echo "\n";
}

function displayHostTemplates()
{
    $host_templates = getHostTemplates();
    echo "Known host templates:\n\n";
    foreach ($host_templates as $id => $name) {
        echo "$id\t$name\n";
    }
    echo "\n";
}

function displayCommunities()
{
    echo "Known communities:\n\n";
    $communities = db_fetch_assoc("select snmp_community from host where snmp_community != \"\" group by snmp_community");
    foreach ($communities as $community) {
        echo $community["snmp_community"]."\n";
    }
    echo "\n";
}

function displayQueryTypes($snmpQueryId)
{
    $types = getSNMPQueryTypes($snmpQueryId);
    echo "Known SNMP Query Types:\n\n";
    while (list($id, $name) = each ($types))
    {
        echo $id . "\t" . $name . "\n";
    }
    echo "\n";
}

function displaySNMPQueries()
{
    $queries = getSNMPQueries();
    echo "Known SNMP Queries:\n\n";
    while (list($id, $name) = each ($queries))
    {
        echo $id . "\t" . $name . "\n";
    }
    echo "\n";
}

function displaySNMPValues($hostId, $field)
{
    $values = getSNMPValues($hostId, $field);
    echo "Known values for $field for host $hostId:\n\n";
    while (list($value, $foo) = each($values))
    {
        echo "$value\n";
    }
    echo "\n";
}

function displaySNMPif($hostId)
{
    $fields = getSNMPif($hostId);
    echo "Known interface for host-id $hostId:\n\n";
    while (list($field, $values) = each ($fields))
    {
        if (($values['ifOperStatus'] == 1)|| ($values['ifOperStatus'] == "Up"))
            $state="up";
        else
            $state="down";
        if (isset($values['ifAlias'])&&($values['ifAlias']!=""))
            printf("%s\t%s\t%s\t%s\n", $field, $values['ifDescr'],$values['ifAlias'],$state);
        else
            printf("%s\t%s\t%s\t%s\n", $field, $values['ifDescr'],$values['ifDescr'],$state);
    }
    echo "\n";
}

function displaySNMPNsif($hostId)
{
    $fields = getSNMPNsif($hostId);
    echo "Known NS interface for host-id $hostId:\n\n";
    while (list($field, $values) = each ($fields))
    {
        if (($values['nsIfStatus'] == 1)|| ($values['nsIfStatus'] == "Up"))
            $state="up";
        else
            $state="down";
        printf("%s\t%s\t%s\t%s\n", $field, $values['nsIfName'],$values['nsIfName'],$state);
    }
    echo "\n";
}

function displaySNMPpart($hostId)
{
    $fields = getSNMPpart($hostId);
    echo "Known partitions for host-id $hostId:\n\n";
    while (list($field, $values) = each ($fields))
    {
        printf("%s\t%s\n", $field, $values['hrStorageDescr']);
    }
    echo "\n";
}

function displaySNMPNcpu($hostId)
{
    $fields = getSNMPNcpu($hostId);
    echo "Known cpu for host-id $hostId:\n\n";
    while (list($field, $values) = each ($fields))
    {
        printf("%s\t%s\n", $field, $values['hrProcessorFrwID']);
    }
    echo "\n";
}

function displaySNMPNAvol($hostId)
{
    $fields = getSNMPNAvol($hostId);
    echo "Known Netapp partitions for host-id $hostId:\n\n";
    while (list($field, $values) = each ($fields))
    {
        printf("%s\t%s\n", $field, $values['dfMountedOn']);
    }
    echo "\n";
}

function displaySNMPFields($hostId)
{
    $fields = getSNMPFields($hostId);
    echo "Known SNMP Fields for host-id $hostId:\n\n";
    while (list($field, $values) = each ($fields))
    {
        printf("%s\n", $field);
    }
    echo "\n";
}

function displayHostGraphs($HostId)
{
    $hg = getHostGraphs($HostId);
    echo "Known graph for host $HostId:\n\n";
    foreach ($hg as $h)
    {
        printf("%d\t%d\t%s\n",$h['lid'],$h['tid'],$h['title_cache']);
    }
    echo "\n";
}

function displayHosts()
{
    $hosts = getHosts();
    echo "Known Hosts:\n\n";
    while (list($id, $host) = each ($hosts))
    {
        echo $id . "\t" . $host . "\n";
    }
    echo "\n";
}

// get functions
function getHostTemplates()
{
    $tmparray = db_fetch_assoc("select id, name from host_template order by id");
    foreach ($tmparray as $template)
        $host_templates[$template["id"]] = $template["name"];
    return $host_templates;
}

function getAddresses()
{
    $addresses = array();
    $tmparray = db_fetch_assoc("select id, hostname from host order by hostname");
    foreach ($tmparray as $tmp)
        $addresses[$tmp["hostname"]] = $tmp["id"];
    return $addresses;
}

function getHostGraphs($HostId)
{
    $tmpArray = db_fetch_assoc("select l.id as lid, t.id as tid, title_cache from graph_local l, graph_templates_graph t where l.id=t.local_graph_id and host_id=".$HostId);
    return $tmpArray;
}

function getHosts()
{
    $hosts = array();
    $tmpArray = db_fetch_assoc("select id, hostname from host order by id");
    $hosts = array();
    foreach ($tmpArray as $host)
        $hosts[$host["id"]] = $host["hostname"];
    return $hosts;
}

function getHostId($host)
{  
    $tmpArray = db_fetch_assoc("select id from host where description=\"".$host."\"");
    if (isset($tmpArray[0]['id'])) return $tmpArray[0]['id'];
    else return -1;
}

function getHostDescName($hostid)
{  
    $tmpArray = db_fetch_assoc("select description from host where id=".$hostid);
    if (isset($tmpArray[0]['description'])) return $tmpArray[0]['description'];
    else return -1;
}

function getHostsDesc()
{
    $hosts = array();
    $tmparray = db_fetch_assoc("select id, description from host order by description");
    foreach ($tmparray as $tmp)
        $hosts[$tmp["description"]] = $tmp["id"];
    return $hosts;
}

function getSNMPFields($hostId)
{
    $fieldNames = array();
    $tmpArray = db_fetch_assoc("select distinct field_name from host_snmp_cache where host_id = " . $hostId . " order by field_name");
    foreach ($tmpArray as $f) {
        $fieldNames[$f["field_name"]] = 1;
    }
    return $fieldNames;
}

function getSNMPif($hostId)
{
    $g = array();
    $tmpArray = db_fetch_assoc("select distinct field_value from host_snmp_cache where host_id = " . $hostId . " and field_name=\"ifIndex\" order by field_value");
    foreach ($tmpArray as $f)
    {
        $snmp_index=$f['field_value'];
        $tmp2Array = db_fetch_assoc("select field_name,field_value from host_snmp_cache where host_id = " . $hostId . " and snmp_index=\"".$snmp_index."\" order by snmp_index");
        foreach ($tmp2Array as $f2) {
            $g[$snmp_index][$f2['field_name']] = $f2['field_value']; // ex : g[0]['ifName']='eth0'
        }
    }
    asort($g);
    return $g;
}

function getSNMPNsif($hostId)
{
    $g = array();
    $tmpArray = db_fetch_assoc("select distinct field_value from host_snmp_cache where host_id = " . $hostId . " and field_name=\"nsIfFlowIfIdx\" order by field_value");
    foreach ($tmpArray as $f)
    {
        $snmp_index=$f['field_value'];
        $tmp2Array = db_fetch_assoc("select field_name,field_value from host_snmp_cache where host_id = " . $hostId . " and snmp_index=\"".$snmp_index."\" order by snmp_index");
        foreach ($tmp2Array as $f2) {
            $g[$snmp_index][$f2['field_name']] = $f2['field_value']; 
        }
    }
    asort($g);
    return $g;
}

function getSNMPpart($hostId)
{
    $g = array();
    $tmpArray = db_fetch_assoc("select distinct field_value from host_snmp_cache where host_id = " . $hostId . " and field_name=\"hrStorageDescr\" order by field_value");
    foreach ($tmpArray as $f)
    {
        $snmp_index=$f['field_value'];
        $tmp2Array = db_fetch_assoc("select snmp_index,field_name,field_value from host_snmp_cache where host_id = " . $hostId . " and field_value=\"".$snmp_index."\" order by snmp_index");
        foreach ($tmp2Array as $f2) {
            $g[$f2['snmp_index']][$f2['field_name']] = $f2['field_value']; 
        }
    }
    asort($g);
    return $g;
}

function getSNMPNcpu($hostId)
{
    $g = array();
    $tmpArray = db_fetch_assoc("select distinct field_value from host_snmp_cache where host_id = " . $hostId . " and field_name=\"hrProcessorFrwID\" order by field_value");
    foreach ($tmpArray as $f)
    {
        $snmp_index=$f['field_value'];
        $tmp2Array = db_fetch_assoc("select snmp_index,field_name,field_value from host_snmp_cache where host_id = " . $hostId . " and field_value=\"".$snmp_index."\" order by snmp_index");
        foreach ($tmp2Array as $f2) {
            $g[$f2['snmp_index']][$f2['field_name']] = $f2['field_value']; 
        }
    }
    asort($g);
    return $g;
}

function getSNMPNAvol($hostId)
{
    $g = array();
    $tmpArray = db_fetch_assoc("select distinct field_value from host_snmp_cache where host_id = " . $hostId . " and field_name=\"dfMountedOn\" order by field_value");
    foreach ($tmpArray as $f)
    {
        $snmp_index=$f['field_value'];
        $tmp2Array = db_fetch_assoc("select snmp_index,field_name,field_value from host_snmp_cache where host_id = " . $hostId . " and field_value=\"".$snmp_index."\" order by snmp_index");
        foreach ($tmp2Array as $f2) {
            $g[$f2['snmp_index']][$f2['field_name']] = $f2['field_value']; 
        }
    }
    asort($g);
    return $g;
}

function getSNMPValues($hostId, $field)
{
    $values = array();
    $tmpArray = db_fetch_assoc("select field_value from host_snmp_cache where host_id = " . $hostId . " and field_name = '" . $field . "' order by field_value");
    foreach ($tmpArray as $v)
        $values[$v["field_value"]] = 1;
    return $values;
}

function getSNMPQueries()
{
    $queries = array();
    $tmpArray = db_fetch_assoc("select id, name from snmp_query order by id");
    foreach ($tmpArray as $q)
        $queries[$q["id"]] = $q["name"];
    return $queries;
}

function getSNMPQueryTypes($snmpQueryId)
{
    $types = array();
    $tmpArray = db_fetch_assoc("select id, name from snmp_query_graph where snmp_query_id = " . $snmpQueryId . " order by id");
    foreach ($tmpArray as $type)
        $types[$type["id"]] = $type["name"];
    return $types;
}

function getGraphTemplates()
{
    $graph_templates = array();
    $tmpArray = db_fetch_assoc("select id, name from graph_templates order by id");
    foreach ($tmpArray as $t)
        $graph_templates[$t["id"]] = $t["name"];
    return $graph_templates;
}

function getTrees()
{
    $graph_trees = array();
    $tmpArray = db_fetch_assoc("select id, name from graph_tree order by id");
    foreach ($tmpArray as $t)
        $graph_trees[$t["id"]] = $t["name"];
    return $graph_trees;
}


// main functions

function deleteGraph($LgId,$HostId)
{
    if( db_execute("delete from graph_templates_graph where local_graph_id=".$LgId) ) {
        if ( db_execute("delete from graph_local where id=".$LgId) ) {
            db_execute("delete from graph_tree_items where local_graph_id =".$LgId);
            return 1;
        }
    }    
    return -1;
}

function deleteTree($treeId)
{
    if( db_execute("delete from graph_tree where id=".$treeId) ) {
        if ( db_execute("delete from graph_tree_items where graph_tree_id=".$treeId) ) {
            return 1;
        }
    }    
    return -1;
}

function reloadQueryCache($hostId) 
{
    global $verbose;
    if ($hostId == "all") {
        $t = db_fetch_assoc("select id, description from host order by id");
        foreach ($t as $tt) {
            if ($verbose) printf("Reload cache for host (%s)\n", $tt['description']);
                run_data_query($tt['id'], 1);
        }
        return 0;
    }

    $hosts = getHosts();
    if (!isset($hosts[$hostId]))
    {
        if ($verbose) printf("No such host (%s) exists. Try --list-hosts\n", $hostId);
        return -1;
    }
    if ($verbose) printf("Reload cache for host-id (%s)\n", $hostId);
    run_data_query($hostId,1);
    return 0;

}

function deleteDevice($hostId) 
{
    // delete all data_source attached to this device
    $data_sources = db_fetch_assoc("select data_local.id as local_data_id from data_local where host_id=" . $hostId ); 
    if (sizeof($data_sources) > 0) {
        foreach ($data_sources as $data_source) {
            api_data_source_remove($data_source["local_data_id"]);
        }
    }
    // delete graphs from this device
    $graphs = db_fetch_assoc("select graph_local.id as local_graph_id from graph_local where graph_local.host_id=" . $hostId ); 
    if (sizeof($graphs) > 0) {
        foreach ($graphs as $graph) {
            api_graph_remove($graph["local_graph_id"]);
            db_execute("delete from graph_tree_items where local_graph_id =".$graph["local_graph_id"]);
        }
    }
    // delete the device
    db_execute("delete from graph_tree_items where host_id =".$hostId );
    api_device_remove($hostId);
    return 0;
}

function addDevice($hostTemplateId,$description,$ip,$community,$snmp_ver) 
{
    global $verbose, $snmp_username, $snmp_password, $snmp_port, $snmp_timeout;
    global $availability_method, $ping_method, $ping_port, $ping_timeout, $ping_retries;
    global $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $max_oids;
    $hosts          = getHosts();
    $graphTemplates = getGraphTemplates();
    $host_templates = getHostTemplates();
    $addresses = getAddresses();

    if (!isset($host_templates[$hostTemplateId])) {
        echo "Unknown template id  : $hostTemplateId\n";
        return -1;
    }
    if (isset($hosts[$description])) {
        db_execute("update host set hostname = '$ip' where id = " . $hosts[$description]);
        if ($verbose) echo "This host already exists in the database ($description) device-id : " . $hosts[$description] . "\n";
        return -1;
    }
    if (isset($addresses[$ip])) {
        db_execute("update host set description = '$description' where id = " . $addresses[$ip]);
        if ($verbose) echo "This IP already exists in the database ($ip) device-id : " . $addresses[$ip] . "\n";
        return -1;
    }
    echo "Adding $description ($ip) as \"".$host_templates[$hostTemplateId]."\" using SNMP v$snmp_ver with community \"$community\"\n";
    $new_host_id = api_device_save(0, $hostTemplateId, $description, $ip, $community, $snmp_ver, 
                                   $snmp_username, $snmp_password, $snmp_port, $snmp_timeout, "",
                                   $availability_method, $ping_method, $ping_port, $ping_timeout, $ping_retries,
                                   "cactibatch auto-added : $description", $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $max_oids); 

    if (is_error_message()) {
        echo "Failed to add this device\n";
        return -1;
    } else {
        if ($verbose) echo "Success - new device-id : $new_host_id\n";
    }
    return $new_host_id;
}


function addTree($name,$sortMethod) 
{    
    global $verbose;
    $treeId     = 0;   // When creating a node, it has to go in a tree
    $sortTypes = array('a' => 2, 'n' => 3, 'm' => 1);

    $treeOpts = array();
    $treeOpts["id"]        = 0; // Zero means create a new one rather than save over an existing one
    $treeOpts["name"]      = $name;
    if (!empty($sortTypes[$sortMethod])) $treeOpts["sort_type"] = $sortTypes[$sortMethod];

    if (!isset($treeOpts["sort_type"]) || empty($treeOpts["sort_type"]))
    {
        if ($verbose) printf("Invalid sort-method: %s\n", $sortMethod);
        return -1;
    }

    $existsAlready = db_fetch_cell("select id from graph_tree where name = '$name'");
    if ($existsAlready)
    {
        if ($verbose) printf("Not adding tree - it already exists - tree-id : %d\n", $existsAlready);
        return -1;
    }

    $treeId = sql_save($treeOpts, "graph_tree");

    sort_tree(SORT_TYPE_TREE, $treeId, $treeOpts["sort_type"]);

    if ($verbose) printf("Tree Created - tree-id : %d\n", $treeId);

    return $treeId;
}


function addTreeNodeHeader($name,$parentNode,$treeId) 
{    
    global $verbose;
    $graphId        = 0;
    $rra_id         = 0;
    $hostId         = 0;
    $hostGroupStyle = 1;
    $nodeTypes = array('header' => 1, 'graph' => 2, 'host' => 3);
    $itemType = $nodeTypes["header"];

    if ($parentNode > 0)
    {
        $parentNodeExists = db_fetch_cell("SELECT id FROM graph_tree_items WHERE graph_tree_id = $treeId AND id = $parentNode");
        if (!isset($parentNodeExists))
        {
            if ($verbose) printf("parent-node %d does not exist\n",$parentNode);
            return -1;
        }
    }

// $nodeId could be a Header Node, a Graph Node, or a Host node.
    $nodeId = api_tree_item_save(0, $treeId, $itemType, $parentNode, $name, $graphId, $rra_id, $hostId, $hostGroupStyle, 1, false);
    if ($verbose) printf("Added Header Node node-id : %d\n", $nodeId);
    return $nodeId;
}


function addTreeNodeGraph($graphId,$rra_id,$parentNode,$treeId) 
{    
    global $verbose;
    $name           = '';
    $hostId         = 0;
    $hostGroupStyle = 1;
    $nodeTypes = array('header' => 1, 'graph' => 2, 'host' => 3);
    $itemType = $nodeTypes['graph'];

    if ($parentNode > 0)
    {
        $parentNodeExists = db_fetch_cell("SELECT id FROM graph_tree_items WHERE graph_tree_id = $treeId AND id = $parentNode");
        if (!isset($parentNodeExists))
        {
            if ($verbose) printf("parent-node %d does not exist\n",$parentNode);
            return -1;
        }
    }

// $nodeId could be a Header Node, a Graph Node, or a Host node.
    $nodeId = api_tree_item_save(0, $treeId, $itemType, $parentNode, $name, $graphId, $rra_id, $hostId, $hostGroupStyle, 1, false);
    if ($verbose) printf("Added Graph Node node-id : %d\n", $nodeId);
    return $nodeId;
}

function addTreeNodeHost($hostId,$parentNode,$treeId) 
{    
    global $verbose;
    $hosts          = getHosts();
    $name           = '';
    $graphId        = 0;
    $rra_id         = 0;
    $hostGroupStyle = 1;
    $nodeTypes = array('header' => 1, 'graph' => 2, 'host' => 3);
    $itemType = $nodeTypes['host'];

    if ($parentNode > 0)
    {
        $parentNodeExists = db_fetch_cell("SELECT id FROM graph_tree_items WHERE graph_tree_id = $treeId AND id = $parentNode");
        if (!isset($parentNodeExists))
        {
            if ($verbose) printf("parent-node %d does not exist\n",$parentNode);
            return -1;
        }
    }

    if (!isset($hosts[$hostId]))
    {
        if ($verbose) printf("No such host-id (%s) exists. Try --list-hosts\n", $hostId);
        return -1;
    }

    // $nodeId could be a Header Node, a Graph Node, or a Host node.
    $nodeId = api_tree_item_save(0, $treeId, $itemType, $parentNode, $name, $graphId, $rra_id, $hostId, $hostGroupStyle, 1, false);
    if ($verbose) printf("Added Host Node node-id : %d\n", $nodeId);
    return $nodeId;
}

function addGraphCG($templateId,$hostId,$graphTitle)
{
    global $verbose;
    $hosts          = getHosts();
    $graphTemplates = getGraphTemplates();
    if (!isset($hosts[$hostId]) || $hostId == 0)
    {
        if ($verbose) echo "Unknown Host ID ($hostId)\n";
        if ($verbose) echo "Try --list-hosts\n";
        return -1;
    }
    if (!isset($graphTemplates[$templateId]))
    {
        if ($verbose) echo "Unknown graph-template-id (" . $templateId . ")\n";
        if ($verbose) echo "Try --list-graph-templates\n";
        return -1;
    }

    $returnArray = array();

    $existsAlready = db_fetch_cell("select id from graph_local where graph_template_id = $templateId AND host_id = $hostId ");
    if (isset($existsAlready) && $existsAlready > 0)
    {
        if ($graphTitle != "")
        {
            db_execute("update graph_templates_graph set title = \"$graphTitle\" where local_graph_id = $existsAlready");
            update_graph_title_cache($existsAlready);
            return $existsAlready;
        }
        $dataSourceId = db_fetch_cell("SELECT DISTINCT data_template_rrd.local_data_id
                FROM graph_templates_item, data_template_rrd
                WHERE graph_templates_item.local_graph_id = " . $existsAlready .
                " AND graph_templates_item.task_item_id = data_template_rrd.id");
        if ($verbose) echo "Not Adding Graph - this graph already exists - graph-id : $existsAlready : data-source-id : $dataSourceId\n";
        return -1;
    }

    $empty = array(); /* Suggested Values are not been implemented */
    $returnArray = create_complete_graph_from_template($templateId, $hostId, "", $empty);

    if ($graphTitle != "")
    {
        db_execute("update graph_templates_graph set title = \"$graphTitle\" where local_graph_id = " . $returnArray["local_graph_id"]);
        update_graph_title_cache($returnArray["local_graph_id"]);
    }
    push_out_host($hostId,0);
    $dataSourceId = db_fetch_cell("SELECT DISTINCT data_template_rrd.local_data_id
                                     FROM graph_templates_item, data_template_rrd
                                   WHERE graph_templates_item.local_graph_id = " . $returnArray["local_graph_id"] .
                                   " AND graph_templates_item.task_item_id = data_template_rrd.id");
    if ($verbose) echo "Graph Added - graph-id : " . $returnArray["local_graph_id"] . " : - data-source-id : $dataSourceId\n";
    return $returnArray["local_graph_id"];
}
    
    
function addGraphDS($templateId,$hostId,$graphTitle,$snmpQueryId,$snmpField,$snmpQueryType,$snmpValue)
{
    global $verbose;
    $hosts          = getHosts();
    $graphTemplates = getGraphTemplates();
    $dsGraph = array();
    $dsGraph["snmpQueryId"] = $snmpQueryId;
    $dsGraph["snmpField"] = $snmpField;
    $dsGraph["snmpQueryType"] = $snmpQueryType;
    $dsGraph["snmpValue"] = $snmpValue;

    if (!isset($hosts[$hostId]) || $hostId == 0)
    {
        if ($verbose) echo "Unknown Host ID ($hostId)\n";
        if ($verbose) echo "Try --list-hosts\n";
        return -1;
    }
    if (!isset($graphTemplates[$templateId]))
    {
        if ($verbose) echo "Unknown graph-template-id (" . $templateId . ")\n";
        if ($verbose) echo "Try --list-graph-templates\n";
        return -1;
    }

    $returnArray = array();

    if ((!isset($dsGraph["snmpQueryId"])) || (!isset($dsGraph["snmpQueryType"])) || (!isset($dsGraph["snmpField"])) || (!isset($dsGraph["snmpValue"])))
    {
        if ($verbose) echo "For graph-type of 'ds' you must supply more options\n";
        return -1;
    }
    
    $snmpQueries = getSNMPQueries();

    if (!isset($snmpQueries[$dsGraph["snmpQueryId"]]))
    {
        if ($verbose) echo "Unknown snmp-query-id (" . $dsGraph["snmpQueryId"] . ")\n";
        if ($verbose) echo "Try --list-snmp-queries\n";
        return -1;
    }

    $snmp_query_types = getSNMPQueryTypes($dsGraph["snmpQueryId"]);

    if (!isset($snmp_query_types[$dsGraph["snmpQueryType"]]))
    {
        if ($verbose) echo "Unknown snmp-query-type-id (" . $dsGraph["snmpQueryType"] . ")\n";
        if ($verbose) echo "Try --snmp-query-id " . $dsGraph["snmpQueryId"] . " --list-query-types\n";
        return -1;
    }


    $snmpFields = getSNMPFields($hostId);

    $snmpValues = array();
    if (!isset($snmpFields[$dsGraph["snmpField"]]))
    {
        if ($verbose) echo "Unknwon snmp-field " . $dsGraph["snmpField"] . " for host $hostId\n";
        if ($verbose) echo "Try --list-snmp-fields\n";
        return -1;
    }
    $snmpValues = getSNMPValues($hostId, $dsGraph["snmpField"]);

    if (isset($dsGraph["snmpValue"]))
    {
        if(!isset($snmpValues[$dsGraph["snmpValue"]]))
        {
            if ($verbose) echo "Unknown snmp-value for field " . $dsGraph["snmpField"] . " - " . $dsGraph["snmpValue"] . "\n";
            if ($verbose) echo "Try --snmp-field " . $dsGraph["snmpField"] . " --list-snmp-values\n";
            return -1;
        }
    }

    $snmp_query_array = array();
    $snmp_query_array["snmp_query_id"]       = $dsGraph["snmpQueryId"];
    $snmp_query_array["snmp_index_on"]       = $dsGraph["snmpField"];
    $snmp_query_array["snmp_query_graph_id"] = $dsGraph["snmpQueryType"];

    $snmp_query_array["snmp_index"] = db_fetch_cell("select snmp_index from host_snmp_cache WHERE host_id = " . $hostId . " and snmp_query_id = " . $dsGraph["snmpQueryId"] . " and field_name = '" . $dsGraph["snmpField"] . "' and field_value = '" . $dsGraph["snmpValue"] . "'");

    if (!isset($snmp_query_array["snmp_index"]))
    {
        if ($verbose) echo "Could not find snmp-field " . $dsGraph["snmpField"] . " (" . $dsGraph["snmpValue"] . ") for host-id " . $hostId . " (" . $hosts[$hostId] . ")\n";
        if ($verbose) echo "Try --host-id " . $hostId . " --list-snmp-fields\n";
        return -1;
    }

    $existsAlready = db_fetch_cell("select id from graph_local where graph_template_id = $templateId AND host_id = $hostId AND snmp_query_id = " . $dsGraph["snmpQueryId"] . " AND snmp_index = " . $snmp_query_array["snmp_index"]);
    if (isset($existsAlready) && $existsAlready > 0)
    {
            if ($verbose) echo "Graph already exist : ".$graphTitle." ".$snmp_query_array["snmp_index"]." \n";
        if ($graphTitle != "")
        {
            db_execute("update graph_templates_graph set title = \"$graphTitle\" where local_graph_id = $existsAlready");
            update_graph_title_cache($existsAlready);
            return $existsAlready;
        }
        $dataSourceId = db_fetch_cell("SELECT DISTINCT data_template_rrd.local_data_id
                FROM graph_templates_item, data_template_rrd
                WHERE graph_templates_item.local_graph_id = " . $existsAlready .
                " AND graph_templates_item.task_item_id = data_template_rrd.id");
        if ($verbose) echo "Not Adding Graph - this graph already exists - graph-id : $existsAlready : data-source-id : $dataSourceId\n";
        return -1;
    }

    $empty = array(); /* Suggested Values are not been implemented */
    $returnArray = create_complete_graph_from_template($templateId, $hostId, $snmp_query_array, $empty);

    if ($graphTitle != "")
    {
        db_execute("update graph_templates_graph set title = \"$graphTitle\" where local_graph_id = " . $returnArray["local_graph_id"]);
        update_graph_title_cache($returnArray["local_graph_id"]);
    }
    push_out_host($hostId,0);
    $dataSourceId = db_fetch_cell("SELECT DISTINCT data_template_rrd.local_data_id
                                     FROM graph_templates_item, data_template_rrd
                                   WHERE graph_templates_item.local_graph_id = " . $returnArray["local_graph_id"] .
                                   " AND graph_templates_item.task_item_id = data_template_rrd.id");
    if ($verbose) echo "Graph Added - graph-id : " . $returnArray["local_graph_id"] . " : - data-source-id : $dataSourceId\n";
    return $returnArray["local_graph_id"];
}
    
?> 
