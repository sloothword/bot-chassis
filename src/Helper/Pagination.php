<?php

namespace Chassis\Helper;

use Chassis\MetaData\MessageData;
use Chassis\MetaData\MetaData;

/**
 * Helper class to include pagination function into messages
 */
class Pagination
{

    /**
     *
     * @param int $page Current page
     * @param int $perPage Items per page
     * @param int $count Total number of items
     */
    public function __construct($page, $perPage, $count)
    {
        $this->page = $page;
        $this->perPage = $perPage;
        $this->count = $count;
    }

    var $page;
    var $perPage;
    var $count;

    /**
     * Selects the correct items from an Eloquent Query
     *
     * @param type $query
     * @return type
     */
    public function selectPage($query)
    {
        if ($this->count == -1) {
            $this->count = $query->count();
        }
        $this->checkBounds();

        return $query->limit($this->perPage)->offset($this->perPage * $this->page);
    }

    /**
     * Get navigation buttons to inline into the message
     *
     * @return array
     */
    public function getButtons()
    {
        $buttons = [];
        if ($this->page > 0) {
            $buttons[] = "<";
        }
        if (!$this->isLastPage()) {
            $buttons[] = ">";
        }
        return $buttons;
    }

    /**
     *
     * @return boolean
     */
    public function isLastPage()
    {
        return $this->page == $this->lastPage();
    }

    /**
     *
     * @return int Index of last page
     */
    public function lastPage()
    {
        return ceil($this->count / $this->perPage) - 1;
    }

    /**
     * Add pagination data to MessageData
     *
     * @param MessageData $messageData
     */
    public function connect(MetaData $messageData)
    {
        $messageData['page'] = $this->page;
        $messageData['perPage'] = $this->perPage;
    }

    /**
     * Select next page
     */
    public function next()
    {
        $this->page++;
    }

    /**
     * Select previous page
     */
    public function previous()
    {
        $this->page--;
    }

    /**
     * Select page depending on pressed navigation button
     *
     * @param string $data Button text
     */
    public function processButton($data)
    {
        if ($data == "<") {
            $this->previous();
        } else {
            $this->next();
        }
    }

    /**
     * Restrict page number to valid range
     */
    private function checkBounds()
    {
        $this->page = max($this->page, 0);
        $this->page = min($this->page, $this->lastPage());
    }
}
