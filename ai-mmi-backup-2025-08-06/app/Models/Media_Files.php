<?php
namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

class Media_Files extends BaseModel {
    protected $_media_file_table = 'media_file';
    protected $_is_public = true;

    public function __construct($data = []){
        parent::__construct($data);
    }
    
    public function setMode($public_mode = true) {
        $this->_is_public = (!empty($public_mode))?true:false;
        return $this;
    }
    
    public function getAll($related_token = '', $related_type = 'file', $related_category = '') {
        // init
        $keywords = $this->specialChars($this->getParamValue('keywords'));

        if(is_array($related_token)) {
            $where = 
            [
                [
                    'name'      =>  'related_token', 
                    'operate'   =>  'in', 
                    'value'     =>  $related_token
                ],
                [
                    'name'      =>  'related_type', 
                    'operate'   =>  '=', 
                    'value'     =>  $related_type
                ]
            ];
        }
        else {
            $where = 
            [
                [
                    'name'      =>  'related_token', 
                    'operate'   =>  '=', 
                    'value'     =>  $related_token
                ],
                [
                    'name'      =>  'related_type', 
                    'operate'   =>  '=', 
                    'value'     =>  $related_type
                ]
            ];
        }
        
        if(!empty($related_category)) {
            $where = array_merge($where, 
            [
                [
                    'name'      =>  'related_category', 
                    'operate'   =>  '=', 
                    'value'     =>  $related_category
                ]
            ]);
        }
        
        if(!empty($keywords)) {
            $where = array_merge($where, 
            [
                [
                    'name'      =>  'file_name', 
                    'operate'   =>  'like', 
                    'value'     =>  $keywords
                ]
            ]);
        }
        
        $result = $this->setWhere($where)->setOrder(['related_seq_desc','id_desc'])->setPageSize(10)->queryListData($this->_media_file_table, ((empty($related_token))?true:false));
        if(!empty($related_token)) {
            $result['data'] = $result;
        }

        if(!empty($result['data'])) {
            foreach ($result['data'] as $key => $value) {
                $location = ('upload/'.implode('/', array_filter([$value['related_type'], $value['related_category']])));
                if(!empty($value['related_token'])) {
                    $location = ('upload/'.implode('/', array_filter([$value['related_type'], $value['related_token'], $value['related_category']])));
                }
                $result['data'][$key]['file_absolute_path'] = storage_path($location.'/'.$value['file_path']);
                $result['data'][$key]['is_image'] = 0;
                $result['data'][$key]['image_width'] = 0;
                $result['data'][$key]['image_height'] = 0;
                $result['data'][$key]['file_thumbnail'] = '';
                if(file_exists(public_path($location.'/thumbnail/'.$value['file_path']))) {
                    list($image_width, $image_height) = getimagesize(public_path($location.'/'.$value['file_path']));
                    $result['data'][$key]['is_image'] = 1;
                    $result['data'][$key]['image_width'] = $image_width;
                    $result['data'][$key]['image_height'] = $image_height;
                    $result['data'][$key]['file_thumbnail'] = ($location.'/thumbnail/'.$value['file_path']);
                }
                $result['data'][$key]['file_path'] = ($location.'/'.$value['file_path']);
                $result['data'][$key]['file_size_unit'] = $this->formatSizeUnits($value['file_size']);
            }
        }
        
        if(!empty($related_token) && isset($result['data'])) {
            $result = $result['data'];
        }
 
        return $result;
    }
    
    public function getByID($media_file_id = 0) {
        $result = $this->setWhere(
        [
            'name'      =>  'id', 
            'operate'   =>  '=', 
            'value'     =>  (int)$media_file_id
        ])->queryOneData($this->_media_file_table);
        
        if(!empty($result)) {
            $location = ('upload/'.implode('/', array_filter([$result['related_type'], $result['related_category']])));
            if(!empty($result['related_token'])) {
                $location = ('upload/'.implode('/', array_filter([$result['related_type'], $result['related_token'], $result['related_category']])));
            }
            $result['file_absolute_path'] = storage_path($location.'/'.$result['file_path']);
            $result['is_image'] = 0;
            $result['image_width'] = 0;
            $result['image_height'] = 0;
            $result['file_thumbnail'] = '';
            if(file_exists(public_path($location.'/thumbnail/'.$result['file_path']))) {
                list($image_width, $image_height) = getimagesize(public_path(($location.'/'.$result['file_path'])));
                $result['is_image'] = 1;
                $result['image_width'] = $image_width;
                $result['image_height'] = $image_height;
                $result['file_thumbnail'] = ($location.'/thumbnail/'.$result['file_path']);
            }
            $result['file_path'] = ($location.'/'.$result['file_path']);
            $result['file_size_unit'] = $this->formatSizeUnits($result['file_size']);
        }
        
        return $result;
    } 

