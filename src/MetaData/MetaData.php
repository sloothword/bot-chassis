<?php

namespace Chassis\MetaData;

use Chassis\MetaData\MetaDataRepository;
use Illuminate\Support\Collection;

/**
 * Saves an additional data array linked to a Telegram object
 */
class MetaData extends Collection
{

    /** @var boolean MetaData shall be deleted */
    var $shallRemove = false;

    /**
     * Add repository information
     *
     * @param MetaDataRepository $repo
     * @param string $key Own key
     */
    public function connect($repo, $key)
    {
        $this->repo = $repo;
        $this->key = $key;
    }

    /**
     * Create new Collection without persistence information
     *
     * @return Collection
     */
    public function disconnect()
    {
        return new Collection($this->items);
    }

    /**
     * Replace all items with a Collection
     * @param Collection $metaData
     */
    public function replaceItems($metaData)
    {
        $this->items = $metaData->all();
        $this->shallRemove = $this->isEmpty();
    }

    var $repo;
    var $key;

    /**
     * Save meta data back to repository
     */
    public function save()
    {
        if ($this->shallRemove) {
            $this->repo->delete($this->getKey());
        } else {
            $this->repo->save($this);
        }
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Delete all entries
     */
    public function clear()
    {
        $this->replaceItems(new Collection());
    }
}
