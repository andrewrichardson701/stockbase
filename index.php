<?php  
// This file is part of StockBase.
// StockBase is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
// StockBase is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
// You should have received a copy of the GNU General Public License along with StockBase. If not, see <https://www.gnu.org/licenses/>.

// INVENTORY VIEW PAGE. SHOWS ALL INVENTORY ONCE LOGGED IN AND SHOWS FILTERS IN THE NAV
include 'session.php'; // Session setup and redirect if the session is not active 
// include 'http-headers.php'; // $_SERVER['HTTP_X_*']
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'head.php'; // Sets up bootstrap and other dependencies ?>
    <title><?php echo ucwords($current_system_name);?></title>
</head>
<body onload="getInventory(0)">
    <?php // dependency PHP
    // $show_inventory = 1; // for nav.php to show the site and area on the banner - no longer used.
    ?>
    <!-- Header and Nav -->
    <?php 
        $navHighlight = 'index'; // for colouring the nav bar link
        include 'nav.php'; 
    ?>
    <!-- End of Header and Nav -->

    <div class="content">
        <?php // Error section
        $errorPprefix = '<div class="container"><p class="red" style="padding-top:10px">Error: ';
        $errorPsuffix = '</p></div>';
        $successPprefix = '<div class="container"><p class="green" style="padding-top:10px">';
        $successPsuffix = '</p></div>';

        include 'includes/responsehandling.inc.php';
        showResponse(); 

        ?>
        <!-- Get Inventory -->
        <?php
        $showOOS = isset($_GET['oos']) ? (int)$_GET['oos'] : 0;
        $site = isset($_GET['site']) ? $_GET['site'] : "0";
        $area = isset($_GET['area']) ? $_GET['area'] : "0";
        $name = isset($_GET['name']) ? $_GET['name'] : "";
        $sku = isset($_GET['sku']) ? $_GET['sku'] : "";
        $location = isset($_GET['location']) ? $_GET['location'] : "";
        $shelf = isset($_GET['shelf']) ? $_GET['shelf'] : "";
        $tag = isset($_GET['tag']) ? $_GET['tag'] : "";
        $manufacturer = isset($_GET['manufacturer']) ? $_GET['manufacturer'] : "";
        $page = isset($_GET['page']) ? $_GET['page'] : 1;
        if ($page == '' || $page < 1) {
            $page = 1;
        }
        $site_names_array = [];
        $area_names_array = [];

        if (isset($_GET['rows'])) {
            if ($_GET['rows'] == 50 || $_GET['rows'] == 100) {
                $rowSelectValue = htmlspecialchars($_GET['rows']);
            } else {
                $rowSelectValue = 10;
            }
        } else {
            $rowSelectValue = 10;
        }
        echo('<input id="hidden-row-count" type="hidden" value="'.$rowSelectValue.'" />
        <input id="hidden-page-number" type="hidden" value="'.$page.'" />
        <input id="hidden-oos" type="hidden" value="'.$showOOS.'" />
        <pre id="hidden-sql" hidden></pre>');

        
        include 'includes/dbh.inc.php';                

        // GET SITE AND AREA VALUES
        //site
        $sql_site = "SELECT DISTINCT site.id, site.name, site.description
                    FROM site 
                    ORDER BY site.id";
        $stmt_site = mysqli_stmt_init($conn);
        if (!mysqli_stmt_prepare($stmt_site, $sql_site)) {
            echo("ERROR getting entries");
        } else {
            mysqli_stmt_execute($stmt_site);
            $result_site = mysqli_stmt_get_result($stmt_site);
            $rowCount_site = $result_site->num_rows;
            $siteCount = $rowCount_site;
            if ($rowCount_site < 1) {
                // echo ("No sites found");
                // exit();
            } else {
                while( $row = $result_site->fetch_assoc() ) {
                    $site_id = $row['id'];
                    $site_name = $row['name'];
                    $site_description = $row['description'];
                    $site_names_array[$site_id] = $site_name;
                    // echo('<option style="color:black" value="'.$site_id.'"'); if ($site == $site_id) { echo('selected'); } echo('>'.$site_name.'</option>');
                }          
            }
        }
        $sql_areaCheck = "SELECT DISTINCT area.id, area.name, area.description
                    FROM area 
                    ORDER BY area.id";
        $stmt_areaCheck = mysqli_stmt_init($conn);
        if (!mysqli_stmt_prepare($stmt_areaCheck, $sql_areaCheck)) {
            echo("ERROR getting entries");
        } else {
            mysqli_stmt_execute($stmt_areaCheck);
            $result_areaCheck = mysqli_stmt_get_result($stmt_areaCheck);
            $rowCount_areaCheck = $result_areaCheck->num_rows;
            $areaCount = $rowCount_areaCheck;
            if ($rowCount_areaCheck < 1) {
                // echo ("No areas found");
                // exit();
            } else {
                while( $row = $result_areaCheck->fetch_assoc() ) {
                    $area_id = $row['id'];
                    $area_name = $row['name'];
                    $area_description = $row['description'];
                    $area_names_array[$area_id] = $area_name;
                    // echo('<option style="color:black" value="'.$area_id.'"'); if ($area == $area_id) { echo('selected'); } echo('>'.$area_name.'</option>');
                }          
            }
        }
        

        $sql_shelfCheck = "SELECT DISTINCT shelf.id, shelf.name    
                    FROM shelf 
                    ORDER BY shelf.id";
        $stmt_shelfCheck = mysqli_stmt_init($conn);
        if (!mysqli_stmt_prepare($stmt_shelfCheck, $sql_shelfCheck)) {
            echo("ERROR getting entries");
        } else {
            mysqli_stmt_execute($stmt_shelfCheck);
            $result_shelfCheck = mysqli_stmt_get_result($stmt_shelfCheck);
            $rowCount_shelfCheck = $result_shelfCheck->num_rows;
            $shelfCount = $rowCount_shelfCheck;
        }

        if (!$siteCount > 0 || !$areaCount > 0 || !$shelfCount > 0) {
            // missing sites or areas
            echo('
                <div class="container" style="margin-top:20px">
                    <h2 style="padding-bottom:20px;padding-top:20px">Add First Locations</h2>
                    <form id="addLocations" enctype="multipart/form-data" action="./includes/admin.inc.php" method="POST">
                        <!-- Include CSRF token in the form -->
                        <input type="hidden" name="csrf_token" value="'.htmlspecialchars($_SESSION['csrf_token']).'">
                        <input type="hidden" name="index" value="1"/>
                        <table id="area-table">
                            <tbody>
                                <tr class="nav-row" id="area-headings" style="margin-bottom:20px">
                                    <th style="width:250px;"><h3 style="font-size:22px">Add Site</h3></th>
                                    <th style="width: 250px"></th>
                                </tr>
                                <tr class="nav-row" id="site-name-row">
                                    <td id="site-name-label" style="width:250px;margin-left:25px">
                                        <p style="min-height:max-content;margin:0px" class="nav-v-c align-middle" for="site-name">Site Name:</p>
                                    </td>
                                    <td id="site-name-input">
                                        <input class="form-control nav-v-c" type="text" style="width: 250px" id="site-name" name="site-name"required>
                                    </td>
                                </tr>
                                <tr class="nav-row" id="site-description-row">
                                    <td id="site-description-label" style="width:250px;margin-left:25px">
                                        <p style="min-height:max-content;margin:0px" class="nav-v-c align-middle" for="site-description">Site Description:</p>
                                    </td>
                                    <td id="site-description-input">
                                        <input class="form-control nav-v-c" type="text" style="width: 250px" id="site-description" name="site-description"required>
                                    </td>
                                </tr>
                                
                                <tr class="nav-row" id="area-headings" style="margin-top:50px;margin-bottom:20px">
                                    <th style="width:250px;"><h3 style="font-size:22px">Add Area</h3></th>
                                    <th style="width: 250px"></th>
                                </tr>
                                <tr class="nav-row" id="area-name-row">
                                    <td id="area-name-label" style="width:250px;margin-left:25px">
                                        <p style="min-height:max-content;margin:0px" class="nav-v-c align-middle" for="area-name">Area Name:</p>
                                    </td>
                                    <td id="area-name-input">
                                        <input class="form-control nav-v-c" type="text" style="width: 250px" id="area-name" name="area-name"required>
                                    </td>
                                </tr>
                                <tr class="nav-row" id="area-description-row">
                                    <td id="area-description-label" style="width:250px;margin-left:25px">
                                        <p style="min-height:max-content;margin:0px" class="nav-v-c align-middle" for="area-description">Area Description:</p>
                                    </td>
                                    <td id="area-description-input">
                                        <input class="form-control nav-v-c" type="text" style="width: 250px" id="area-description" name="area-description"required>
                                    </td>
                                </tr>

                                <tr class="nav-row" id="shelf-headings" style="margin-top:50px;margin-bottom:20px">
                                    <th style="width:250px;"><h3 style="font-size:22px">Add Shelf</h3></th>
                                    <th style="width: 250px"></th>
                                </tr>
                                <tr class="nav-row" id="shelf-name-row">
                                    <td id="shelf-name-label" style="width:250px;margin-left:25px">
                                        <p style="min-height:max-content;margin:0px" class="nav-v-c align-middle" for="shelf-name">Shelf Name:</p>
                                    </td>
                                    <td id="shelf-name-input">
                                        <input class="form-control nav-v-c" type="text" style="width: 250px" id="shelf-name" name="shelf-name"required>
                                    </td>
                                </tr>
                                
                                <tr class="nav-row" style="margin-top:20px">
                                    <td style="width:250px">
                                        <input id="location-submit" type="submit" name="location-submit" class="btn btn-success" style="margin-left:25px" value="Submit">
                                    </td>
                                    <td style="width:250px">
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </form>
                </div>
            ');
        } else {
            // all is as expected. we have sites and areas
            echo('
                <div class="container" id="search-fields" style="max-width:max-content;margin-bottom:10px; margin-top:20px">
                    <div class="nav-row">
                        <form action="./" method="get" class="nav-row" style="max-width:max-content">
                            <input id="query-site" type="hidden" name="site" value="'.$site.'" /> 
                            <input id="query-area" type="hidden" name="area" value="'.$area.'" />
                            <input id="query-oos" type="hidden" name="oos" value="'.$showOOS.'" /> 

                            <span id="search-input-site-span" style="margin-bottom:10px;" class="index-dropdown">
                                <label for="search-input-site">Site</label><br>
                                <select id="site-dropdown" name="site" class="form-control nav-v-b theme-dropdown" oninput="getInventory(1)" >
                                <option value="0"'); if ($area == 0) { echo('selected'); } echo('>All</option>
                            ');
                            if (!empty($site_names_array)) {
                                foreach (array_keys($site_names_array) as $site_id) {
                                    $site_name = $site_names_array[$site_id];
                                    echo('<option value="'.$site_id.'"'); if ($site == $site_id) { echo('selected'); } echo('>'.$site_name.'</option>');
                                }
                            }
                            
                            echo('
                                </select>
                            </span>
                            ');  
                            echo ('
                            <span id="search-input-area-span" style="margin-bottom:10px;" class="index-dropdown">
                                <label for="area-dropdown">Area</label><br>
                                    <select id="area-dropdown" name="area" class="form-control nav-v-b theme-dropdown" oninput="getInventory(1)" >
                                    <option style="color:white" value="0"'); if ($area == 0) { echo('selected'); } echo('>All</option>
                                ');
                                if (!empty($area_names_array)) {
                                    foreach (array_keys($area_names_array) as $area_id) {
                                        $area_name = $area_names_array[$area_id];
                                        echo('<option style="color:white" value="'.$area_id.'"'); if ($area == $area_id) { echo('selected'); } echo('>'.$area_name.'</option>');
                                    }
                                }
                                
                            
                            echo('
                                </select>
                            </span>
                            ');
                            echo('
                            <span id="search-input-name-span" style="margin-right:0.5em;margin-bottom:10px;">
                                <label for="search-input-name">Name</label><br>
                                <input id="search-input-name" type="text" name="name" class="form-control" style="width:160px;display:inline-block" placeholder="Search by Name" oninput="getInventory(1)" value="'); echo(isset($_GET['name']) ? $_GET['name'] : ''); echo('" />
                            </span>
                            <span class="viewport-large-block" id="search-input-sku-span" style="margin-right:0.5em;margin-bottom:10px;">
                                <label for="search-input-sku">SKU</label><br>
                                <input id="search-input-sku" type="text" name="sku" class="form-control" style="width:160px;display:inline-block" placeholder="Search by SKU" oninput="getInventory(1)" value="'); echo(isset($_GET['sku']) ? $_GET['sku'] : ''); echo('" />
                            </span>
                            <span class="viewport-large-block" id="search-input-shelf-span" style="margin-right:0.5em;margin-bottom:10px;">
                                <label for="search-input-shelf">Shelf</label><br>
                                <input id="search-input-shelf" type="text" name="shelf" class="form-control" style="width:160px;display:inline-block" placeholder="Search by Shelf" oninput="getInventory(1)" value="'); echo(isset($_GET['shelf']) ? $_GET['shelf'] : ''); echo('" />
                            </span>
                            <span class="viewport-large-block" id="search-input-manufacturer-span" style="margin-right:0.5em;margin-bottom:10px;">
                                <label for="search-input-manufacturer">Manufacturer</label><br>
                                
                                <select id="search-input-manufacturer" name="manufacturer" class="form-control" style="width:160px;display:inline-block" placeholder="Search by Manufacturer" onchange="getInventory(1)">
                                <option value="" '); if (!isset($_GET['manufacturer']) || $_GET['manufacturer'] == '') { echo('selected'); } echo('>All</option>');
                                $sql_manufacturer = "SELECT * FROM manufacturer WHERE deleted=0 ORDER BY name";
                                $stmt_manufacturer = mysqli_stmt_init($conn);
                                if (!mysqli_stmt_prepare($stmt_manufacturer, $sql_manufacturer)) {
                                    echo("ERROR getting entries");
                                } else {
                                    mysqli_stmt_execute($stmt_manufacturer);
                                    $result_manufacturer = mysqli_stmt_get_result($stmt_manufacturer);
                                    $rowCount_manufacturer = $result_manufacturer->num_rows;
                                    while ($row_manufacturer = $result_manufacturer->fetch_assoc()) {
                                        echo('<option value="'.htmlspecialchars($row_manufacturer['name'], ENT_QUOTES, 'UTF-8').'" '); if (isset($_GET['manufacturer']) && $_GET['manufacturer'] == $row_manufacturer['name']) { echo ('selected'); } echo('>'.$row_manufacturer['name'].'</option>');
                                    }
                                }
                                echo('
                                </select>
                            </span>
                            <span class="viewport-large-block" id="search-input-label-span" style="margin-right:1em;margin-bottom:10px;">
                                <label for="search-input-label">Tag</label><br>
                                
                                <select id="search-input-tag" name="tag" class="form-control" style="width:160px;display:inline-block" placeholder="Search by Tag" onchange="getInventory(1)">
                                <option value="" '); if (!isset($_GET['tag']) || $_GET['tag'] == '') { echo('selected'); } echo('>All</option>');
                                $sql_tags = "SELECT * FROM tag WHERE deleted=0 ORDER BY name";
                                $stmt_tags = mysqli_stmt_init($conn);
                                if (!mysqli_stmt_prepare($stmt_tags, $sql_tags)) {
                                    echo("ERROR getting entries");
                                } else {
                                    mysqli_stmt_execute($stmt_tags);
                                    $result_tags = mysqli_stmt_get_result($stmt_tags);
                                    $rowCount_tags = $result_tags->num_rows;
                                    while ($row_tags = $result_tags->fetch_assoc()) {
                                        echo('<option value="'.htmlspecialchars($row_tags['name'], ENT_QUOTES, 'UTF-8').'" title="'.$row_tags['description'].'" '); if (isset($_GET['tag']) && $_GET['tag'] == $row_tags['name']) { echo ('selected'); } echo('>'.$row_tags['name'].'</option>');
                                    }
                                }
                                echo('<option value="tags" class="gold link theme-tableOuter">view tags</option>');
                                echo('
                                </select>
                            </span>
                            <input type="submit" value="submit" hidden>
                        </form>');

                        echo('
                        <div id="clear-div" class="nav-div viewport-large-block" style="margin-left:0px;margin-right:0px;margin-bottom:10px;">
                            <button id="clear-filters" class="btn btn-warning nav-v-b" style="opacity:80%;color:black" onclick="navPage(\'/\')">
                                <i class="fa fa-ban fa-rotate-90" style="padding-top:4px"></i>
                            </button>
                        </div>
                        <div id="zero-div" class="nav-div viewport-large-block" style="margin-left:15px;margin-right:0px;margin-bottom:10px;">');
                        if ($showOOS == 0) {
                            echo('<button id="zerostock" class="btn btn-success nav-v-b" style="opacity:90%;color:black;padding:0px 2px 0px 2px" onclick="navPage(updateQueryParameter(\'\', \'oos\', \'1\'))">');
                        } else {
                            echo('<button id="zerostock" class="btn btn-danger nav-v-b" style="opacity:80%;color:black;padding:0px 2px 0px 2px" onclick="navPage(updateQueryParameter(\'\', \'oos\', \'0\'))">');
                        }
                                echo('
                                <span class="zeroStockFont">
                                    <p style="margin:0px;padding:0px">'); if ($showOOS == 0) { echo('<i class="fa fa-plus"></i> Show'); } else { echo('<i class="fa fa-minus"></i> Hide'); } echo('</p>
                                    <p style="margin:0px;padding:0px">0 Stock</p>
                            </button>
                        </div>
                        <!-- <div id="zero-div" class="nav-div viewport-large-block" style="margin-left:15px;margin-right:0px;margin-bottom:10px;">
                            <button id="cable-stock" class="btn clickable btn-dark nav-v-b" style="opacity:90%;color:white;padding:6px 6px 6px 6px" onclick="navPage(\'cablestock.php\')">
                                Cables
                            </button>
                        </div> -->
                    </div>
                </div>
                <!-- mobile layout section -->
                <div class="container viewport-small" style="margin-top:-10px;max-width:max-content;">
                    <div class="nav-row">
                        <div id="clear-div" class="nav-div" style="margin-left:0px;margin-right:0px;margin-bottom:10px;">
                            <button id="clear-filters" class="btn btn-warning nav-v-b" style="opacity:80%;color:black" onclick="navPage(\'/\')">
                                <i class="fa fa-ban fa-rotate-90" style="padding-top:4px"></i>
                            </button>
                        </div>
                        <div id="zero-div" class="nav-div" style="margin-left:15px;margin-right:0px;margin-bottom:10px;">');
                        if ($showOOS == 0) {
                            echo('<button id="zerostock" class="btn btn-success nav-v-b" style="opacity:90%;color:black;padding:0px 2px 1px 2px" onclick="navPage(updateQueryParameter(\'\', \'oos\', \'1\'))">');
                        } else {
                            echo('<button id="zerostock" class="btn btn-danger nav-v-b" style="opacity:80%;color:black;padding:0px 2px 1px 2px" onclick="navPage(updateQueryParameter(\'\', \'oos\', \'0\'))">');
                        }
                                echo('
                                <span class="zeroStockFont">
                                    <p style="margin:0px;padding:0px">'); if ($showOOS == 0) { echo('<i class="fa fa-plus"></i> Show'); } else { echo('<i class="fa fa-minus"></i> Hide'); } echo('</p>
                                    <p style="margin:0px;padding:0px">0 Stock</p>
                            </button>
                        </div>
                        <div id="zero-div" class="nav-div" style="margin-left:15px;margin-right:0px;margin-bottom:10px;">
                            <button id="cable-stock" class="btn clickable btn-dark nav-v-b" style="opacity:90%;color:white;padding:6px 6px 6px 6px" onclick="navPage(\'cablestock.php\')">
                                Cables
                            </button>
                        </div>
                    </div>
                </div>
            ');
                
            echo('
            <!-- Modal Image Div -->
            <div id="modalDiv" class="modal" onclick="modalClose()">
                <span class="close" onclick="modalClose()">&times;</span>
                <img class="modal-content bg-trans" id="modalImg">
                <div id="caption" class="modal-caption"></div>
            </div>
            <!-- End of Modal Image Div -->

            <!-- Table -->');
            
            echo('
            <div class="container">
                <table class="table table-dark theme-table centertable" id="inventoryTable" style="margin-bottom:0px;">
                    <thead style="text-align: center; white-space: nowrap;">
                        <tr class="theme-tableOuter">
                            <th id="id" hidden>id</th>
                            <th id="img"></th>
                            <th class="clickable sorting sorting-asc" id="name" onclick="sortTable(2, this)">Name</th>
                            <th class="clickable sorting viewport-large-empty" id="sku" onclick="sortTable(3, this)">SKU</th>
                            <th class="clickable sorting" id="quantity" onclick="sortTable(4, this)">Quantity</th>
                            <th class="clickable sorting" id="site" onclick="sortTable(5, this)" '); if ($site == 0) { echo('hidden'); } echo('>Site</th>
                            <th id="tags" class="viewport-large-empty">Tags</th>
                        <th id="location">Location(s)</th>
                        </tr>
                    </thead>
                    <tbody id="inv-body" class="align-middle" style="text-align: center; white-space: nowrap;">
                    </tbody>
                </table>
            ');
            // Inventory Rows

            // PAGE COUNT
            
            echo('
                <table class="table table-dark theme-table centertable">
                    <tbody>
                        <tr class="theme-tableOuter">
                            <td colspan="100%" style="margin:0px;padding:0px" class="invTablePagination">
                            <div class="row">
                                <div class="col text-center"></div>
                                <div id="inv-page-numbers" class="col-6 text-center align-middle" style="overflow-y:auto; display:flex;justify-content:center;align-items:center;">
                                </div>
                                <div class="col text-center">
                                    <table style="margin-left:auto; margin-right:20px">
                                        <tbody>
                                            <tr>
                                                <td class="theme-textColor align-middle" style="border:none;padding-top:4px;padding-bottom:4px">
                                                    Rows: 
                                                </td>
                                                <td class="align-middle" style="border:none;padding-top:4px;padding-bottom:4px">
                                                    <select id="tableRowCount" class="form-control row-dropdown" style="width:50px;height:25px; padding:0px" name="rows" onchange="navPage(updateQueryParameter(\'\', \'rows\', this.value))">
                                                        <option id="rows-10"  value="10"');  if($rowSelectValue == 10)  { echo('selected'); } echo('>10</option>
                                                        <option id="rows-50"  value="50"');  if($rowSelectValue == 50)  { echo('selected'); } echo('>50</option>
                                                        <option id="rows-100" value="100"'); if($rowSelectValue == 100) { echo('selected'); } echo('>100</option>
                                                    </select>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </tr>
                    </tbody>
                </table>
            </div>
            ');
        }

        ?>
    </div> 

    <!-- Add the JS for the file -->
    <script src="assets/js/index.js"></script>

    <?php include 'foot.php'; ?>

</body>