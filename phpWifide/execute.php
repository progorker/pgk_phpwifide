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

session_start();
set_time_limit(0);

global $g_results, $g_config, $g_work_dir, $g_buffer_dir, $g_open_text, $g_source_text, $g_list_text, $g_remove_text, $g_workdir_text, $g_use_open, $g_open_cfg, $g_download_text, $g_load_text;

require_once __DIR__ . '/libs/phpsandbox/auto.php';
require_once __DIR__ . '/testor.php';
require_once __DIR__ . '/config.php';

$g_open_cfg = false;

function g_phpwifide_help() {
  $text = <<<EOT
command\tdescription
//help\tDisplay this help.
//source\tInclude PHP script file which does not include '<?php ' & '?>'. Argument is .shp file path.
//pattern\tGet code pattern from myTestor.
//workdir\tSet work dir. Argument is selected directory.
//upload \tUpload zip file.
//download\tZip folder & download zip file. Argument is relative path.
//load  \tLoad script file into script editor. Argument is relative path.
//list  \tList buffer directory. Argument is relative path.
//remove\tRemove file. Argument is relative path.
//save  \tSave previous code to file. Does not execute script. Argument is relative path.
//cat   \tDisplay script file. Does not execute script. Argument is relative path.
//username\tSet Testor's username. It is executed in client side.
//password\tSet Testor's password. It is executed in client side.
//suite\tSet test suite code. It is executed in client side.
EOT;
  $rs = '';
  g_phpwifide_parse_results( $text, $rs );
  return $rs;
}

function g_phpwifide_exec_sql( $sql, $decor = false ) {
  global $g_config, $g_use_open, $g_open_cfg;

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
  if ( $decor ) {
    $results = '';
    g_phpwifide_parse_results( $text, $results );
    return $results;
  } else {
    return $text;
  }
}

