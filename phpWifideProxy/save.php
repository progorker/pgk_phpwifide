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

global $g_config, $g_buffers_dir;

require_once __DIR__ . '/config.php';

$g_buffers_dir = $g_config['mytestor.buffers_dir'];

header( 'Content-Type: text/plain' );

function g_param( $key ) {
  if ( isset( $_POST[ $key ] ) ) return $_POST[ $key ];
  if ( isset( $_GET[ $key ] ) ) return $_GET[ $key ];
  return '';
}

if ( trim( g_param('token') ) !== $g_config['mytestor.proxy_token'] ) {
  echo "Error: Invalid token!";
  exit;
}

if ( strtolower( $_SERVER['REQUEST_METHOD'] ) === 'post' ) {
  if ( isset( $_FILES['file'] ) ) {
    $tmp_file = $_FILES['file']['tmp_name'];
    $filename = g_param('name');
    $filename = trim( $filename );
    $filename = str_replace( '__d__', '.', $filename );
    $filename = str_replace( '__s__', '/', $filename );
    $filename = str_replace( '..', '', $filename );
    $filename = str_replace( '..', '', $filename );
    $filename = trim( $filename );

    $tag_file = $g_buffers_dir . '/' . $filename;
    $dir = dirname( $tag_file );
    @mkdir( $dir, 0777, true );
    $tmp_dir = $g_buffers_dir . '/tmp/' . uniqid();
    @mkdir( $tmp_dir, 0777, true );
    $buf_file = $tmp_dir . '/' . uniqid() . '.file';
    if ( move_uploaded_file( $tmp_file, $buf_file ) ) {
      $cmd = "cp -f $buf_file $tag_file";
      @shell_exec( $cmd );
      echo "Success: ";
    } else {
      echo "Error: Failed to save uploaded file!";
    }
  } else {
    echo "Error: There is no uploaded file!";
  }
} else {
  echo "Error: There is no uploaded file! Method is not post!";
}
?>