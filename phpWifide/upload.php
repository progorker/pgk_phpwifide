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

set_time_limit(0);

global $g_config;

require_once __DIR__ . '/config.php';

header( 'Content-Type: text/plain' );

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

function copy_folder( $src_dir, $tag_dir ) {
  $text = trim( @shell_exec( "ls -1 $src_dir" ) . '' );
  $lines = explode( "\n", $text );
  foreach ( $lines as $ln ) {
    $ln = trim( $ln );
    if ( $ln === '' || $ln === '.' || $ln === '..' ) continue;
    if ( strpos( strtolower( $ln ), '.php' ) !== false ) continue;
    if ( strpos( strtolower( $ln ), '.html' ) !== false ) continue;
    if ( strpos( strtolower( $ln ), '.htm' ) !== false ) continue;
    if ( strpos( strtolower( $ln ), '.js' ) !== false ) continue;
    if ( strpos( strtolower( $ln ), '.css' ) !== false ) continue;

    $src_file = $src_dir . '/' . $ln;
    $tag_file = $tag_dir . '/' . $ln;
    if ( is_file( $src_file ) ) {
      if ( strpos( strtolower( $ln ), '.shp' ) === false ) continue;
      copy_file( $src_file, $tag_file );
    } else if ( is_dir( $src_file ) ) {
      @mkdir( $tag_file, 0777, true );
      copy_folder( $src_file, $tag_file );
    }
  }
}

function copy_file( $src_file, $tag_file ) {
  $cmd = "cp -f $src_file $tag_file";
  @shell_exec( $cmd );
}

function g_ucode() {
  $tmp_dir = __DIR__ . '/buffers';
  @mkdir( $tmp_dir, 0777, true );
  $code = substr( strrev( uniqid() ), 0, 4 );
  $tag_dir = $tmp_dir . '/' . $code;
  while ( is_dir( $tag_dir ) || is_file( $tag_dir ) ) {
    $code = substr( strrev( uniqid() ), 0, 4 );
    $tag_dir = $tmp_dir . '/' . $code;
  }
  return $code;
}

if ( strtolower( $_SERVER['REQUEST_METHOD'] ) === 'post' ) {
  if ( isset( $_FILES['zip'] ) ) {
    $tmp_file = $_FILES['zip']['tmp_name'];
    $filename = $_FILES['zip']['name'];
    $fileext = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
    if ( $fileext === 'zip' ) {
      $tmp_dir = __DIR__ . '/tmp/' . uniqid();
      @mkdir( $tmp_dir, 0777, true );
      $tmp_dir_2 = __DIR__ . '/tmp/' . uniqid();
      @mkdir( $tmp_dir_2, 0777, true );
      $zip_file = $tmp_dir . '/' . $filename;
      $zip_file_2 = $tmp_dir_2 . '/' . $filename;
      if ( move_uploaded_file( $tmp_file, $zip_file ) ) {
        $code = g_ucode();
        $cmd = "cp -f $zip_file $zip_file_2";
        @shell_exec( $cmd );
        $cmd = "cd $tmp_dir && unzip $filename";
        @shell_exec( $cmd );
        @unlink( $zip_file );
        $text = trim( @shell_exec( "ls -1 $tmp_dir" ) . '' );
        $lines = explode( "\n", $text );
        if ( count( $lines ) === 1 ) {
          $dir = trim( $lines[0] );
          if ( $dir !== '' ) {
            $src_dir = $tmp_dir . '/' . $dir;
            $tag_dir = __DIR__ . '/buffers/' . $code;
            @mkdir( $tag_dir, 0777, true );
            copy_folder( $src_dir, $tag_dir );
            $proxy_url = $g_config['mytestor.proxy_url'];
            $proxy_token = $g_config['mytestor.proxy_token'];
            if ( strlen( $proxy_url ) > 0 ) {
              $curl_cmd = $g_config['mytestor.curl_cmd'];
              $upload_url = "$proxy_url". "upload.php?token=$proxy_token&code=$code";
              $cmd = "$curl_cmd -F " . '"' . "zip=@$zip_file_2" . '"' . " $upload_url";
              @shell_exec( $cmd );
            }
            echo "\n", "[ $filename ] file is uploaded to __BUFFER_DIR__/$code folder!", "\n";
          } else {
            echo "\n", "Failed to unzip [ $filename ] file!", "\n";
          }
        } else {
          echo "\n", "Failed to unzip [ $filename ] file!", "\n";
        }
      } else {
        echo "\n", "Failed to upload [ $filename ] file!", "\n";
      }
      $cmd = "rm -rf $tmp_dir";
      @shell_exec( $cmd );
      $cmd = "rm -rf $tmp_dir_2";
      @shell_exec( $cmd );
    } else {
      echo "\n", "[$fileext] file is not supported!", "\n";
    }
  } else {
    echo "\n", "There is no uploaded zip file!", "\n";
  }
} else {
  echo "\n", "There is no uploaded zip file! Method is not post!", "\n";
}
?>