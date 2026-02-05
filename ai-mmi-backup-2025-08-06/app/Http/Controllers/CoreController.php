<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;

// set time zone
date_default_timezone_set('Asia/Hong_Kong');

class CoreController extends Controller {
    // global variable
    protected $_today_datetime = null;
    protected $_today_date = null;
    
    protected $_mapping_data = null;
    
    protected $_current_lang_code = 'en';
    protected $_current_lang_index = 0;
    
    protected $_current_user = null;
    protected $_current_member = null;
    
    protected $_css_files = [];
    protected $_js_files = [];
    protected $_css_extra_files = [];
    protected $_js_extra_files = [];
    
    protected $_meta_data = [];
    protected $_navigator_data = [];
    
    protected $_page_index = '';
    protected $_page_subindex = '';
    protected $_page_back_url = '';
    protected $_page_global_lang = [];
    protected $_page_lang = [];
    protected $_page_setting = [];
    protected $_page_options = [];
    protected $_page_data = [];
    protected $_page_csrf_token = '';  

    protected $_page_get_data = [];
    protected $_page_post_data = [];
    
    protected $_page_readonly = false;
    protected $_page_view_history = [];

    // init
    public function __construct($data = null) {
        $this->_mapping_data = $data;
        // If mapping data is empty, allow the controller to be instantiated by the container
        // (for artisan commands, route:list, middleware gathering, etc.) without aborting.
        // Full initialization (loading language, page meta, session handling) requires
        // valid mapping data and will be skipped when it's absent.
        if (empty($this->_mapping_data)) {
            // keep mapping_data as empty array to avoid PHP warnings elsewhere
            $this->_mapping_data = [];
            return;
        }
        
        // set current date & time
        $this->_today_datetime = date('Y-m-d H:i:s');
        $this->_today_date = date('Y-m-d', strtotime($this->_today_datetime));
        
        // set current lang & load lang files
        $this->_current_lang_index = $data['current_lang_index'];
        $this->_current_lang_code = ($data['module'] == 'admin')?$data['current_lang_admin']:$data['current_lang_web'];
        \Illuminate\Support\Facades\App::setLocale($this->_current_lang_code);
       
        foreach ([$data['module'], 'global'] as $value) {
            if(file_exists(resource_path('lang/'.$this->_current_lang_code.'/_'.$value.'.php'))) {
                $lang_file = trans('_'.$value);
                // max two level
                if(!empty($lang_file) && is_array($lang_file)) {
                    foreach ($lang_file as $lang_file_key => $lang_file_value) {
                        if(is_array($lang_file_value)) {
                            foreach ($lang_file_value as $lang_file_sub_key => $lang_file_sub_value) {
                                if(is_int($lang_file_sub_key)) {
                                    if($value == 'global') {
                                        $this->_page_global_lang[$lang_file_key][$lang_file_sub_key] = $lang_file_sub_value;
                                    }
                                    else {
                                        $this->_page_lang[$lang_file_key][$lang_file_sub_key] = $lang_file_sub_value;
                                    }
                                }
                                else {
                                    if($value == 'global') {
                                        $this->_page_global_lang[$lang_file_key.'.'.$lang_file_sub_key] = $lang_file_sub_value;
                                    }
                                    else {
                                        $this->_page_lang[$lang_file_key.'.'.$lang_file_sub_key] = $lang_file_sub_value;
                                    }
                                }
                            }
                        }
                        else {
                            if($value == 'global') {
                                $this->_page_global_lang[$lang_file_key] = $lang_file_value;
                            }
                            else {
                                $this->_page_lang[$lang_file_key] = $lang_file_value;
                            }
                        }
                    }
                }
            }
        }
        /* Pass language to js variable */
        /* <script>const _page_global_lang = JSON.parse('<?php echo json_encode($_page_global_lang); ?>');</script> */

        $this->_page_get_data = Request::input();
        if((strtolower(Request::method()) == 'post')) {
            $this->_page_post_data = Request::post();
        }
        
        //default page meta
        $this->pageMeta();
    }
    
    // get & post
    protected function getParamValue($name = '', $default_value = '') {
        $name = (string)$name;
        $get_data = $this->_page_get_data;
        return (!empty($name))?((!empty($get_data[$name]))?($get_data[$name]):$default_value):$get_data;
    }

