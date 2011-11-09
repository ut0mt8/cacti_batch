#!/usr/bin/php 
<?php
/*
   +-------------------------------------------------------------------------+
   | RMZ - B3G - 2006   raph AT b3g.fr                                       |
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
include "/home/cacti/cactibatch/lib/api_auto.php";
global $verbose;
$verbose = 1;

function usage()
{
echo <<<EOT
List Options: 
    
    --list-hosts
    --list-communities
    --list-host-templates
    --list-graph-templates
    --list-snmp-queries
    --snmp-query-id [ID] --list-query-types
    --host-id [ID] --list-snmp-fields
    --host-id [ID] --list-snmp-if
    --host-id [ID] --list-snmp-part
    --host-id [ID] --list-graphs 
    --host-id [ID] --snmp-field [Field] --list-snmp-values


Action Options:

    --reload-query-cache ([HostID]|all)

    --delete-host [HostID] 

    --delete-graph [LocalGraphID]  --host-id [ID]

    --add-graph --graph-type [cg|ds] --graph-template-id [ID] --host-id [ID] (--graph-title title) [graph options]
    
      x cg graphs options: no further options are required  (cg graph are for CPU/HDD for ex)
      x ds graphs options: --snmp-query-id [ID] --snmp-query-type-id [ID] --snmp-field [SNMP Field] --snmp-value [SNMP Value]
      (--graph-title is optional - it defaults to what ever is in the graph template/data-source template)

    --add-tree --tree-type [tree|node] [type-options]

      tree options: --tree-name [Tree Name] --sort-method [a|n|m] (sort methods: a=Alphabetic, n=numeric, m=manual)
      node options: --node-type [header|graph|host] --tree-id [ID] --parent-node [ID]  [Node Type Options]

        x header node options: --header-name [Name]
        x graph node options: --graph-id [ID] --rra-id [ID]
        x host node options: --host-id [ID]

    --add-device --host-template-id [ID] --host-description [DESC] --host-ip [IP|FQDN] --snmp-community [Comm] --snmp-version [1|2]



EOT;
}

if ($_SERVER["argc"] == 1)
{
    usage();
    return(1);
}

for ($i = 1; $i < $_SERVER["argc"]; $i++)
{
    switch($_SERVER["argv"][$i])
    {
    case "--tree-type":
        $i++;
        $treetype = $_SERVER["argv"][$i];
    break;
    case "--tree-name":
        $i++;
        $treename = $_SERVER["argv"][$i];
    break;
    case "--header-name":
        $i++;
        $headername = $_SERVER["argv"][$i];
    break;
    case "--sort-method":
        $i++;
        $sortmethod = $_SERVER["argv"][$i];
    break;
    case "--parent-node":
        $i++;
        $parentnode = $_SERVER["argv"][$i];
    break;
    case "--tree-id":
        $i++;
        $treeId = $_SERVER["argv"][$i];
    break;
    case "--node-type":
        $i++;
        $nodetype = $_SERVER["argv"][$i];
    break;
    case "--graph-id":
        $i++;
        $graphId = $_SERVER["argv"][$i];
    break;
    case "--rra-id":
        $i++;
        $rraId = $_SERVER["argv"][$i];
    break;
    case "--host-group-style":
        $i++;
        $hostGroupStyle = $_SERVER["argv"][$i];
    break;
    case "--graph-type":
        $i++;
        $graphtype = $_SERVER["argv"][$i];
    break;
    case "--graph-title":
        $i++;
        $graphtitle = $_SERVER["argv"][$i];
    break;
    case "--graph-template-id":
        $i++;
        $graphtemplateId = $_SERVER["argv"][$i];
    break;
    case "--host-template-id":
        $i++;
        $hostTemplateId = $_SERVER["argv"][$i];
    break;
    case "--host-description":
        $i++;
        $description = $_SERVER["argv"][$i];
    break;
    case "--host-id":
        $i++;
        $hostId = $_SERVER["argv"][$i];
    break;
    case "--host-ip":
        $i++;
        $ip = $_SERVER["argv"][$i];
    break;
    case "--snmp-community":
        $i++;
        $community = $_SERVER["argv"][$i];
    break;
    case "--snmp-version":
        $i++;
        $snmp_ver = $_SERVER["argv"][$i];
    break;
    case "--snmp-query-id":
        $i++;
        $snmpQueryId = $_SERVER["argv"][$i];
    break;
    case "--snmp-query-type-id":
        $i++;
        $snmpQueryType = $_SERVER["argv"][$i];
    break;
    case "--snmp-field":
        $i++;
        $snmpField = $_SERVER["argv"][$i];
    break;
    case "--snmp-value":
        $i++;
        $snmpValue = $_SERVER["argv"][$i];
    break;
    case "--list-hosts":
        displayHosts();
        return 0;
    break;
    case "--list-snmp-fields":
        if(!isset($hostId)) {
            echo "/!\ --host-id not set\n\n";
            usage();
            return 1;
        }
        else {
            displaySNMPFields($hostId);
            return 0;
        }
    break;
    case "--list-snmp-if":
        if(!isset($hostId)) {
            echo "/!\ --host-id not set\n\n";
            usage();
            return 1;
        }
        else {
            displaySNMPif($hostId);
            return 0;
        }
    break;
    case "--list-snmp-part":
        if(!isset($hostId)) {                                                                          
            echo "/!\ --host-id not set\n\n";                                                                      
            usage();
            return 1;
        }  
        else {
            displaySNMPpart($hostId);
            return 0;
        } 
    break;
    case "--list-graphs":
        if(!isset($hostId)) {
            echo "/!\ --host-id not set\n\n";
            usage();
            return 1;
        }
        else {
            displayHostGraphs($hostId);
            return 0;
        }
    break;
    case "--list-snmp-values":
        if((!isset($hostId))||(!isset($snmpField))) {
            echo "/!\ --host-id or --snmp-field not set\n\n";
            usage();
            return 1;
        }
        else {
            displaySNMPValues($hostId,$snmpField);
            return 0;
        }
    break;
    case "--list-query-types":
        if(!isset($snmpQueryId)) {
            echo "/!\ --snmp-query-id not set\n\n";
            usage();
            return 1;
        }
        else {
            displayQueryTypes($snmpQueryId);
            return 0;
        }
    break;
    case "--list-snmp-queries":
        displaySNMPQueries();
        return 0;
    break;
    case "--list-graph-templates":
        displayGraphTemplates();
        return 0;
    break;
    case "--list-communities":
        displayCommunities();
        return 0;
    break;
    case "--list-host-templates":
        displayHostTemplates();
        return 0;
    break;
    case "--delete-graph":
        $i++;
        $delgid = $_SERVER["argv"][$i];
        $action = "deleteGraph";
    break;
    case "--delete-host":
        $i++;
        deleteDevice($_SERVER["argv"][$i]);
        return 0;
    break;
    case "--add-graph":
        $action = "addGraph";
    break;
    case "--add-tree":
        $action = "addTree";
    break;
    case "--add-device":
        $action = "addDevice";
    break;
    case "--reload-query-cache":
        $i++;
        reloadQueryCache($_SERVER["argv"][$i]);
        return 0;
    break;
    default:
        printf("Unknown parameter %s\n",$_SERVER["argv"][$i]);
        usage();
        return 0;
    }
}

switch($action)
{
        case "deleteGraph":
        if((!isset($hostId))||(!isset($delgid))) {
            echo "/!\ --host-id or --delete-graph not set\n\n";
            usage();
            return 1;
        }
        else {
            $ret = deleteGraph($delgid,$hostId);
            if ($ret > 0) echo "Deleting graph $delgid on host $hostId succesfull\n";
            else echo "Deleting graph $delgid on host $hostId failed\n";
            return 0;
        }
    break;
    case "addTree":
        if(!isset($treetype)) {
            echo "/!\ --tree-type not set\n\n";
            usage();
            return 1;
        }
        switch($treetype) {
            case "tree":
                if((!isset($treename))||(!isset($sortmethod))) {
                    echo "/!\ --tree-name or --sort-method not set\n\n";
                    usage();
                    return 1;
                }
                else {
                    $ret = addTree($treename,$sortmethod);
                    if ($ret > 0) echo "Adding tree $treename succesfull\n";
                    else echo "Adding tree $treename failed\n";
                    return 0;
                }
            break;
            case "node":
                if(((!isset($nodetype))||(!isset($treeId))||!isset($parentnode))) {
                    echo "/!\ --node-type or --tree-id or --parent-node not set\n\n";
                    usage();
                    return 1;
                }
                switch($nodetype) {
                    case "header":
                        if(!isset($headername)) {
                            echo "/!\ --header-name not set\n\n";
                            usage();
                            return 1;
                        }
                        else {
                            $ret = addTreeNodeHeader($headername,$parentnode,$treeId);
                            if ($ret > 0) echo "Adding header $headername succesfull\n";
                            else echo "Adding header $headername failed\n";
                            return 0;
                        }
                    break;
                    case "host":
                        if(!isset($hostId)) {
                            echo "/!\ --host-id not set\n\n";
                            usage();
                            return 1;
                        }
                        else {
                            $ret = addTreeNodeHost($hostId,$parentnode,$treeId);
                            if ($ret > 0) echo "Adding host $hostId in tree succesfull\n";
                            else echo "Adding host $hostId in tree failed\n";
                            return 0;
                        }
                    break;
                    case "graph":
                        if((!isset($graphId))||(!isset($rraId))) {
                            echo "/!\ --graph-id or --rra-id not set\n\n";
                            usage();
                            return 1;
                        }
                        else {
                            $ret = addTreeNodeGraph($graphId,$rraId,$parentnode,$treeId);
                            if ($ret > 0) echo "Adding graph $graphId in tree succesfull\n";
                            else echo "Adding graph $graphId in tree failed\n";
                            return 0;
                        }
                    break;
                    default:
                        echo "/!\ --node-type value incorrect\n\n";
                        usage();
                        return 1;
                }
            break;
            default:
                echo "/!\ --tree-type value incorrect\n\n";
                usage();
                return 1;
        }
    break;
    case "addDevice":
        if((!isset($hostTemplateId))||(!isset($description))||(!isset($ip))) {
            echo "/!\ --host-template-id or --host-description or --host-ip not set\n\n";
            usage();
            return 1;
        }
        else {
            $ret = addDevice($hostTemplateId,$description,$ip,$community,$snmp_ver);
            if ($ret > 0) echo "Adding device $description successfull\n";
            else echo "Adding device $description failed\n";
            return 0;
        }
    break;
    case "addGraph":
        if((!isset($graphtype))||(!isset($graphtemplateId))||(!isset($hostId))) {
            echo "/!\ --graph-type or --graph-template-id or --host-id not set\n\n";
            usage();
            return 1;
        }
        if(!isset($graphtitle)) $graphtitle="";
        switch ($graphtype) {
            case "cg":
                $ret = addGraphCG($graphtemplateId,$hostId,$graphtitle);
                if ($ret > 0) echo "Adding graph $graphtitle succesfull\n";
                else echo "Adding graph $graphtitle failed\n";
                return 0;
            break;
            case "ds":
                if((!isset($snmpQueryId))||(!isset($snmpQueryType))||(!isset($snmpField))||(!isset($snmpValue))) {
                    echo "/!\ --snmp-query-id or --snmp-query-type-id or --snmp-field or --snmp-value not set\n\n";
                    usage();
                    return 1;
                }
                else {
                    $ret = addGraphDS($graphtemplateId,$hostId,$graphtitle,$snmpQueryId,$snmpField,$snmpQueryType,$snmpValue);
                    if ($ret > 0) echo "Adding graph $graphtitle succesfull\n";
                    else echo "Adding graph $graphtitle failed\n";
                    return 0;

                }
            break;
            default:
                echo "/!\ --graph-type value incorrect\n\n";
                usage();
                return 1;
        }
    break;
    default:
        printf("Unknown parameter %s\n",$_SERVER["argv"][$i]);
        usage();
        return 0;
}

usage();                                                                            
return -1;

?>
