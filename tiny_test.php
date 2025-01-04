<?php namespace enu\tiny_test;

// Useful for environments like WP where all files are in /public.
if (php_sapi_name() !== 'cli') {
	die( "We're sorry, but you can not directly access this file.\n" );
}

function __bootstrap() {
  define("TINY_TEST_TESTS_DIR", "tests");
  // require_once "vendor/autoload.php"; 
}

use AssertionError;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

function suite($description, $callable) {
  return describe($description, $callable);
}

function describe($description, $tests) {
  return function() use($description, $tests) {
    array_map(function($test) use ($description) { 
      try {
        $test();
      } catch (Throwable $e) {
        __propagate_error($e, $description);
      }
    }, $tests());
  };
}

function test($description, $test) {
  return function() use($description, $test) {
    try {
      $test();
    } catch (Throwable $e) {
      __propagate_error($e, $description);
    }
  };
}

function tiny_assert($condition, $fail_msg = "") {
  $condition 
    ? __output(__text(".", "green")) 
    : throw new AssertionError($fail_msg);
}

function __propagate_error(Throwable $e, $msg = "") {
    $msg = implode("\n - ", array_filter([$msg, $e->getMessage()]));
    throw new AssertionError($msg);
}

function __text($text, $color = "") {
  switch($color) {
    case "green":
      return "\033[32m$text\033[0m";
    case "red":
      return "\033[31m$text\033[0m";
    default: 
      return $text;
  } 
}

function __find_test_files($dir) {
    $result = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    
    foreach ($iterator as $file) {
        if ($file->isFile() && str_ends_with($file->getFilename(), '_test.php')) {
            $result[] = $file->getPathname();
        }
    }
    
    return $result;
}

function __output($text) {
  echo $text;
}

function run() {
  __bootstrap();
  $test_files = __find_test_files(TINY_TEST_TESTS_DIR);
  try {
    array_map(function($file) {
        $suite = require_once($file);
        $suite();
      }, $test_files);
    __output("\n\n" . __text("Tests passed! 🎉 \n", "green"));
    exit(0);
  } catch (Throwable $e) {
    __output(__text("\nFAILED:\n", "red"));
    __output(__text($e->getMessage() . PHP_EOL, "red"));
    exit(1);
  }
}

run();
