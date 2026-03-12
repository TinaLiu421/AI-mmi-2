<?php
/*
1. back-end:
http://{domain_name}/admin/{class_name}/{function_name}/{parameter_1}/{parameter_2}/.../?t=xxx

2. front-end: website within multi language:
http://{domain_name}/{language_code}/{class_name}/{function_name}/{parameter_1}/{parameter_2}/.../?t=xxx
 
or
3. front-end: website within single language:
http://{domain_name}/{class_name}/{function_name}/{parameter_1}/{parameter_2}/.../?t=xxx
*/

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Config;

class RouteMapping extends Controller {
    private $_mapping_data = null;

    public function __construct() {
        $this->_mapping_data = $this->getMappingData();

        // middleware: $this->middleware('middelware_name:param_1,param_2,...')
        $middleware_mapping = Config::get('app_portal.middleware_mapping');
        
        if(!empty($middleware_mapping)) {
            $find_middleware_index = [
                $this->_mapping_data['module'],
                implode('.', 
                [
                    $this->_mapping_data['module'],
                    $this->_mapping_data['class']
                ]),
                implode('.', 
                [
                    $this->_mapping_data['module'],
                    $this->_mapping_data['class'],
                    $this->_mapping_data['function']
                ])
            ];
            foreach ($find_middleware_index as $find_middleware) {
                if(array_key_exists($find_middleware,$middleware_mapping)) {
                    if(!empty($middleware_mapping[$find_middleware])) {
                        if(is_array($middleware_mapping[$find_middleware])) {
                            foreach ($middleware_mapping[$find_middleware] as $mm_key => $mm_value) {
                                if(!empty($mm_value)) {
                                    $this->middleware($mm_value);
                                }
                            }
                        }
                        else {
                            $this->middleware($middleware_mapping[$find_middleware]);
                        }
                    }
                }
            }
        }
    }
    
    public function index() {
        // redirect to default url if need
        if((strtolower(\Illuminate\Support\Facades\Request::method()) == 'get')) {
            if($this->_mapping_data['module'] == 'web' && !in_array($this->_mapping_data['current_url'],$this->_mapping_data['multi_url'])) {
               header('Location: '.$this->_mapping_data['multi_url'][$this->_mapping_data['current_lang_web']]);
               exit();
            }
        }
        
        // custom link mode
        if($this->_mapping_data['module'] == 'web' && Config::get('app_portal.custom_link')) {
            if($this->_mapping_data['function'] != 'index') {
                array_unshift($this->_mapping_data['parameters'], $this->_mapping_data['function']);
            }
            array_unshift($this->_mapping_data['parameters'], $this->_mapping_data['class']);
            $this->_mapping_data['class'] = 'main';
            $this->_mapping_data['function'] = 'index';
        }
        
        // index will be ignored function = interger, string (add, edit, delete, details)
        else if(is_numeric($this->_mapping_data['function']) || ($this->_mapping_data['module'] == 'admin' && in_array($this->_mapping_data['function'], ['add', 'edit', 'delete', 'details']))){
            if($this->_mapping_data['function'] != 'index') {
                array_unshift($this->_mapping_data['parameters'], $this->_mapping_data['function']);
            }
            $this->_mapping_data['function'] = 'index';
        }
        
        // call target class & function if exist
        $target_class = 'App\\Http\\Controllers\\'.ucfirst($this->_mapping_data['module']).'\\'.ucwords(ucwords($this->_mapping_data['class'],'_'),'-');
        if(class_exists($target_class) && method_exists($target_class, $this->_mapping_data['function'])) {
            return call_user_func_array(array((new $target_class($this->_mapping_data)), $this->_mapping_data['function']), $this->_mapping_data['parameters']);
        }
        
        // return 404 if not found
        return abort(404);
    }
    
