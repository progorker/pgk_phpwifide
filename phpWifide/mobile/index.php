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
global $g_config, $g_unlocked_str, $g_token, $g_testor_username, $g_testor_password;
require_once __DIR__ . '/../config.php';
$g_token = uniqid();

$g_unlocked_str = 'true';
$_SESSION['phpWifide_'.$g_token] = true;
if ( $g_config['mytestor.locking'] ) {
  $g_unlocked_str = 'false';
  $_SESSION['phpWifide_'.$g_token] = false;
}

$g_testor_username = $g_config['testor.username'];
$g_testor_password = $g_config['testor.password'];

require_once __DIR__ . '/../mobile_detect.php';
    
if ( ! check_http_headers_for_mobile() ) {
  header('Location: ./../desktop/');
}
?>
<html>
<head>
  <title>[ phpWifide ] IDE @ Wi-Fi Network for PHP</title>
  <script src="./../jquery-4.0.0.min.js"></script>
  <link rel="stylesheet" href="./../libs/codemirror/lib/codemirror.css">
  <link rel="stylesheet" href="./../libs/codemirror/theme/idea.css">
  <script src="./../libs/codemirror/lib/codemirror.js"></script>
  <script src="./../libs/codemirror/mode/clike/clike.js"></script>
  <script src="./../libs/codemirror/mode/htmlmixed/htmlmixed.js"></script>
  <script src="./../libs/codemirror/mode/php/php.js"></script>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />
  <style>
body {
  margin: 0px;
  padding: 0px;
  font-family: monospace;
  font-size: 12px;
  color: black;
  background: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAgAAAAECAYAAACzzX7wAAAAAXNSR0IArs4c6QAAAClJREFUCJmFjCESAAAIg5jn/788ixoskrYAsm0ASQD0XYKHnHHNKb6FAgU2CgYY5wFmAAAAAElFTkSuQmCC'); 
}

.dtt-page-counter-cover {
  width: 210mm;
  height: 1px;
  margin: 0px auto 0px auto;
}

