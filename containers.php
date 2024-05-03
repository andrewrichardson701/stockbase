<?php  
// This file is part of StockBase.
// StockBase is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
// StockBase is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
// You should have received a copy of the GNU General Public License along with StockBase. If not, see <https://www.gnu.org/licenses/>.

// Container View Page. Shows all containers and what they contain
include 'session.php'; // Session setup and redirect if the session is not active 
// include 'http-headers.php'; // $_SERVER['HTTP_X_*']
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'head.php'; // Sets up bootstrap and other dependencies ?>
    <title><?php echo ucwords($current_system_name);?></title>
</head>
<body>

    <!-- Header and Nav -->
    <?php 
        $navHighlight = 'containers'; // for colouring the nav bar link
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
        showResponse(); // 

        ?>

        <div class="container" style="margin-top:20px">
            <h2 class="header-small" style="padding-bottom:5px"><?php if (isset($_GET['return'])) { echo('<button class="btn btn-dark" style="margin-right:20px" onclick="window.location.href=\''.urldecode($_GET['return']).'\'"><i class="fa fa-chevron-left"></i> Back</button>'); } ?>Containers</h2>
        </div>

        <?php
        $container_array = [];
        $container_array['container'] = [];
        $container_array['itemcontainer'] = [];

        $sql = "SELECT c.id AS c_id, c.name AS c_name, c.description AS c_description,
                        ic.id AS ic_id, ic.item_id AS ic_item_id, ic.container_id AS ic_container_id, ic.container_is_item AS ic_container_is_item,
                        icontainer.id AS icontainer_id,
                        scontainer.id AS scontainer_id, scontainer.name AS scontainer_name, scontainer.description as scontainer_description,
                        i.id AS i_id,
                        c_sh.id AS c_sh_id, i_sh.id AS i_sh_id,
                        (CONCAT(c_si.name, ' - ', c_a.name, ' - ', c_sh.name)) AS c_location,
                        (CONCAT(i_si.name, ' - ', i_a.name, ' - ', i_sh.name)) AS i_location,
                        s.id AS s_id, s.name AS s_name, s.description AS s_description,
                        (SELECT COUNT(item_id) 
                            FROM item_container 
                            WHERE item_container.container_id=ic_container_id 
                                AND item_container.container_is_item=ic_container_is_item
                        ) AS object_count,
                        (SELECT id
                            FROM stock_img
                            WHERE stock_id=scontainer_id
                            LIMIT 1
                        ) AS simgcontainer_id,
                        (SELECT image
                            FROM stock_img
                            WHERE stock_id=scontainer_id
                            LIMIT 1
                        ) AS simgcontainer_image,
                        (SELECT id
                            FROM stock_img
                            WHERE stock_id=s_id
                            LIMIT 1
                        ) AS simg_id,
                        (SELECT image
                            FROM stock_img
                            WHERE stock_id=s_id
                            LIMIT 1
                        ) AS simg_image
                FROM item_container AS ic
                LEFT JOIN container AS c ON ic.container_id=c.id AND ic.container_is_item=0 AND c.deleted=0
                LEFT JOIN item AS icontainer ON icontainer.id=ic.container_id AND ic.container_is_item=1 AND icontainer.deleted=0
                LEFT JOIN stock AS scontainer ON scontainer.id=icontainer.stock_id
                LEFT JOIN stock_img AS simgcontainer ON simgcontainer.stock_id=scontainer.id
                LEFT JOIN item AS i ON i.id=ic.item_id
                LEFT JOIN stock AS s ON s.id=i.stock_id 
                LEFT JOIN stock_img AS simg ON simg.stock_id=s.id
                LEFT JOIN shelf AS c_sh ON c.shelf_id=c_sh.id
                LEFT JOIN area AS c_a ON c_sh.area_id=c_a.id
                LEFT JOIN site AS c_si ON c_a.site_id=c_si.id
                LEFT JOIN shelf AS i_sh ON i.shelf_id=i_sh.id
                LEFT JOIN area AS i_a ON i_sh.area_id=i_a.id
                LEFT JOIN site AS i_si ON i_a.site_id=i_si.id
                GROUP BY c_id, c_name, c_description, 
                        ic_id, ic_item_id, ic_container_id, ic_container_is_item, 
                        icontainer_id, 
                        scontainer_id, scontainer_name, 
                        i_id, 
                        s_id,
                        simgcontainer_id, simgcontainer_image, 
                        simg_id, simg_image,
                        c_sh.id, i_sh.id,
                        i_location, c_location
                ORDER BY c_name, scontainer_name;
                ";
        $stmt = mysqli_stmt_init($conn);
        if (!mysqli_stmt_prepare($stmt, $sql)) {
            header("Location: ../login.php?sqlerror=getLDAPconfig");
            exit();
        } else {
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $rowCount = $result->num_rows;
            if ($rowCount > 0) {
                while ($row = $result->fetch_assoc()) {
                    $containers_in_array = $container_array['container'];
                    if (!is_null($row['c_id'])) {
                        if (!array_key_exists($row['c_id'], $containers_in_array)) {
                            $container_array['container'][$row['c_id']] = array('id' => $row['c_id'], 'name' => $row['c_name'], 'description' => $row['c_description'], 'count' => $row['object_count'],
                                                                                'img_id' => $row['simgcontainer_id'], 'img_image' => $row['simgcontainer_image'], 'location' => $row['c_location']);
                        }
                        $container_array['container'][$row['c_id']]['object'][] = array('ic_id' => $row['ic_id'], 'item_id' => $row['i_id'], 'id' => $row['s_id'], 'name' => $row['s_name'], 'description' => $row['s_description'],
                                                                                        'img_id' => $row['simg_id'], 'img_image' => $row['simg_image']);
                    }
                    $itemcontainers_in_array = $container_array['itemcontainer'];
                    if (!is_null($row['icontainer_id'])) {
                        if (!array_key_exists($row['icontainer_id'], $itemcontainers_in_array)) {
                            $container_array['itemcontainer'][$row['icontainer_id']] = array('id' => $row['icontainer_id'], 'stock_id' => $row['scontainer_id'], 'name' => $row['scontainer_name'], 'description' => $row['scontainer_description'], 'count' => $row['object_count'],
                                                                                                'img_id' => $row['simgcontainer_id'], 'img_image' => $row['simgcontainer_image'], 'location' => $row['i_location']);
                        }
                        $container_array['itemcontainer'][$row['icontainer_id']]['object'][] = array('ic_id' => $row['icontainer_id'], 'item_id' => $row['i_id'], 'id' => $row['s_id'], 'name' => $row['s_name'], 'description' => $row['s_description'],
                                                                                                        'img_id' => $row['simg_id'], 'img_image' => $row['simg_image']);
                    }
                }
            } 
        }

        $sql_c_empty = "SELECT c.id AS c_id, c.name as c_name, c.description as c_description,
                            (CONCAT(si.name, ' - ', a.name, ' - ', sh.name)) AS location
                        FROM container AS c
                        LEFT JOIN item_container AS ic ON c.id = ic.container_id AND ic.container_is_item=0
                        LEFT JOIN shelf AS sh ON sh.id = c.shelf_id
                        LEFT JOIN area AS a ON a.id = sh.area_id
                        LEFT JOIN site AS si ON si.id = a.site_id
                        WHERE ic.id IS NULL AND c.deleted = 0
                        ORDER BY c_name;";
        $stmt_c_empty = mysqli_stmt_init($conn);
        if (!mysqli_stmt_prepare($stmt_c_empty, $sql_c_empty)) {
            echo ("error");
        } else {
            mysqli_stmt_execute($stmt_c_empty);
            $result_c_empty = mysqli_stmt_get_result($stmt_c_empty);
            $rowCount_c_empty = $result_c_empty->num_rows;
            if ($rowCount_c_empty > 0) {
                while ($row_c_empty = $result_c_empty->fetch_assoc()) {
                    $containers_in_array = $container_array['container'];
                    if (!is_null($row_c_empty['c_id'])) {
                        if (!array_key_exists($row_c_empty['c_id'], $containers_in_array)) {
                            $container_array['container'][$row_c_empty['c_id']] = array('id' => $row_c_empty['c_id'], 'name' => $row_c_empty['c_name'], 'description' => $row_c_empty['c_description'], 'count' => 0,
                                                                                'img_id' => '', 'img_image' => '', 'location' => $row_c_empty['location']);
                        }
                    }
                }
            }
        }

        $sql_i_empty = "SELECT i.id AS i_id, s.id AS s_id, s.name as s_name, s.description as s_description,
                            (CONCAT(si.name, ' - ', a.name, ' - ', sh.name)) AS location,
                            (SELECT id
                                FROM stock_img
                                WHERE stock_id=s_id
                                LIMIT 1
                            ) AS img_id,
                            (SELECT image
                                FROM stock_img
                                WHERE stock_id=s_id
                                LIMIT 1
                            ) AS img_image
                        FROM item AS i
                        LEFT JOIN item_container AS ic ON i.id = ic.container_id AND ic.container_is_item=1
                        LEFT JOIN shelf AS sh ON sh.id = i.shelf_id
                        LEFT JOIN area AS a ON a.id = sh.area_id
                        LEFT JOIN site AS si ON si.id = a.site_id
                        LEFT JOIN stock AS s ON s.id = i.stock_id
                        LEFT JOIN stock_img AS simg ON simg.stock_id=s.id
                        WHERE ic.id IS NULL AND i.is_container = 1 AND i.deleted = 0
                        ORDER BY s_name;";
        $stmt_i_empty = mysqli_stmt_init($conn);
        if (!mysqli_stmt_prepare($stmt_i_empty, $sql_i_empty)) {
            echo ("error");
        } else {
            mysqli_stmt_execute($stmt_i_empty);
            $result_i_empty = mysqli_stmt_get_result($stmt_i_empty);
            $rowCount_i_empty = $result_i_empty->num_rows;
            if ($rowCount_i_empty > 0) {
                while ($row_i_empty = $result_i_empty->fetch_assoc()) {
                    $itemcontainers_in_array = $container_array['itemcontainer'];
                    if (!is_null($row_i_empty['i_id'])) {
                        if (!array_key_exists($row_i_empty['i_id'], $itemcontainers_in_array)) {
                            $container_array['itemcontainer'][$row_i_empty['i_id']] = array('id' => $row_i_empty['i_id'], 'stock_id' => $row_i_empty['s_id'], 'name' => $row_i_empty['s_name'], 'description' => $row_i_empty['s_description'], 
                                                                                                'count' => 0,
                                                                                                'img_id' => $row_i_empty['img_id'], 'img_image' => $row_i_empty['img_image'], 'location' => $row_i_empty['location']);
                        }
                    }
                }
            }
        }

        ?>

        <?php 
        if (is_array($container_array) && !empty($container_array)) {
            print_r('<pre hidden>');
            print_r($container_array);
            print_r('</pre>');
            ?>
                <div class="content">
                    <table class="table table-dark theme-table centertable" style="max-width:max-content;margin-bottom:0px;">
                        <thead style="text-align: center; white-space: nowrap;">
                            <tr class="theme-tableOuter align-middle text-center">
                                <th></th>
                                <th class="align-middle">ID</th>
                                <th class="align-middle">Name</th>
                                <th class="align-middle">Description</th>
                                <th class="align-middle">Location</th>
                                <th class="align-middle">Objects</th>
                                <th class="align-middle">Item?</th>
                                <th class="align-middle">Edit</th>
                                <th class="align-middle"><!--Delete Col--></th>
                                <th colspan=2 class="align-middle"><button type="button" style="padding: 3px 6px 3px 6px" class="btn btn-success" onclick="modalLoadAddContainer()">+ Add New</button></th>
                            </tr>
                        </thead>
                        <tbody>

                            <?php 
                                
                
                                foreach ($container_array['container'] as $col) {
                                    $container_id = isset($col['id']) ? $col['id'] : '';
                                    $container_name = isset($col['name']) ? $col['name'] : '';
                                    $container_count = isset($col['count']) ? $col['count'] : '';
                                    $container_description = isset($col['description']) ? $col['description'] : '';
                                    $container_location = isset($col['location']) ? $col['location'] : '';
                                    $container_objects = array_key_exists('object', $col) ? $col['object'] : '';
                                    $container_img_id = array_key_exists('img_id', $col) ? $col['img_id'] : '';
                                    $container_img_image = array_key_exists('img_image', $col) ? $col['img_image'] : '';
                                    $img_folder = './assets/img/stock/';
                                    if (isset($container_objects) && !empty($container_objects) && count($container_objects) > 0 && $container_objects !== '') {
                                        $toggle = '+';
                                        $toggle_class = ' clickable';
                                    } else {
                                        $toggle = '&nbsp;';
                                        $toggle_class = '';
                                    }
                                    if ($container_count < 1) {
                                        $container_color = ' red';
                                    } else {
                                        $container_color = '';
                                    }
                                    echo ('
                                            <tr id="container-'.$container_id.'">
                                                <td class="text-center align-middle">'); if($container_img_id !== '' && $container_img_id !== null) { echo('<img id="image-'.$container_img_id.'" class="inv-img-main thumb" style="cursor:default !important" src="'.$img_folder.$container_img_image.'">'); } echo('</td>
                                                <td class="text-center align-middle">'.$container_id.'</td>
                                                <td id="container-'.$container_id.'-name" class="text-center align-middle" style="width:300px">'.$container_name.'</td>
                                                <td class="text-center align-middle">'.$container_description.'</td>
                                                <td class="text-center align-middle">'.$container_location.'</td>
                                                <td class="text-center align-middle'.$container_color.'">'.$container_count.'</td>
                                                <td class="text-center align-middle red">No</td>
                                                <td class="text-center align-middle"><button class="btn btn-info" name="submit" title="Edit" onclick="toggleEditcontainer(\''.$container_id.'\')"><i class="fa fa-pencil"></i></button></td>
                                                <td class="text-center align-middle" style="padding-left:0px;padding-right:0px">');
                                                if ($container_count == 0) {
                                                    echo('
                                                        <form action="includes/containers.inc.php" method="POST" id="form-container-'.$container_id.'-delete" enctype="multipart/form-data" hidden>
                                                            <input type="hidden" name="container_delete_submit" form="form-container-'.$container_id.'-delete" value="1" />
                                                            <input type="hidden" name="container_id" form="form-container-'.$container_id.'-delete" value="'.$container_id.'" />
                                                        </form>
                                                        <button class="btn btn-danger" type="submit" form="form-container-'.$container_id.'-delete" title="Delete Container" name="delete-submit"><i class="fa fa-trash"></i></button>
                                                    ');
                                                }
                                                echo('
                                                </td>
                                                <th class="text-center align-middle'.$toggle_class.'" style="width:50px" id="container-'.$container_id.'-toggle"'); if ($toggle == '+') { echo(' onclick="toggleHiddencontainer(\''.$container_id.'\')">+'); } else { echo('><button style="padding: 0px 3px 0px 3px; color:black" class="btn btn-success" onclick="modalLoadAddChildren('.$container_id.', 1)">+ <i class="fa fa-link" style="color:black"></i></button>');} echo('</th>
                                            </tr>
                                            <tr id="container-'.$container_id.'-edit" hidden>
                                                <form action="includes/containers.inc.php" method="POST" id="form-container-'.$container_id.'-edit" enctype="multipart/form-data">
                                                    <input type="hidden" name="container_edit_submit" form="form-container-'.$container_id.'-edit" value="1" />
                                                    <input type="hidden" name="container_id" form="form-container-'.$container_id.'-edit" value="'.$container_id.'" />
                                                    <td></td>
                                                    <td class="text-center align-middle">'.$container_id.'</td>
                                                    <td class="text-center align-middle" style="width:300px"><input type="text" class="form-control text-center" style="max-width:100%" name="container_name" form="form-container-'.$container_id.'-edit" value="'.htmlspecialchars($container_name, ENT_QUOTES, 'UTF-8').'"></td>
                                                    <td class="text-center align-middle"><input type="text" class="form-control text-center" style="max-width:100%" name="container_description" form="form-container-'.$container_id.'-edit" value="'.htmlspecialchars($container_description, ENT_QUOTES, 'UTF-8').'"></td>
                                                    <td class="text-center align-middle">'.$container_location.'</th>
                                                    <td class="text-center align-middle'.$container_color.'">'.$container_count.'</td>
                                                    <td class="text-center align-middle red">No</td>
                                                    <td class="text-center align-middle" style=""><span style="white-space: nowrap"><button class="btn btn-success" type="submit" form="form-container-'.$container_id.'-edit" title="Save" style="margin-right:10px"><i class="fa fa-save"></i></button><button type="button" class="btn btn-warning" name="submit" style="padding:3px 12px 3px 12px" onclick="toggleEditcontainer(\''.$container_id.'\')">Cancel</button></span></td>
                                                    <td></td>
                                                    <th class="text-center align-middle'.$toggle_class.'" style="width:50px" id="container-'.$container_id.'-toggle"'); if ($toggle == '+') { echo(' onclick="toggleHiddencontainer(\''.$container_id.'\')">+'); } else { echo('><button style="padding: 0px 3px 0px 3px; color:black" class="btn btn-success" onclick="modalLoadAddChildren('.$container_id.', 1)">+ <i class="fa fa-link" style="color:black"></i></button>');} echo('</th>
                                                </form>
                                            </tr>
                                    ');
                                    if (isset($container_objects) && !empty($container_objects) && count($container_objects) > 0 && $container_objects !== '') {
                                        echo('
                                            <tr id="container-'.$container_id.'-objects" hidden>
                                                <td colspan=100% style="position: relative;">
                                                    <div style="margin: 5px 20px 10px 20px;">
                                                        <table class="table table-dark theme-table centertable" style="margin:0px;max-width:100%;border: 1px solid #454d55;">
                                                            <thead style="text-align: center; white-space: nowrap;">
                                                                <tr class="theme-tableOuter">
                                                                    <th></th>
                                                                    <th>Item ID</th>
                                                                    <th>Stock ID</th>
                                                                    <th>Stock Name</th>
                                                                    <th style="width:85px"></th>
                                                                    <button type="button" style="padding: 3px 6px 3px 6px; position: absolute; top: 26px; right: 40px;" class="btn btn-success" onclick="modalLoadAddChildren('.$container_id.', 0)">+ Add More</button>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                        ');
                                        foreach($container_objects as $stock) {
                                            $stock_id = $stock['id'];
                                            $item_id = $stock['item_id'];
                                            $stock_name = $stock['name'];
                                            $stock_description = $stock['description'];
                                            $stock_img_id = $stock['img_id'];
                                            $stock_img_image = $stock['img_image'];
                                            $img_folder = './assets/img/stock/';
                                            echo('
                                                                <tr id="container-'.$container_id.'-stock-'.$stock_id.'">
                                                                    <td class="text-center align-middle">'); if (isset($stock['img_id'])) { echo('<img id="image-'.$stock_img_id.'" class="inv-img-main thumb" style="cursor:default !important" src="'.$img_folder.$stock_img_image.'">'); } echo('</td>
                                                                    <td class="text-center align-middle">'.$item_id.'</td>
                                                                    <td class="text-center align-middle">'.$stock_id.'</td>
                                                                    <td class="text-center align-middle link" ><a href="./stock.php?stock_id='.$stock_id.'" id="container-'.$container_id.'-item-'.$item_id.'-name">'.$stock_name.'</a></td>
                                                                    <td class="text-center align-middle"  style="width:85px">
                                                                        <form>
                                                                            <button class="btn btn-danger" type="button" name="submit" onclick="modalLoadUnlinkContainer(\''.$container_id.'\', \''.$item_id.'\', 0)" style="color:black !important; opacity: 0.85; margin-left:5px; padding: 0px 3px 0px 3px" title="Unlink from container">
                                                                                <i class="fa fa-unlink"></i>
                                                                            </button>
                                                                        </form>
                                                                    </td> 
                                                                </tr>
                
                                            ');
                                        }
                                        echo('
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </td>
                                            </tr>
                                        ');
                                    }
                                }
                                foreach ($container_array['itemcontainer'] as $col) {
                                    $container_id = isset($col['id']) ? $col['id'] : '';
                                    $container_stock_id = isset($col['stock_id']) ? $col['stock_id'] : '';
                                    $container_name = isset($col['name']) ? $col['name'] : '';
                                    $container_count = isset($col['count']) ? $col['count'] : '';
                                    $container_description = isset($col['description']) ? $col['description'] : '';
                                    $container_location = isset($col['location']) ? $col['location'] : '';
                                    $container_objects = array_key_exists('object', $col) ? $col['object'] : '';
                                    $container_img_id = array_key_exists('img_id', $col) ? $col['img_id'] : '';
                                    $container_img_image = array_key_exists('img_image', $col) ? $col['img_image'] : '';
                                    $img_folder = './assets/img/stock/';
                                    if (isset($container_objects) && !empty($container_objects) && count($container_objects) > 0 && $container_objects !== '') {
                                        $toggle = '+';
                                        $toggle_class = ' clickable';
                                    } else {
                                        $toggle = '&nbsp;';
                                        $toggle_class = '';
                                    }
                                    if ($container_count < 1) {
                                        $container_color = ' red';
                                    } else {
                                        $container_color = '';
                                    }
                                    echo ('
                                            <tr id="container-'.$container_id.'">
                                                <td class="text-center align-middle">'); if($container_img_id !== '' && $container_img_id !== null) { echo('<img id="image-'.$container_img_id.'" class="inv-img-main thumb" style="cursor:default !important" src="'.$img_folder.$container_img_image.'">'); } echo('</td>
                                                <td class="text-center align-middle">'.$container_id.'</td>
                                                <td class="text-center align-middle" style="width:300px"><a href="stock.php?stock_id='.$container_stock_id.'" class="link" id="container-'.$container_id.'-'.$container_id.'-name">'.$container_name.'</a><p hidden id="container-'.$container_id.'-name">'.$container_name.'</p></td>
                                                <td class="text-center align-middle">'.$container_description.'</td>
                                                <td class="text-center align-middle">'.$container_location.'</td>
                                                <td class="text-center align-middle'.$container_color.'">'.$container_count.'</td>
                                                <td class="text-center align-middle green">Yes</td>
                                                <td class="text-center align-middle"><button class="btn btn-info" name="submit" title="Edit" onclick="navPage(\'stock.php?stock_id='.$container_stock_id.'&modify=edit\')"><i class="fa fa-pencil"></i></button></td>
                                                <td class="text-center align-middle" style="padding-left:0px;padding-right:0px"></td>
                                                <th class="text-center align-middle'.$toggle_class.'" style="width:50px" id="container-'.$container_id.'-toggle"'); if ($toggle == '+') { echo(' onclick="toggleHiddencontainer(\''.$container_id.'\')">+'); } else { echo('><button style="padding: 0px 3px 0px 3px; color:black" class="btn btn-success" onclick="modalLoadAddChildren('.$container_id.', 1)">+ <i class="fa fa-link" style="color:black"></i></button>');} echo('</th>
                                            </tr>
                                    ');
                                    if (isset($container_objects) && !empty($container_objects) && count($container_objects) > 0 && $container_objects !== '') {
                                        echo('
                                            <tr id="container-'.$container_id.'-objects" hidden>
                                                <td colspan=100%  style="position: relative;">
                                                    <div style="margin: 5px 20px 10px 20px">
                                                        <table class="table table-dark theme-table centertable" style="margin:0px;max-width:100%;border: 1px solid #454d55;">
                                                            <thead style="text-align: center; white-space: nowrap;">
                                                                <tr class="theme-tableOuter">
                                                                    <th></th>
                                                                    <th>Item ID</th>
                                                                    <th>Stock ID</th>
                                                                    <th>Stock Name</th>
                                                                    <th style="width:85px"></th>
                                                                    <button type="button" style="padding: 3px 6px 3px 6px; position: absolute; top: 26px; right: 40px;" class="btn btn-success" onclick="modalLoadAddChildren('.$container_id.', 1)">+ Add More</button>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                        ');
                                        foreach($container_objects as $stock) {
                                            $stock_id = $stock['id'];
                                            $item_id = $stock['item_id'];
                                            $stock_name = $stock['name'];
                                            $stock_description = $stock['description'];
                                            $stock_img_id = $stock['img_id'];
                                            $stock_img_image = $stock['img_image'];
                                            $img_folder = './assets/img/stock/';
                                            echo('
                                                                <tr id="container-'.$container_id.'-stock-'.$stock_id.'">
                                                                    <td class="text-center align-middle">'); if (isset($stock['img_id'])) { echo('<img id="image-'.$stock_img_id.'" class="inv-img-main thumb" style="cursor:default !important" src="'.$img_folder.$stock_img_image.'">'); } echo('</td>
                                                                    <td class="text-center align-middle">'.$item_id.'</td>
                                                                    <td class="text-center align-middle">'.$stock_id.'</td>
                                                                    <td class="text-center align-middle link"><a href="./stock.php?stock_id='.$stock_id.'" id="container-'.$container_id.'-item-'.$item_id.'-name">'.$stock_name.'</a></td>
                                                                    <td class="text-center align-middle"  style="width:85px">
                                                                        <form>
                                                                            <button class="btn btn-danger" type="button" name="submit" onclick="modalLoadUnlinkContainer(\''.$container_id.'\', \''.$item_id.'\', 0)" style="color:black !important; opacity: 0.85; margin-left:5px; padding: 0px 3px 0px 3px" title="Unlink from container">
                                                                                <i class="fa fa-unlink"></i>
                                                                            </button>
                                                                        </form>
                                                                    </td> 
                                                                </tr>
                
                                            ');
                                        }
                                        echo('
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </td>
                                            </tr>
                                        ');
                                    }
                                }
                            ?>
                        </tbody>
                    </table>
                </div>
            <?php 
        } else {
            echo('<div class="container">No Collections Found</div>');
        }

        include 'includes/stock-new-properties.inc.php';
        ?>

    </div>
    <!-- Start Modal for uninking from container -->
    <div id="modalDivUnlinkContainer" class="modal">
        <span class="close" onclick="modalCloseUnlinkContainer()">&times;</span>
        <div class="container well-nopad theme-divBg" style="padding:25px">
            <div class="well-nopad theme-divBg" style="overflow-y:auto; height:450px; display:flex;justify-content:center;align-items:center;" id="property-container">
                <form action="includes/stock-modify.inc.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" id="form-unlink-container-item-id" name="item_id" value=""  />
                    <input type="hidden" name="container-unlink" value="1"/>
                    <table class="centertable">
                        <tbody>
                            <tr class="nav-row">
                                <th colspan=100%>Container:</th>
                            </tr>
                            <tr class="nav-row">
                                <td style="width: 200px"><label class="nav-v-c align-middle">Container ID:</label></td>
                                <td style="margin-left:10px"><label id="unlink-container-id" class="nav-v-c align-middle">PLACEHOLDER ID</label></td>
                            </tr>
                            <tr class="nav-row">
                                <td style="width: 200px"><label class="nav-v-c align-middle">Container Name:</label></td>
                                <td style="margin-left:10px"><label id="unlink-container-name" class="nav-v-c align-middle">PLACEHOLDER NAME</label></td>
                            </tr>
                            <tr class="nav-row" style="padding-top:20px">
                                <th colspan=100%>Item to unlink:</th>
                            </tr>
                            <tr class="nav-row">
                                <td style="width: 200px"><label class="nav-v-c align-middle">Item ID:</label></td>
                                <td style="margin-left:10px"><label id="unlink-container-item-id" class="nav-v-c align-middle">PLACEHOLDER ID</label></td>
                            </tr>
                            <tr class="nav-row">
                                <td style="width: 200px"><label class="nav-v-c align-middle">Item Name:</label></td>
                                <td style="margin-left:10px"><label id="unlink-container-item-name" class="nav-v-c align-middle">PLACEHOLDER NAME</label></td>
                            </tr>
                            <tr class="nav-row text-center align-middle" style="padding-top:10px">
                                <td class="text-center align-middle" colspan=100% style="width:100%">
                                    <span style="white-space:nowrap; width:100%">
                                        <button class="btn btn-danger" type="submit" name="submit" style="color:black !important; margin-right:10px">Unlink <i style="margin-left:5px" class="fa fa-unlink"></i></button>
                                        <button class="btn btn-warning" type="button" onclick="modalCloseUnlinkContainer()" style="margin-left:10px">Cancel</button>
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </form>
            </div>
        </div>
    </div>
    <!-- End Modal for uninking from container -->
    <!-- Container Add New Modal -->
    <div id="modalDivAddContainer" class="modal">
        <span class="close" onclick="modalCloseAddContainer()">&times;</span>
        <div class="container well-nopad theme-divBg" style="padding:25px">
            <div class="well-nopad theme-divBg" style="overflow-y:auto; height:450px; display:flex;justify-content:center;align-items:center;" id="property-container">
                <form action="includes/containers.inc.php" method="POST" enctype="multipart/form-data">
                    <table class="centertable">
                        <tbody>
                            <tr class="nav-row">
                                <td style="width: 200px"><label for="container_name" class="nav-v-c align-middle">Container Name:</label></td>
                                <td style="margin-left:10px"><input type="text" class="form-control nav-v-c align-middle" id="container_name" name="container_name" placeholder="Name" /></td>
                                <td></td>
                            </tr>
                            <tr class="nav-row">
                                <td style="width:200px"><label for="container_description" class="nav-v-c align-middle">Container Description:</label></td>
                                <td style="margin-left:10px"><input type="text" class="form-control nav-v-c align-middle" id="container_description" name="container_description" placeholder="Description" /></td>              
                                <!-- <td style="margin-left:5px"><input type="submit" name="submit" value="Add Container" class="btn btn-success"/></td> -->
                            </tr>
                            <tr class="nav-row">
                                <td style="width:200px"><label for="container_description" class="nav-v-c align-middle">Site:</label></td>
                                <td style="margin-left:10px">
                                    <?php
                                        echo('
                                        <select class="form-control stock-inputSize" id="site" name="site" style="width:228px !important" required>
                                            <option value="" selected disabled hidden>Select Site</option>');
                                                include 'includes/dbh.inc.php';
                                                $sql = "SELECT id, name
                                                        FROM site 
                                                        WHERE site.deleted=0
                                                        ORDER BY id";
                                                $stmt = mysqli_stmt_init($conn);
                                                if (!mysqli_stmt_prepare($stmt, $sql)) {
                                                    // fails to connect
                                                } else {
                                                    mysqli_stmt_execute($stmt);
                                                    $result = mysqli_stmt_get_result($stmt);
                                                    $rowCount = $result->num_rows;
                                                    if ($rowCount < 1) {
                                                        echo('<option value="0">No Sites Found...</option>');
                                                    } else {
                                                        // rows found
                                                        while ($row = $result->fetch_assoc()) {
                                                            $sites_id = $row['id'];
                                                            $sites_name = $row['name'];
                                                            echo('<option value="'.$sites_id.'">'.$sites_name.'</option>');
                                                        }
                                                    }
                                                }
                                        echo('
                                        </select>
                                        ');
                                    ?>
                                </td>
                            </tr>
                            <tr class="nav-row">
                                <td style="width:200px"><label for="container_description" class="nav-v-c align-middle">Area:</label></td>
                                <td style="margin-left:10px">
                                    <select class="form-control stock-inputSize" id="area" name="area" style="width:228px !important" disabled required>
                                        <option value="" selected disabled hidden>Select Area</option>
                                    </select>
                                </td>
                            </tr>
                            <tr class="nav-row">
                                <td style="width:200px"><label for="container_description" class="nav-v-c align-middle">Shelf:</label></td>
                                <td style="margin-left:10px">
                                <select class="form-control stock-inputSize" id="shelf" name="shelf" style="width:228px !important" disabled required>
                                        <option value="" selected disabled hidden>Select Shelf</option>
                                    </select>
                                </td>
                            </tr>
                            <tr class="nav-row">
                                <td style="width:200px"></td>
                                <td style="margin-top:10px;margin-left:10px"><button type="submit" name="container_add_submit" value="Add Container" class="btn btn-success">Add Container</button></td>
                                <td hidden><input id="container_type" type="hidden" name="type" value="container" /></td>
                            </tr>
                        </tbody>
                    </table>
                </form>
            </div>
        </div>
    </div>

    <!-- Link to Container Modal -->
    <div id="modalDivAddChildren" class="modal">
        <span class="close" onclick="modalCloseAddChildren()">&times;</span>
        <div class="container well-nopad theme-divBg" style="padding:25px">
            <div class="well-nopad theme-divBg" style="overflow-y:auto; overflow-x: auto; height:600px; " id="property-container" >
                <h4 class="text-center align-middle" style="width:100%;margin-top:10px">Add item to selected container</h4>
                <table class="centertable"><tbody><tr><th style="padding-right:5px">Container ID:</th><td style="padding-right:20px" id="contID"></td><th style="padding-right:5px">Container Name:</th><td id="contName"></td></tr></tbody></table>
                <div class="row" id="TheRow" style="min-width: 100%; max-width:1920px; flex-wrap:nowrap !important; padding-left:10px;padding-right:10px; max-width:max-content">
                    <div class="col well-nopad theme-divBg" style="margin: 20px 10px 20px 10px; padding:20px;">
                        <p><strong>Stock</strong></p>
                        <input type="text" name="search" class="form-control" style="width:300px; margin-bottom:5px" placeholder="Search" oninput="addChildrenSearch(document.getElementById('contID').innerHTML, this.value)"/>
                        <div style=" overflow-y:auto; overflow-x: hidden; height:300px; ">
                            <table id="containerSelectTable" class="table table-dark theme-table centertable" style="margin-bottom:0px; white-space:nowrap;">
                                <thead>
                                    <tr>
                                        <th class='text-center align-middle'>Stock ID</th>
                                        <th class='text-center align-middle'>Name</th>
                                        <th class='text-center align-middle'>Serial Number</th>
                                        <th class='text-center align-middle'>Quantity</th>
                                        <th class='text-center align-middle'>Item ID</th>
                                    </tr>
                                </thead>
                                <tbody id="addChildrenTableBody">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <form enctype="multipart/form-data" action="./includes/stock-modify.inc.php" method="POST" style="padding: 0px; margin:0px">
                <input type="hidden" name="container-link-fromcontainers" value="1" />
                <input type="hidden" id="addChildrenIsItem" name="is_item" value="" />
                <input type="hidden" id="addChildrenContID" name="container_id" value="" />
                <input type="hidden" id="addChildrenStockID" name="stock_id" value="" />
                <input type="hidden" id="addChildrenItemID" name="item_id" value="" />
                <span class="align-middle text-center" style="display:block; white-space:nowrap;width:100%">
                    <input id="submit-button-addChildren" type="submit" name="submit" value="Link" class="btn btn-success" style="margin:10px 10px 0px 10px" disabled></input>
                    <button class="btn btn-warning" type="button" style="margin:10px 10px 0px 10px" onclick="modalCloseAddChildren()">Cancel</button>
                </span>
            </form>
        </div>
    </div>
    <!-- End of Container Add item Modal -->

    
    
    <script>
        function toggleEditcontainer(id) {
            var row = document.getElementById('container-'+id);
            var rowEdit = document.getElementById('container-'+id+'-edit');
            if (rowEdit.hidden == true) {
                row.hidden=true;
                rowEdit.hidden=false;
            } else {
                row.hidden=false;
                rowEdit.hidden=true;
            }
        }
        function toggleHiddencontainer(id) {
            var Row = document.getElementById('container-'+id);
            var RowEdit = document.getElementById('container-'+id+'-edit');
            var button = document.getElementById('container-'+id+'-toggle');
            var buttonEdit = document.getElementById('container-'+id+'-edit-toggle');
            var hiddenID = 'container-'+id+'-objects';
            var hiddenRow = document.getElementById(hiddenID);
            if (hiddenRow.hidden == false) {
                hiddenRow.hidden=true;
                hiddenRow.classList.remove('theme-th-selected');
                Row.classList.remove('theme-th-selected');
                button.innerText='+';
                if (RowEdit) {
                    RowEdit.classList.remove('theme-th-selected');
                }
                if (buttonEdit) {
                    buttonEdit.innerText='+';
                }
            } else {
                hiddenRow.hidden=false;
                hiddenRow.classList.add('theme-th-selected');
                Row.classList.add('theme-th-selected');
                button.innerText='-';
                if (RowEdit) {
                    RowEdit.classList.add('theme-th-selected');
                }
                if (buttonEdit) { 
                    buttonEdit.innerText='-';
                }
            }
        }

        function modalLoadAddContainer() {
            //get the modal div with the property
            var modal = document.getElementById("modalDivAddContainer");
            modal.style.display = "block";
        }

        // When the user clicks on <span> (x), close the modal or if they click the image.
        function modalCloseAddContainer() { 
            var modal = document.getElementById("modalDivAddContainer");
            modal.style.display = "none";
        }

        function populateAreasContainers() {
            // Get the selected site
            var site = document.getElementById("site").value;
            
            // Make an AJAX request to retrieve the corresponding areas
            var xhr = new XMLHttpRequest();
            xhr.open("GET", "includes/stock-selectboxes.inc.php?site=" + site, true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    // Parse the response and populate the area select box
                    var areas = JSON.parse(xhr.responseText);
                    var select = document.getElementById("area");
                    select.options.length = 0;
                    select.options[0] = new Option("Select Area", "");
                    select.options[0].hidden = true;
                    select.options[0].disabled = true;
                    for (var i = 0; i < areas.length; i++) {
                        select.options[select.options.length] = new Option(areas[i].name, areas[i].id);
                    }
                    select.disabled = (select.options.length === 1);
                }
            };
            xhr.send();
        }

        function populateShelvesContainers() {
            // Get the selected area
            var area = document.getElementById("area").value;
            
            // Make an AJAX request to retrieve the corresponding shelves
            var xhr = new XMLHttpRequest();
            xhr.open("GET", "includes/stock-selectboxes.inc.php?area=" + area, true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    // Parse the response and populate the shelf select box
                    var shelves = JSON.parse(xhr.responseText);
                    var select = document.getElementById("shelf");
                    select.options.length = 0;
                    select.options[0] = new Option("Select Shelf", "");
                    select.options[0].hidden = true;
                    select.options[0].disabled = true;
                    for (var i = 0; i < shelves.length; i++) {
                        select.options[select.options.length] = new Option(shelves[i].name, shelves[i].id);
                    }
                    select.disabled = (select.options.length === 1);
                }
            };
            xhr.send();
        }

        if (document.getElementById("site")) {
            document.getElementById("site").addEventListener("change", populateAreasContainers);
        }
        if (document.getElementById("area")) {
            document.getElementById("area").addEventListener("change", populateShelvesContainers);
        }


        function modalLoadAddChildren(container_id, is_item) {
            var modal = document.getElementById("modalDivAddChildren");
            // Get the image and insert it inside the modal - use its "alt" text as a caption
            modal.style.display = "block";
            var contID = document.getElementById('contID');
            var contName = document.getElementById('contName');
            var formContId = document.getElementById('addChildrenContID');
            var formIsItem = document.getElementById('addChildrenIsItem');
            formIsItem.value = is_item;
            formContId.value = container_id;
            contID.innerHTML = container_id;
            contName.innerHTML = document.getElementById('container-'+container_id+'-name').innerHTML;
            var tableBody = document.getElementById('addChildrenTableBody');
            // do the ajax to fill the table
            var xhr = new XMLHttpRequest();
            xhr.open("GET", "includes/stockajax.php?request-nearby-stock=1&item_id="+container_id+"&is_item="+is_item, true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    // Parse the response and populate the shelf select box
                    var data = JSON.parse(xhr.responseText);
                    // console.log(inventory);
                    var bodyExtras = '';
                    var totalCount = data['count'];
                    var trs = '';
                    var tr = '';
                    if (totalCount > 0) {
                        for (let i=0; i<totalCount; i++) {
                            if (data['data'][i]) {
                                if (data['data'][i]['item_id'] == null) {
                                    var id = '--';
                                } else {
                                    var id = data['data'][i]['item_id'];
                                }
                                tr = "<tr class='clickable' onclick=\"addChildrenClicked(this, '"+data['data'][i]['stock_id']+"', '"+id+"')\"> \
                                        <td class='text-center align-middle'>"+data['data'][i]['stock_id']+"</td> \
                                        <td class='text-center align-middle'>"+data['data'][i]['stock_name']+"</td> \
                                        <td class='text-center align-middle'>"+data['data'][i]['item_serial_number']+"</td> \
                                        <td class='text-center align-middle'>"+data['data'][i]['quantity']+"</td> \
                                        <td class='text-center align-middle'>"+id+"</td> \
                                    </tr>";
                                trs = trs+tr;
                            }
                        }
                    }
                    tableBody.innerHTML=trs;
                    // console.log(trs);
                }
            };
            xhr.send();
        }
        function addChildrenClicked(row, stockID, itemID) {
            var linkButton = document.getElementById('submit-button-addChildren');
            var formStockID = document.getElementById('addChildrenStockID');
            var formItemID = document.getElementById('addChildrenItemID');
            var tableBody = document.getElementById('addChildrenTableBody');
            if (tableBody) {
                var tableRows = tableBody.querySelectorAll('tr');
                // Iterate over each <tr> element and remove the class 'theme-th-selected'
                tableRows.forEach(row => {
                    row.classList.remove('theme-th-selected');
                });
            }
            row.classList.add("theme-th-selected");
            linkButton.disabled = false;
            formStockID.value = stockID;
            if (itemID !== '--') {
                formItemID.value = itemID;
            } else {
                formItemID.value = '';
            }
        }
        function modalCloseAddChildren(itemID) { 
            var modal = document.getElementById("modalDivAddChildren");
            modal.style.display = "none";
        }

        function modalLoadUnlinkContainer(containerID, itemID, is_item) {

            if (is_item == 1) {
                var item = 'item';
            } else {
                var item = '';
            }
            var modal = document.getElementById("modalDivUnlinkContainer");

            var text_containerID = document.getElementById("unlink-container-id");
            var text_containerName = document.getElementById("unlink-container-name");
            var text_itemID = document.getElementById("unlink-container-item-id");
            var text_itemName = document.getElementById("unlink-container-item-name");

            var form_itemID = document.getElementById("form-unlink-container-item-id");

            modal.style.display = "block";


            var containerName = document.getElementById("container"+item+"-"+containerID+"-name").innerHTML;
            var itemName = document.getElementById("container"+item+"-"+containerID+"-item-"+itemID+"-name").innerHTML;

            form_itemID.value = itemID;
            text_containerID.innerText = containerID;
            text_containerName.innerText = containerName;
            text_itemID.innerText = itemID;
            text_itemName.innerHTML = itemName;
                
        }

        function modalCloseUnlinkContainer() { 
            var modal = document.getElementById("modalDivUnlinkContainer");
            modal.style.display = "none";
        }
    </script>

    <?php include 'foot.php'; ?>
</body>