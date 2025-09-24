<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;

class Media_Files extends AdminController {
    
    public function __construct($data) {
        parent::__construct($data);
        
        // load model
        $this->_media_files_model = $this->loadModel('media_files');
        
        // set nav
        $this->pageNavigator($this->_page_lang['media_files'], $this->toURL($this->_mapping_data['class']));
    }
    
    public function index() {
        // post
        $this->pageAction(function() {
            $result = false;
            switch ($this->postParamValue('page_action')) {
                case 'file_upload':
                    if($this->hasUserRole('media_files', 102)) {
                        $result = $this->_media_files_model->doUpload($this->_page_post_data);
                    }
                    break;
                case 'file_upload_meta':
                    if($this->hasUserRole('media_files', 102)) {
                        $file_url = $this->_media_files_model->doUpload($this->_page_post_data, true);
                    }
                    break;
                case 'delete':
                    if($this->hasUserRole('media_files', 104)) {
                        $this->_media_files_model->doDelete($this->postParamValue('id', 0));
                    }
                    break;
                case 'seq':
                    if($this->hasUserRole('media_files', 103)) {
                        $this->_media_files_model->switchSeq($this->postParamValue('id', 0), $this->postParamValue('to_seq'));
                    }
                    break;
                case 'rotate':
                    if($this->hasUserRole('media_files', 103)) {
                        $this->_media_files_model->doRotate($this->postParamValue('id', 0));
                    }
                    break;
            }
            $this->pageResult(
            [
                'status'    =>  $this->_media_files_model->getResultCode(),
                'message'   =>  $this->_media_files_model->getResultMessage(),
                'file_url'  =>  (!empty($file_url))?$file_url:''
            ], ((!empty($result))?true:false));
        });
     
        // get list
        $media_files_data = $this->_media_files_model->getAll();
        if((int)$this->getParamValue('page', 0) > 1 && empty($media_files_data['data'])) {
            $this->doRedirect($this->toURL($this->_mapping_data['class']));
        }
        
        // load view
        return $this->pageSetting(
        [
            'can_add'       =>  $this->hasUserRole('media_files', 102),
            'can_delete'    =>  $this->hasUserRole('media_files', 104)
        ])->pageData(
        [
            'media_files'   => $media_files_data
        ])->pageView(null, (($this->getParamValue('inline', 0)==1)?false:true), (($this->getParamValue('inline', 0)==1)?false:true));
    }
    
