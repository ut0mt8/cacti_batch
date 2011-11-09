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
include "/home/cacti/cactibatch/lib/api_batch.php";

# flush the query cache
reloadQueryCache("all");

?>