    protected function postParamValue($name = '', $default_value = '') {
        $name = (string)$name;
        $post_data = $this->_page_post_data;
        return (!empty($name))?((!empty($post_data[$name]))?($post_data[$name]):$default_value):$post_data;
    }
    
    // load model
    protected function loadModel($name, $parameters = []) {
        $parameters = array_merge([
            'is_backend'    =>  ($this->_mapping_data['module']=='admin')?true:false,
            'current_user'  =>  $this->_current_user
        ], $parameters);
        $model = 'App\\Models\\'.ucwords(ucwords(strtolower($name),'_'),'-');
        return new $model($parameters);
    }

    // session
    protected function setSession($data = [],$prefix = true) {
        /*
        $data = [
            'username'  => 'johndoe',
            'email'     => 'johndoe@some-site.com'
        ];
        */
        $data = (array)$data;
        if(!empty($data) && is_array($data)) {
            foreach ($data as $data_key => $data_value) {
                if(!empty($data_value)) {
                    Session::put(implode('_', array_filter(
                        [
                            (($prefix)?('_'.$this->_mapping_data['module']):''),
                            \Illuminate\Support\Facades\Config::get('app_portal.application_uid'),
                            $data_key
                        ])), 
                        $data_value
                    );
                }
                else {
                    $this->delSession(implode('_', array_filter(
                    [
                        (($prefix)?('_'.$this->_mapping_data['module']):''),
                        \Illuminate\Support\Facades\Config::get('app_portal.application_uid'),
                        $data_key
                    ])), $prefix);
                }
            }
            Session::save();
        }
    }
    
    protected function getSession($name = '', $prefix = true) {
        $name = (string)$name;
        if(!empty($name)) {
            return Session::get(implode('_', array_filter(
            [
                (($prefix)?('_'.$this->_mapping_data['module']):''),
                \Illuminate\Support\Facades\Config::get('app_portal.application_uid'),
                $name
            ])));
        }
        else {
            return Session::all();
        }
    }
    
    protected function getSessionOnce($name = '', $prefix = true) {
        $name = (string)$name;
        $value = $this->getSession($name, $prefix);
        $this->delSession($name, $prefix);
        return $value;
    }
    
    protected function delSession($name = '', $prefix = true) {
        /* $name = 'username' or $name = ['username','email'] */
        if(!empty($name)) {
            if(is_array($name)) {
                foreach ($name as $name_child) {
                    Session::forget(implode('_', array_filter(
                    [
                        (($prefix)?('_'.$this->_mapping_data['module']):''),
                        \Illuminate\Support\Facades\Config::get('app_portal.application_uid'),
                        $name_child
                    ])));
                }
            }
            else {
                Session::forget(implode('_', array_filter(
                [
                    (($prefix)?('_'.$this->_mapping_data['module']):''),
                     \Illuminate\Support\Facades\Config::get('app_portal.application_uid'),
                    $name
                ])));
            }
        }
        else {
            Session::flush();
        }
        Session::save();
    }
    
    protected function delSessionPrefix($pname = '') {
        if(!empty($pname)) {
            $all_session = $this->getSession();
            if(!empty($all_session)) {
                foreach ($all_session as $session_key => $session) {
                    preg_match('/^(_'.$pname.'_)(.*)$/i', $session_key, $match);
                    if(!empty($match)) {
                        Session::forget($session_key);
                    }
                }
            }
        }
        Session::save();
    }

    // page
    protected function pageCss($path = null, $prefix = 'asset/css', $module = true) {
        if(!empty($path)) {
            if(is_array($path)) {
                foreach ($path as $path_key => $path_value) {
                    $path_value = (string)$path_value;
                    if(!preg_match('/^(https|http)(:\/\/)(.*)$/i', $path_value, $external_match) && !preg_match('/^(.*)((\.css$)|(\.css?(.*)$))/i', $path_value, $suffix_match)) {
                        $path_value = $path_value.'.css';
                    }
                    if(!preg_match('/^(https|http)(:\/\/)(.*)$/i', $path_value, $external_match)) {
                        $path_value = implode('/', array_filter([
                            (!empty($prefix))?$prefix:'',
                            ($module)?$this->_mapping_data['module']:'',
                            $path_value
                        ]));
                    }
                    $path_value = trim($path_value);
                    if(file_exists($path_value) || preg_match('/^(https|http)(:\/\/)(.*)$/i', $path_value, $external_match)) {
                        $this->_css_files[] = $path_value;
                    }
                }
            }
            else {
                $path_value = (string)$path;
                if(!preg_match('/^(https|http)(:\/\/)(.*)$/i', $path_value, $external_match) && !preg_match('/^(.*)((\.css$)|(\.css?(.*)$))/i', $path_value, $suffix_match)) {
                    $path_value = $path_value.'.css';
                }
                if(!preg_match('/^(https|http)(:\/\/)(.*)$/i', $path_value, $external_match)) {
                    $path_value = implode('/', array_filter([
                        (!empty($prefix))?$prefix:'',
                        ($module)?$this->_mapping_data['module']:'',
                        $path_value
                    ]));
                }
                $path_value = trim($path_value);
                if(file_exists($path_value) || preg_match('/^(https|http)(:\/\/)(.*)$/', $path_value, $external_match)) {
                    $this->_css_files[] = $path_value;
                }
            }
        }
    }
    