    public function doUpload($data = [], $return_link = false) {
        // init
        $data = array_merge([
            'attribute'         =>  '',
            'related_token'     =>  '',
            'related_type'      =>  'file',
            'related_category'  =>  '',
            'related_seq'       =>  1
        ], $data);
        
        if(!empty($file = Request::file('myfile'))) {
            // get file info
            $file_ori_name = $file->getClientOriginalName();
            $file_extension = $file->getClientOriginalExtension();
            $file_size = $file->getSize();
            $file_name = md5(uniqid(rand())).'.'. strtolower($file_extension);

            // upload folder
            $location = ('upload/'.implode('/', array_filter([$data['related_type'], $data['related_category']])));
            if(!empty($data['related_token'])) {
                $location = ('upload/'.implode('/', array_filter([$data['related_type'], $data['related_token'], $data['related_category']])));
            }
            if(!file_exists(storage_path($location))){
                @mkdir(storage_path($location), 0755, true);
            }
            
            // mapping folder
            $location_mapping = ('upload/'.implode('/', array_filter([$data['related_type'], $data['related_category']])));
            if(!empty($data['related_token'])) {
                $location_mapping = ('upload/'.implode('/', array_filter([$data['related_type'], $data['related_token'], $data['related_category']])));
            }
            if(!file_exists(public_path($location_mapping))){
                @mkdir(public_path($location_mapping), 0755, true);
            }
            
            // thumbnail folder
            if(!file_exists(public_path($location_mapping.'/thumbnail'))){
                @mkdir(public_path($location_mapping.'/thumbnail'), 0755, true);
            }
            
            // save file to server
            if($file->move(storage_path($location), $file_name)) {
                $find_max = DB::table($this->_media_file_table)
                        ->where('related_token', '=', $data['related_token'])
                        ->where('related_type', '=', $data['related_type'])
                        ->where('related_category', '=', $data['related_category'])
                        ->max('related_seq');
                if(!empty($find_max)) {
                    $find_max = $find_max + 1;
                }
                else {
                    $find_max = 1;
                }
                
                $new_media_file_id = $this->queryInsertData($this->_media_file_table, [
                    'is_public'         =>  ((!empty($this->_is_public))?1:0),
                    'file_name'         =>  $file_ori_name,
                    'file_path'         =>  $file_name,
                    'file_size'         =>  $file_size,
                    'file_attribute'    =>  '',
                    'file_source'       =>  $file_name,
                    'file_revised'      =>  0,
                    'related_token'     =>  $data['related_token'],
                    'related_type'      =>  $data['related_type'],
                    'related_category'  =>  $data['related_category'],
                    'related_seq'       =>  $find_max
                ]);
                
                // if image, try to resize and create thumbnail (max: 1920*1920 and  200*200)
                // or do copy
                if(!empty($new_media_file_id) && !empty($this->_is_public)) {
                    $image_file_type = ['jpeg', 'jpg', 'gif', 'png', 'bmp'];
                    $extension = pathinfo($file_name, PATHINFO_EXTENSION);
                    if(in_array(strtolower($extension), $image_file_type)) {
                        // resize
                        if(file_exists(storage_path($location.'/'.$file_name)) && !file_exists(public_path($location_mapping.'/'.$file_name))) {
                            \Intervention\Image\Facades\Image::make(storage_path($location.'/'.$file_name))->resize(1920, 1920, function ($constraint) {
                                $constraint->aspectRatio();
                                $constraint->upsize();
                            })->save(public_path($location_mapping.'/'.$file_name));
                            
                            \Intervention\Image\Facades\Image::make(storage_path($location.'/'.$file_name))->resize(400, 400, function ($constraint) {
                                $constraint->aspectRatio();
                                $constraint->upsize();
                            })->save(public_path($location_mapping.'/thumbnail/'.$file_name));
                        }
                    }
                    else {
                        if(file_exists(storage_path($location.'/'.$file_name)) && !file_exists(public_path($location_mapping.'/'.$file_name))) {
                            @copy(storage_path($location.'/'.$file_name), public_path($location_mapping.'/'.$file_name));
                        }
                    }
                }
                
                if(!empty($return_link)) {
                    if(file_exists(public_path($location_mapping.'/'.$file_name))) {
                        return url($location_mapping.'/'.$file_name);
                    }
                }
            }
        }
        else {
            $this->setResultMessage($this->pLang('bad_request'), 400);
        }
        
        return false;
    }
    
