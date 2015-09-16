<?php
$path = __FILE__;
require_once(str_replace('index.php', '', $path) . 'src/exceptionhandler.class.php');

ExceptionHandler::init();