    protected function pageScript($path = null, $prefix = 'asset/js', $module = true) {
        if(!empty($path)) {
            if(is_array($path)) {
                foreach ($path as $path_key => $path_value) {
                    $path_value = (string)$path_value;
                    if(!preg_match('/^(https|http)(:\/\/)(.*)$/i', $path_value, $external_match) && !preg_match('/^(.*)((\.js$)|(\.js?(.*)$))/i', $path_value, $suffix_match)) {
                        $path_value = $path_value.'.js';
                    }
                    if(!preg_match('/^(https|http)(:\/\/)(.*)$/i', $path_value, $external_match)) {
                        $path_value = implode('/', array_filter([
                            (!empty($prefix))?$prefix:'',
                            ($module)?$this->_mapping_data['module']:'',
                            $path_value
                        ]));
                    }
                    $path_value = trim($path_value);
                    if(file_exists($path_value) || preg_match('/^(https|http)(:\/\/)(.*)$/', $path_value, $path_match)) {
                        $this->_js_files[] = $path_value;
                    }
                }
            }
            else {
                $path_value = (string)$path;
                if(!preg_match('/^(https|http)(:\/\/)(.*)$/i', $path_value, $external_match) && !preg_match('/^(.*)((\.js$)|(\.js?(.*)$))/i', $path_value, $suffix_match)) {
                    $path_value = $path_value.'.js';
                }
                if(!preg_match('/^(https|http)(:\/\/)(.*)$/i', $path_value, $external_match)) {
                    $path_value = implode('/', array_filter([
                        (!empty($prefix))?$prefix:'',
                        ($module)?$this->_mapping_data['module']:'',
                        $path_value
                    ]));
                }
                $path_value = trim($path_value);
                if(file_exists($path_value) || preg_match('/^(https|http)(:\/\/)(.*)$/', $path_value, $external_match)) {
                    $this->_js_files[] = $path_value;
                }
            }
        }
    }
    
    protected function pageCssExtra($path = null, $prefix = 'asset/css', $module = true) {
        if(!empty($path)) {
            if(is_array($path)) {
                foreach ($path as $path_key => $path_value) {
                    $path_value = (string)$path_value;
                    if(!preg_match('/^(https|http)(:\/\/)(.*)$/i', $path_value, $external_match) && !preg_match('/^(.*)((\.css$)|(\.css?(.*)$))/i', $path_value, $suffix_match)) {
                        $path_value = $path_value.'.css';
                    }
                    if(!preg_match('/^(https|http)(:\/\/)(.*)$/i', $path_value, $external_match)) {
                        $path_value = implode('/', array_filter([
                            (!empty($prefix))?$prefix:'',
                            ($module)?$this->_mapping_data['module']:'',
                            $path_value
                        ]));
                    }
                    $path_value = trim($path_value);
                    if(file_exists($path_value) || preg_match('/^(https|http)(:\/\/)(.*)$/i', $path_value, $external_match)) {
                        $this->_css_extra_files[] = $path_value;
                    }
                }
            }
            else {
                $path_value = (string)$path;
                if(!preg_match('/^(https|http)(:\/\/)(.*)$/i', $path_value, $external_match) && !preg_match('/^(.*)((\.css$)|(\.css?(.*)$))/i', $path_value, $suffix_match)) {
                    $path_value = $path_value.'.css';
                }
                if(!preg_match('/^(https|http)(:\/\/)(.*)$/i', $path_value, $external_match)) {
                    $path_value = implode('/', array_filter([
                        (!empty($prefix))?$prefix:'',
                        ($module)?$this->_mapping_data['module']:'',
                        $path_value
                    ]));
                }
                $path_value = trim($path_value);
                if(file_exists($path_value) || preg_match('/^(https|http)(:\/\/)(.*)$/', $path_value, $external_match)) {
                    $this->_css_extra_files[] = $path_value;
                }
            }
        }
    }
    