    private function getMappingData() {
        // lang config
        $config_support_lang = Config::get('app_portal.support_lang');
        $config_default_lang_index = Config::get('app_portal.default_lang_index');
        
        // init default value
        $current_lang_index = $config_default_lang_index;
        $current_lang_web = Config::get('app_portal.default_lang');
        $current_module = 'web';
        $current_class = 'home';
        $current_function = 'index';
        $current_parameters = [];
        
        // url segments
        $segments = \Illuminate\Support\Facades\Request::segments();
        $app_source_dir = Config::get('app.source_dir');
        if(!empty($app_source_dir)) {
            $segments = explode('/', trim(preg_replace('/^(('.(str_ireplace('/','\/', $app_source_dir)).')(\/|$))/i', '', implode('/', $segments)), '/'));
        }
        $segments = array_values(array_filter($segments));
   
        // remove non alphanumeric
        if(!empty($segments)) {
            foreach ($segments as $key => $segment) {
                $segments[$key] = strtolower(trim(preg_replace('/[^0-9a-zA-Z_\-\/]/ui', '', $segment)));
            }
            $segments = array_filter($segments);
        }
        
        // assign default value if empty
        if(empty($segments)) {
            $segments = (count($config_support_lang) > 1)?[$current_lang_web]:[$current_class];
        }
        
        // find target class & function
        $parameters_index = 2;
        if(reset($segments) == 'admin') {
            $current_module = 'admin';
            if(!empty($segments[1])) {
                $current_class = $segments[1];
            }
            if(!empty($segments[2])) {
                $current_function = $segments[2];
            }
            $parameters_index = 3;
        }
        else {
            $in_default_lang = false;
            foreach ($config_support_lang as $lang_key => $lang) {
                if(reset($segments) == $lang['code']) {
                    $in_default_lang = true;
                    break;
                }
            }
            if($in_default_lang) {
                $current_lang_web = reset($segments);
                if(!empty($segments[1])) {
                    $current_class = $segments[1];
                }
                if(!empty($segments[2])) {
                    $current_function = $segments[2];
                }
                $parameters_index = 3;
            }
            else {
                if(!empty($segments[0])) {
                    $current_class = $segments[0];
                }
                if(!empty($segments[1])) {
                    $current_function = $segments[1];
                }
            }
        }
        
        // others parameters
        if(count($segments) > $parameters_index) {
            foreach ($segments as $key => $segment) {
                if($key >= $parameters_index) {
                    $current_parameters[] = $segment;
                }
            }
        }
        
        // set url & lang index
        $other_parameters = [];
        if((strtolower(\Illuminate\Support\Facades\Request::method()) == 'get')) {
            $other_parameters = \Illuminate\Support\Facades\Request::input();
        }

        $app_url = trim(preg_replace('/([\/]+)$/i', '', Config::get('app.url')));
        if (app()->environment('local')) {
            $requestRoot = \Illuminate\Support\Facades\Request::root();
            if (!empty($requestRoot)) {
                $app_url = rtrim($requestRoot, '/');
            }
        }
        $current_url = implode('/', [$app_url, implode('/', $segments)]).(($other_parameters)?('?'.http_build_query($other_parameters)):'');
        $multi_url = [];
        if($current_module == 'web') {
            foreach ($config_support_lang as $lang_key => $lang) {
                $multi_url[$lang['code']] = implode('/', array_filter([
                    $app_url,
                    (count($config_support_lang) > 1)?$lang['code']:'',
                    $current_class,
                    (($current_function != 'index' || !empty($current_parameters))?$current_function:''),
                    implode('/', $current_parameters)
                ])).(($other_parameters)?('?'.http_build_query($other_parameters)):'');
            }
            
            foreach ($config_support_lang as $support_lang_key => $support_lang) {
                if($support_lang['code'] == $current_lang_web) {
                    $current_lang_index = !empty($support_lang['content_lang_index'])
                        ? (int)$support_lang['content_lang_index']
                        : $support_lang_key;
                    break;
                }
            }
        }

        $current_url = preg_replace('/(\/home\/\?|\/home\?)/i', '?', $current_url);
        $current_url = preg_replace('/(\/home\/$|\/home$)/i', '', $current_url);
        if(!empty($multi_url)) {
            foreach ($multi_url as $url_key => $url) {
                $multi_url[$url_key] = preg_replace('/(\/home\/\?|\/home\?)/i', '?', $url);
                $multi_url[$url_key] = preg_replace('/(\/home\/$|\/home$)/i', '', $multi_url[$url_key]);
            }
        }

        // return
        return [
            'support_lang'              =>  $config_support_lang,
            'default_lang_index'        =>  $config_default_lang_index,
            
            'current_lang_index'        =>  $current_lang_index,
            'current_lang_web'          =>  $current_lang_web,
            'current_lang_admin'        =>  Config::get('app_portal.default_lang_admin'),

            'app_url'                   =>  $app_url,
            'base_url'                  =>  $app_url.(($current_module == 'admin')?'/admin':((count($config_support_lang) > 1)?('/'.$current_lang_web):'')),
            'current_url'               =>  $current_url,
            'multi_url'                 =>  $multi_url,
            
            'module'                    =>  $current_module,
            'class'                     =>  $current_class,
            'function'                  =>  $current_function,
            'parameters'                =>  $current_parameters
        ];
    }
}