.dtt-page {
  width: 210mm;
  height: 297mm;
  height: 165mm;
  background: url('data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEBLAEsAAD/4QDyRXhpZgAASUkqAAgAAAAIAA4BAgASAAAAbgAAABIBAwABAAAAAQAAABoBBQABAAAAgAAAABsBBQABAAAAiAAAACgBAwABAAAAAgAAADEBAgANAAAAkAAAADIBAgAUAAAAngAAAGmHBAABAAAAsgAAAAAAAABDcmVhdGVkIHdpdGggR0lNUAAsAQAAAQAAACwBAAABAAAAR0lNUCAyLjEwLjM2AAAyMDI2OjAyOjI2IDA1OjAxOjEzAAIAhpIHABkAAADQAAAAAaADAAEAAAABAAAAAAAAAAAAAAAAAAAAQ3JlYXRlZCB3aXRoIEdJTVAA/+EMz2h0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8APD94cGFja2V0IGJlZ2luPSLvu78iIGlkPSJXNU0wTXBDZWhpSHpyZVN6TlRjemtjOWQiPz4gPHg6eG1wbWV0YSB4bWxuczp4PSJhZG9iZTpuczptZXRhLyIgeDp4bXB0az0iWE1QIENvcmUgNC40LjAtRXhpdjIiPiA8cmRmOlJERiB4bWxuczpyZGY9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkvMDIvMjItcmRmLXN5bnRheC1ucyMiPiA8cmRmOkRlc2NyaXB0aW9uIHJkZjphYm91dD0iIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RFdnQ9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZUV2ZW50IyIgeG1sbnM6ZGM9Imh0dHA6Ly9wdXJsLm9yZy9kYy9lbGVtZW50cy8xLjEvIiB4bWxuczpHSU1QPSJodHRwOi8vd3d3LmdpbXAub3JnL3htcC8iIHhtbG5zOnhtcD0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wLyIgeG1wTU06RG9jdW1lbnRJRD0iZ2ltcDpkb2NpZDpnaW1wOjkyZDMwMjA3LWFmMmQtNDY5Ni1hMzJmLTMyMzYwOWJmNzE1YiIgeG1wTU06SW5zdGFuY2VJRD0ieG1wLmlpZDo5ZmY0YzA0Yy1mZjExLTRiOTYtOTA3Zi1mMDkzNjUzMTE5Y2IiIHhtcE1NOk9yaWdpbmFsRG9jdW1lbnRJRD0ieG1wLmRpZDpmOWI0ODY4MS05MzA2LTQzY2MtOTFiZi1iOTFlOWVhMTMzYmMiIGRjOkZvcm1hdD0iaW1hZ2UvanBlZyIgR0lNUDpBUEk9IjIuMCIgR0lNUDpQbGF0Zm9ybT0iTGludXgiIEdJTVA6VGltZVN0YW1wPSIxNzcyMDU2ODc1MDg4MzE5IiBHSU1QOlZlcnNpb249IjIuMTAuMzYiIHhtcDpDcmVhdG9yVG9vbD0iR0lNUCAyLjEwIiB4bXA6TWV0YWRhdGFEYXRlPSIyMDI2OjAyOjI2VDA1OjAxOjEzKzA3OjAwIiB4bXA6TW9kaWZ5RGF0ZT0iMjAyNjowMjoyNlQwNTowMToxMyswNzowMCI+IDx4bXBNTTpIaXN0b3J5PiA8cmRmOlNlcT4gPHJkZjpsaSBzdEV2dDphY3Rpb249InNhdmVkIiBzdEV2dDpjaGFuZ2VkPSIvIiBzdEV2dDppbnN0YW5jZUlEPSJ4bXAuaWlkOjY5ZjczZmYxLTZjMDMtNGMzMC05YjE3LTNlN2M2MTEzZmYxOSIgc3RFdnQ6c29mdHdhcmVBZ2VudD0iR2ltcCAyLjEwIChMaW51eCkiIHN0RXZ0OndoZW49IjIwMjYtMDItMjZUMDU6MDE6MTUrMDc6MDAiLz4gPC9yZGY6U2VxPiA8L3htcE1NOkhpc3Rvcnk+IDwvcmRmOkRlc2NyaXB0aW9uPiA8L3JkZjpSREY+IDwveDp4bXBtZXRhPiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDw/eHBhY2tldCBlbmQ9InciPz7/4gKwSUNDX1BST0ZJTEUAAQEAAAKgbGNtcwRAAABtbnRyUkdCIFhZWiAH6gACABkAFQA3AABhY3NwQVBQTAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA9tYAAQAAAADTLWxjbXMAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA1kZXNjAAABIAAAAEBjcHJ0AAABYAAAADZ3dHB0AAABmAAAABRjaGFkAAABrAAAACxyWFlaAAAB2AAAABRiWFlaAAAB7AAAABRnWFlaAAACAAAAABRyVFJDAAACFAAAACBnVFJDAAACFAAAACBiVFJDAAACFAAAACBjaHJtAAACNAAAACRkbW5kAAACWAAAACRkbWRkAAACfAAAACRtbHVjAAAAAAAAAAEAAAAMZW5VUwAAACQAAAAcAEcASQBNAFAAIABiAHUAaQBsAHQALQBpAG4AIABzAFIARwBCbWx1YwAAAAAAAAABAAAADGVuVVMAAAAaAAAAHABQAHUAYgBsAGkAYwAgAEQAbwBtAGEAaQBuAABYWVogAAAAAAAA9tYAAQAAAADTLXNmMzIAAAAAAAEMQgAABd7///MlAAAHkwAA/ZD///uh///9ogAAA9wAAMBuWFlaIAAAAAAAAG+gAAA49QAAA5BYWVogAAAAAAAAJJ8AAA+EAAC2xFhZWiAAAAAAAABilwAAt4cAABjZcGFyYQAAAAAAAwAAAAJmZgAA8qcAAA1ZAAAT0AAACltjaHJtAAAAAAADAAAAAKPXAABUfAAATM0AAJmaAAAmZwAAD1xtbHVjAAAAAAAAAAEAAAAMZW5VUwAAAAgAAAAcAEcASQBNAFBtbHVjAAAAAAAAAAEAAAAMZW5VUwAAAAgAAAAcAHMAUgBHAEL/2wBDAAMCAgMCAgMDAwMEAwMEBQgFBQQEBQoHBwYIDAoMDAsKCwsNDhIQDQ4RDgsLEBYQERMUFRUVDA8XGBYUGBIUFRT/2wBDAQMEBAUEBQkFBQkUDQsNFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBT//gAUQ3JlYXRlZCB3aXRoIEdJTVAA/8IAEQgADwAPAwERAAIRAQMRAf/EABcAAAMBAAAAAAAAAAAAAAAAAAABAgj/xAAVAQEBAAAAAAAAAAAAAAAAAAAAAf/aAAwDAQACEAMQAAAB1MjUSl//xAAXEAEBAQEAAAAAAAAAAAAAAAABABEx/9oACAEBAAEFAgMwjl//xAAUEQEAAAAAAAAAAAAAAAAAAAAg/9oACAEDAQE/AR//xAAUEQEAAAAAAAAAAAAAAAAAAAAg/9oACAECAQE/AR//xAAYEAACAwAAAAAAAAAAAAAAAAAAARAhMf/aAAgBAQAGPwJUYKP/xAAYEAEBAAMAAAAAAAAAAAAAAAAAMWGRof/aAAgBAQABPyGaMTTmR//aAAwDAQACAAMAAAAQe4//xAAUEQEAAAAAAAAAAAAAAAAAAAAg/9oACAEDAQE/EB//xAAXEQEBAQEAAAAAAAAAAAAAAAABABAR/9oACAECAQE/EFus5//EABoQAQACAwEAAAAAAAAAAAAAAAEAESFRsUH/2gAIAQEAAT8QQiihbRqGTkmz45Foq4n/2Q==');
  background-color: rgba(255, 255, 255, 0.05);
  backdrop-filter: blur(400px);
  -webkit-backdrop-filter: blur(400px);
  margin: 10px auto 10px auto;
  outline: solid 1px black;
  border-right: solid 1px black;
  border-bottom: solid 1px black;
  overflow: hidden;
}