    protected function pageScriptExtra($path = null, $prefix = 'asset/js', $module = true) {
        if(!empty($path)) {
            if(is_array($path)) {
                foreach ($path as $path_key => $path_value) {
                    $path_value = (string)$path_value;
                    if(!preg_match('/^(https|http)(:\/\/)(.*)$/i', $path_value, $external_match) && !preg_match('/^(.*)((\.js$)|(\.js?(.*)$))/i', $path_value, $suffix_match)) {
                        $path_value = $path_value.'.js';
                    }
                    if(!preg_match('/^(https|http)(:\/\/)(.*)$/i', $path_value, $external_match)) {
                        $path_value = implode('/', array_filter([
                            (!empty($prefix))?$prefix:'',
                            ($module)?$this->_mapping_data['module']:'',
                            $path_value
                        ]));
                    }
                    $path_value = trim($path_value);
                    if(file_exists($path_value) || preg_match('/^(https|http)(:\/\/)(.*)$/', $path_value, $path_match)) {
                        $this->_js_extra_files[] = $path_value;
                    }
                }
            }
            else {
                $path_value = (string)$path;
                if(!preg_match('/^(https|http)(:\/\/)(.*)$/i', $path_value, $external_match) && !preg_match('/^(.*)((\.js$)|(\.js?(.*)$))/i', $path_value, $suffix_match)) {
                    $path_value = $path_value.'.js';
                }
                if(!preg_match('/^(https|http)(:\/\/)(.*)$/i', $path_value, $external_match)) {
                    $path_value = implode('/', array_filter([
                        (!empty($prefix))?$prefix:'',
                        ($module)?$this->_mapping_data['module']:'',
                        $path_value
                    ]));
                }
                $path_value = trim($path_value);
                if(file_exists($path_value) || preg_match('/^(https|http)(:\/\/)(.*)$/', $path_value, $external_match)) {
                    $this->_js_extra_files[] = $path_value;
                }
            }
        }
    }

    protected function pageNavigator($name = '', $url = '') {
        $name = (string)$name;
        $url = (string)$url;
        if(!empty($name)) {
            $this->_navigator_data[] = [
                'name'  =>  $name,
                'url'   =>  $url
            ];
        }
    }
    
    protected function pageMeta($data = [], $use_default = true, $prefix = true) {
        $setting_model = $this->loadModel('setting');
        
        // default title (normalize inputs to strings to avoid array->string warnings)
        $default_app_title = $setting_model->getByName('meta_title', $this->_current_lang_index);
        $title_parts = [
            ((!empty($data['title']))?$data['title']:''),
            ((!empty($default_app_title) && $prefix)?$default_app_title:'')
        ];
        $normalized_parts = [];
        foreach ($title_parts as $part) {
            if (is_array($part)) {
                // flatten array parts to a single string (preserve readable separators)
                $part = implode(' ', array_filter(array_map(function($p){ return is_scalar($p) ? (string)$p : ''; }, $part)));
            } elseif (is_object($part)) {
                $part = method_exists($part, '__toString') ? (string)$part : '';
            } else {
                $part = (string)$part;
            }
            $part = trim($part);
            if ($part !== '') { $normalized_parts[] = $part; }
        }
        $data['title'] = implode(' - ', array_unique($normalized_parts));

        // default description
        if(!isset($data['description']) || (empty($data['description']) && $use_default)) {
            $data['description'] = $setting_model->getByName('meta_description', $this->_current_lang_index);
        }

        // default image
        if(!isset($data['image']) || (empty($data['image']) && $use_default)) {
            $data['image'] = $setting_model->getByName('meta_image', $this->_current_lang_index);
        }

        // normalize description/image/url to scalar strings to avoid passing arrays to toPlainText()
        $desc_val = isset($data['description']) ? $data['description'] : '';
        if (is_array($desc_val)) {
            $desc_val = implode(' ', array_filter(array_map('strval', $desc_val)));
        } elseif (is_object($desc_val)) {
            $desc_val = method_exists($desc_val, '__toString') ? (string)$desc_val : '';
        } else {
            $desc_val = (string)$desc_val;
        }

        $img_val = isset($data['image']) ? $data['image'] : '';
        if (is_array($img_val)) {
            $img_val = implode(' ', array_filter(array_map('strval', $img_val)));
        } elseif (is_object($img_val)) {
            $img_val = method_exists($img_val, '__toString') ? (string)$img_val : '';
        } else {
            $img_val = (string)$img_val;
        }

        $url_val = isset($data['url']) ? $data['url'] : '';
        if (is_array($url_val)) {
            $url_val = implode(' ', array_filter(array_map('strval', $url_val)));
        } elseif (is_object($url_val)) {
            $url_val = method_exists($url_val, '__toString') ? (string)$url_val : '';
        } else {
            $url_val = (string)$url_val;
        }

        $this->_meta_data['title'] = (!empty($data['title'])) ? $this->toPlainText($data['title']) : '';
        $this->_meta_data['description'] = (!empty($desc_val)) ? $this->toPlainText($desc_val) : '';
        $this->_meta_data['image'] = (!empty($img_val)) ? $this->toPlainText($img_val) : '';
        $this->_meta_data['url'] = (!empty($url_val)) ? $this->toPlainText($url_val) : $this->_mapping_data['current_url'];
        
        return $this;
    }

