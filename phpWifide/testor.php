<?php
/*
 * Copyright (c) 2026 Dinh Thoai Tran <zinospetrel@sdf.org>
 * All rights reserved.
 *
 * + Source URL: https://github.com/progorker/pgk_phptestor/
 *
 * + License: GPL-2.0
 */

namespace phptestor;

global $g_config;

require_once __DIR__ . '/testor-cfg.php';

function g_mytestor_exec( $sql ) {
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

  $text = @shell_exec("$cmd --disable-auto-rehash -h $host -P $port --user=$user --password=$pass -e \"use $db; $sql \" ");
  return $text;
}

function g_escape( $sql ) {
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

function g_unescape( $sql ) {
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

function g_fill_table( $cols, $rows, $fsz, &$p_results ) {
  $p_results .= "\n\n";
  $p_results .= '+';
  for ( $i = 0; $i < count( $cols ); $i++ ) {
    $p_results .= str_pad( '-', $fsz[ $i ] + 2, '-' ) . '+';
  }
  $p_results .= "\n";
  $p_results .= '|';
  for ( $i = 0; $i < count( $cols ); $i++ ) {
    $p_results .= str_pad( ' ' . $cols[ $i ] . ' ', $fsz[ $i ] + 2, ' ' ) . '|';
  }
  $p_results .= "\n";
  $p_results .= '+';
  for ( $i = 0; $i < count( $cols ); $i++ ) {
    $p_results .= str_pad( '-', $fsz[ $i ] + 2, '-' ) . '+';
  }
  $p_results .= "\n";
  for ( $j = 0; $j < count( $rows ); $j++ ) {
    $rw = $rows[ $j ];
    $p_results .= '|';
    for ( $i = 0; $i < count( $rw ); $i++ ) {
      $p_results .= str_pad( ' ' . $rw[ $i ] . ' ', $fsz[ $i ] + 2, ' ' ) . '|';
    }
    $p_results .= "\n";
  }
  $p_results .= '+';
  for ( $i = 0; $i < count( $cols ); $i++ ) {
    $p_results .= str_pad( '-', $fsz[ $i ] + 2, '-' ) . '+';
  }
  $p_results .= "\n";
}

function g_parse_results( $text, &$p_results ) {
  if ( $text === null ) $text = '';
  $lines = explode( "\n", $text );
  $fld_cnt = -1;
  $cols = [];
  $rows = [];
  $fsz = [];
  $p_results = '';
  foreach ( $lines as $ln ) {
    if ( trim( $ln ) === '' ) continue;
    $fields = explode( "\t", $ln );
    if ( count( $fields ) !== $fld_cnt ) {
      if ( $fld_cnt > 0 ) {
        g_fill_table( $cols, $rows, $fsz, $p_results );
      }
      $rows = [];
      $cols = [];
      $fsz = [];
      foreach ( $fields as $fd ) {
        $cols[] = $fd;
        $fsz[] = strlen( $fd );
      }
      $fld_cnt = count( $fields );
      continue;
    } 
    $rw = [];
    for ( $i = 0; $i < count( $fields ); $i++ ) {
      $fd = $fields[ $i ];
      $sz = strlen( $fd );
      if ( $sz > $fsz[ $i ] ) {
        $fsz[ $i ] = $sz;
      }
      $rw[] = $fd;
    }
    $rows[] = $rw;
  }
  if ( $fld_cnt > 0 ) {
    g_fill_table( $cols, $rows, $fsz, $p_results );
  }
}

function g_testing_double( $p_token, &$p_id, $p_suite_id, $p_case_id, $p_code, $p_operand, $p_value, $p_proc ) {
  $proc_list = ['api_testor_equals', 'api_testor_not_equals', 'api_testor_greater_than', 'api_testor_not_greater_than', 'api_testor_less_than', 'api_testor_not_less_than' ];
  if ( ! in_array( $p_proc, $proc_list, true ) ) return;
  $token = g_escape( $p_token );
  $suite_id = intval( $p_suite_id . '' );
  $case_id = intval( $p_case_id . '' );
  $code = g_escape( $p_code );
  $operand = doubleval( $p_operand . '' );
  $value = doubleval( $p_value . '' );
  $sql = "set @v_token = api_testor_unescape('$token'); set @v_suite_id = $suite_id; set @v_case_id = $case_id; set @v_code = api_testor_unescape('$code'); set @v_operand = $operand; set @v_value = $value; set @v_id = -1; call $p_proc( @v_token, @v_id, @v_suite_id, @v_case_id, @v_code, @v_operand, @v_value ); select @v_id;";
  $text = g_mytestor_exec( $sql );
  $lines = explode( "\n", $text );
  $ln = trim( $lines[1] );
  $p_id = intval( $ln );
}

function g_testing_string( $p_token, &$p_id, $p_suite_id, $p_case_id, $p_code, $p_operand, $p_value, $p_proc ) {
  $proc_list = ['api_testor_same', 'api_testor_not_same', 'api_testor_contains', 'api_testor_not_contains'];
  if ( ! in_array( $p_proc, $proc_list, true ) ) return;
  $token = g_escape( $p_token );
  $suite_id = intval( $p_suite_id . '' );
  $case_id = intval( $p_case_id . '' );
  $code = g_escape( $p_code );
  $operand = g_escape( $p_operand );
  $value = g_escape( $p_value );
  $sql = "set @v_token = api_testor_unescape('$token'); set @v_suite_id = $suite_id; set @v_case_id = $case_id; set @v_code = api_testor_unescape('$code'); set @v_operand = api_testor_unescape('$operand'); set @v_value = api_testor_unescape('$value'); set @v_id = -1; call $p_proc( @v_token, @v_id, @v_suite_id, @v_case_id, @v_code, @v_operand, @v_value ); select @v_id;";
  $text = g_mytestor_exec( $sql );
  $lines = explode( "\n", $text );
  $ln = trim( $lines[1] );
  $p_id = intval( $ln );
}

// Table: 1.
function api_testor_welcome() {
  $sql = "select message from testor_welcome order by id asc";
  $text = g_mytestor_exec( $sql );
  $results = '';
  g_parse_results( $text, $results );
  return $results;
}

// Function: 1.
function api_testor_is_online( $p_token ) {
  $token = g_escape( $p_token );
  $sql = "set @v_token = api_testor_unescape('$token'); set @v_online = api_testor_is_online( @v_token ); select @v_online;";
  $text = g_mytestor_exec( $sql );
  $lines = explode( "\n", $text );
  $rs = trim( $lines[1] );
  if ( $rs === '1' ) return true;
  return false;
}

// Function: 2.
function api_testor_has_right( $p_token, $p_right_code ) {
  $token = g_escape( $p_token );
  $right_code = g_escape( $p_right_code );
  $sql = "set @v_token = api_testor_unescape('$token'); set @v_right_code = api_testor_unescape('$right_code'); set @v_right = api_testor_has_right( @v_token, @v_right_code ); select @v_right;";
  $text = g_mytestor_exec( $sql );
  $lines = explode( "\n", $text );
  $rs = trim( $lines[1] );
  if ( $rs === '1' ) return true;
  return false;
}

// Function: 3.
function api_testor_escape( $p_input ) {
  return g_escape( $p_input );
}

// Function: 4.
function api_testor_unescape( $p_input ) {
  return g_unescape( $p_input );
}

// Procedure: 1.
function api_testor_login( &$p_token, $p_username, $p_password ) {
  $username = g_escape( $p_username );
  $password = g_escape( $p_password );
  $sql = "set @v_token = '_'; set @v_username = api_testor_unescape('$username'); set @v_password = api_testor_unescape('$password'); call api_testor_login( @v_token, @v_username, @v_password); select @v_token;";
  $text = g_mytestor_exec( $sql );
  $lines = explode( "\n", $text );
  $p_token = trim( $lines[1] );
}

// Procedure 2.
function api_testor_logout( $p_token ) {
  $token = g_escape( $p_token );
  $sql = "set @v_token = api_testor_unescape('$token'); call api_testor_logout( @v_token );";
  g_mytestor_exec( $sql );
}

// Procedure: 3.
function api_testor_current_user( $p_token, &$p_user_id, &$p_username ) {
  $token = g_escape( $p_token );
  $sql = "set @v_token = api_testor_unescape('$token'); set @v_user_id = -1; set @v_username = '_'; call api_testor_current_user( @v_token, @v_user_id, @v_username ); select @v_user_id, @v_username;";
  $text = g_mytestor_exec( $sql );
  $lines = explode( "\n", $text );
  $ln = trim( $lines[1] );
  $fields = explode( "\t", $ln );
  $p_user_id = intval( $fields[0] );
  $p_username = trim( $fields[1] );
}

// Procedure: 4.
function api_testor_user_rights( $p_token, &$p_api_call, &$p_user_make, &$p_user_demo, &$p_storage_full ) {
  $token = g_escape( $p_token );
  $sql = "set @v_token = api_testor_unescape('$token'); set @v_api_call = -1; set @v_user_make = -1; set @v_user_demo = -1; set @v_storage_full = -1; call api_testor_user_rights( @v_token, @v_api_call, @v_user_make, @v_user_demo, @v_storage_full ); select @v_api_call, @v_user_make, @v_user_demo, @v_storage_full;";
  $text = g_mytestor_exec( $sql );
  $lines = explode( "\n", $text );
  $ln = trim( $lines[1] );
  $fields = explode( "\t", $ln );
  $p_api_call = trim( $fields[0] ) == '1' ? true : false;
  $p_user_make = trim( $fields[1] ) == '1' ? true : false;
  $p_user_demo = trim( $fields[2] ) == '1' ? true : false;
  $p_storage_full = trim( $fields[3] ) == '1' ? true : false;
}

// Procedure 5.
function api_testor_change_password( $p_token, $p_password ) {
  $token = g_escape( $p_token );
  $password = g_escape( $p_password );
  $sql = "set @v_token = api_testor_unescape('$token'); set @v_password = api_testor_unescape('$password'); call api_testor_change_password( @v_token, @v_password );";
  g_mytestor_exec( $sql );
}

// Procedure 6.
function api_testor_create_user( $p_token, $p_username, $p_password, $p_api_call, $p_user_make, $p_user_demo, $p_quota ) {
  $token = g_escape( $p_token );
  $username = g_escape( $p_username );
  $password = g_escape( $p_password );
  $api_call = ( $p_api_call ? 1 : 0 );
  $user_make = ( $p_user_make ? 1 : 0 );
  $user_demo = ( $p_user_demo ? 1 : 0 );
  $quota = intval( $p_quota . '' );
  $sql = "set @v_token = api_testor_unescape('$token'); set @v_username = api_testor_unescape('$username'); set @v_password = api_testor_unescape('$password'); set @v_api_call = $api_call; set @v_user_make = $user_make; set @v_user_demo = $user_demo; set @v_quota = $quota; call api_testor_create_user( @v_token, @v_username, @v_password, @v_api_call, @v_user_make, @v_user_demo, @v_quota );";
  g_mytestor_exec( $sql );
}

// Procedure: 7.
function api_testor_suite( $p_token, &$p_id, $p_code ) {
  $token = g_escape( $p_token );
  $code = g_escape( $p_code );
  $sql = "set @v_token = api_testor_unescape('$token'); set @v_code = api_testor_unescape('$code'); set @v_id = -1; call api_testor_suite( @v_token, @v_id, @v_code ); select @v_id;";
  $text = g_mytestor_exec( $sql );
  $lines = explode( "\n", $text );
  $ln = trim( $lines[1] );
  $p_id = intval( $ln );
}

// Procedure: 8.
function api_testor_case( $p_token, &$p_id, $p_suite_id, $p_code ) {
  $token = g_escape( $p_token );
  $code = g_escape( $p_code );
  $suite_id = intval( $p_suite_id . '' );
  $sql = "set @v_token = api_testor_unescape('$token'); set @v_code = api_testor_unescape('$code'); set @v_suite_id = $suite_id; set @v_id = -1; call api_testor_case( @v_token, @v_id, @v_suite_id, @v_code ); select @v_id;";
  $text = g_mytestor_exec( $sql );
  $lines = explode( "\n", $text );
  $ln = trim( $lines[1] );
  $p_id = intval( $ln );
}

// Procedure: 9.
function api_testor_suite_case( $p_token, &$p_suite_id, &$p_case_id, $p_suite_code, $p_case_code ) {
  $token = g_escape( $p_token );
  $suite_code = g_escape( $p_suite_code );
  $case_code = g_escape( $p_case_code );
  $sql = "set @v_token = api_testor_unescape('$token'); set @v_suite_code = api_testor_unescape('$suite_code'); set @v_case_code = api_testor_unescape('$case_code'); set @v_suite_id = -1; set @v_case_id = -1; call api_testor_suite_case( @v_token, @v_suite_id, @v_case_id, @v_suite_code, @v_case_code ); select @v_suite_id, @v_case_id;";
  $text = g_mytestor_exec( $sql );
  $lines = explode( "\n", $text );
  $ln = trim( $lines[1] );
  $fields = explode( "\t", $ln );
  $p_suite_id = intval( trim( $fields[0] ) );
  $p_case_id = intval( trim( $fields[1] ) );
}

// Procedure: 10.
function api_testor_clean( $p_token, $p_id ) {
  $token = g_escape( $p_token );
  $id = intval( $p_id . '' );
  $sql = "set @v_token = api_testor_unescape('$token'); set @v_id = $id; call api_testor_clean( @v_token, @v_id );";
  g_mytestor_exec( $sql );
}

// Procedure: 11.
function api_testor_test( $p_token, &$p_id, $p_suite_id, $p_case_id, $p_code, $p_condition, $p_message ) {
  $token = g_escape( $p_token );
  $suite_id = intval( $p_suite_id . '' );
  $case_id = intval( $p_case_id . '' );
  $code = g_escape( $p_code );
  $condition = ( $p_condition ? 1 : 0 );
  $message = g_escape( $p_message );
  $sql = "set @v_token = api_testor_unescape('$token'); set @v_suite_id = $suite_id; set @v_case_id = $case_id; set @v_code = api_testor_unescape('$code'); set @v_condition = $condition; set @v_message = api_testor_unescape('$message'); set @v_id = -1; call api_testor_test( @v_token, @v_id, @v_suite_id, @v_case_id, @v_code, @v_condition, @v_message ); select @v_id;";
  $text = g_mytestor_exec( $sql );
  $lines = explode( "\n", $text );
  $ln = trim( $lines[1] );
  $p_id = intval( $ln );
}

// Procedure: 12.
function api_testor_finish( $p_token, $p_id, &$p_results, $p_beauty = false ) {
  $token = g_escape( $p_token );
  $id = intval( $p_id . '' );
  if ( $p_beauty === true ) {
    $sql = "set @v_token = api_testor_unescape('$token'); set @v_id = $id; call api_testor_finish( @v_token, @v_id )\\G";
    $p_results = g_mytestor_exec( $sql );
  } else {
    $sql = "set @v_token = api_testor_unescape('$token'); set @v_id = $id; call api_testor_finish( @v_token, @v_id );";
    $text = g_mytestor_exec( $sql );
    g_parse_results( $text, $p_results );
  }
}

// Procedure: 13.
function api_testor_result( $p_token, $p_id, &$p_results, $p_beauty = false ) {
  $token = g_escape( $p_token );
  $id = intval( $p_id . '' );
  if ( $p_beauty === true ) {
    $sql = "set @v_token = api_testor_unescape('$token'); set @v_id = $id; call api_testor_result( @v_token, @v_id )\\G";
    $p_results = g_mytestor_exec( $sql );
  } else {
    $sql = "set @v_token = api_testor_unescape('$token'); set @v_id = $id; call api_testor_result( @v_token, @v_id );";
    $text = g_mytestor_exec( $sql );
    g_parse_results( $text, $p_results );
  }
}

// Procedure: 14.
function api_testor_option( $p_token, $p_suite_id, &$p_data, $p_code, $p_remove ) {
  $token = g_escape( $p_token );
  $suite_id = intval( $p_suite_id . '' );
  $code = g_escape( $p_code );
  $remove = ( $p_remove ? 1 : 0 );
  if ( $p_data === null ) {
    $sql = "set @v_token = api_testor_unescape('$token'); set @v_suite_id = $suite_id; set @v_code = api_testor_unescape('$code'); set @v_remove = $remove; set @v_data = NULL; call api_testor_option( @v_token, @v_suite_id, @v_data, @v_code, @v_remove ); select @v_data;";
  } else {
    $data = g_escape( $p_data );
    $sql = "set @v_token = api_testor_unescape('$token'); set @v_suite_id = $suite_id; set @v_code = api_testor_unescape('$code'); set @v_remove = $remove; set @v_data = api_testor_unescape('$data'); call api_testor_option( @v_token, @v_suite_id, @v_data, @v_code, @v_remove ); select @v_data;";
  }
  $text = g_mytestor_exec( $sql );
  $lines = explode( "\n", $text );
  $ln = $lines[1];
  $p_data = $ln;
}

// Procedure: 15.
function api_testor_e_functions( $p_mysql_database, $p_find, &$p_names ) {
  $mysql_database = g_escape( $p_mysql_database );
  $find = g_escape( $p_find );
  $sql = "set @v_mysql_database = api_testor_unescape('$mysql_database'); set @v_find = api_testor_unescape('$find'); set @v_names = ''; call api_testor_e_functions( @v_mysql_database, @v_find, @v_names); select @v_names;";
  $text = g_mytestor_exec( $sql );
  $lines = explode( "\n", $text );
  $ln = $lines[1];
  $p_names = $ln;
}

// Procedure: 16.
function api_testor_e_procedures( $p_mysql_database, $p_find, &$p_names ) {
  $mysql_database = g_escape( $p_mysql_database );
  $find = g_escape( $p_find );
  $sql = "set @v_mysql_database = api_testor_unescape('$mysql_database'); set @v_find = api_testor_unescape('$find'); set @v_names = ''; call api_testor_e_procedures( @v_mysql_database, @v_find, @v_names); select @v_names;";
  $text = g_mytestor_exec( $sql );
  $lines = explode( "\n", $text );
  $ln = $lines[1];
  $p_names = $ln;
}

// Procedure: 17.
function api_testor_e_tables( $p_mysql_database, $p_find, &$p_names ) {
  $mysql_database = g_escape( $p_mysql_database );
  $find = g_escape( $p_find );
  $sql = "set @v_mysql_database = api_testor_unescape('$mysql_database'); set @v_find = api_testor_unescape('$find'); set @v_names = ''; call api_testor_e_tables( @v_mysql_database, @v_find, @v_names); select @v_names;";
  $text = g_mytestor_exec( $sql );
  $lines = explode( "\n", $text );
  $ln = $lines[1];
  $p_names = $ln;
}

// Procedure: 18.
function api_testor_version( $p_token, $p_suite_id, $p_cur_ver ) {
  $token = g_escape( $p_token );
  $suite_id = intval( $p_suite_id . '' );
  $cur_ver = intval( $p_cur_ver . '' );
  $sql = "set @v_token = api_testor_unescape('$token'); set @v_suite_id = $suite_id; set @v_cur_ver = $cur_ver; call api_testor_version( @v_token, @v_suite_id, @v_cur_ver );";
  g_mytestor_exec( $sql );
}

// Procedure: 19.
function api_testor_source( $p_token, $p_suite_id, $p_case_code, &$p_results, $p_beauty = false ) {
  $token = g_escape( $p_token );
  $suite_id = intval( $p_suite_id . '' );
  $case_code = g_escape( $p_case_code );
  if ( $p_beauty === true ) {
    $sql = "set @v_token = api_testor_unescape('$token'); set @v_suite_id = $suite_id; set @v_case_code = api_testor_unescape('$case_code'); call api_testor_source( @v_token, @v_suite_id, @v_case_code )\\G";
    $p_results = g_mytestor_exec( $sql );
  } else {
    $sql = "set @v_token = api_testor_unescape('$token'); set @v_suite_id = $suite_id; set @v_case_code = api_testor_unescape('$case_code'); call api_testor_source( @v_token, @v_suite_id, @v_case_code );";
    $text = g_mytestor_exec( $sql );
    g_parse_results( $text, $p_results );
  }
}

// Procedure: 20.
function api_testor_source_list( $p_token, $p_suite_id, $p_page_no, &$p_results, $p_beauty = false ) {
  $token = g_escape( $p_token );
  $suite_id = intval( $p_suite_id . '' );
  $page_no = intval( $p_page_no . '' );
  if ( $p_beauty === true ) {
    $sql = "set @v_token = api_testor_unescape('$token'); set @v_suite_id = $suite_id; set @v_page_no = $page_no; call api_testor_source_list( @v_token, @v_suite_id, @v_page_no )\\G";
    $p_results = g_mytestor_exec( $sql );
  } else {
    $sql = "set @v_token = api_testor_unescape('$token'); set @v_suite_id = $suite_id; set @v_page_no = $page_no; call api_testor_source_list( @v_token, @v_suite_id, @v_page_no );";
    $text = g_mytestor_exec( $sql );
    g_parse_results( $text, $p_results );
  }
}

// Procedure: 21.
function api_testor_true( $p_token, &$p_id, $p_suite_id, $p_case_id, $p_code, $p_condition ) {
  $token = g_escape( $p_token );
  $suite_id = intval( $p_suite_id . '' );
  $case_id = intval( $p_case_id . '' );
  $code = g_escape( $p_code );
  $condition = ( $p_condition ? 1 : 0 );
  $sql = "set @v_token = api_testor_unescape('$token'); set @v_suite_id = $suite_id; set @v_case_id = $case_id; set @v_code = api_testor_unescape('$code'); set @v_condition = $condition; set @v_id = -1; call api_testor_true( @v_token, @v_id, @v_suite_id, @v_case_id, @v_code, @v_condition ); select @v_id;";
  $text = g_mytestor_exec( $sql );
  $lines = explode( "\n", $text );
  $ln = trim( $lines[1] );
  $p_id = intval( $ln );
}

// Procedure: 22.
function api_testor_not_true( $p_token, &$p_id, $p_suite_id, $p_case_id, $p_code, $p_condition ) {
  $token = g_escape( $p_token );
  $suite_id = intval( $p_suite_id . '' );
  $case_id = intval( $p_case_id . '' );
  $code = g_escape( $p_code );
  $condition = ( $p_condition ? 1 : 0 );
  $sql = "set @v_token = api_testor_unescape('$token'); set @v_suite_id = $suite_id; set @v_case_id = $case_id; set @v_code = api_testor_unescape('$code'); set @v_condition = $condition; set @v_id = -1; call api_testor_not_true( @v_token, @v_id, @v_suite_id, @v_case_id, @v_code, @v_condition ); select @v_id;";
  $text = g_mytestor_exec( $sql );
  $lines = explode( "\n", $text );
  $ln = trim( $lines[1] );
  $p_id = intval( $ln );
}

// Procedure: 23.
function api_testor_success( $p_token, $p_suite_id, $p_page_no, &$p_results, $p_beauty = false ) {
  $token = g_escape( $p_token );
  $suite_id = intval( $p_suite_id . '' );
  $page_no = intval( $p_page_no . '' );
  if ( $p_beauty === true ) {
    $sql = "set @v_token = api_testor_unescape('$token'); set @v_suite_id = $suite_id; set @v_page_no = $page_no; call api_testor_success( @v_token, @v_suite_id, @v_page_no )\\G";
    $p_results = g_mytestor_exec( $sql );
  } else {
    $sql = "set @v_token = api_testor_unescape('$token'); set @v_suite_id = $suite_id; set @v_page_no = $page_no; call api_testor_success( @v_token, @v_suite_id, @v_page_no );";
    $text = g_mytestor_exec( $sql );
    g_parse_results( $text, $p_results );
  }
}

// Procedure: 24.
function api_testor_error( $p_token, &$p_id, $p_suite_id, $p_case_id, $p_code, $p_message ) {
  $token = g_escape( $p_token );
  $suite_id = intval( $p_suite_id . '' );
  $case_id = intval( $p_case_id . '' );
  $code = g_escape( $p_code );
  $message = g_escape( $p_message );
  $sql = "set @v_token = api_testor_unescape('$token'); set @v_suite_id = $suite_id; set @v_case_id = $case_id; set @v_code = api_testor_unescape('$code'); set @v_message = api_testor_unescape('$message'); set @v_id = -1; call api_testor_error( @v_token, @v_id, @v_suite_id, @v_case_id, @v_code, @v_message ); select @v_id;";
  $text = g_mytestor_exec( $sql );
  $lines = explode( "\n", $text );
  $ln = trim( $lines[1] );
  $p_id = intval( $ln );
}

// Procedure: 25.
function api_testor_equals( $p_token, &$p_id, $p_suite_id, $p_case_id, $p_code, $p_operand, $p_value ) {
  $proc = 'api_testor_equals';
  g_testing_double( $p_token, $p_id, $p_suite_id, $p_case_id, $p_code, $p_operand, $p_value, $proc );
}

// Procedure: 26.
function api_testor_not_equals( $p_token, &$p_id, $p_suite_id, $p_case_id, $p_code, $p_operand, $p_value ) {
  $proc = 'api_testor_not_equals';
  g_testing_double( $p_token, $p_id, $p_suite_id, $p_case_id, $p_code, $p_operand, $p_value, $proc );
}

// Procedure: 27.
function api_testor_greater_than( $p_token, &$p_id, $p_suite_id, $p_case_id, $p_code, $p_operand, $p_value ) {
  $proc = 'api_testor_greater_than';
  g_testing_double( $p_token, $p_id, $p_suite_id, $p_case_id, $p_code, $p_operand, $p_value, $proc );
}

// Procedure: 28.
function api_testor_not_greater_than( $p_token, &$p_id, $p_suite_id, $p_case_id, $p_code, $p_operand, $p_value ) {
  $proc = 'api_testor_not_greater_than';
  g_testing_double( $p_token, $p_id, $p_suite_id, $p_case_id, $p_code, $p_operand, $p_value, $proc );
}

// Procedure: 29.
function api_testor_less_than( $p_token, &$p_id, $p_suite_id, $p_case_id, $p_code, $p_operand, $p_value ) {
  $proc = 'api_testor_less_than';
  g_testing_double( $p_token, $p_id, $p_suite_id, $p_case_id, $p_code, $p_operand, $p_value, $proc );
}

// Procedure: 30.
function api_testor_not_less_than( $p_token, &$p_id, $p_suite_id, $p_case_id, $p_code, $p_operand, $p_value ) {
  $proc = 'api_testor_not_less_than';
  g_testing_double( $p_token, $p_id, $p_suite_id, $p_case_id, $p_code, $p_operand, $p_value, $proc );
}

// Procedure: 31.
function api_testor_same( $p_token, &$p_id, $p_suite_id, $p_case_id, $p_code, $p_operand, $p_value ) {
  $proc = 'api_testor_same';
  g_testing_string( $p_token, $p_id, $p_suite_id, $p_case_id, $p_code, $p_operand, $p_value, $proc );
}

// Procedure: 32.
function api_testor_not_same( $p_token, &$p_id, $p_suite_id, $p_case_id, $p_code, $p_operand, $p_value ) {
  $proc = 'api_testor_not_same';
  g_testing_string( $p_token, $p_id, $p_suite_id, $p_case_id, $p_code, $p_operand, $p_value, $proc );
}

// Procedure: 33.
function api_testor_contains( $p_token, &$p_id, $p_suite_id, $p_case_id, $p_code, $p_operand, $p_value ) {
  $proc = 'api_testor_contains';
  g_testing_string( $p_token, $p_id, $p_suite_id, $p_case_id, $p_code, $p_operand, $p_value, $proc );
}

// Procedure: 34.
function api_testor_not_contains( $p_token, &$p_id, $p_suite_id, $p_case_id, $p_code, $p_operand, $p_value ) {
  $proc = 'api_testor_not_contains';
  g_testing_string( $p_token, $p_id, $p_suite_id, $p_case_id, $p_code, $p_operand, $p_value, $proc );
}

// Procedure: 35.
function api_testor_failed( $p_token, $p_suite_id, $p_page_no, &$p_results, $p_beauty = false ) {
  $token = g_escape( $p_token );
  $suite_id = intval( $p_suite_id . '' );
  $page_no = intval( $p_page_no . '' );
  if ( $p_beauty === true ) {
    $sql = "set @v_token = api_testor_unescape('$token'); set @v_suite_id = $suite_id; set @v_page_no = $page_no; call api_testor_failed( @v_token, @v_suite_id, @v_page_no )\\G";
    $p_results = g_mytestor_exec( $sql );
  } else {
    $sql = "set @v_token = api_testor_unescape('$token'); set @v_suite_id = $suite_id; set @v_page_no = $page_no; call api_testor_failed( @v_token, @v_suite_id, @v_page_no );";
    $text = g_mytestor_exec( $sql );
    g_parse_results( $text, $p_results );
  }
}

// Procedure: 36.
function api_testor_man( $p_module, $p_kind, $p_code, &$p_man ) {
  $module = g_escape( $p_module );
  $kind = g_escape( $p_kind );
  $code = g_escape( $p_code );
  $sql = "set @v_module = api_testor_unescape('$module'); set @v_kind = api_testor_unescape('$kind'); set @v_code = api_testor_unescape('$code'); set @v_man = ''; call api_testor_man( @v_module, @v_kind, @v_code, @v_man ); select @v_man as manual\\G";
  $p_man = g_mytestor_exec( $sql );
  $idx = strpos( $p_man, 'manual:' );
  if ( $idx !== false ) {
    $p_man = trim( substr( $p_man, $idx + 7 ) );
  }
}

// Procedure: 37.
function api_testor_pattern( $p_module, $p_kind, $p_code, $p_variant, &$p_pattern ) {
  $module = g_escape( $p_module );
  $kind = g_escape( $p_kind );
  $code = g_escape( $p_code );
  $variant = g_escape( $p_variant );
  $sql = "set @v_module = api_testor_unescape('$module'); set @v_kind = api_testor_unescape('$kind'); set @v_code = api_testor_unescape('$code'); set @v_variant = api_testor_unescape('$variant'); set @v_pattern = ''; call api_testor_pattern( @v_module, @v_kind, @v_code, @v_variant, @v_pattern ); select @v_pattern as pattern\\G";
  $p_pattern = g_mytestor_exec( $sql );
  $idx = strpos( $p_pattern, 'pattern:' );
  if ( $idx !== false ) {
    $p_pattern = trim( substr( $p_pattern, $idx + 8 ) );
  }
}

// Procedure: 38.
function api_testor_startup() {
  global $g_testor_dir, $g_suite_code, $g_testor_username, $g_testor_password, $g_token, $g_src_dir, $g_suite_id, $g_last_version, $g_clear_version;

  \phptestor\api_testor_login( $g_token, $g_testor_username, $g_testor_password );
  \phptestor\api_testor_suite( $g_token, $g_suite_id, $g_suite_code );
  \phptestor\api_testor_clean( $g_token, $g_suite_id );


  register_shutdown_function( function() {
    global $g_token, $g_suite_id;
    $error = error_get_last();
    if ( $error !== null && in_array( $error['type'], [E_ERROR, E_PARSE, E_COMPILE_ERROR, E_CORE_ERROR] ) ) {
      $message = print_r( $error, true );
      $case_id = -1;
      $test_id = -1;
      \phptestor\api_testor_case( $g_token, $case_id, $g_suite_id, 'global_error' );
      \phptestor\api_testor_error( $g_token, $test_id, $g_suite_id, $case_id, 'exception.1', $message );
      \phptestor\api_testor_shutdown();
    }
    exit();
  } );

  if ( $g_clear_version ) {
    $data = null;
    \phptestor\api_testor_option( $g_token, $g_suite_id, $data, 'ver:cur', true );
  }
  \phptestor\api_testor_version( $g_token, $g_suite_id, $g_last_version );
  \phptestor\api_testor_option( $g_token, $g_suite_id, $g_src_dir, 'src_dir', false );
}

// Procedure: 38.
function api_testor_shutdown() {
  global $g_token, $g_suite_id;

  $results = '';
  \phptestor\api_testor_finish( $g_token, $g_suite_id, $results, false);
  echo "\n========== Results: Finished ==========\n", $results, "\n";

  $results = '';
  \phptestor\api_testor_source_list( $g_token, $g_suite_id, 1, $results, false);
  echo "\n========== Results: Source List ==========\n", $results, "\n";

  $results = '';
  \phptestor\api_testor_success( $g_token, $g_suite_id, 1, $results, false);
  echo "\n========== Results: Success ==========\n", $results, "\n";

  $results = '';
  \phptestor\api_testor_failed( $g_token, $g_suite_id, 1, $results, false);
  echo "\n========== Results: Failed ==========\n", $results, "\n";

  $results = '';
  \phptestor\api_testor_result( $g_token, $g_suite_id, $results, false);
  echo "\n========== Results: Result ==========\n", $results, "\n";

  \phptestor\api_testor_logout( $g_token );
}

?>