.dtt-page-inner {
  margin: 5px;
  padding: 0px;
  font-family: monospace;
  font-size: 12px;
  color: black;
  white-space: pre-wrap;
  line-height: 18px;
  width: 102mm;
  height: 297mm;
  overflow: hidden;
  float: left;
}

.dtt-page-counter-inner {
  width: 1px;
  height: 1px;
  position: absolute;
}

.dtt-page-counter {
  width: 1px;
  height: 1px;
  position: absolute;
  top: 5px;
  left: -20px;
}

.dtt-page-counter div {
  background-color: white;
  border: solid 1px black;
  border-radius: 10px 0px 0px 10px;
  width: 30px;
  height: 14px;
  padding: 5px;
  text-align: left; 
  cursor: pointer;
  cursor: hand;
}

.dtt-grey {
  color: grey;
}

#dtt-minute {
  font-weight: bold;
  font-size: 18px;
}

#dtt-second {
  font-weight: bold;
  font-size: 18px;
}

#page-s .dtt-result {
  border: dashed 2px gainsboro;
  border-radius: 10px;
  background-color: white-smoke;
}

#page-s .dtt-script {
  border: dashed 2px gainsboro;
}

.dtt-textbox {
  font-family: monospace;
  font-size: 12px;
  color: black;
  background-color: white;
  border: solid 1px gainsboro;
  border-radius: 5px;
  padding: 2px 5px 2px 5px;
}

.dtt-button {
  font-family: monospace;
  font-size: 12px;
  color: black;
  background-color: white;
  border: solid 2px green;
  border-left: solid 5px green;
  border-radius: 0px 5px 5px 0px;
  padding: 5px 10px 5px 10px;
}

  </style>
  <script>