    protected function pageIndex($name = '') {
        $this->_page_index = (string)$name;
        return $this;
    }
    
    protected function pageSubIndex($name = '') {
        $this->_page_subindex = (string)$name;
        return $this;
    }
    
    protected function pageBack($url = '') {
        $this->_page_back_url = (string)$url;
        return $this;
    }
    
    protected function pageSetting($page_setting = []) {
        $this->_page_setting = (array)$page_setting;
        return $this;
    }

    protected function pageOptions($page_options = []) {
        $this->_page_options = (array)$page_options;
        return $this;
    }
    
    protected function pageData($page_data = []) {
        $this->_page_data = array_merge($this->_page_data, (array)$page_data);
        return $this;
    }
    
    protected function pageView($view_name = '', $included_header_footer = true, $refresh_token = true) {
        // assign default view if view name is empty
        $view_name = strtolower((!empty($view_name))?$view_name:($this->_mapping_data['class'].'_'.$this->_mapping_data['function']));
        if(!(\Illuminate\Support\Facades\View::exists($this->_mapping_data['module'].'.'.$view_name))) {
            $view_name = $this->_mapping_data['class'].'.'.$this->_mapping_data['function'];
        }
        if(!(\Illuminate\Support\Facades\View::exists($this->_mapping_data['module'].'.'.$view_name))) {
            $view_name = $this->_mapping_data['class'];
        }

        // checking
        if(\Illuminate\Support\Facades\View::exists($this->_mapping_data['module'].'.'.$view_name)) {
            
            // auto load js & css
            $this->pageCss('common');
            $this->pageScript('common');

            $this->pageCss($this->_mapping_data['class']);
            $this->pageCss($this->_mapping_data['class'].'_'.$this->_mapping_data['function']);
            $this->pageScript($this->_mapping_data['class']);
            $this->pageScript($this->_mapping_data['class'].'_'.$this->_mapping_data['function']);
            
            if($this->_mapping_data['module'] == 'admin') {
                if($view_name == 'template.list') {
                    $this->pageCssExtra('template_list.css');
                    $this->pageScriptExtra('template_list.js');
                }
                else if($view_name == 'template.form') {
                    $this->pageCssExtra('template_form.css');
                    $this->pageScriptExtra('template_form.js');
                }
            }
 
            // set token
            $this->_page_csrf_token = md5(md5(uniqid()).rand(10000,99999));
            if(!empty($refresh_token)) {
                $this->setSession(['itoken' => $this->_page_csrf_token]);
            }
            else if(!empty($this->getSession('itoken'))) {
                $this->_page_csrf_token = $this->getSession('itoken');
            }
            
            // previous url
            if($this->_mapping_data['module'] == 'admin') {
                if(!($this->_mapping_data['class'] == 'media_files' && !empty($this->getParamValue('inline')))) {
                    if(empty($this->_page_back_url)) {
                        $this->_page_view_history = $this->getSession(($this->_mapping_data['class'].'_'.$this->_mapping_data['function']).'_page_view_history');
                        if(empty($this->_page_view_history)) { $this->_page_view_history = []; }

                        $find_history_index = md5($this->_mapping_data['current_url']);
                        if(empty($this->_page_view_history[$find_history_index])) {
                            $this->_page_view_history[$find_history_index] = $this->_mapping_data['current_url'];
                        }
                        else {
                            $revised_page_view_history = [];
                            foreach ($this->_page_view_history as $histroy_key => $history_url) {
                                $revised_page_view_history[$histroy_key] = $history_url;
                                if($find_history_index == $histroy_key) {
                                    break;
                                }
                            }
                            $this->_page_view_history = $revised_page_view_history;
                        }
                        $this->setSession([$this->_mapping_data['class'].'_'.$this->_mapping_data['function'].'_page_view_history' => $this->_page_view_history]);
                        if(count($this->_page_view_history) > 1) {
                            $this->_page_back_url = (array_slice($this->_page_view_history, -2, 1));
                            $this->_page_back_url = reset($this->_page_back_url);
                        }
                    }
                    $this->setSession(['my_previous_url' => $this->_page_back_url]);
                }
            }

            // return view
            return \Illuminate\Support\Facades\View::make($this->_mapping_data['module'].'.'.$view_name,[
                '_included_header_footer'   =>  $included_header_footer,
                '_token'                    =>  csrf_token(),
                '_current_user'             =>  $this->_current_user,
                '_current_member'           =>  $this->_current_member,
                
                '_mapping_data'             =>  $this->_mapping_data,
                '_current_lang_index'       =>  $this->_current_lang_index,
                '_current_lang_code'        =>  $this->_current_lang_code,
                
                '_page_base_url'            =>  $this->_mapping_data['base_url'],
                '_page_css_files'           =>  array_unique(array_filter(array_merge($this->_css_files, $this->_css_extra_files))),
                '_page_js_files'            =>  array_unique(array_filter(array_merge($this->_js_files, $this->_js_extra_files))),
                '_page_meta_data'           =>  $this->_meta_data,
                '_page_navigator_data'      =>  $this->_navigator_data,
                
                '_page_csrf_token'          =>  $this->_page_csrf_token,
                '_page_index'               =>  (!empty($this->_page_index))?$this->_page_index:$this->_mapping_data['class'],
                '_page_subindex'            =>  (!empty($this->_page_subindex))?$this->_page_subindex:$this->_mapping_data['function'],
                '_page_back_url'            =>  $this->_page_back_url,
                '_page_global_lang'         =>  $this->_page_global_lang,
                '_page_lang'                =>  $this->_page_lang,
                '_page_setting'             =>  $this->_page_setting,
                '_page_options'             =>  $this->_page_options,
                '_page_data'                =>  $this->_page_data,
                '_page_get_data'            =>  $this->getParamValue(),
                '_page_post_data'           =>  $this->postParamValue(),
                '_page_error_message'       =>  $this->getSessionOnce('error_message'),
                '_page_success_message'     =>  $this->getSessionOnce('success_message'),
                '_page_readonly'            =>  $this->_page_readonly
            ]);
            
            exit();
        }
        
        // return 404 if not found
        return abort(404);
    }
    
