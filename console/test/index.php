<?php

require_once('/home/nobu/composer/console/index.php');

$console = new Console();

$console->log(1, true, null, 'string', array(1,2,3,4));

throw new Exception('bad');
