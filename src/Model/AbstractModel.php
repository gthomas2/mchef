<?php

namespace App\Model;

class AbstractModel {
    public function __construct($data) {
        if (is_array($data)) {
            $data = (object) $data;
        }
        foreach (get_object_vars($data) as $key => $var) {
            $this->$key = $var;
        }
    }
}