    protected function pageAction(callable $callback, $extra_param = [], $model_object = null, $options = []) {
        if(!empty($this->_page_post_data)) {
            // before action need to check token
            $next = false;
            $session_itoken = $this->getSession('itoken');
            $post_itoken = explode('%',base64_decode($this->postParamValue('itoken', '')));
            if(!empty($session_itoken) && (!empty($post_itoken) && is_array($post_itoken) && !empty($post_itoken[0]) && !empty($post_itoken[1]))) {
                if(trim(md5(md5(md5('iweb@'.((!empty($_SERVER['SERVER_NAME']))?$_SERVER['SERVER_NAME']:'/')).'@'.$session_itoken).'#dt'.$post_itoken[1])) == trim($post_itoken[0])) {
                    $next = true;
                }
            }

            //sleep(1);
            
            if(empty($next)) {
                $this->pageResult([
                    'status'    =>  408,
                    'message'   =>  'CSRF Token Expired',
                ], true);
            }
            else {
                $callback($extra_param, $model_object, $options);
                exit();
            }
        }
    }

    // 200: OK, 400: Bad Request, 403: Forbidden, 404: Not Found, 408: Time Out, 500: Server Error
    protected function pageResult($data = [], $enable_session = false, $return = false) {
        $result = [
            'status'    =>  200,
            'message'   =>  '',
            'url'       =>  ''
        ];
        if(is_array($data)) {
            $result = array_merge($result, $data);
        }
        
        // set message
        if($enable_session && !empty($result['message'])) {
            if((int)$result['status'] != 200) {
                $this->setSession(['error_message' => $result['message']]);
            }
            else {
                $this->setSession(['success_message' => $result['message']]);
            }
        }
        else {
            $this->delSession('error_message');
            $this->delSession('success_message');
        }

        if($return) {
            return $result;
        }
        else {
            echo json_encode($result);
        }
        exit();
    }