let g_token = '<?php print( $g_token ); ?>';
let g_unlocked = <?php print( $g_unlocked_str ); ?>;
let g_editor = false;
let g_data = [];
let g_cur_tab = '0';
let g_cur_file = '';
let g_testor_username = '<?php print( $g_testor_username ); ?>';
let g_testor_password = '<?php print( $g_testor_password ); ?>';
let g_testor_suite = '_';

function g_utp_phptestor() {
  return `$g_suite_code = '__SUITE_CODE__';
g_phpwifide_init($g_suite_code, '__USERNAME__', '__PASSWORD__');

\\phptestor\\api_testor_startup();

$vars = g_phpwifide_vars();
$g_token = $vars['token'];
$g_suite_code = $vars['suite_code'];
$g_suite_id = $vars['suite_id'];

__TEST_SOURCE__

\\phptestor\\api_testor_shutdown();  
`;
}

function g_refine_result( text ) {
  let find = "\n=====] FAILED [=====\n\n";
  let idx = text.indexOf( "\n" );
  if ( idx >= 0 ) {
    let ln = text.substring(0, idx);
    text = text.substring( idx + 1);
  }
  idx = text.indexOf(find);
  if (idx >= 0) {
    text = text.substring(idx);
  }
  text = "=====> Tab: '" + g_cur_tab + "', File: " + g_cur_file + " <======" + "\n" + text;
  return text;
}

function g_initialize_data() {
  for ( var i = 0; i < 10; i++ ) {
    g_data[''+i] = { 's': "/*\nWelcome to phpWifide v.0.0.1 !\n\nPlease try following command to start: \n\n$) //help\n\n$) $results = ''; $beauty = false; \\phptestor\\api_testor_man( 'phptestor', 'procedure', 'api_testor_equals', $results, $beauty ); echo \"\\n\", \"Manual: \", \"\\n\", $results, \"\\n\";\n\n$) $results = ''; $beauty = false; \\phptestor\\api_testor_pattern( 'phptestor', 'procedure', 'api_testor_equals', 'scrp', $results, $beauty ); echo \"\\n\", \"Pattern: \", \"\\n\", $results, \"\\n\"; \n\n*/", 'r': '', 'f': './tab-' + i + '.shp' };
  }
}

function g_save_tab( code ) {
  g_data[code]['s'] = g_editor.doc.getValue();
  g_data[code]['r'] = $('#page-s .dtt-result').val();
  g_data[code]['f'] = g_cur_file;
}

function g_show_tab( code ) {
  g_save_tab( g_cur_tab );
  let script = g_data[code]['s'];
  let result = g_data[code]['r'];
  let cur_file = g_data[code]['f'];
  g_editor.doc.setValue(script);
  g_cur_file = cur_file;
  g_cur_tab = code;
  $('#page-s .dtt-result').val(g_refine_result(result));
  
  $('#page-s .dtt-tab-button div').css( 'backgroundColor', 'white' );
  $('#page-s .dtt-tab-button div').css( 'color', 'black' );
  
  $('#page-s #dtt-tab-' + code + ' div').css( 'backgroundColor', 'lightpink' );
  $('#page-s #dtt-tab-' + code + ' div').css( 'color', 'white' );
}

function g_unlock( cb ) {
  if ( g_unlocked ) return true;
  let pwd = prompt( 'Please enter password to unlock feature: ');
  let v_cb = cb;
  $.post( './../unlock.php', { 'token': g_token, 'pwd': pwd } ).done(function(response) {
    if ( response == 'y' ) {
      g_unlocked = true;
      setTimeout( v_cb, 100 );
    } else {
      alert( 'Password does not match! Feature is not unlocked!' );
    }
  }).fail(function(jqXHR, textStatus, errorThrown) {
    alert( 'Failed to unlock feature!' );
  });  
  return false;
}