    public function doDelete($media_file_id = 0) {
        if(!empty($media_file_id)) {
            if(is_numeric($media_file_id)) {
                $media_file_id = [$media_file_id];
            }
            else if(is_string($media_file_id)){
                $media_file_id = explode(',', $media_file_id);
            }
            else {
                $media_file_id = (array)$media_file_id;
            }
            
            return $this->setWhere(
            [
                'name'      =>  'id', 
                'operate'   =>  'in', 
                'value'     =>  $media_file_id
            ])->queryDeleteData($this->_media_file_table);
        }
        else {
            $this->setResultMessage($this->pLang('bad_request'), 400);
        }
        
        return false;
    }
    
    public function switchSeq($media_file_id = 0, $from_seq = 0, $to_seq = 0) {
        $file_data = $this->getByID($media_file_id);
        if(!empty($file_data) && !empty($from_seq) && !empty($to_seq)) {
            $direction = ((int)$from_seq > (int)$to_seq)?'up':'down';
            $from_file_data = $file_data;
            
            // extra filter
            $extra_where = [
                '`related_token` = \''.$from_file_data['related_token'].'\'',
                '`related_type` = \''.$from_file_data['related_type'].'\'',
                '`related_category` = \''.$from_file_data['related_category'].'\''
            ];
            
            // find page by seq number
            if($direction == 'down') {
                $sql = 'SELECT * FROM (';
                $sql.= 'SELECT `id`,`file_name`, `related_seq`, ROW_NUMBER() OVER(ORDER BY `related_seq` DESC, `id` DESC) AS `new_seq` ';
                $sql.= 'FROM `'.($this->_db_prefix.$this->_media_file_table).'` ';
                $sql.=' WHERE `status` >= 0'.((!empty($extra_where))?(' AND '. implode(' AND ', $extra_where)):'').' GROUP BY `id`';
                $sql.= ') AS `t` WHERE `new_seq` > '.((int)$from_seq).' AND `new_seq` <= '.((int)$to_seq).' ORDER BY `new_seq` ASC';
                $match_file_data = $this->queryRowSQL($sql);
            }
            else {
                $sql = 'SELECT * FROM (';
                $sql.= 'SELECT `id`,`file_name`, `related_seq`, ROW_NUMBER() OVER(ORDER BY `related_seq` DESC, `id` DESC) AS `new_seq` ';
                $sql.= 'FROM `'.($this->_db_prefix.$this->_media_file_table).'` ';
                $sql.=' WHERE `status` >= 0'.((!empty($extra_where))?(' AND '. implode(' AND ', $extra_where)):'').' GROUP BY `id`';
                $sql.= ') AS `t` WHERE `new_seq` >= '.((int)$to_seq).' AND `new_seq` < '.((int)$from_seq).' ORDER BY `new_seq` DESC';
                $match_file_data = $this->queryRowSQL($sql);
            }

            // loop
            if(!empty($from_file_data) && !empty($match_file_data)) {
                DB::beginTransaction();
                try {
                    foreach ($match_file_data as $match_file) {
                        if((int)$from_file_data['id'] != (int)$match_file['id']) {
                            $to_file_data = $this->getByID($match_file['id']);
                            if(!empty($to_file_data) && ((int)$from_file_data['related_seq'] != (int)$to_file_data['related_seq'])) {
                                $this->setWhere(
                                [
                                    'name'      =>  'id', 
                                    'operate'   =>  '=', 
                                    'value'     =>  (int)$from_file_data['id']
                                ])->queryUpdateData($this->_media_file_table, ['related_seq'  =>  $to_file_data['related_seq']], false);

                                $this->setWhere(
                                [
                                    'name'      =>  'id', 
                                    'operate'   =>  '=', 
                                    'value'     =>  (int)$to_file_data['id']
                                ])->queryUpdateData($this->_media_file_table, ['related_seq'  =>  $from_file_data['related_seq']], false);
                                
                                $from_file_data['related_seq'] = $to_file_data['related_seq'];
                            }
                        }
                    }
                    DB::commit();
                    return true;
                }
                catch (Exception $e) {
                    $this->setResultMessage($this->pLang('query_error'), 500);
                    DB::rollBack();
                    throw $e;
                }
            }
            else {
                $this->setResultMessage($this->pLang('bad_request'), 400);
            }
        }
        else {
            $this->setResultMessage($this->pLang('bad_request'), 400);
        }
        
        // return
        return false;
    }
    
