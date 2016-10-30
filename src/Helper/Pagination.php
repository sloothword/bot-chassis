<?php

namespace Chassis\Helper;
use Log;
class Pagination{    
    
    public function __construct($page, $perPage, $count) {
        $this->page = $page;
        $this->perPage = $perPage;
        $this->count = $count;
    }
    
    var $page;
    var $perPage;
    var $count;
    
    public function selectPage($query){
        if($this->count == -1){
            $this->count = $query->count();
        }
        $this->checkBounds();
        Log::info("Page", [$this->page]);
        Log::info("Count", [$this->count]);
        Log::info("PerPage", [$this->perPage]);
        
        return $query->limit($this->perPage)->offset($this->perPage * $this->page);
    }
    
        
    public function getButtons(){
        $buttons = [];
        if($this->page > 0){
            $buttons[] = "<";
        }
        if(!$this->isLastPage()){
            $buttons[] = ">";
        }
        return $buttons;
    }
    
    public function isLastPage(){        
        return $this->page == $this->lastPage();
    }
    
    function lastPage(){
        return ceil($this->count / $this->perPage)-1;
    }
    
    public function connect($messageData){
        $messageData['page'] = $this->page;
        $messageData['perPage'] = $this->perPage;        
    }
    
    function next(){
        $this->page++;
    }
    
    function previous(){
        $this->page--;
    }   
    
    function processButton($data){
        if($data == "<"){
            $this->previous();
        }else{
            $this->next();
        }
    }
    
    function checkBounds(){
        $this->page = max($this->page, 0);
        $this->page = min($this->page, $this->lastPage());
    }
}