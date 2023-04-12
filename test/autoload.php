<?php

// This is required as we MUST load PhpOverrides.php prior to any of the other
// source files otherwise it seems that the namespace overloads will not work.
include_once __DIR__ . "/PhpOverrides.php";

include_once __DIR__ . "/../vendor/autoload.php";
