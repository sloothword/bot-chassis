<?php

namespace Chassis\Integration;

interface StorageInterface
{
    function save($userId, $chatId, $key, $data);
    
    function load($userId, $chatId, $key);
    
    function delete($userId, $chatId, $key);
}