    public function ajaxlist() {
        $file_list_data = $this->_media_files_model->getAll($this->postParamValue('related_token', ''), $this->postParamValue('related_type', ''), $this->postParamValue('related_category', ''));
        if(!empty($file_list_data)) {
            $k = 1; 
            $max_size = count($file_list_data);
            foreach ($file_list_data as $file_key => $file) {
                $extension = pathinfo($file['file_path'], PATHINFO_EXTENSION);
                $file['file_index'] = 0;
                echo '<div class="block">';
                    echo '<div class="widget">';
                        echo '<div class="iweb-checkbox"><div class="options"><div><input type="checkbox" name="media_file_id[]" value="'.$file['id'].'"><a>&nbsp;</a></div></div><div class="virtual-msg">&nbsp;</div></div>';
                        echo '<div class="photo">';
                            echo '<a class="link" href="'.url($file['file_path']).'" target="_blank">';
                            switch(strtolower($extension)) {
                                case 'pdf':
                                    echo '<i class="fa fa-file-pdf-o" style="color:#ef4130;"></i>';
                                    break;
                                case 'doc':
                                case 'docx':
                                    echo '<i class="fa fa-file-word-o" style="color:#5091cd;"></i>';
                                    break;
                                case 'xls':
                                case 'xlsx':
                                    echo '<i class="fa fa-file-excel-o" style="color:#66cdaa;"></i>';
                                    break;
                                case 'ppt':
                                case 'pptx':
                                    echo '<i class="fa fa-file-powerpoint-o" style="color:#f7b002;"></i>';
                                    break;
                                case 'txt':
                                    echo '<i class="fa fa-file-text-o"></i>';
                                    break;
                                case 'jpeg':
                                case 'jpg':
                                case 'gif':
                                case 'png':
                                case 'bmp':
                                    $file['file_index'] = 1;
                                    echo '<img src="'.((!empty($file['file_thumbnail']))?$file['file_thumbnail']:'').'"/>';
                                    break;
                                case 'avi':
                                case 'mov':
                                case 'mp4':
                                case 'ogg':
                                case 'wmv':
                                case 'webm':
                                    echo '<i class="fa fa-file-video-o" style="color:#5091cd;"></i>';
                                    break;
                                case 'mp3':
                                case 'ogg':
                                case 'wav':
                                    echo '<i class="fa fa-file-audio-o" style="color:#66cdaa;"></i>';
                                    break;
                                case 'rar':
                                case 'zip':
                                    echo '<i class="fa fa-file-zip-o" style="color:#f7b002;"></i>';
                                    break;
                                default:
                                    echo '<i class="fa fa-file-code-o"></i>';    
                            }
                            echo '</a>';
                            if(!empty($file['is_image'])) {
                                echo '<a class="rotate" data-id="'.$file['id'].'"><i class="fa fa-undo"></i></a>';
                            }
                        echo '</div>';

                        echo '<div class="info">';
                            echo '<div class="name"><strong>'.$file['file_name'].'</strong> | '.$file['file_size_unit'].'</div>';
                            echo '<div class="path">'.url($file['file_path']).'</div>';
                        echo '</div>';

                        echo '<div class="controls">';
                            if($this->hasUserRole('media_files', 103)) {
                                echo '<button class="edit" type="button" data-id="'.$file['id'].'"><i class="fa fa-pencil"></i></button>';
                            }
                            
                            if($this->hasUserRole('media_files', 101)) {
                                echo '<button class="download" type="button" data-url="'.$this->toURL('media_files/download/'.$file['id']).'"><i class="fa fa-download"></i></button>';
                            }
                            
                            if($this->hasUserRole('media_files', 104)) {
                                echo '<button class="delete" type="button" data-id="'.$file['id'].'"><i class="fa fa-trash"></i></button>';
                            }
                            
                            if($this->hasUserRole('media_files', 103)) {
                                echo '<div class="dd">';
                                    echo '<select class="seq_number" data-id="'.$file['id'].'" data-offset="'.$k.'">';
                                    for($size = 1; $size <= $max_size; $size++) {
                                        if($k == $size) {
                                            echo '<option value="'.$size.'" selected>'.$size.'</option>';
                                        }
                                        else {
                                            echo '<option value="'.$size.'">'.$size.'</option>';
                                        }
                                    }
                                    echo '</select>';
                                echo '</div>';
                            }
                        echo '</div>';
                    echo '</div>';
                echo '</div>';
                $k++;
            }
        }
    }
    
    public function attribute() {
        if($this->hasUserRole('media_files', 103)) {
            switch ($this->postParamValue('page_action')) {
                case 'save_info':
                    $this->_media_files_model->renewAttribute($this->_page_post_data, $this->postParamValue('media_file_id',0));
                    $this->pageResult(
                    [
                        'status'    =>  $this->_media_files_model->getResultCode(),
                        'message'   =>  $this->_media_files_model->getResultMessage()
                    ]);
                    break;
                default:
                    $file_data = $this->_media_files_model->getByID($this->postParamValue('media_file_id',0));
                    if(!empty($file_data)) {
                        $this->pageResult([
                            'status'    =>  200,
                            'data'      =>  $file_data
                        ]);
                    }
            }
        }
        else {
            $this->pageResult([
                'status'    =>  403,
                'data'      =>  $this->_page_lang['permission_denied']
            ]);
        }
    }

    public function download($id = 0) {
        if($this->hasUserRole('media_files', 101)) {
            $file_data = $this->_media_files_model->getByID($id);
            if(!empty($file_data)) {
                header('Content-Type: '.mime_content_type($file_data['file_absolute_path']));
                header('Content-Disposition: attachment; filename='.$file_data['file_name']);
                while (ob_get_level()) {
                    ob_end_clean();
                }
                readfile($file_data['file_absolute_path']);
            }
        }
    }
}