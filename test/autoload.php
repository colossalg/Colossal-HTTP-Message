<?php

// This is required as we MUST load ForcedFailures.php prior to any of the other
// PHP source files otherwise it seems that the namespace overloads will not work.
include_once __DIR__ . "/ForcedFailures.php";

include_once __DIR__ . "/../vendor/autoload.php";
