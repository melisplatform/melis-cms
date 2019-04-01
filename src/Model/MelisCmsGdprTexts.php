<?php
/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2019 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Model;

class MelisCmsGdprTexts
{
    protected $unfilteredDataCount = 0;
    protected $filteredDataCount = 0;

    public function getUnfilteredDataCount()
    {
        return $this->unfilteredDataCount;
    }

    public function setUnfilteredDataCount(int $count = 0)
    {
        $this->unfilteredDataCount = $count;
    }

    public function getFilteredDataCount()
    {
        return $this->filteredDataCount;
    }

    public function setFilteredDataCount(int $count = 0)
    {
        $this->filteredDataCount = $count;
    }
}