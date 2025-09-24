<?php
/* Add 'admin.authn' => \App\Http\Middleware\AdminAuthn::class to Kernel.php, $middlewareGroups */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminAuthn { 
    
    public function handle(Request $request, Closure $next) {
        // ip whitelist
        $ip_whitelist = (new \App\Models\Setting(null))->getByName('ip_whitelist');
        if(!empty($ip_whitelist)) {
            $ip_whitelist = array_filter(explode(PHP_EOL, $ip_whitelist));
            $match_ip = false;
            foreach($ip_whitelist as $ip) {
                if(trim($this->getCurrentIP()) == trim($ip)) {
                    $match_ip = true;
                    break;
                }
            }
            if(!$match_ip) {
                if(strtolower($request->method()) == 'post') {
                    echo json_encode([
                        'status'    =>  403,
                        'message'   =>  'Permission denied.' 
                    ]);
                }
                else {
                    return abort(403);
                }
                exit();
            }
        }

        // url segments
        $current_admin_class = 'home';
        $current_admin_function = 'index';
        $segments = \Illuminate\Support\Facades\Request::segments();
        $app_source_dir = \Illuminate\Support\Facades\Config::get('app.source_dir');
        if(!empty($app_source_dir)) {
            $segments = explode('/', trim(preg_replace('/^(('.(str_ireplace('/','\/', $app_source_dir)).')(\/|$))/i', '', implode('/', $segments)), '/'));
        }
        $segments = array_values(array_filter($segments));
        if(!empty($segments[1])) {
            $current_admin_class = trim(strtolower($segments[1]));
            if(!empty($segments[2])) {
                $current_admin_function = trim(strtolower($segments[2]));
            }
        }

        // current user
        $user_data = null;
        $current_admin_access_token = \Illuminate\Support\Facades\Session::get('_admin_'.(\Illuminate\Support\Facades\Config::get('app_portal.application_uid')).'_admin_access_token');
        if(!empty($current_admin_access_token)) {
            $user_data = (new \App\Models\User(null))->getByToken($current_admin_access_token);
        }

        // user privilege
        if(!in_array($current_admin_class, ['authn'])) {
            if(!empty($user_data)) {
                if($user_data['id'] > 1 && $current_admin_class != 'profile') {
                    $role_data = (new \App\Models\User(null))->getRoleByID($user_data['role_id']);
                    if(!empty($role_data)) {
                        $user_data['allowed'] = $role_data['allowed'];
                    }
                    else {
                        $user_data['allowed'] = [];
                    }
                    if(empty($user_data['allowed'][$current_admin_class])) {
                        if(strtolower($request->method()) == 'post') {
                            echo json_encode([
                                'status'    =>  403,
                                'message'   =>  'Permission denied.' 
                            ]);
                        }
                        else {
                            if($current_admin_class == 'home') {
                                header('Location: '.trim(implode('/', [
                                    preg_replace('/([\/]+)$/i', '', (\Illuminate\Support\Facades\Config::get('app.url'))),
                                    'admin',
                                    'profile'
                                ])));
                            }
                            else {
                                return abort(403);
                            }
                        }
                        exit();
                    }
                }
            }
            else {
                if(strtolower($request->method()) == 'post') {
                    echo json_encode([
                        'status'    =>  403,
                        'message'   =>  'You don not have permission to access, please login first.' 
                    ]);
                }
                else {
                    header('Location: '.trim(implode('/', [
                        preg_replace('/([\/]+)$/i', '', (\Illuminate\Support\Facades\Config::get('app.url'))),
                        'admin',
                        'authn'
                    ])));
                }
                exit();
            }
        }
        else if(!empty($user_data) && strtolower($request->method()) != 'post' && $current_admin_function != 'logout') {
            header('Location: '.trim(implode('/', [
                preg_replace('/([\/]+)$/i', '', (\Illuminate\Support\Facades\Config::get('app.url'))),
                'admin',
                'profile'
            ])));
            exit();
        }
        
        // next
        return $next($request);
    }
    
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