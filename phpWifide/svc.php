<?php
/*
 * =====] PHPSandbox has license as                  ]====
 *
 * Copyright (c) 2013 - 2016 by Corveda, LLC.
 *
 * + License: Custom
 *
 * + License URL: https://github.com/Corveda/PHPSandbox/blob/main/LICENSE
 *
 * + Source URL: https://github.com/Corveda/PHPSandbox
 *
 *
 * =====] CodeMirror has license as                  ]=====
 *
 * Copyright (C) 2017 by Marijn Haverbeke <marijn@haverbeke.berlin> and others
 *
 * + Product URL: https://codemirror.net/5/
 *
 * + License: MIT
 *
 * + License URL: https://codemirror.net/5/LICENSE
 *
 *
 * =====] jQuery on client side has license as       ]=====
 *
 * + License: MIT
 * 
 * + License URL: https://jquery.com/license/
 *
 * ========================================================
 *
 * =====] Following PHP functions has license as [=========
 *
 * + check_http_headers_for_mobile()
 * + g_match(string $regex, string $userAgent)
 * + match_user_agent_with_first_found_matching_rule( $userAgent )
 *
 * -----
 *
 * Copyright (c) 2021 Şerban Ghiţă, Nick Ilyin and contributors.
 *
 * + License: MIT
 * 
 * + License URL: https://github.com/serbanghita/Mobile-Detect/blob/4.x/LICENSE
 *
 * + Source URL: https://github.com/serbanghita/Mobile-Detect
 *
 * ========================================================
 *
 * =====] Other PHP, HTML & CSS codes has license as [=====
 *
 * Copyright (c) 2026 Dinh Thoai Tran <zinospetrel@sdf.org>
 * All rights reserved.
 *
 * + Source URL: https://github.com/progorker/pgk_phpwifide/
 *
 * + License: GPL-2.0
 *
 * ========================================================
 */

global $g_config, $g_svc_token, $g_svc_data_dir, $g_svc_suite_code;

require_once __DIR__ . '/config.php';

function g_svc_exec( $sql ) {
  global $g_config;

  $host = $g_config['mytestor.host'];
  $port = $g_config['mytestor.port'];
  $user = $g_config['mytestor.username'];
  $pass = $g_config['mytestor.password'];
  $db = $g_config['mytestor.database'];
  
  $cmd = $g_config['mytestor.command'];
  if ( strpos( $cmd, 'mariadb' ) !== false ) {
    $cmd .= " --skip-ssl-verify-server-cert";
  } else if ( strpos( $cmd, 'mysql' ) !== false ) {
    $cmd .= " --ssl-mode=DISABLED";  
  }
  $uid = uniqid();
  $fn = $uid . '.sql';
  $ufn = __DIR__ . '/buffers/' . $fn;
  $dir = dirname( $ufn );
  @mkdir( $dir, 0777, true );
  @file_put_contents( $ufn, $sql );
  
  $query = "cd $dir && $cmd --disable-auto-rehash -h $host -P $port --user=$user --password=$pass -e \"use $db; source ./$fn ; \" ";
  $text = @shell_exec($query) . '';
  @unlink( $ufn );
  return $text;
}

function g_svc_escape( $sql ) {
  $sql = str_replace( "_", "_._us_._", $sql );
  $sql = str_replace( "\n", "__nl__", $sql );
  $sql = str_replace( "\r", "__cr__", $sql );
  $sql = str_replace( "\t", "__tb__", $sql );
  $sql = str_replace( "\\", "__sl__", $sql );
  $sql = str_replace( '"', "__dq__", $sql );
  $sql = str_replace( "'", "__sq__", $sql );
  $sql = str_replace( "`", "__td__", $sql );
  return $sql;
}

function g_svc_unescape( $sql ) {
  $sql = str_replace( "__nl__", "\n", $sql );
  $sql = str_replace( "__cr__", "\r", $sql );
  $sql = str_replace( "__tb__", "\t", $sql );
  $sql = str_replace( "__sl__", "\\", $sql );
  $sql = str_replace( "__dq__", '"', $sql );
  $sql = str_replace( "__sq__", "'", $sql );
  $sql = str_replace( "__td__", "`", $sql );
  $sql = str_replace( "_._us_._", "_", $sql );
  return $sql;
}

function g_svc_login() {
  global $g_config, $g_svc_token;
  $username = g_svc_escape($g_config['svc.username']);
  $password = g_svc_escape($g_config['svc.password']);
  $sql = "set @v_token = '_'; set @v_username = api_testor_unescape('$username'); set @v_password = api_testor_unescape('$password'); call api_testor_login( @v_token, @v_username, @v_password ); select @v_token;";
  $text = g_svc_exec( $sql );
  $lines = explode( "\n", $text );
  $g_svc_token = trim( $lines[1] );
}