function g_load() {
  g_initialize_data();
  $(window).resize( function() {
    g_resize();
  } );
  g_resize();
  g_show_page( 'o' );
  
  var editor = CodeMirror.fromTextArea(document.getElementById("dtt-script"), {
    lineNumbers: true,
    styleActiveLine: true,
    matchBrackets: true
  });
  editor.setOption("theme", 'idea');
  editor.setOption("mode", 'text/x-php');
  g_editor = editor;
  g_resize();
  
  let script = g_data['0']['s'];
  let result = g_data['0']['r'];
  g_cur_file = g_data['0']['f'];
  g_editor.doc.setValue(script);
  $('#page-s .dtt-result').val(g_refine_result(result));
  g_show_tab('0');
}

function g_show_page_raw( code ) {
  $('.dtt-cover').hide();
  $('#page-' + code).show();
}

function g_show_page( code ) {
  if ( code !== 'o' ) {
     let cb = "g_show_page_raw('" + code + "');";
     if ( ! g_unlock( cb ) ) return;
  }
  $('.dtt-cover').hide();
  $('#page-' + code).show();
}

function g_resize() {
  let sw = $(window).width();
  let sh = $(window).height();
  $('body').width( sw - 5 );
  $('body').height( sh - 5 );
  $('body').css( 'overflow', 'hidden' );
  $('.dtt-page').width( sw - 40 );
  $('.dtt-page').height( sh - 15 );
  $('.dtt-page').css( 'margin', '5px 0px 5px 25px' );
  $('.dtt-page').css( 'overflowX', 'hidden' );
  $('.dtt-page').css( 'overflowY', 'scroll' );
  $('.dtt-page-counter-cover').width( sw - 40 );
  $('.dtt-page-inner').width( sw - 40 - 35 );
  $('.dtt-page-inner').css( 'minHeight', (sh - 15) + 'px');
  $('#page-o .dtt-page-inner').css( 'minHeight', '2200px');
  $('.dtt-page-inner').css( 'border', 'dotted 2px gainsboro' );
  $('.dtt-page-inner').css( 'padding', '5px' );
  
  $('#page-s .dtt-script-cover').width( sw - 40 - 35 - 15 + 10 );
  $('#page-s .dtt-script-cover').height( parseInt( ( sh - 15 - 15 - 15 ) / 2 ) - 10 );
  $('#page-s .dtt-script-cover').css( 'border', 'dotted 2px gainsboro' );
  $('#page-s .dtt-script-cover').css( 'borderRadius', '10px' );
  $('#page-s .dtt-script-cover').css( 'overflow', 'hidden' );
  $('#page-s .dtt-script-cover .CodeMirror').width( sw - 40 - 35 - 15 + 10 );
  $('#page-s .dtt-script-cover .CodeMirror').height( parseInt( ( sh - 15 - 15 - 15 ) / 2 ) - 10 );
  
  $('#page-s .dtt-script').width( sw - 40 - 35 - 15 + 10 );
  $('#page-s .dtt-script').height( parseInt( ( sh - 15 - 15 - 15 ) / 2 ) - 10 );
  $('#page-s .dtt-result').width( sw - 40 - 35 - 15 + 10 );
  $('#page-s .dtt-result').height( parseInt( ( sh - 15 - 15 - 15 ) / 2 ) - 10 );
  $('#page-s .dtt-page-inner').height( sh - 15 - 15 - 10 );
  $('#page-s .dtt-result').css( 'marginTop', '10px' );
  $('#page-s .dtt-page-inner').css( 'minHeight', (sh - 15 - 15 - 15) + 'px');
  $('#page-s .dtt-page').css( 'overflowY', 'hidden' );
}