function g_phpwifide_escape( $sql ) {
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

function g_phpwifide_unescape( $sql ) {
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

function g_phpwifide_finds( $keys, $src, $start = 0 ) {
  $ret = [];
  $srt = [];
  $szl = [];
  $kyl = [];
  $pidx = -1;
  foreach ( $keys as $k ) {
    $idx = strpos( $src, $k, $start );
    if ( $idx !== false ) {
      $ret[] = $idx;
      $srt[] = $idx;
      $szl[] = strlen( $k );
      $kyl[] = $k;
    }
  }
  if ( count( $srt ) > 0 ) {
    sort( $srt );
    $v = $srt[0];
    for ( $i = 0; $i < count( $ret ); $i++ ) {
      if ( $ret[ $i ] == $v ) {
        $pidx = $i;
        break;
      }
    }
  }
  return array( 'idxl' => $ret, 'szl' => $szl, 'kyl' => $kyl, 'pidx' => $pidx );
}

function g_phpwifide_pattern( $p_module, $p_kind, $p_code, $p_variant ) {
  $module = g_phpwifide_escape( $p_module );
  $kind = g_phpwifide_escape( $p_kind );
  $code = g_phpwifide_escape( $p_code );
  $variant = g_phpwifide_escape( $p_variant );
  $sql = "set @v_pattern = '_'; set @v_module = api_testor_unescape('$module'); set @v_kind = api_testor_unescape('$kind'); set @v_code = api_testor_unescape('$code'); set @v_variant = api_testor_unescape('$variant'); call api_testor_pattern( @v_module, @v_kind, @v_code, @v_variant, @v_pattern ); select @v_pattern as pattern\\G";
  $text = g_phpwifide_exec_sql( $sql, false );
  $idx = strpos( $text, 'pattern:' );
  if ( $idx !== false ) {
    $text = substr( $text, $idx + 8 );
  }
  return $text;
}

function g_phpwifide_load_help( $sql ) {
  global $g_buffer_dir;
  
  $has_help = false;
  $nsql = '';
  $start = 0;
  $finds = [' //help ', "\n".'//help ', "\n".'//help'."\n" ];
  $finds_2 = [';', "\n", "\r"];
  $rets = g_phpwifide_finds( $finds, $sql, $start );
  while ( count( $rets['idxl'] ) > 0 ) {
    $pidx = $rets['pidx'];
    $key = $rets['kyl'][$pidx];
    $idx = $rets['idxl'][$pidx];
    $sz = $rets['szl'][$pidx];
    $nsql .= substr( $sql, $start, $idx - $start );
    $rets_2 = g_phpwifide_finds( $finds_2, $sql, $idx + $sz );
    $pidx_2 = $rets_2['pidx'];
    if ( count( $rets_2['idxl'] ) > 0 ) {
      $filename = substr( $sql, $idx + $sz, $rets_2['idxl'][$pidx_2] - $idx - $sz);
      $start = $rets_2['idxl'][$pidx_2] + $rets_2['szl'][$pidx_2];
      if ( $rets_2['kyl'][$pidx_2] == "\n" ) {
        $start -= 1;
      }
    } else {
      $filename = substr( $sql, $idx + $sz );
      $start = strlen( $sql );
    }
    $has_help = true;
    $rets = g_phpwifide_finds( $finds, $sql, $start );
  }
  $nsql .= substr( $sql, $start );
  $rets = g_phpwifide_finds( $finds, $nsql, 0 );
  if ( count( $rets['idxl'] ) > 0 ) {
    $nsql = g_phpwifide_load_help( $nsql );
  }
  if ( $has_help ) {
    $nsql = "\n// loadhelp //\n" . $nsql;
  }
  return $nsql;
}

function g_phpwifide_load_cat( $sql ) {
  global $g_buffer_dir;
  
  $has_cat = false;
  $nsql = '';
  $start = 0;
  $finds = [' //cat ', "\n".'//cat ' ];
  $finds_2 = [';', "\n", "\r"];
  $rets = g_phpwifide_finds( $finds, $sql, $start );
  while ( count( $rets['idxl'] ) > 0 ) {
    $pidx = $rets['pidx'];
    $key = $rets['kyl'][$pidx];
    $idx = $rets['idxl'][$pidx];
    $sz = $rets['szl'][$pidx];
    $nsql .= substr( $sql, $start, $idx - $start );
    $rets_2 = g_phpwifide_finds( $finds_2, $sql, $idx + $sz );
    $pidx_2 = $rets_2['pidx'];
    if ( count( $rets_2['idxl'] ) > 0 ) {
      $filename = substr( $sql, $idx + $sz, $rets_2['idxl'][$pidx_2] - $idx - $sz);
      $start = $rets_2['idxl'][$pidx_2] + $rets_2['szl'][$pidx_2];
      if ( $rets_2['kyl'][$pidx_2] == "\n" ) {
        $start -= 1;
      }
    } else {
      $filename = substr( $sql, $idx + $sz );
      $start = strlen( $sql );
    }
    $filename = trim( $filename );
    $filename = str_replace( '..', '', $filename );
    $filename = str_replace( '..', '', $filename );
    $filename = trim( $filename );
    $cat = trim( @file_get_contents( $g_buffer_dir . '/' . $filename ) );
    if ( $cat !== '' ) {
      $has_cat = true;
      $nsql .= "\n" . $cat . "\n";
    }
    $rets = g_phpwifide_finds( $finds, $sql, $start );
  }
  $nsql .= substr( $sql, $start );
  $rets = g_phpwifide_finds( $finds, $nsql, 0 );
  if ( count( $rets['idxl'] ) > 0 ) {
    $nsql = g_phpwifide_load_cat( $nsql );
  }
  if ( $has_cat ) {
    $nsql = "\n// loadcat //\n" . $nsql;
  }
  return $nsql;
}

function g_phpwifide_load_load( $sql ) {
  global $g_buffer_dir, $g_load_text;
  
  $nsql = '';
  $start = 0;
  $finds = [' //load ', "\n".'//load ' ];
  $finds_2 = [';', "\n", "\r"];
  $rets = g_phpwifide_finds( $finds, $sql, $start );
  while ( count( $rets['idxl'] ) > 0 ) {
    $pidx = $rets['pidx'];
    $key = $rets['kyl'][$pidx];
    $idx = $rets['idxl'][$pidx];
    $sz = $rets['szl'][$pidx];
    $nsql .= substr( $sql, $start, $idx - $start );
    $rets_2 = g_phpwifide_finds( $finds_2, $sql, $idx + $sz );
    $pidx_2 = $rets_2['pidx'];
    if ( count( $rets_2['idxl'] ) > 0 ) {
      $filename = substr( $sql, $idx + $sz, $rets_2['idxl'][$pidx_2] - $idx - $sz);
      $start = $rets_2['idxl'][$pidx_2] + $rets_2['szl'][$pidx_2];
      if ( $rets_2['kyl'][$pidx_2] == "\n" ) {
        $start -= 1;
      }
    } else {
      $filename = substr( $sql, $idx + $sz );
      $start = strlen( $sql );
    }
    $filename = trim( $filename );
    $filename = str_replace( '..', '', $filename );
    $filename = str_replace( '..', '', $filename );
    $filename = trim( $filename );
    $cat = trim( @file_get_contents( $g_buffer_dir . '/' . $filename ) );
    if ( $cat !== '' ) {
      $g_load_text = "\n// loading //\n" . $cat;
      return '';
    }
    $rets = g_phpwifide_finds( $finds, $sql, $start );
  }
  $nsql .= substr( $sql, $start );
  $rets = g_phpwifide_finds( $finds, $nsql, 0 );
  if ( count( $rets['idxl'] ) > 0 ) {
    $nsql = g_phpwifide_load_load( $nsql );
  }
  return $nsql;
}

function g_phpwifide_load_list( $sql ) {
  global $g_buffer_dir, $g_list_text;
  
  $has_list = false;
  $nsql = '';
  $start = 0;
  $finds = [' //list ', "\n".'//list ' ];
  $finds_2 = [';', "\n", "\r"];
  $rets = g_phpwifide_finds( $finds, $sql, $start );
  while ( count( $rets['idxl'] ) > 0 ) {
    $pidx = $rets['pidx'];
    $key = $rets['kyl'][$pidx];
    $idx = $rets['idxl'][$pidx];
    $sz = $rets['szl'][$pidx];
    $nsql .= substr( $sql, $start, $idx - $start );
    $rets_2 = g_phpwifide_finds( $finds_2, $sql, $idx + $sz );
    $pidx_2 = $rets_2['pidx'];
    if ( count( $rets_2['idxl'] ) > 0 ) {
      $filename = substr( $sql, $idx + $sz, $rets_2['idxl'][$pidx_2] - $idx - $sz);
      $start = $rets_2['idxl'][$pidx_2] + $rets_2['szl'][$pidx_2];
      if ( $rets_2['kyl'][$pidx_2] == "\n" ) {
        $start -= 1;
      }
    } else {
      $filename = substr( $sql, $idx + $sz );
      $start = strlen( $sql );
    }
    $filename = trim( $filename );
    $filename = str_replace( '..', '', $filename );
    $filename = str_replace( '..', '', $filename );
    $filename = trim( $filename );
    $cmd = "ls -1 " . $g_buffer_dir . '/' . $filename;
    $dir = dirname( $g_buffer_dir . '/' . $filename );
    @mkdir( $dir, 0777, true );
    $cat = trim( @shell_exec( $cmd ) . '' );
    if ( $cat === '' ) {
      $cat = '__BLANK__';
    }
    if ( $cat !== '' ) {
      $has_list = true;
      $cat = "[DIR] $filename" . "\n" . $cat;
      $rs = '';
      g_phpwifide_parse_results( $cat, $rs );
      $g_list_text .= "\n" . $rs . "\n";
    }
    $rets = g_phpwifide_finds( $finds, $sql, $start );
  }
  $nsql .= substr( $sql, $start );
  $rets = g_phpwifide_finds( $finds, $nsql, 0 );
  if ( count( $rets['idxl'] ) > 0 ) {
    $nsql = g_phpwifide_load_list( $nsql );
  }
  if ( $has_list ) {
    $nsql = "\n// loadlist //\n" . $nsql;
  }
  return $nsql;
}

function g_phpwifide_load_remove( $sql ) {
  global $g_buffer_dir, $g_remove_text;
  
  $nsql = '';
  $start = 0;
  $finds = [' //remove ', "\n".'//remove ' ];
  $finds_2 = [';', "\n", "\r"];
  $rets = g_phpwifide_finds( $finds, $sql, $start );
  while ( count( $rets['idxl'] ) > 0 ) {
    $pidx = $rets['pidx'];
    $key = $rets['kyl'][$pidx];
    $idx = $rets['idxl'][$pidx];
    $sz = $rets['szl'][$pidx];
    $nsql .= substr( $sql, $start, $idx - $start );
    $rets_2 = g_phpwifide_finds( $finds_2, $sql, $idx + $sz );
    $pidx_2 = $rets_2['pidx'];
    if ( count( $rets_2['idxl'] ) > 0 ) {
      $filename = substr( $sql, $idx + $sz, $rets_2['idxl'][$pidx_2] - $idx - $sz);
      $start = $rets_2['idxl'][$pidx_2] + $rets_2['szl'][$pidx_2];
      if ( $rets_2['kyl'][$pidx_2] == "\n" ) {
        $start -= 1;
      }
    } else {
      $filename = substr( $sql, $idx + $sz );
      $start = strlen( $sql );
    }
    $filename = trim( $filename );
    $filename = str_replace( '..', '', $filename );
    $filename = str_replace( '..', '', $filename );
    $filename = trim( $filename );
    $kind = '';
    if ( is_dir( $g_buffer_dir . '/' . $filename ) ) {
      $dir = $g_buffer_dir . '/' . $filename;
      $cmd = "rm -rf $dir";
      $kind = '[DIR]';
      @shell_exec( $cmd );
    } else if ( is_file( $g_buffer_dir . '/' . $filename ) ) {
      @unlink( $g_buffer_dir . '/' . $filename );
      $kind = '[FILE]';
    }
    $cat = "$kind Remove" . "\n" . $filename;
    $rs = '';
    g_phpwifide_parse_results( $cat, $rs );
    $g_remove_text .= "\n" . $rs . "\n";
    $nsql .= "\n// loadremove //\n";
    $rets = g_phpwifide_finds( $finds, $sql, $start );
  }
  $nsql .= substr( $sql, $start );
  $rets = g_phpwifide_finds( $finds, $nsql, 0 );
  if ( count( $rets['idxl'] ) > 0 ) {
    $nsql = g_phpwifide_load_remove( $nsql );
  }
  return $nsql;
}

function g_phpwifide_load_download( $sql ) {
  global $g_config, $g_buffer_dir, $g_download_text;
  
  $zip_cmd = $g_config['mytestor.zip_cmd'];
  
  $nsql = '';
  $start = 0;
  $finds = [' //download ', "\n".'//download ' ];
  $finds_2 = [';', "\n", "\r"];
  $rets = g_phpwifide_finds( $finds, $sql, $start );
  while ( count( $rets['idxl'] ) > 0 ) {
    $pidx = $rets['pidx'];
    $key = $rets['kyl'][$pidx];
    $idx = $rets['idxl'][$pidx];
    $sz = $rets['szl'][$pidx];
    $nsql .= substr( $sql, $start, $idx - $start );
    $rets_2 = g_phpwifide_finds( $finds_2, $sql, $idx + $sz );
    $pidx_2 = $rets_2['pidx'];
    if ( count( $rets_2['idxl'] ) > 0 ) {
      $filename = substr( $sql, $idx + $sz, $rets_2['idxl'][$pidx_2] - $idx - $sz);
      $start = $rets_2['idxl'][$pidx_2] + $rets_2['szl'][$pidx_2];
      if ( $rets_2['kyl'][$pidx_2] == "\n" ) {
        $start -= 1;
      }
    } else {
      $filename = substr( $sql, $idx + $sz );
      $start = strlen( $sql );
    }
    $filename = trim( $filename );
    $filename = str_replace( '..', '', $filename );
    $filename = str_replace( '..', '', $filename );
    $filename = trim( $filename );
    $src_dir = $g_buffer_dir . '/' . $filename;
    if ( is_dir( $src_dir ) ) {
      $tmp_dir = __DIR__ . '/tmp/' . uniqid();
      @mkdir( $tmp_dir, 0777, true );
      $code = substr( strrev( uniqid() ), 0, 4 );
      $zip_dir = $tmp_dir . '/' . $code;
      @mkdir( $zip_dir, 0777, true );
      $cmd = "cp -rf $src_dir/* $zip_dir/";
      @shell_exec( $cmd );
      $zip_file = $code . '.zip';
      $cmd = "cd $tmp_dir && $zip_cmd -r $zip_file $code";      
      @shell_exec( $cmd );
      $zip_file = $tmp_dir . '/' . $zip_file;
      $dl_dir = __DIR__ . '/downloads';
      @mkdir( $dl_dir, 0777, true );
      $cmd = "cp -f $zip_file $dl_dir/";
      @shell_exec( $cmd );
      $zip_file = $code . '.zip';
      $uri = $_SERVER['REQUEST_URI'];
      $idx = strrpos( $uri, '/' );
      if ( $idx !== false ) {
        $uri = substr( $uri, 0, $idx );
      }
      $protocol = ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ) ? 'https' : 'http';
      $dl_url = $protocol . '://' . $_SERVER['HTTP_HOST'] . $uri . '/downloads/' . $zip_file;
      $cat = "[ Download ] " . $filename . "\n" . $dl_url;
      $rs = '';
      g_phpwifide_parse_results( $cat, $rs );
      $g_download_text .= "\n" . $rs . "\n";
      $cmd = "rm -rf $tmp_dir";
      @shell_exec( $cmd );
    }
    $nsql .= "\n// loaddownload //\n";
    $rets = g_phpwifide_finds( $finds, $sql, $start );
  }
  $nsql .= substr( $sql, $start );
  $rets = g_phpwifide_finds( $finds, $nsql, 0 );
  if ( count( $rets['idxl'] ) > 0 ) {
    $nsql = g_phpwifide_load_download( $nsql );
  }
  return $nsql;
}

function g_phpwifide_load_workdir( $sql ) {
  global $g_buffer_dir, $g_workdir_text, $g_work_dir;
  
  $nsql = '';
  $start = 0;
  $finds = [' //workdir ', "\n".'//workdir ' ];
  $finds_2 = [';', "\n", "\r"];
  $rets = g_phpwifide_finds( $finds, $sql, $start );
  while ( count( $rets['idxl'] ) > 0 ) {
    $pidx = $rets['pidx'];
    $key = $rets['kyl'][$pidx];
    $idx = $rets['idxl'][$pidx];
    $sz = $rets['szl'][$pidx];
    $nsql .= substr( $sql, $start, $idx - $start );
    $rets_2 = g_phpwifide_finds( $finds_2, $sql, $idx + $sz );
    $pidx_2 = $rets_2['pidx'];
    if ( count( $rets_2['idxl'] ) > 0 ) {
      $filename = substr( $sql, $idx + $sz, $rets_2['idxl'][$pidx_2] - $idx - $sz);
      $start = $rets_2['idxl'][$pidx_2] + $rets_2['szl'][$pidx_2];
      if ( $rets_2['kyl'][$pidx_2] == "\n" ) {
        $start -= 1;
      }
    } else {
      $filename = substr( $sql, $idx + $sz );
      $start = strlen( $sql );
    }
    $filename = trim( $filename );
    $filename = str_replace( '..', '', $filename );
    $filename = str_replace( '..', '', $filename );
    $filename = trim( $filename );
    if ( is_dir( $g_buffer_dir . '/' . $filename ) ) {
      $g_work_dir = $filename;
      $dir = $g_buffer_dir . '/' . $filename;
      $g_buffer_dir = $dir;
      $cat = "[DIR] Work" . "\n" . $filename;
      $rs = '';
      g_phpwifide_parse_results( $cat, $rs );
      $g_workdir_text .= "\n" . $rs . "\n";
    }
    $nsql .= "\n// loadworkdir //\n";
    $rets = g_phpwifide_finds( $finds, $sql, $start );
  }
  $nsql .= substr( $sql, $start );
  $rets = g_phpwifide_finds( $finds, $nsql, 0 );
  if ( count( $rets['idxl'] ) > 0 ) {
    $nsql = g_phpwifide_load_workdir( $nsql );
  }
  return $nsql;
}

function g_phpwifide_load_save( $sql ) {
  global $g_buffer_dir;
  
  $has_save = false;
  $nsql = '';
  $start = 0;
  $finds = [' //save ', "\n".'//save ' ];
  $finds_2 = [';', "\n", "\r"];
  $rets = g_phpwifide_finds( $finds, $sql, $start );
  while ( count( $rets['idxl'] ) > 0 ) {
    $pidx = $rets['pidx'];
    $key = $rets['kyl'][$pidx];
    $idx = $rets['idxl'][$pidx];
    $sz = $rets['szl'][$pidx];
    $nsql .= substr( $sql, $start, $idx - $start );
    $rets_2 = g_phpwifide_finds( $finds_2, $sql, $idx + $sz );
    $pidx_2 = $rets_2['pidx'];
    if ( count( $rets_2['idxl'] ) > 0 ) {
      $filename = substr( $sql, $idx + $sz, $rets_2['idxl'][$pidx_2] - $idx - $sz);
      $start = $rets_2['idxl'][$pidx_2] + $rets_2['szl'][$pidx_2];
      if ( $rets_2['kyl'][$pidx_2] == "\n" ) {
        $start -= 1;
      }
    } else {
      $filename = substr( $sql, $idx + $sz );
      $start = strlen( $sql );
    }
    $filename = trim( $filename );
    $filename = str_replace( '..', '', $filename );
    $filename = str_replace( '..', '', $filename );
    $filename = trim( $filename );
    $fileext = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
    if ( $fileext === 'shp' ) {    
      $dir = @dirname( $g_buffer_dir . '/' . $filename ) . '';
      @mkdir( $dir, 0777, true );
      @file_put_contents( $g_buffer_dir . '/' . $filename, "\n" . trim( $nsql ) . "\n" );
      $has_save = true;
    }
    $rets = g_phpwifide_finds( $finds, $sql, $start );
  }
  $nsql .= substr( $sql, $start );
  $rets = g_phpwifide_finds( $finds, $nsql, 0 );
  if ( count( $rets['idxl'] ) > 0 ) {
    $nsql = g_phpwifide_load_save( $nsql );
  }
  if ( $has_save ) {
    $nsql = "\n// loadsave //\n" . $nsql;
  }
  return $nsql;
}

function g_phpwifide_load_pattern( $sql ) {
  global $g_buffer_dir;
  
  $nsql = '';
  $start = 0;
  $finds = [ ' //pattern ', "\n".'//pattern ' ];
  $finds_2 = [';', "\n", "\r"];
  $rets = g_phpwifide_finds( $finds, $sql, $start );
  while ( count( $rets['idxl'] ) > 0 ) {
    $pidx = $rets['pidx'];
    $key = $rets['kyl'][$pidx];
    $idx = $rets['idxl'][$pidx];
    $sz = $rets['szl'][$pidx];
    $nsql .= substr( $sql, $start, $idx - $start );
    $rets_2 = g_phpwifide_finds( $finds_2, $sql, $idx + $sz );
    $pidx_2 = $rets_2['pidx'];
    if ( count( $rets_2['idxl'] ) > 0 ) {
      $filename = substr( $sql, $idx + $sz, $rets_2['idxl'][$pidx_2] - $idx - $sz);
      $start = $rets_2['idxl'][$pidx_2] + $rets_2['szl'][$pidx_2];
      if ( $rets_2['kyl'][$pidx_2] == "\n" ) {
        $start -= 1;
      }
    } else {
      $filename = substr( $sql, $idx + $sz );
      $start = strlen( $sql );
    }
    $filename = trim( $filename );
    $fields = explode( " ", $filename );
    if ( count( $fields ) >= 5 ) {
      $module = $fields[0];
      $kind = $fields[1];
      $code = $fields[2];
      $variant = $fields[3];
      $filename = $fields[4];
      $filename = trim( $filename );
      $filename = str_replace( '..', '', $filename );
      $filename = str_replace( '..', '', $filename );
      $filename = trim( $filename );
      $fileext = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
      if ( $fileext === 'shp' ) {    
        $dir = @dirname( $g_buffer_dir . '/' . $filename ) . '';
        @mkdir( $dir, 0777, true );
        $pattern = "\n" . trim( g_phpwifide_pattern( $module, $kind, $code, $variant ) ) . "\n";
        @file_put_contents( $g_buffer_dir . '/' . $filename, $pattern );
      } 
    }
    $rets = g_phpwifide_finds( $finds, $sql, $start );
  }
  $nsql .= substr( $sql, $start );
  $rets = g_phpwifide_finds( $finds, $nsql, 0 );
  if ( count( $rets['idxl'] ) > 0 ) {
    $nsql = g_phpwifide_load_pattern( $nsql );
  }
  return $nsql;
}

function g_phpwifide_load_source( $sql ) {
  global $g_buffer_dir, $g_source_text;
  
  $nsql = '';
  $start = 0;
  $finds = [' //source ', "\n". '//source ' ];
  $finds_2 = [';', "\n", "\r"];
  $rets = g_phpwifide_finds( $finds, $sql, $start );
  while ( count( $rets['idxl'] ) > 0 ) {
    $pidx = $rets['pidx'];
    $key = $rets['kyl'][$pidx];
    $idx = $rets['idxl'][$pidx];
    $sz = $rets['szl'][$pidx];
    $nsql .= substr( $sql, $start, $idx - $start );
    $rets_2 = g_phpwifide_finds( $finds_2, $sql, $idx + $sz );
    $pidx_2 = $rets_2['pidx'];
    if ( count( $rets_2['idxl'] ) > 0 ) {
      $filename = substr( $sql, $idx + $sz, $rets_2['idxl'][$pidx_2] - $idx - $sz);
      $start = $rets_2['idxl'][$pidx_2] + $rets_2['szl'][$pidx_2];
      if ( $rets_2['kyl'][$pidx_2] == "\n" ) {
        $start -= 1;
      }
    } else {
      $filename = substr( $sql, $idx + $sz );
      $start = strlen( $sql );
    }
    $filename = trim( $filename );
    $filename = str_replace( '..', '', $filename );
    $filename = str_replace( '..', '', $filename );
    $filename = trim( $filename );

    $script = "\n" . @file_get_contents( $g_buffer_dir . '/' . $filename ) . "\n";
    $script = g_phpwifide_refine( $script );
    $nsql .= "\n// loadsrc //\n";
    $nsql .= $script;

    $rets = g_phpwifide_finds( $finds, $sql, $start );
  }
  $nsql .= substr( $sql, $start );
  return $nsql;
}

function g_phpwifide_refine( $sql ) {
  $sql = g_phpwifide_load_save( "\n" . $sql . "\n" );
  if ( strpos( "\n" . $sql . "\n", "\n// loadsave //\n" ) === false ) {
    $sql = g_phpwifide_load_workdir( "\n" . $sql . "\n" );
    $sql = g_phpwifide_load_source( "\n" . $sql . "\n" );
    $sql = g_phpwifide_load_pattern( "\n" . $sql . "\n" );

    $sql = g_phpwifide_load_cat( "\n" . $sql . "\n" );
    $sql = g_phpwifide_load_list( "\n" . $sql . "\n" );
    $sql = g_phpwifide_load_remove( "\n" . $sql . "\n" );
    $sql = g_phpwifide_load_download( "\n" . $sql . "\n" );
    $sql = g_phpwifide_load_load( "\n" . $sql . "\n" );
    $sql = g_phpwifide_load_help( "\n" . $sql . "\n" );
  }

  return "\n" . trim( $sql ) . "\n";
}

function g_phpwifide_fill_table( $cols, $rows, $fsz, &$p_results ) {
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

function g_phpwifide_parse_results( $text, &$p_results ) {
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
        g_phpwifide_fill_table( $cols, $rows, $fsz, $p_results );
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
    g_phpwifide_fill_table( $cols, $rows, $fsz, $p_results );
  }
}

function g_phpwifide__valid_funcs( $func ) {
  $supported = [
"g_phpwifide_init",
"g_phpwifide_vars",
"phptestor\\api_testor_escape",
"phptestor\\api_testor_has_right",
"phptestor\\api_testor_is_online",
"phptestor\\api_testor_unescape",
"phptestor\\api_testor_welcome",
"phptestor\\api_testor_case",
"phptestor\\api_testor_change_password",
"phptestor\\api_testor_clean",
"phptestor\\api_testor_contains",
"phptestor\\api_testor_create_user",
"phptestor\\api_testor_current_user",
"phptestor\\api_testor_e_functions",
"phptestor\\api_testor_e_procedures",
"phptestor\\api_testor_equals",
"phptestor\\api_testor_error",
"phptestor\\api_testor_e_tables",
"phptestor\\api_testor_failed",
"phptestor\\api_testor_finish",
"phptestor\\api_testor_greater_than",
"phptestor\\api_testor_less_than",
"phptestor\\api_testor_login",
"phptestor\\api_testor_logout",
"phptestor\\api_testor_man",
"phptestor\\api_testor_not_contains",
"phptestor\\api_testor_not_equals",
"phptestor\\api_testor_not_greater_than",
"phptestor\\api_testor_not_less_than",
"phptestor\\api_testor_not_same",
"phptestor\\api_testor_not_true",
"phptestor\\api_testor_option",
"phptestor\\api_testor_pattern",
"phptestor\\api_testor_result",
"phptestor\\api_testor_same",
"phptestor\\api_testor_shutdown",
"phptestor\\api_testor_source_list",
"phptestor\\api_testor_source",
"phptestor\\api_testor_startup",
"phptestor\\api_testor_success",
"phptestor\\api_testor_suite_case",
"phptestor\\api_testor_suite",
"phptestor\\api_testor_test",
"phptestor\\api_testor_true",
"phptestor\\api_testor_user_rights",
"phptestor\\api_testor_version"
];

  if ( in_array( $func, $supported ) ) return true;
  return false;
}

function g_phpwifide__invalid_funcs( $func ) {
  $supported = [
'g_phpwifide__php_exec', 
'g_phpwifide_param', 
'g_phpwifide__invalid_funcs', 
'g_phpwifide__valid_funcs',
'g_phpwifide_help',
'g_phpwifide_exec_sql',
'g_phpwifide_escape',
'g_phpwifide_unescape',
'g_phpwifide_finds',
'g_phpwifide_pattern',
'g_phpwifide_load_help',
'g_phpwifide_load_cat',
'g_phpwifide_load_load',
'g_phpwifide_load_list',
'g_phpwifide_load_remove',
'g_phpwifide_load_download',
'g_phpwifide_load_workdir',
'g_phpwifide_load_save',
'g_phpwifide_load_pattern',
'g_phpwifide_load_source',
'g_phpwifide_refine',
'g_phpwifide_fill_table',
'g_phpwifide_parse_results'
];
  if ( in_array( $func, $supported ) ) return true;
  return false;
}

function g_phpwifide__php_exec( $script ) {
  global $g_results;
  register_shutdown_function( function() {
    global $g_results;
    $error = error_get_last();
    if ( $error !== null && in_array( $error['type'], [E_ERROR, E_PARSE, E_COMPILE_ERROR, E_CORE_ERROR] ) ) {
      $g_results = "\n=====] FAILED [=====\n\n" . print_r( $error, true ) . "\n";
      echo $g_results;
      exit();
    }
  } );

  ob_start();
  $sandbox = new PHPSandbox\PHPSandbox;
  $sandbox->setFuncValidator( function( $func, PHPSandbox\PHPSandbox $sandbox ) {
    if ( g_phpwifide__invalid_funcs( $func ) ) return false;
    if ( g_phpwifide__valid_funcs( $func ) ) return true;
    return false;
  } );
  $g_results = $sandbox->execute( $script );
  $g_results .= ob_get_clean();
}

function g_phpwifide_init( $suite_code, $username, $password ) {
  global $g_token, $g_suite_id, $g_testor_dir, $g_suite_code, $g_testor_username, $g_testor_password, $g_last_version, $g_src_dir, $g_clear_version;
  $g_testor_dir = __DIR__;
  $g_suite_code = $suite_code;
  $g_testor_username = $username;
  $g_testor_password = $password;
  $g_last_version = 0;
  $g_src_dir = '';
  $g_clear_version = false;
  $g_token = '_';
  $g_suite_id = -1;
}

function g_phpwifide_vars() {
  global $g_token, $g_suite_id, $g_testor_dir, $g_suite_code, $g_testor_username, $g_testor_password, $g_last_version, $g_src_dir, $g_clear_version;
  return array( 'token' => $g_token, 'suite_code' => $g_suite_code, 'suite_id' => $g_suite_id );
}

function g_phpwifide_param( $key ) {
  if ( isset( $_POST[ $key ] ) ) return $_POST[ $key ];
  if ( isset( $_GET[ $key ] ) ) return $_GET[ $key ];
  return '';
}

header('Content-Type: text/plain');

$g_results = '';

$token = g_phpwifide_param('token');
if ( !isset( $_SESSION['phpWifide_'.$token] ) || $_SESSION['phpWifide_'.$token] === false ) {
  echo $g_results;
  exit();
}


$g_buffer_dir = __DIR__ . '/buffers';
@mkdir( $g_buffer_dir, 0777, true );

$g_work_dir = './';
$g_load_text = '';
$g_download_text = '';
$g_source_text = '';
$g_list_text = '';
$g_remove_text = '';
$g_workdir_text = '';

$sql = g_phpwifide_param('s');
$sql = g_phpwifide_refine( $sql );
if ( strpos( $g_load_text, "\n// loading //\n" ) !== false ) {
  echo $g_load_text;
  exit;
}
if ( strpos( $sql, "\n// loadsrc //\n" ) !== false ) {
  $cat = "[SRC]" . "\n" . $g_source_text;
  $rs = '';
  g_phpwifide_parse_results( $cat, $rs );
  $g_results = $g_results . "\n" . trim( $rs ) . "\n";
}
if ( strpos( $sql, "\n// loadcat //\n" ) !== false ||  strpos( $sql, "\n// loadsave //\n" ) !== false ) {
  if ( strpos( $sql, "\n// rawsrc //\n" ) !== false ) {
    $cat = "[PHP] Start                                        ";
    $rs = '';
    g_phpwifide_parse_results( $cat, $rs );
    $g_results .= "\n" . trim( $rs ) . "\n";
    $g_results .= $sql;
    $cat = "[PHP] End                                          ";
    $rs = '';
    g_phpwifide_parse_results( $cat, $rs );
    $g_results .= "\n" . trim( $rs ) . "\n";
  } else {
    $cat = "[PHP]" . "\n" . $sql;
    $rs = '';
    g_phpwifide_parse_results( $cat, $rs );
    $g_results .= "\n" . trim( $rs ) . "\n";
  }
} else {
  g_phpwifide__php_exec( $sql );
}
if ( $g_results === null ) {
  $g_results = '';
}
if ( strpos( "\n" . $sql . "\n", "\\phptestor\\api_testor_startup(" ) !== false &&  strpos( "\n" . $sql . "\n", "\\phptestor\\api_testor_shutdown(" ) !== false ) {
  if ( strpos( $g_results, "| GREEN" ) !== false || strpos( $g_results, "| RED" ) !== false ) {
    if ( strpos( "\n" . $sql . "\n", '$g_suite_code = ' ) !== false ) {
      $idx = strpos( "\n" . $sql . "\n", '$g_suite_code = ' );
      $tmp = substr( $sql, $idx + 16 );
      $idx = strpos( $tmp, ';' );
      if ( $idx !== false ) {
        $tmp = substr( $tmp, 0, $idx );
      }
      $idx = strpos( $tmp, "\n" );
      if ( $idx !== false ) {
        $tmp = substr( $tmp, 0, $idx );
      }
      $tmp = trim( $tmp );
      if ( $tmp[0] === "'" ) {
        $tmp = substr( $tmp, 1 );
      }
      if ( $tmp[strlen($tmp) - 1] === "'" ) {
        $tmp = substr( $tmp, 0, strlen( $tmp ) - 1 );
      }
      $tmp = trim( $tmp );
      if ( $tmp[0] === '"' ) {
        $tmp = substr( $tmp, 1 );
      }
      if ( $tmp[strlen($tmp) - 1] === '"' ) {
        $tmp = substr( $tmp, 0, strlen( $tmp ) - 1 );
      }
      $suite_code = $tmp;
      $suite_code = str_replace( "\n", '', $suite_code );
      $suite_code = str_replace( "\r", '', $suite_code );
      $suite_code = str_replace( '"', '', $suite_code );
      $suite_code = str_replace( '\\', '', $suite_code );
      if ( $suite_code !== '_' && $suite_code !== '' ) {
        require_once __DIR__ . '/svc.php';
        g_svc( $suite_code, $g_work_dir );
      }
    }
  }
}

if ( strpos( $sql, "\n// loadhelp //\n" ) !== false ) {
  $g_results = "\n" . trim( g_phpwifide_help() ) . "\n" . $g_results;
}
if ( strpos( $sql, "\n// loadlist //\n" ) !== false ) {
  $g_results = $g_results . "\n" . trim( $g_list_text ) . "\n";
}
if ( strpos( $sql, "\n// loadremove //\n" ) !== false ) {
  $g_results = $g_results . "\n" . trim( $g_remove_text ) . "\n";
}
if ( strpos( $sql, "\n// loaddownload //\n" ) !== false ) {
  $g_results = $g_results . "\n" . trim( $g_download_text ) . "\n";
}
if ( strpos( $sql, "\n// loadworkdir //\n" ) !== false ) {
  $g_results = $g_results . "\n" . trim( $g_workdir_text ) . "\n";
}
if ( strpos( $sql, "\n// shp //\n" ) !== false ) {
  echo "\n", "=====] SHP [=====", "\n", $sql, "\n", "====================", "\n";
}
echo $g_results;
?>