<!-- Navigation Bar for the top of the page, using the config settings for logo and colour -->
<a href="./" class="nav-head" style="color:<?php echo($current_banner_text_color); ?> !important"><?php echo($current_system_name); ?></a>
<div class="nav inv-nav">
    <div id="nav-row" class="nav-row">
        <div class="logo-div">
            <a href="./">
                <img class="logo" src="assets/img/config/<?php echo($current_logo_image); ?>" />
            </a>
        </div>
        <?php 
        if ((isset($_SESSION['username'])) && ($_SESSION['username'] !== '')) {
            echo('<div id="add-div" class="nav-div" style="margin-right:5px">
                <button id="add-stock" class="btn btn-success cw nav-v-c btn-nav" style="opacity:90%" onclick="navPage(updateQueryParameter(\'stock.php\', \'modify\', \'add\'))">
                    <i class="fa fa-plus"></i> Add 
                </button>
            </div> 
            <div id="remove-div" class="nav-div" style="margin-left:5px;margin-right:5px">
                <button id="remove-stock" class="btn btn-danger cw nav-v-c btn-nav" onclick="navPage(updateQueryParameter(\'stock.php\', \'modify\', \'remove\'))">
                    <i class="fa fa-minus"></i> Remove 
                </button>
            </div>
            <div id="transfer-div" class="nav-div" style="margin-left:5px;margin-right:0px">
                <button id="transfer-stock" class="btn btn-warning nav-v-c btn-nav" style="color:black" onclick="navPage(updateQueryParameter(\'./stock.php\', \'modify\', \'move\'))">
                    <i class="fa fa-arrows-h"></i> Move 
                </button>
            </div>');

            $nav_right_set = 0;

            if (isset($profile_name)) {
                echo('
                <div id="profile-div" class="'); if ($nav_right_set == 0) { echo('nav-right'); $nav_right_set = 1; } echo(' nav-div">
                    <button id="profile" class="nav-v-c nav-trans" style="color:'.$current_banner_text_color.'" onclick="window.location=\'./profile.php\';">'.$profile_name.'</button>
                </div> 
                ');
            }
            if (isset($loggedin_role)) {
                if (in_array($loggedin_role, $config_admin_roles_array)) {
                    echo('
                    <div id="admin-div" class="'); if ($nav_right_set == 0) { echo('nav-right'); $nav_right_set = 1; } echo(' nav-div">
                        <button id="admin" class="nav-v-c nav-trans" style="color:'.$current_banner_text_color.'" onclick="window.location=\'./admin.php\';">Admin</button>
                    </div> 
                    ');
                }
            }
            if (isset($profile_name)) {
                echo ('
                    <div id="logout-div" class="'); if ($nav_right_set == 0) { echo('nav-right'); $nav_right_set = 1; } echo('nav-div">
                        <button id="logout" class="nav-v-c nav-trans" style="color:'.$current_banner_text_color.'" onclick="window.location=\'./logout.php\';">Logout</button>
                    </div> 
                ');
            }
        }
        
        ?>
    </div>
</div>