function g_refine( sql ) {
  let nsql = "\n" + sql + "\n";
  if ( nsql.indexOf( "\n//upload" ) >= 0 || nsql.indexOf( " //upload" ) >= 0 ) {
    $('#dtt-file').val('');
    g_show_page('u');
    sql = sql.replaceAll( '//upload', '//_upload_' );
  } else {
  
    let idx = ("\n" + sql + "\n").indexOf( "\n//username " );
    if ( idx >= 0 ) {
      let tmp = ("\n" + sql + "\n").substring( idx + 12 );
      idx = tmp.indexOf( ";" );
      if ( idx >= 0 ) {
        tmp = tmp.substring( 0, idx );
      }
      idx = tmp.indexOf( "\n" );
      if ( idx >= 0 ) {
        tmp = tmp.substring( 0, idx );
      }
      tmp = tmp.trim();
      g_testor_username = tmp;
    }
      
    idx = ("\n" + sql + "\n").indexOf( "\n//password " );
    if ( idx >= 0 ) {
      let tmp = ("\n" + sql + "\n").substring( idx + 12 );
      idx = tmp.indexOf( ";" );
      if ( idx >= 0 ) {
        tmp = tmp.substring( 0, idx );
      }
      idx = tmp.indexOf( "\n" );
      if ( idx >= 0 ) {
        tmp = tmp.substring( 0, idx );
      }
      tmp = tmp.trim();
      g_testor_password = tmp;
    }

    idx = ("\n" + sql + "\n").indexOf( "\n//suite " );
    if ( idx >= 0 ) {
      let tmp = ("\n" + sql + "\n").substring( idx + 9 );
      idx = tmp.indexOf( ";" );
      if ( idx >= 0 ) {
        tmp = tmp.substring( 0, idx );
      }
      idx = tmp.indexOf( "\n" );
      if ( idx >= 0 ) {
        tmp = tmp.substring( 0, idx );
      }
      tmp = tmp.trim();
      g_testor_suite = tmp;
    }

  }
  return sql;
}

function g_unit_test() {
  let username = prompt( "Please enter Testor's username: " );
  let password = prompt( "Please enter Testor's password: " );
  let suite_code = prompt( "Please enter test suite code: " );
  let code = g_utp_phptestor();
  code = code.replaceAll( '__USERNAME__', username );
  code = code.replaceAll( '__PASSWORD__', password );
  code = code.replaceAll( '__SUITE_CODE__', suite_code );
  code = code.replaceAll( '__TEST_SOURCE__', g_editor.doc.getValue() );
  code = g_refine( code );
  $('#page-s .dtt-result').val(g_refine_result(''));
  $('#page-s .dtt-result').val( g_refine_result("\n" + 'Executing script ...' + "\n") );
  $.post( './../execute.php', { 'token': g_token, 's': code } ).done(function(response) {
    $('#page-s .dtt-result').val(g_refine_result(response));
  }).fail(function(jqXHR, textStatus, errorThrown) {
    let message = "\n" + 'Status Code: ' + jqXHR.status + "\n" + 'Status Text: ' + textStatus + "\n" + 'Error Thrown: ' + errorThrown + "\n" + 'Server Response: ' + jqXHR.responseText + "\n";
    $('#page-s .dtt-result').val( g_refine_result(message) );    
  });  
}

function g_def_unit_test() {
  let code_tmp = g_refine( g_editor.doc.getValue() );
  let username = g_testor_username;
  let password = g_testor_password;
  let suite_code = g_testor_suite;
  if ( g_testor_suite == '_' || g_testor_suite == '' || g_testor_suite == '__' ) {
    suite_code = prompt( "Please enter test suite code: " );
    suite_code = suite_code.trim();
    g_testor_suite = suite_code;
    if ( suite_code == '_' || suite_code == '' || suite_code == '__' ) return;
  }
  let code = g_utp_phptestor();
  code = code.replaceAll( '__USERNAME__', username );
  code = code.replaceAll( '__PASSWORD__', password );
  code = code.replaceAll( '__SUITE_CODE__', suite_code );
  code = code.replaceAll( '__TEST_SOURCE__', g_editor.doc.getValue() );
  code = g_refine( code );
  $('#page-s .dtt-result').val(g_refine_result(''));
  $('#page-s .dtt-result').val( g_refine_result("\n" + 'Executing script ...' + "\n") );
  $.post( './../execute.php', { 'token': g_token, 's': code } ).done(function(response) {
    $('#page-s .dtt-result').val(g_refine_result(response));
  }).fail(function(jqXHR, textStatus, errorThrown) {
    let message = "\n" + 'Status Code: ' + jqXHR.status + "\n" + 'Status Text: ' + textStatus + "\n" + 'Error Thrown: ' + errorThrown + "\n" + 'Server Response: ' + jqXHR.responseText + "\n";
    $('#page-s .dtt-result').val( g_refine_result(message) );    
  });  
}

