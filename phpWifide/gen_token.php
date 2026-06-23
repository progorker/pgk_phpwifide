<?php

$p1 = substr( strrev( uniqid() ), 0, 4 );
$p2 = substr( strrev( uniqid() ), 0, 4 );
$p3 = substr( strrev( uniqid() ), 0, 4 );
$p4 = substr( strrev( uniqid() ), 0, 4 );
$p5 = substr( strrev( uniqid() ), 0, 4 );
$p6 = substr( strrev( uniqid() ), 0, 4 );
$p7 = substr( strrev( uniqid() ), 0, 4 );
$p8 = substr( strrev( uniqid() ), 0, 4 );

echo "Token: " . $p1 . $p2 . $p3 . $p4 . $p5 . $p6 . $p7 . $p8;
?>