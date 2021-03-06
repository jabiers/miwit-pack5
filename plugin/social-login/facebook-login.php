<?php
/**
 * 소셜로그인 (Social-login for Gnuboard4)
 *
 * Copyright (c) 2012 Choi Jae-Young <www.miwit.com>
 *
 * 저작권 안내
 * - 저작권자는 이 프로그램을 사용하므로서 발생하는 모든 문제에 대하여 책임을 지지 않습니다. 
 * - 이 프로그램을 어떠한 형태로든 재배포 및 공개하는 것을 허락하지 않습니다.
 * - 이 저작권 표시사항을 저작권자를 제외한 그 누구도 수정할 수 없습니다.
 */
include_once("_common.php");
include_once($g4['path']."/plugin/social-login/_lib.php");
include_once($g4['path']."/plugin/social-login/_config.php");

if ($is_member)
    alert_close("이미 로그인 하셨습니다.");

$mw_facebook_config['callback'] = set_http($tmp['host'].dirname($_SERVER['SCRIPT_NAME'])."/facebook-login.php");

if ($_SESSION['access_token']) {
    $url = sprintf("https://graph.facebook.com/v2.5/me?fields=id,name,email,gender,bio,birthday");
   
    $ch = curl_init();  
    curl_setopt ($ch, CURLOPT_URL, $url);
    curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0); 
    curl_setopt ($ch, CURLOPT_SSLVERSION, 1); 
    curl_setopt ($ch, CURLOPT_HEADER, 0); 
    curl_setopt ($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer '.$_SESSION['access_token'])); 
    curl_setopt ($ch, CURLOPT_POST, 0);
    curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt ($ch, CURLOPT_TIMEOUT, 30); 
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($ch);       
    curl_close($ch);

    $facebook_profile = js_decode($result);

    if (!preg_match('/^utf/i', $g4['charset'])) {
        $facebook_profile['name'] = set_euckr($facebook_profile['name']);
        //$facebook_profile['username'] = set_euckr($facebook_profile['username']);
        $facebook_profile['bio'] = set_euckr($facebook_profile['bio']);
        $facebook_profile['quotes'] = set_euckr($facebook_profile['quotes']);
    }

    $row = sql_fetch(sprintf("select * from mw_facebook_login where facebook_id = '%s'", $facebook_profile['id']), false);
    if ($row['facebook_id'])
    {
        $mb = get_member($row['mb_id']);

        // 차단된 아이디인가?
        if ($mb['mb_intercept_date'] && $mb['mb_intercept_date'] <= date("Ymd", $g4['server_time'])) {
            $date = preg_replace("/([0-9]{4})([0-9]{2})([0-9]{2})/", "\\1년 \\2월 \\3일", $mb['mb_intercept_date']); 
            unset($_SESSION['access_token']);
            alert_close("회원님의 아이디는 접근이 금지되어 있습니다.\\n\\n처리일 : $date");
        }

        // 탈퇴한 아이디인가?
        if ($mb['mb_leave_date'] && $mb['mb_leave_date'] <= date("Ymd", $g4['server_time'])) {
            $date = preg_replace("/([0-9]{4})([0-9]{2})([0-9]{2})/", "\\1년 \\2월 \\3일", $mb['mb_leave_date']); 
            unset($_SESSION['access_token']);
            alert_close("탈퇴한 아이디이므로 접근하실 수 없습니다.\\n\\n탈퇴일 : $date");
        }

        // 회원아이디 세션 생성
        set_session('ss_mb_id', $mb['mb_id']);
        set_session('ss_mb_key', md5($mb['mb_datetime'] . $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']));
    }
    else if (!$row && $facebook_profile['id'])
    {
        mwp_is_social();

        include_once($g4['path']."/plugin/social-login/_upgrade.php");

        $c = 2;
        $mb_nick = $facebook_profile['name'];
        if (!$mb_nick) {
            unset($_SESSION['access_token']);
            alert_close("이름 정보가 없어 소셜로그인 할 수 없습니다.");
        }

        while ($row = sql_fetch(sprintf("select * from %s where mb_nick = '%s'", $g4['member_table'], $mb_nick))) {
            $mb_nick = $facebook_profile['name'].$c++;
        }

        $mb_email = $facebook_profile['email'];
        $row = sql_fetch(" select * from {$g4['member_table']} where mb_email = '$mb_email' ");
        if ($row) {
            if ($facebook_profile['username'])
                $mb_email = $facebook_profile['username']."@facebook.com";
            else
                $mb_email = $facebook_profile['id']."@facebook.com";

            $row = sql_fetch(" select * from {$g4['member_table']} where mb_email = '$mb_email' ");
            if ($row) {
                unset($_SESSION['access_token']);
                alert_close("페이스북에 등록된 이메일주소가 이미 회원으로 가입되어 있어 소셜로그인이 불가능 합니다.");
            }
        }

        $mb_id = $member['mb_id']?$member['mb_id']:'@fb-'.$facebook_profile['id'];

        $sql = " insert into mw_facebook_login set ";
        $sql.= "   mb_id = '{$mb_id}' ";
        $sql.= " , facebook_access_token = '{$access_token}' ";
        $sql.= " , facebook_id = '{$facebook_profile['id']}' ";
        $sql.= " , facebook_name = '{$facebook_profile['name']}' ";
        $sql.= " , facebook_username = '{$facebook_profile['username']}' ";
        $sql.= " , facebook_email = '{$facebook_profile['email']}' ";
        $sql.= " , facebook_birthday = '{$facebook_profile['birthday']}' ";
        $sql.= " , facebook_link = '{$facebook_profile['link']}' ";
        $sql.= " , facebook_gender = '{$facebook_profile['gender']}' ";
        $sql.= " , facebook_locale = '{$facebook_profile['locale']}' ";
        $sql.= " , facebook_bio = '{$facebook_profile['bio']}' ";
        $sql.= " , facebook_datetime = '{$g4['time_ymdhis']}' ";
        sql_query($sql);

        if (!$is_member) {
            $mb_sex = $facebook_profile['gender'] == 'male' ? 'M' : 'F';
            $mb_birth = explode("/", $facebook_profile['birthday']);
            $mb_birth = "$mb_birth[2]$mb_birth[0]$mb_birth[1]";

            $sql = " insert into {$g4['member_table']} set ";
            $sql.= "   mb_id = '{$mb_id}' ";
            $sql.= " , mb_name = '{$facebook_profile['name']}' ";
            $sql.= " , mb_nick = '{$mb_nick}' ";
            $sql.= " , mb_email = '{$mb_email}' ";
            $sql.= " , mb_homepage = '{$facebook_profile['link']}' ";
            $sql.= " , mb_level = '{$config['cf_register_level']}' ";
            $sql.= " , mb_sex = '{$mb_sex}' ";
            $sql.= " , mb_birth = '{$mb_birth}' ";
            $sql.= " , mb_signature = '{$facebook_profile['bio']}' ";
            $sql.= " , mb_datetime = '{$g4['time_ymdhis']}' ";
            $sql.= " , mb_ip = '{$_SERVER['REMOTE_ADDR']}' ";
            $sql.= " , mb_email_certify = '{$g4['time_ymdhis']}' ";
            $sql.= " , mb_mailling = '1}' ";
            $sql.= " , mb_open = '1}' ";
            sql_query($sql);

            sql_query("update {$g4['member_table']} set mb_birth = replace(mb_birth, '-', '') where mb_id like '@fb-%'");

            // 회원아이디 세션 생성
            set_session('ss_mb_id', $mb_id);
            set_session('ss_mb_reg', $mb_id);
            set_session('ss_mb_key', md5($g4['time_ymdhis'] . $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']));

            // 회원가입 포인트 부여
            insert_point($mb_id, $config['cf_register_point'], "회원가입 축하", '@member', $mb_id, '회원가입');

            if (function_exists("mw_partner_register")) mw_partner_register($mb_id);
        }

    }

    echo "<script> opener.location.reload(); self.close(); </script>";
    exit;
}

if ($_GET['code']) {
    $url = sprintf("https://graph.facebook.com/v2.3/oauth/access_token?client_id=%s&client_secret=%s&redirect_uri=%s&code=%s"
        , $mw_facebook_config['appId'], $mw_facebook_config['secret'], $mw_facebook_config['callback'], $_GET['code']);
   
    $ch = curl_init();  
    curl_setopt ($ch, CURLOPT_URL, $url);
    curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0); 
    curl_setopt ($ch, CURLOPT_SSLVERSION,1); 
    curl_setopt ($ch, CURLOPT_HEADER, 0); 
    curl_setopt ($ch, CURLOPT_POST, 0);
    curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt ($ch, CURLOPT_TIMEOUT, 30); 
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($ch);       
    curl_close($ch);

    $json = json_decode($result, true);

    if ($json['access_token']) {
        set_session("access_token", $json['access_token']);

        echo "<script>location.reload()</script>";
        exit;
    }
    else
        alert_close("로그인 실패");

    exit;
}

if (!$_SESSION['access_token'] ) {
    $url = sprintf("https://www.facebook.com/dialog/oauth?client_id=%s&redirect_uri=%s&display=popup"
        , $mw_facebook_config['appId'], $mw_facebook_config['callback']);

    header("location: ".$url);
    exit;
}