function g_svc_read_cur_ver() {
  global $g_config, $g_svc_data_dir;
  $data_dir = $g_svc_data_dir;
  $inf_dir = $data_dir . '/ver/inf';
  @mkdir( $inf_dir, 0777, true );
  $inf_file = $inf_dir . '/cur.lst';
  $text = @file_get_contents( $inf_file );
  $text = trim( $text );
  return intval( $text );
}

function g_svc_update_cur_ver() {
  global $g_svc_data_dir;
  $data_dir = $g_svc_data_dir;
  $inf_dir = $data_dir . '/ver/inf';
  @mkdir( $inf_dir, 0777, true );
  $inf_file = $inf_dir . '/cur.lst';
  @file_put_contents( $inf_file, $g_cur_ver );
}

function g_svc_cur_ver() {
  global $g_config, $g_svc_token, $g_svc_suite_code;
  $token = g_svc_escape($g_svc_token);
  $suite_code = g_svc_escape($g_svc_suite_code);
  $sql = "set @v_token = api_testor_unescape('$token'); set @v_suite_id = -1; call api_testor_suite( @v_token, @v_suite_id, api_testor_unescape('$suite_code') ); set @v_data = NULL; call api_testor_option( @v_token, @v_suite_id, @v_data, 'ver:cur', false ); select @v_data;";
  $text = g_svc_exec( $sql );
  $text = trim( $text );
  if ( $text === '' ) return 1;
  $lines = explode( "\n", $text );
  if ( count( $lines ) < 2 ) return 1;
  $str = trim( $lines[1] );
  $str = str_replace( '"', '', $str );
  return intval( $str );
}

function g_svc_test_results() {
  global $g_config, $g_svc_token, $g_svc_suite_code;
  $token = g_svc_escape($g_svc_token);
  $suite_code = g_svc_escape($g_svc_suite_code);
  $sql = "set @v_token = api_testor_unescape('$token'); set @v_suite_id = -1; call api_testor_suite( @v_token, @v_suite_id, api_testor_unescape('$suite_code') ); call api_testor_result( @v_token, @v_suite_id )\\G";
  $text = g_svc_exec( $sql );
  return $text;
}

function g_svc_test_json( $ver ) {
  global $g_config, $g_svc_token, $g_svc_suite_code;
  $token = g_svc_escape($g_svc_token);
  $suite_code = g_svc_escape($g_svc_suite_code);
  $sql = "set @v_token = api_testor_unescape('$token'); set @v_suite_id = -1; call api_testor_suite( @v_token, @v_suite_id, api_testor_unescape('$suite_code') ); set @v_data = NULL; call api_testor_option( @v_token, @v_suite_id, @v_data, 'ver:$ver', false ); select @v_data;";
  $text = g_svc_exec( $sql );
  $text = trim( $text );
  if ( $text === '' ) return false;
  $lines = explode( "\n", $text );
  $str = trim( $lines[1] );
  if ( $str[0] === '"' ) {
    $str = substr( $str, 1 );
  }
  if ( $str[strlen($str) - 1] === '"' ) {
    $str = substr( $str, 0, strlen( $str ) - 1 );
  }
  $str = g_svc_unescape( $str );
  $nstr = '';
  $pos = strpos( $str, '"c":"' );
  if ( $pos !== false ) {
    $nstr = substr( $str, 0, $pos + 5 );
    $str = substr( $str, $pos + 5 );
    $pos = strpos( $str, ' -:- ' );
    if ( $pos !== false ) {
      $nstr .= substr( $str, $pos + 5 );
    } else {
      $nstr .= $str;
    }
  } else {
    $nstr = $str;
  }
  return $nstr;
}

function g_svc_test_success() {
  global $g_config, $g_svc_token, $g_svc_suite_code;
  $token = g_svc_escape($g_svc_token);
  $suite_code = g_svc_escape($g_svc_suite_code);
  $all_text = '';
  $no = 1;
  do {
    $sql = "set @v_token = api_testor_unescape('$token'); set @v_suite_id = -1; call api_testor_suite( @v_token, @v_suite_id, api_testor_unescape('$suite_code') ); call api_testor_success( @v_token, @v_suite_id, $no )\\G";
    $text = g_svc_exec( $sql );
    $all_text .= "\n" . $text;
    $no++;
  } while ( trim( $text ) !== '' );
  return $all_text;
}

function g_svc_test_failed() {
  global $g_config, $g_svc_token, $g_svc_suite_code;
  $token = g_svc_escape($g_svc_token);
  $suite_code = g_svc_escape($g_svc_suite_code);
  $all_text = '';
  $no = 1;
  do {
    $sql = "set @v_token = api_testor_unescape('$token'); set @v_suite_id = -1; call api_testor_suite( @v_token, @v_suite_id, api_testor_unescape('$suite_code') ); call api_testor_failed( @v_token, @v_suite_id, $no )\\G";
    $text = g_svc_exec( $sql );
    $all_text .= "\n" . $text;
    $no++;
  } while ( trim( $text ) !== '' );
  return $all_text;
}