    public function doRotate($media_file_id = 0) {
        $media_file_data = $this->getByID($media_file_id);
        if(!empty($media_file_data) && $media_file_data['is_public'] == 1 && $media_file_data['is_image'] == 1) {
            if(file_exists($media_file_data['file_absolute_path'])) {
                
                try {
                    $ori_filename = pathinfo($media_file_data['file_path'], PATHINFO_FILENAME);
                    $ori_filename_extension = pathinfo($media_file_data['file_path'], PATHINFO_EXTENSION);
                    $new_filename = (md5(uniqid(rand())).'.'.$ori_filename_extension);

                    // rotae 90deg
                    \Intervention\Image\Facades\Image::make($media_file_data['file_absolute_path'])->rotate(90)->save(preg_replace('/('.$ori_filename.'\.'.$ori_filename_extension.')$/i', $new_filename, $media_file_data['file_absolute_path']),95);

                    // resize
                    \Intervention\Image\Facades\Image::make(preg_replace('/('.$ori_filename.'\.'.$ori_filename_extension.')$/i', $new_filename, $media_file_data['file_absolute_path']))->resize(1920, 1920, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    })->save(preg_replace('/('.$ori_filename.'\.'.$ori_filename_extension.')$/i', $new_filename, public_path($media_file_data['file_path'])));

                    \Intervention\Image\Facades\Image::make(preg_replace('/('.$ori_filename.'\.'.$ori_filename_extension.')$/i', $new_filename, $media_file_data['file_absolute_path']))->resize(400, 400, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    })->save(preg_replace('/('.$ori_filename.'\.'.$ori_filename_extension.')$/i', $new_filename, public_path($media_file_data['file_thumbnail'])));

                    // update info
                    $new_filesize = @filesize(preg_replace('/('.$ori_filename.'\.'.$ori_filename_extension.')$/i', $new_filename, $media_file_data['file_absolute_path']));
                    $this->setWhere(
                    [
                        'name'          =>  'id', 
                        'operate'       =>  '=', 
                        'value'         =>  (int)$media_file_data['id']
                    ])->queryUpdateData($this->_media_file_table, 
                    [
                        'file_path'     =>  $new_filename,
                        'file_size'     =>  $new_filesize,
                        'file_revised'  =>  1
                    ]);

                    // remove old file if need
                    if($media_file_data['file_revised'] == 1) {
                        if(file_exists($media_file_data['file_absolute_path'])) {
                            @unlink($media_file_data['file_absolute_path']);
                        }
                        if(file_exists(public_path($media_file_data['file_path']))) {
                            @unlink(public_path($media_file_data['file_path']));
                        }
                        if(file_exists(public_path($media_file_data['file_thumbnail']))) {
                            @unlink(public_path($media_file_data['file_thumbnail']));
                        }
                    }
                }
                catch (Exception $e) {
                    $this->setResultMessage($this->pLang('query_error'), 500);
                    DB::rollBack();
                    throw $e;
                }
            }
        }
        
        // return
        return false;
    }
    
    public function renewAttribute($data = [], $media_file_id = 0) {
        if(!empty($data) && $media_file_id > 0) {
            return $this->setWhere(
            [
                'name'      =>  'id', 
                'operate'   =>  '=', 
                'value'     =>  (int)$media_file_id
            ])->queryUpdateData($this->_media_file_table, [
                'file_attribute' => 
                [
                    'x'         =>  max(0, (!empty($data['x']))?(int)$data['x']:0),
                    'y'         =>  max(0, (!empty($data['y']))?(int)$data['y']:0),
                    'width'     =>  max(10, (!empty($data['width']))?(int)$data['width']:0),
                    'height'    =>  max(10, (!empty($data['height']))?(int)$data['height']:0),
                    'use_crop'  =>  max(0, (!empty($data['use_crop']))?(int)$data['use_crop']:0),
                    'title'     =>  (!empty($data['title']))?$data['title']:'',
                    'sub_title' =>  (!empty($data['sub_title']))?$data['sub_title']:'',
                    'content'   =>  (!empty($data['content']))?$data['content']:'',
                    'url'       =>  (!empty($data['url']))?$data['url']:'',
                    'extra_1'   =>  (!empty($data['extra_1']))?$data['extra_1']:'',
                    'extra_2'   =>  (!empty($data['extra_2']))?$data['extra_2']:''
                ]
            ]);
        }
        else {
            $this->setResultMessage($this->pLang('bad_request'), 400);
        }
        
        return false;
    }
    
    public function formatSizeUnits($bytes = 0){
        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        }
        elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        }
        elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        }
        elseif ($bytes > 1) {
            $bytes = $bytes . ' bytes';
        }
        elseif ($bytes == 1) {
            $bytes = $bytes . ' byte';
        }
        else {
            $bytes = '0 bytes';
        }

        return $bytes;
    }
}