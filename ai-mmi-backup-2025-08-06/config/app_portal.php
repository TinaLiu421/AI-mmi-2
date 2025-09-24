<?php
$config['application_uid'] = 'a0ea51898cbe2c49c90076cd62f8d008'; 
$config['support_lang'] = [
    1 => [
        'code'          =>  'en', 
        'name'          =>  'English', 
        'short_name'    =>  'EN'
    ],
    2 => [
        'code'          =>  'zh-hant', 
        'name'          =>  '繁體中文', 
        'short_name'    =>  '繁'
    ],
    3 => [
        'code'          =>  'zh-hans', 
        'name'          =>  '简体中文', 
        'short_name'    =>  '简'
    ],
];
$config['default_lang_index'] = 1;
$config['default_lang'] = $config['support_lang'][$config['default_lang_index']]['code'];
$config['default_lang_admin'] = 'en';
$config['custom_link'] = false;

// 1. add "middleware" to $routeMiddleware (path: app\Http\Kernel.php)
// 2. add "module.class.function" & "middleware" to below config
/* e.g.,
$config['portal_middleware'] = [
    'web.login.index' => ['middleware_1','middleware_2']
];
*/
$config['middleware_mapping'] = [
    'admin' => 'admin.authn'
];
  
// return
return $config;