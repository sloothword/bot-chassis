<?php

namespace Chassis\Integration;

interface StorageInterface
{
    function save($key, $data);
    
    function load($key);
    
    function delete($key);
    
    function flush();
}