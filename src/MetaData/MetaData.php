<?php

namespace Chassis\MetaData;

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

class MetaData extends Collection
{    
    var $shallRemove = false;
    
    public function connect($repo, $key){
        $this->repo = $repo;
        $this->key = $key;
    }
    
    public function disconnect(){
        return new Collection($this->items);
    }
    
    public function replaceItems($metaData){
        $this->items = $metaData->all();
        $this->shallRemove = $this->isEmpty();
    }
    
    var $repo;
    var $key;
    
    function save(){
        if($this->shallRemove){
            $this->repo->delete($this->getKey());
        }else{
            $this->repo->save($this);
        }
    }
    
    function getKey()
    {
        return $this->key;
    }
    
    function clear(){
        $this->replaceItems(new Collection());
    }
}

