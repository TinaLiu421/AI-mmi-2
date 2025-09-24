<?php 
spl_autoload_register(function($file) {
    $path = str_replace('\\', '/', $file);
    $path = dirname(__FILE__) . '/' . $path . '.php';
    if(file_exists($path))
    {			
        require_once($path);
    }
    else
    {
        echo '<p><strong style="color: #FF0000; font-size: 15px;">\'' . $path . '\' not found</strong></p>';
    }
});
?>