function g_execute() {
  $('#page-s .dtt-result').val(g_refine_result(''));
  let code = g_editor.doc.getValue();
  code = g_refine( code );
  $('#page-s .dtt-result').val( g_refine_result("\n" + 'Executing script ...' + "\n") );
  $.post( './../execute.php', { 'token': g_token, 's': code } ).done(function(response) {
    if ( response.indexOf( "\n" + '// loading //' + "\n" ) >= 0 ) {
      let text = response.replaceAll( "\n" + '// loading //' + "\n", '' );
      let idx = code.indexOf('-- load ');
      if (idx >= 0) {
        let tmp = code.substring(idx + 8);
        idx = tmp.indexOf( ';' );
        if (idx >= 0) {
          tmp = tmp.substring(0, idx);
        }
        idx = tmp.indexOf( "\n" );
        if (idx >= 0) {
          tmp = tmp.substring(0, idx);
        }
        tmp = tmp.trim();
        g_cur_file = tmp;
      }
      let old_script = g_editor.doc.getValue();
      g_editor.doc.setValue(text);
      $('#page-s .dtt-result').val( g_refine_result('Script is loaded ...' + "\n\nOld scripts are as following:\n-----------\n" + old_script ) );
      g_show_tab( g_cur_tab );
    } else {
      $('#page-s .dtt-result').val(g_refine_result(response));
    }
  }).fail(function(jqXHR, textStatus, errorThrown) {
    let message = "\n" + 'Status Code: ' + jqXHR.status + "\n" + 'Status Text: ' + textStatus + "\n" + 'Error Thrown: ' + errorThrown + "\n" + 'Server Response: ' + jqXHR.responseText + "\n";
    $('#page-s .dtt-result').val( g_refine_result(message) );    
  });
}
  </script>