function g_svc_save_ver( $ver ) {
  global $g_config, $g_svc_data_dir;
  $ver_json = g_svc_test_json( $ver );
  if ( $ver_json === false ) return;
  $data_dir = $g_svc_data_dir;
  $inf_dir = $data_dir . '/ver/inf';
  @mkdir( $inf_dir, 0777, true );
  file_put_contents( $inf_dir . '/' . $ver . '.text', g_svc_test_results() );
  file_put_contents( $inf_dir . '/' . $ver . '.s.text', g_svc_test_success() );
  file_put_contents( $inf_dir . '/' . $ver . '.f.text', g_svc_test_failed() );
  file_put_contents( $inf_dir . '/' . $ver . '.json', $ver_json );
}

function g_svc_backup_ver( $ver ) {
  global $g_config, $g_svc_token, $g_svc_suite_code, $g_svc_data_dir;
  $zip_cmd = $g_config['mytestor.zip_cmd'];
  $ver_json = g_svc_test_json( $ver );
  if ( $ver_json === false ) return;
  $token = g_svc_escape($g_svc_token);
  $suite_code = g_svc_escape($g_svc_suite_code);
  $data_dir = $g_svc_data_dir;
  $dld_dir = $data_dir . '/ver/dld';
  @mkdir( $dld_dir, 0777, true );
  $tmp_dir = $data_dir . '/ver/tmp/' . $ver;
  @mkdir( $tmp_dir, 0777, true );
  $ver_dir = $tmp_dir . '/' . $ver;
  @mkdir( $ver_dir, 0777, true );
  echo "\n-- #Backup: v.$ver ...\n";
  $data = [];
  $no = 1;
  do {
    $cnt = 0;
    $sql = "set @v_token = api_testor_unescape('$token'); set @v_suite_id = -1; call api_testor_suite( @v_token, @v_suite_id, api_testor_unescape('$suite_code') ); call api_testor_source_list( @v_token, @v_suite_id, $no );";
    $text = g_svc_exec( $sql );
    if ( trim( $text ) !== '' ) {
      $lines = explode("\n", $text);
      foreach ( $lines as $ln ) {
        $ln = trim($ln);
        if ( $ln === '' ) continue;
        $fields = explode("\t", $ln);
        if ( count( $fields ) === 4 ) {
          if ( trim( $fields[0] ) === 'rel_key' ) continue;
          $it = array( 'rel_key' => $fields[0], 'abs_key' => $fields[1], 'rel_value' => $fields[2], 'abs_value' => $fields[3] );
          array_push( $data, $it );
          $cnt++;
        }
      }
    }
    $no++;
  } while ( $cnt > 0 );
  $flist = "";
  foreach ( $data as $it ) {
    $ln = $it['rel_value'];
    $sr = $data_dir . $ln;
    if ( ! file_exists( $sr ) ) continue;
    $fn = $ver_dir . $ln;
    $fd = dirname( $fn );
    @mkdir( $fd, 0777, true );
    file_put_contents( $fn, file_get_contents( $sr ) );
    echo "\n-- #Item: $ln";
    $flist .= "\n$ln";
  }
  file_put_contents( "$tmp_dir/$ver/files.lst", trim( $flist ) );
  $cmd = "cd $tmp_dir && $zip_cmd -r $dld_dir/$ver.zip $ver";
  @shell_exec( $cmd );
  echo "\n\n-- #Zip: /ver/dld/$ver.zip \n";
  $cmd = "rm -rf $tmp_dir";
  @shell_exec( $cmd );
}

function g_svc( $suite_code, $filename ) {
  global $g_svc_token, $g_svc_suite_code, $g_svc_data_dir;

  echo "\n\n", "-- =====| SVC-start: $suite_code, $filename |=====", "\n\n";

  $g_svc_suite_code = $suite_code;
  $filename = trim( $filename );
  $filename = str_replace( '..', '', $filename );
  $filename = str_replace( '..', '', $filename );
  $filename = trim( $filename );
  $g_buffer_dir = __DIR__ . '/buffers';
  @mkdir( $g_buffer_dir, 0777, true );
  if ( ! is_dir( $g_buffer_dir . '/' . $filename ) ) {
    return;
  }
  $g_svc_data_dir = $g_buffer_dir . '/' . $filename;

  g_svc_login();

  echo "-- #Token: ", $g_svc_token, "\n";
  $g_svc_cur_ver = g_svc_cur_ver();
  echo "-- #Version: ", $g_svc_cur_ver, "\n";

  $ver = g_svc_cur_ver();
  if ( $ver >= $g_svc_cur_ver ) {
    echo "\n-- #Update: new.ver -> $ver\n";
    g_svc_save_ver( $ver );
    g_svc_backup_ver( $ver );
    $g_svc_cur_ver = $ver;
  }

  echo "\n\n", "-- =====| SVC-end: $suite_code, $filename |=====", "\n\n";
}

?>