    // Other helper functions
    // plain text
    protected function toPlainText($value = '', $no_space = false) {
        // First remove the leading/trialing whitespace
        $value = strip_tags(trim(str_replace('&nbsp;', ' ', $value)));
        // Now remove any doubled-up whitespace
        $value = preg_replace('/\s(?=\s)/', '', $value);
        // Finally, replace any non-space whitespace, with a space
        $value = preg_replace('/[\n\r\t]/', ' ', $value);
        // Echo out: 'This line contains liberal use of whitespace.'
        if($no_space) {
            $value = preg_replace('/\s+/', '', $value);
        }
        return trim($value);
    }
    
    // special character converter
    protected function specialChars($value = null, $encode = true) {
        if(!empty($value)) {
            if(is_array($value)) {
                foreach ($value as $sub_key => $sub_value) {
                    if(is_array($sub_value)) {
                        $value[trim($sub_key)] = $this->specialChars($sub_value, $encode);
                    }
                    else if(!is_numeric($sub_value)) {
                        $value[trim($sub_key)] = ($encode)?htmlspecialchars(trim($sub_value), ENT_QUOTES):htmlspecialchars_decode(trim($sub_value), ENT_QUOTES);
                    }
                }
            }
            else if(!is_numeric($value)) {
                $value = ($encode)?htmlspecialchars(trim($value), ENT_QUOTES):htmlspecialchars_decode(trim($value), ENT_QUOTES);
            }
        }
        return $value;
    }
    
    // url
    protected function toURL($value = [], $prev_url = false) {
        if(is_string($value)) {
            $value = [$value];
        }
        return (!empty($prev_url) && $this->toPrevURL())?$this->toPrevURL():rtrim(implode('/', [$this->_mapping_data['base_url'], implode('/', array_filter($value))]),'/');
    }
    
    protected function toPrevURL() {
        return ((!empty($this->getSession('my_previous_url')))?$this->getSession('my_previous_url'):$this->toURL($this->_mapping_data['class'].(($this->_mapping_data['function']!='index')?('/'.$this->_mapping_data['function']):'')));
    }

    // redirect
    protected function doRedirect($url, $moved_permanently = false) {
        if(!empty($moved_permanently)) {
            header('HTTP/1.1 301 Moved Permanently');
        }
        header('Location: '.$url);
        exit();
    }
    
    // list to array
    protected function optionsToArray($data = [], $index_name = 'id', $lable_name = 'title') {
        if(!empty($data)) {
            $revised_data = [];
            foreach ($data as $key => $value) {
                if(!empty($value[$index_name])) {
                    $mix_name = [];
                    if(is_array($lable_name)) {
                        foreach ($lable_name as $ln) {
                            if (!empty($value[$ln])){
                                $mix_name[] = $value[$ln];
                            }
                        }
                        if(!empty($mix_name)) {
                            $revised_data[$value[$index_name]] = implode(' - ', $mix_name);
                        }
                    }
                    else if (!empty($value[$lable_name])){
                        $revised_data[$value[$index_name]] = $value[$lable_name];
                    }
                }
            }
            return $revised_data;
        }
        return false;
    }
    