</head>
<body onload="g_load()">
  <div id="page-o" class="dtt-cover">
  <div class="dtt-page-counter-cover"><div class="dtt-page-counter-inner"><div style="top: 5px" class="dtt-page-counter" onclick="g_show_page('o');"><div>&nbsp;O</div></div></div></div>
  <div class="dtt-page"><div class="dtt-page-inner">=============================_============
  _ __ _ _ ___  __ _ ___ _ _| |_____ _ _ 
 | '_ \ '_/ _ \/ _` / _ \ '_| / / -_) '_|
 | .__/_| \___/\__, \___/_| |_\_\___|_|  
=|_|===========|___/======================
      Testor - Unit Testing Platform
             ----- oOo ------
[ phpWifide ] IDE @ Wi-Fi Network for PHP
==========================================


-|_|-----------|___/----------------------
                 Pages
------------------------------------------

+ <a href="#" onclick="g_show_page('o'); return false;">Overview<a> : the entrance of phpWifide

+ <a href="#" onclick="g_show_page('s'); return false;">Script<a> : the code editor of phpWifide

  o Tap on 'E' tab on the left to execute PHP script.


-|_|-----------|___/----------------------
                 Help
------------------------------------------

+------------+---------------------------------------------------------------------------------------------+
| command    | description                                                                                 |
+------------+---------------------------------------------------------------------------------------------+
| //help     | Display this help.                                                                          |
| //source   | Include PHP script file which does not include '<?php ' & '?>'. Argument is .shp file path. |
| //pattern  | Get code pattern from myTestor.                                                             |
| //workdir  | Set work dir. Argument is selected directory.                                               |
| //upload   | Upload zip file.                                                                            |
| //download | Zip folder & download zip file. Argument is relative path.                                  |
| //load     | Load script file into script editor. Argument is relative path.                             |
| //list     | List buffer directory. Argument is relative path.                                           |
| //remove   | Remove file. Argument is relative path.                                                     |
| //save     | Save previous code to file. Does not execute script. Argument is relative path.             |
| //cat      | Display script file. Does not execute script. Argument is relative path.                    |
| //username | Set Testor's username. It is executed in client side.                                       |
| //password | Set Testor's password. It is executed in client side.                                       |
| //suite    | Set test suite code. It is executed in client side.                                         |
+------------+---------------------------------------------------------------------------------------------+

  </div></div>
  </div>

  <div id="page-s" class="dtt-cover" style="display: none">
  <div class="dtt-page-counter-cover"><div class="dtt-page-counter-inner"><div style="top: 5px" class="dtt-page-counter" onclick="g_show_page('o');"><div>&nbsp;O</div></div></div></div>

  <div class="dtt-page-counter-cover"><div class="dtt-page-counter-inner"><div style="top: 5px" class="dtt-page-counter" onclick="g_show_page('o');"><div>&nbsp;O</div></div><div style="top: 35px" class="dtt-page-counter" onclick="g_show_page('s');"><div>&nbsp;S</div></div><div style="top: 85px" class="dtt-page-counter" onclick="g_execute();"><div>&nbsp;E</div></div><div style="top: 135px" class="dtt-tab-button dtt-page-counter" onclick="g_show_tab('0');" id="dtt-tab-0"><div>&nbsp;0</div></div><div style="top: 165px" class="dtt-tab-button dtt-page-counter" onclick="g_show_tab('1');" id="dtt-tab-1"><div>&nbsp;1</div></div><div style="top: 195px" class="dtt-tab-button dtt-page-counter" onclick="g_show_tab('2');" id="dtt-tab-2"><div>&nbsp;2</div></div><div style="top: 225px" class="dtt-tab-button dtt-page-counter" onclick="g_show_tab('3');" id="dtt-tab-3"><div>&nbsp;3</div></div><div style="top: 255px" class="dtt-tab-button dtt-page-counter" onclick="g_show_tab('4');" id="dtt-tab-4"><div>&nbsp;4</div></div><div style="top: 285px" class="dtt-tab-button dtt-page-counter" onclick="g_show_tab('5');" id="dtt-tab-5"><div>&nbsp;5</div></div><div style="top: 315px" class="dtt-tab-button dtt-page-counter" onclick="g_show_tab('6');" id="dtt-tab-6"><div>&nbsp;6</div></div><div style="top: 345px" class="dtt-tab-button dtt-page-counter" onclick="g_show_tab('7');" id="dtt-tab-7"><div>&nbsp;7</div></div><div style="top: 375px" class="dtt-tab-button dtt-page-counter" onclick="g_show_tab('8');" id="dtt-tab-8"><div>&nbsp;8</div></div><div style="top: 405px" class="dtt-tab-button dtt-page-counter" onclick="g_show_tab('9');" id="dtt-tab-9"><div>&nbsp;9</div></div><div style="top: 455px" class="dtt-tab-button dtt-page-counter" onclick="g_unit_test();"><div>UT</div></div><div style="top: 485px" class="dtt-tab-button dtt-page-counter" onclick="g_def_unit_test();"><div>DUT</div></div></div></div>
  <div class="dtt-page"><div class="dtt-page-inner"><div class="dtt-script-cover"><textarea id="dtt-script" class="dtt-script"></textarea></div><textarea class="dtt-result"></textarea></div></div></div>

  <div id="page-u" class="dtt-cover" style="display: none">
  <div class="dtt-page-counter-cover"><div class="dtt-page-counter-inner"><div style="top: 5px" class="dtt-page-counter" onclick="g_show_page('o');"><div>&nbsp;O</div></div><div style="top: 35px" class="dtt-page-counter" onclick="g_show_page('u');"><div>&nbsp;U</div></div><div style="top: 85px" class="dtt-page-counter" onclick="g_show_page('s');"><div>&nbsp;S</div></div></div></div>
  <div class="dtt-page"><div class="dtt-page-inner"><form enctype="multipart/form-data" target="_blank" method="post" action="./../upload.php">+ Zip File:
<input type="file" id="dtt-file" name="zip" class="dtt-textbox" />
  
<input name="submit" type="submit" value="Upload" class="dtt-button" onclick="g_show_page('s');" />
</form> 
  </div></div>
  </div>

</body>
</html>
