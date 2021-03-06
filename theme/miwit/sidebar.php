<?php
if (!defined("_GNUBOARD_")) exit;

if (!$is_sidebar or $mw['config']['cf_side_position'] == 'none') {
    /*echo "<style>";
    echo "#mw5 .main { width:{$mw['config']['cf_width']}px; }";
    echo "#mw5 .menu_title { width:{$mw['config']['cf_width']}px; }";
    echo "</style>";*/
    return;
}
?>
<?php $side = array(); ?>
<div class="sidebar">

    <?php if (!$mw['config']['cf_no_sidebar_head']) echo mw_eval($mw['config']['cf_sidebar_head_html']); ?>

    <?php
    ob_start();
    if ($mw['config']['cf_sidebar_outlogin']) echo outlogin("theme/mw5");
    $side['outlogin'] = ob_get_clean();
    ?>

    <?php
    ob_start();
    if (!$is_member and $mw['config']['cf_sidebar_social']) {
        include_once($g4['path']."/plugin/social-login/include.php");
        $is_social_login = false;
        ob_start();
        echo "<div class='block'>".PHP_EOL;
        echo "<div class='social-login'>".PHP_EOL;
        if ($mw['config']['cf_facebook_use_login']) {
            $is_social_login = true;
            echo "<img src=\"".G5_PLUGIN_URL."/social-login/img/facebook24.png\" onclick=\"facebook_login()\"/>".PHP_EOL;
        }
        if ($mw['config']['cf_twitter_use_login']) {
            $is_social_login = true;
            echo "<img src=\"".G5_PLUGIN_URL."/social-login/img/twitter24.png\" onclick=\"twitter_login()\"/>".PHP_EOL;
        }
        if ($mw['config']['cf_google_use_login']) {
            $is_social_login = true;
            echo "<img src=\"".G5_PLUGIN_URL."/social-login/img/google24.png\" onclick=\"google_login()\"/>".PHP_EOL;
        }
        if ($mw['config']['cf_naver_use_login']) {
            $is_social_login = true;
            echo "<img src=\"".G5_PLUGIN_URL."/social-login/img/naver24.png\" onclick=\"naver_login()\"/>".PHP_EOL;
        }
        if ($mw['config']['cf_kakao_use_login']) {
            $is_social_login = true;
            echo "<img src=\"".G5_PLUGIN_URL."/social-login/img/kakao24.png\" onclick=\"kakao_login()\"/>".PHP_EOL;
        }
        echo "</div></div>".PHP_EOL;
        $social_login = ob_get_clean();

        if ($is_social_login) echo $social_login;
    }
    $side['social'] = ob_get_clean();
    ?>

    <?php
    ob_start(); 
    if ($is_member and $mw_cash['cf_cash_name'] and function_exists("mw_cash_grade") and $mw['config']['cf_sidebar_cash']) {?>
    <div class="block my_cash" onclick="location.href='<?php echo $mw_cash['path']?>/'">
    <div>
        <label>나의 <?php echo $mw_cash['cf_cash_name']?></label> :
        <?php echo number_format($mw_cash['mb_cash']).$mw_cash['cf_cash_unit']?>
    </div>
    <?php
    $grade = mw_cash_grade($member['mb_id']);
    //$grade['my_charge'] = 100;
    $min = $grade['my_charge'] - $grade['gd_cash'];
    $max = $grade['next']['gd_cash'] - $grade['gd_cash'];
    $g1 = @round($min / $max, 2) * 100;
    $g2 = 100 - $g1;

    $s1 = "{$grade['gd_name']} ";
    //$s1.= "(".number_format($grade['gd_cash']).")";
    $s2 = "{$grade['next']['gd_name']} (".number_format($grade['next']['gd_cash']).")";

    if (!$grade['gd_name'])
        $s1 = "시작";
    ?>
    <div><label>나의 현재등급</label> : <?php echo $s1?></div>
    <div class="graph">
        <div class="grade" data="<?php echo (int)$grade['my_charge']?>"  width="<?php echo $g1?>">0</div>
    </div>

    <script>
    $(document).ready(function () {
        setTimeout(function () {
            var w = $(".graph .grade").attr("width");
            var m = $(".graph .grade").attr("data");
            var d = 1200;

            if (w <= 0) return;

            $(".graph .grade").animate({'width':w+'%'},{duration:d,step:function () { }});
            $({g:0}).animate({g:m},{duration:d,easing:'swing',step:function () {
                $(".graph .grade").text(Math.round(this.g));
            }, complete:function () {
                $(".graph .grade").text(m);
            }});
        }, 1000);
    });
    </script>
    </div>
    <?php } // is_member ?>
    <?php $side['cybercash'] = ob_get_clean(); ?>

    <?php
    ob_start();
    if ($mw['config']['cf_sidebar_notice']) {
        $tmp = sql_fetch("select * from {$g5['board_table']} where bo_table = '{$mw['config']['cf_sidebar_notice_table']}' ");
        if ($tmp) { 
            echo "<div class='block'>".mw_latest("theme/mw5", $mw['config']['cf_sidebar_notice_table'], $mw['config']['cf_sidebar_notice_limit'])."</div>";
        }
        else {
            echo "<div class='block'>공지사항은 notice 게시판 생성시 자동으로 출력됩니다.</div>";
        }
    }
    $side['notice'] = ob_get_clean();
    ?>

    <?php if ($mw['config']['cf_sidebar_menu'] and count($mw5_menu)) { ?>
    <?php ob_start(); ?>
    <div class="sidebar-nav">
    <ul>
    <?php
    for ($i=0; $row=$mw5_menu[$i]; $i++) {
        //if (!defined("_INDEX_") && $menu && substr($menu['me_code'], 0, 2) != substr($row['me_code'], 0, 2)) { continue; } 
        echo "<li class=\"sidebar-brand\">";
        echo "<a href=\"{$row['me_link']}\" target=\"_{$row['me_target']}\">{$row['me_name']}</a>";
        echo "</li>\n";

        for ($j=0; $row2=$mw5_menu[$i]['sub'][$j]; $j++) {
            $class = "";
            if ($menu['me_code'] == $row2['me_code'])
                $class = "selected";

            $bo_count = '';
            $bo_new = '';

            if ($row2['bo_new'])
                $bo_new = "<span class=\"{$cf_new_class}\">{$row2['bo_new']}</span>";
            if ($row2['bo_count'])
                $bo_count = "<span class=\"count\">{$row2['bo_count']}</span>";

            echo "<li class=\"{$class}\">";
            echo $bo_count;
            echo "<a href=\"{$row2['me_link']}\" target=\"_{$row2['me_target']}\">{$row2['me_name']}{$bo_new}</a>";
            echo "</li>\n";
        }
    }
    ?>
    </ul>
    </div>
    <?php $side['menu'] = ob_get_clean(); ?>
    <?php } ?>

    <?php
    ob_start();
    if ($mw['config']['cf_sidebar_latest_write']) {
        echo '<div class="block">'. mw_latest_write($mw['config']['cf_sidebar_latest_write_limit']).'</div>';
    }
    $side['latest'] = ob_get_clean();
    ?>

    <?php
    ob_start(); 
    if ($mw['config']['cf_sidebar_latest_comment']) { 
        echo '<div class="block">'.mw_latest_comment($mw['config']['cf_sidebar_latest_comment_limit']).'</div>';
    }
    $side['comment'] = ob_get_clean();
    ?>

    <?php
    ob_start(); 
    if ($mw['config']['cf_sidebar_visit']) { 
        echo '<div class="block">'.mw_connect().'</div>';
    }
    $side['visit'] = ob_get_clean();
    ?>

    <?php
    ob_start();
    if ($mw['config']['cf_sidebar_poll']) { 
        $poll = poll("theme/mw5");
        if ($poll)
            echo "<div class=\"block\">{$poll}</div>";
    }
    $side['poll'] = ob_get_clean();
    ?>

    <?php
    $sort = explode(',', $mw['config']['cf_sidebar_sortable']);
    foreach ($sort as $row) echo $side[$row];
    ?>

    <?php if (!$mw['config']['cf_no_sidebar_tail']) echo mw_eval($mw['config']['cf_sidebar_tail_html']); ?>

</div><!--sidebar-->


