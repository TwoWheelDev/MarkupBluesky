<?php

/**
 * FieldtypeBluesky
 * Stores settings for displaying Bluesky feed: handle, post count, include reposts
 */

namespace ProcessWire;

class FieldtypeBluesky extends Fieldtype {
    public static function getModuleInfo()
    {
        return [
            'title' => 'Bluesky',
            'version' => 1,
            'summary' => 'Contains Bluesky page configuration',
            'author' => 'TwoWheelDev',
            'icon' => 'plug',
            'requires' => ['InputfieldBluesky'],
        ];
    }

    public function getDatabaseSchema(Field $field) {
        return [
            'pages_id' => 'int(10) unsigned NOT NULL',
            'data' => 'text NOT NULL',
            'keys' => array('primary' => 'PRIMARY KEY (`pages_id`)')
        ];
    }

    public function getMatchableTypes() {
        return ['string']; // lets PW know it's safe to treat as a string for matching
    }

    public function sanitizeValue(Page $page, Field $field, $value) {
        if (is_array($value)) {
            $wd = new WireData();
            $wd->setArray($value);
            return $wd;
        }
        return $value;
    }

    public function ___savePageField(Page $page, Field $field) {
        $value = $page->get($field->name);
    
        if ($value instanceof WireData) {
            $json = json_encode($value->getArray());
    
            $query = $this->database->prepare("
                INSERT INTO field_{$field->name} (pages_id, data)
                VALUES (:pages_id, :data)
                ON DUPLICATE KEY UPDATE data = :data_update
            ");
    
            $query->bindValue(':pages_id', $page->id, \PDO::PARAM_INT);
            $query->bindValue(':data', $json, \PDO::PARAM_STR);
            $query->bindValue(':data_update', $json, \PDO::PARAM_STR);
            $query->execute();
        }
    }
    
    public function ___loadPageField(Page $page, Field $field) {
        $sql = "SELECT data FROM field_{$field->name} WHERE pages_id = :pages_id LIMIT 1";
        $query = $this->database->prepare($sql);
        $query->bindValue(':pages_id', $page->id, \PDO::PARAM_INT);
        $query->execute();
    
        $row = $query->fetch(\PDO::FETCH_ASSOC);
        
    
        if ($row && !empty($row['data'])) {
            $data = json_decode($row['data'], true);
    
            $wireData = new \ProcessWire\WireData();
            foreach ($data as $key => $value) {
                $wireData->set($key, $value);
            }
            return $wireData;
        }
        
    
        return new \ProcessWire\WireData(); // return an empty object if nothing is saved
    }

    public function getInputfield(Page $page, Field $field) {
        return $this->modules->get('InputfieldBluesky');
    }  

    public function getBlankValue(Page $page, Field $field) {
        $wd = new WireData();
        $wd->set('bluesky_handle', '');
        $wd->set('bluesky_post_count', 5);
        $wd->set('bluesky_include_reposts', false);
        return $wd;
    }
    
}
