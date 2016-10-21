<?php

namespace Chassis\UserData;

use Illuminate\Support\Collection;
use Countable;
use ArrayAccess;
use Traversable;
use ArrayIterator;
use CachingIterator;
use JsonSerializable;
use IteratorAggregate;
use InvalidArgumentException;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;

class UserData
{
    var $collection;
    var $needsSaving = false;
    
    public function __construct($repo, $key, $items = array()) {
        $this->collection = new Collection($items);
        $this->repo = $repo;
        $this->key = $key;
    }
    
    var $repo;
    var $key;
    
    function save(){
        if($this->collection->isEmpty() && $this->needsSaving){
            $this->repo->delete($this->getKey());
        }else{
            $this->repo->save($this);
        }
    }
    
    function getKey()
    {
        return $this->key;
    }
    
    public function getCollection(){
        return $this->collection;
    }
    
    function replaceCollection($collection){
        $this->needsSaving = true;
        $this->collection = $collection;
    }
    

}

