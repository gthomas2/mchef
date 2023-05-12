<?php

namespace App\Model;

abstract class AbstractModel {
    /**
     * Doesn't do much other than allow you to pass in an object as well as arrays.
     * Equivalent without this helper method would be to do something like:
     * new User(...$userdata);
     * or
     * new User(...(array)$userdata);
     *
     * @param array|object $data
     * @return AbstractModel
     */
    public static function fromData(array|object $data): AbstractModel {
        $calledClass = get_called_class();
        $data = is_array($data) ? $data : (array) $data;
        return new $calledClass(...$data);
    }
}

