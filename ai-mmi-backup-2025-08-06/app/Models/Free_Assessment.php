<?php
namespace App\Models;

use Illuminate\Support\Facades\DB;

class Free_Assessment extends BaseModel {
    protected $_free_assessment_table = 'free_assessment';

    public function __construct($data) {
        parent::__construct($data);
    }
    
    public function getByID($id = 0) {
        return $this->setWhere(['id', '=', (int)$id])->queryOneData($this->_free_assessment_table);
    }

    public function doSave($data = []) {
        return $this->queryInsertData($this->_free_assessment_table, $data);
    }
}