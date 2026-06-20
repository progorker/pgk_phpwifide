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
  exit;
}

$filename = g_param('s');
$filename = trim( $filename );
$filename = str_replace( '..', '', $filename );
$filename = str_replace( '..', '', $filename );
$filename = trim( $filename );
$phptestor_dir = $g_config['phptestor.dir'];
$scrp_file = $g_buffers_dir . '/' . $filename;
if ( is_file( $scrp_file ) ) {
  $func = g_param('f');
  $token = g_param('t');
  $suite_id = g_param('i');
  $prefix = <<<EOF
<?php
require_once '$phptestor_dir/testor.php';
require_once '$scrp_file';

global \$g_token, \$g_suite_id;

\$g_token = '$token';
\$g_suite_id = $suite_id;

$func( '$token', $suite_id );
?>
EOF;
  $tmp_dir = $g_buffers_dir . '/' . uniqid();
  @mkdir( $tmp_dir, 0777, true );
  $exec_file = $tmp_dir . '/' . uniqid() . '.php';
  @file_put_contents( $exec_file, $prefix );
  $php_cmd = $g_config['mytestor.php_cmd'];
  $cmd = "$php_cmd $exec_file";
  $rs = @shell_exec( $cmd );
  echo $rs;
}
?>