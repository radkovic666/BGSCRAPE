<?php
header("Content-Type: application/xml; charset=utf-8");
readfile(__DIR__ . "/epg.xml");
?>
