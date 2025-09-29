<?php
namespace App\Models;

use Illuminate\Support\Facades\DB;

class Chatlog extends BaseModel {
    protected $_chat_log_table = 'chat_log';

    public function __construct($data) {
        parent::__construct($data);
    }
    
    public function getAll($member_id = 0, $date_int = '', $chat_mode = 'immigration') {
        // find max
        if(empty($date_int)) {
            $date_int = DB::table($this->_chat_log_table)
                ->where('target_date', '<=', (int)date('Ymd', strtotime($this->_today_date)))
                ->where('member_id', '=', (int)$member_id)
                ->where('chat_mode', '=', $chat_mode)
                ->max('target_date');
        }
        else {
            $date_int = DB::table($this->_chat_log_table)
                ->where('target_date', '<', (int)$date_int)
                ->where('member_id', '=', (int)$member_id)
                ->where('chat_mode', '=', $chat_mode)
                ->max('target_date');
        }

        return $this->setWhere([
            [
                'member_id', '=', (int)$member_id
            ],
            [
                'target_date', '=', (int)$date_int
            ],
            [
                'chat_mode', '=', $chat_mode
            ]
        ])->setOrder(['id_asc'])->queryListData($this->_chat_log_table, false);
    }

    public function doSave($data = []) {
        return $this->queryTransaction(function($data) {
            $new_chatlog_id = $this->queryInsertData($this->_chat_log_table , $data);
            if(!empty($new_chatlog_id) && !empty($data['reply'])) {
                $this->queryInsertData($this->_chat_log_table , [
                    'related_id'    =>  $new_chatlog_id,
                    'member_id'     =>  $data['member_id'],
                    'target_date'   =>  $data['target_date'],
                    'type'          =>  'reply',
                    'content'       =>  $data['reply'],
                    'chat_mode'     =>  isset($data['chat_mode']) ? $data['chat_mode'] : 'immigration'
                ]);
            }
            
            return $new_chatlog_id;
        }, $data);
    }
}