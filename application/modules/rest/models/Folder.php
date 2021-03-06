<?php

class Rest_Model_Folder extends Phusick_Model_Rest_Database {
  
  const TYPE = Common_Enum_Type::FOLDER;
  protected $storageClass = 'Common_Model_DbTable_Items';
  protected $indexColumns = array('id', 'publish', 'title');
  protected $getColumns = array('id', 'publish', 'title', 'uri');
  protected $insertColumns = array('publish', 'uri', 'title');
  protected $updateColumns = array('publish', 'uri', 'title');
  
  protected function beforeIndex(Zend_Db_Select $select, array $params) {
    $storage = $this->getStorage();
    $subselect = $storage->select();
    $subselect->from(array('files' => $storage->getTableName()),
            array(new Zend_Db_Expr('COUNT(*)'))
            )
            ->where('files.parent_id = main.id')
            ->where('files.type = ?', Common_Enum_Type::FILE)
            ;
   
    $select->columns(array(
        'count' => new Zend_Db_Expr('('.$subselect->__toString().')')
    ));
    $select->where('main.type = ?', self::TYPE);
    
    return $select;
  }
  
  protected function afterGet(array $data) {
    $storage = $this->getStorage();
    $select = $storage->select();
    $select->from($storage->getTableName(),
                array('count' => new Zend_Db_Expr('COUNT(*)')))
            ->where('parent_id = ?', $data['id'])
            ;
    
    $sql = $select->__toString();
    $row = $storage->fetchRow($select)->toArray();
    $data['count'] = $row['count'];
    
    return $data;
  }
  
  protected function beforeInsert(array $data, array $params) {
    $data['type'] = self::TYPE;
    $data['user_id'] = Zend_Auth::getInstance()->getStorage()->read()->id;
    $data['date_created'] = new Zend_Db_Expr('NOW()');
    return $data;
  }
  
  protected function beforeUpdate(array $data, array $params) {
    $data['date_updated'] = new Zend_Db_Expr('NOW()');
    return $data;
  }
  
  protected function beforeDelete(Zend_Db_Table_Row $row) {
    $folder = $row->toArray();
    $fileModel = new Rest_Model_File();
    $filesInFolder = $fileModel->index(null, array('parent_id' => $folder['id']));
    foreach($filesInFolder as $file) {
      $fileModel->delete($file['id']);
    }
    return $row;
  }
  
}