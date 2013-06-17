<?php
/*
On-demand profiling using xhprof.
This file should be loaded using auto_prepend
Use by appending a ?profile to the initial URL.
Disable profiling for all subsequent URLs fetched by appending ?noprofile.
You need to install and set up xhprof, as described here:
http://web.archive.org/web/20110514095512/http://mirror.facebook.net/facebook/xhprof/doc.html
*/

# Do not use const for compatibility with old PHP versions.

# Change this according to where your distribution places
# xhprof's files, e.g. /usr/share/php52-xhprof/ in Ubuntu for old PHP5.2
define('XHPROF_ROOT', '/usr/share/webapps/xhprof');
define('XHPROF_DISPLAY', false);
define('XHPROF_COOKIENAME', 'XHPROF_PROFILE');

# Rather than a session, since I only need to store one flag value, I am using a cookie.
if (array_key_exists('profile', $_GET)) {
  setcookie('XHPROF_PROFILE', 1);
  $_COOKIE[XHPROF_COOKIENAME] = 1; # Set the value of the cookie in this request already.
} elseif (array_key_exists('noprofile', $_GET)) {
  setcookie('XHPROF_PROFILE', ''); # Delete the cookie
  unset($_COOKIE[XHPROF_COOKIENAME]); # Unset hte cookie in this request already.
}

if (isset($_COOKIE[XHPROF_COOKIENAME]) && $_COOKIE[XHPROF_COOKIENAME]) {
  # Start profiling.
  # Add XHPROF_FLAGS_NO_BUILTINS to not profile builtin functions.
  xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY,
    array('ignored_functions' => array('xhprof_disable')));

  function xhprof_prepend_finalize() {
    # Stop profiler.
    $xhprof_data = xhprof_disable();
  
    if (XHPROF_DISPLAY) {
      # Display raw xhprof data for the profiler run.
      echo '<pre>';
      var_export($xhprof_data);
      echo '</pre>';
    } else {
      # Saving the XHProf run using the default implementation of iXHProfRuns.
      include_once XHPROF_ROOT . '/xhprof_lib/utils/xhprof_lib.php';
      include_once XHPROF_ROOT . '/xhprof_lib/utils/xhprof_runs.php';
  
      $xhprof_runs = new XHProfRuns_Default();
  
      $namespace = isset($_GET['namespace']) ? $_GET['namespace'] : 'xhprof_debug';
  
      /*
      Save the run under a namespace.
  
      **NOTE**:
      By default save_run() will automatically generate a unique
      run id for you. [You can override that behavior by passing
      a run id (optional arg) to the save_run() method instead.]
      */
      $run_id = $xhprof_runs->save_run($xhprof_data, $namespace);
  
      echo <<<EOH
<hr>
<a href="http://$_SERVER[SERVER_NAME]/xhprof/index.php?run=$run_id&source=$namespace" target="xhprof">View run #$run_id under namespace $namespace.</a>
EOH;
    }
  }
  # BUG: other shutdown functions registered after this won't be profiled.
  register_shutdown_function('xhprof_prepend_finalize');
}

?>