    // cache image
    protected function generateImage($data = [], $width = 0, $height = 0, $crop = false) {
        ini_set('memory_limit', '-1');
        
        $image_file_type = ['jpeg', 'jpg', 'gif', 'png', 'bmp'];
        $cache_folder = 'cache';
        if(!file_exists(public_path($cache_folder))){
            @mkdir(public_path($cache_folder), 0755, true);
        }
        
        if(empty($data)) {
            $data = [
                'absolute_path' =>  'asset/image/no-image.jpg',
                'file_path'     =>  'asset/image/no-image.jpg'
            ];
        }
 
        if(!empty($data['file_path'])) {
            $extension = strtolower(pathinfo($data['file_path'], PATHINFO_EXTENSION));
            // image only
            if(in_array(strtolower($extension), $image_file_type)) {
                if(!empty($data['file_attribute']) && !empty($data['file_attribute']['use_crop']) && !empty($data['file_attribute']['width']) && !empty($data['file_attribute']['height'])) {
                    $file_name =  md5(json_encode([
                        'file_path' =>  $data['file_path'],
                        'attribute' =>  
                        [
                            'x'     =>  $data['file_attribute']['x'],
                            'y'     =>  $data['file_attribute']['y'],
                            'w'     =>  $data['file_attribute']['width'],
                            'h'     =>  $data['file_attribute']['height']
                        ]
                    ])).'.'.$extension;
                    if(!file_exists(public_path($cache_folder.'/'.$file_name))) {
                        $thumbnail = \Intervention\Image\Facades\Image::make($data['file_path'])->crop(
                            $data['file_attribute']['width'],
                            $data['file_attribute']['height'],
                            $data['file_attribute']['x'],
                            $data['file_attribute']['y']
                        )->save(public_path($cache_folder.'/'.$file_name));
                    }
                }
                else if($width > 0 && $height > 0) {
                    $file_name =  md5(json_encode([
                        'file_path' =>  $data['file_path'],
                        'attribute' =>  
                        [
                            'w'     =>  $width,
                            'h'     =>  $height,
                        ],
                        'crop'      =>  $crop
                    ])).'.'.$extension;
                    
                    if(empty($crop)) {
                        if(!file_exists(public_path($cache_folder.'/'.$file_name))) {
                            $thumbnail = \Intervention\Image\Facades\Image::make($data['file_path'])->resize($width, $height, function ($constraint) {
                                $constraint->aspectRatio();
                                $constraint->upsize();
                            })->save(public_path($cache_folder.'/'.$file_name));
                        }
                    }
                    else {
                        if(!file_exists(public_path($cache_folder.'/'.$file_name)) || true) {
                            $imageSize = getimagesize($data['file_path']);
                            if ($imageSize) {
                                if($imageSize[0] < $width) {
                                    $height = (int)($imageSize[0]/$width*$height);
                                    $width = (int)$imageSize[0];
                                }
                            }
                            
                            $thumbnail = \Intervention\Image\Facades\Image::make($data['file_path'])->resize(((int)$width*1.2), null, function ($constraint) {
                                $constraint->aspectRatio();
                                $constraint->upsize();
                            });
        
                            $thumbnail->resizeCanvas($width, $thumbnail->height(), 'center', true, '#ffffff')->crop(
                                $width,
                                $height
                            )->save(public_path($cache_folder.'/'.$file_name));
                        }
                    }
                }
                else {
                    list($image_width, $image_height) = getimagesize($data['file_path']);  
                    $file_name =  md5(json_encode([
                        'file_path' => $data['file_path'],
                        'attribute' =>  
                        [
                            'x'     =>  0,
                            'y'     =>  0,
                            'w'     =>  $image_width,
                            'h'     =>  $image_height
                        ],
                        'crop'      =>  $crop
                    ])).'.'.$extension;
                    if(!file_exists(public_path($cache_folder.'/'.$file_name))) {
                        $thumbnail = \Intervention\Image\Facades\Image::make($data['file_path'])->resize($image_width, $image_height, function ($constraint) {
                            $constraint->aspectRatio();
                            $constraint->upsize();
                        })->save(public_path($cache_folder.'/'.$file_name));
                    }
                }

                return ($cache_folder.'/'.$file_name);
            }
        }
        
        return '';
    }
    
    // get ip address
    protected function getCurrentIP() {
        if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
            $ip_address = $_SERVER["HTTP_CLIENT_IP"];
        } elseif(!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
            $ip_address = $_SERVER["HTTP_X_FORWARDED_FOR"];
        } else {
            $ip_address = $_SERVER["REMOTE_ADDR"];
        }
        return $ip_address;
    }
}