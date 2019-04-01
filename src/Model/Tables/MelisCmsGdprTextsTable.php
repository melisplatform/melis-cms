<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2019 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Model\Tables;


use MelisEngine\Model\Tables\MelisGenericTable;
use Zend\Db\TableGateway\TableGateway;

class MelisCmsGdprTextsTable extends MelisGenericTable
{
    protected $tableGateway;
    protected $idField;

    public function __construct(TableGateway $tableGateway)
    {
        parent::__construct($tableGateway);
        $this->idField = 'mcgdpr_text_id';
    }

    public function getGdprBannerText($siteId, $langId)
    {
        $select = $this->tableGateway->getSql()->select();

        if (!empty($siteId)) {
            $select->where->equalTo('mcgdpr_text_site_id', $siteId);
        }

        if (!empty($langId)) {
            $select->where->equalTo('mcgdpr_text_lang_id', $langId);
        }

        $select->where($select);

        return $this->tableGateway->selectWith($select);
    }

    /**
     * Deletes entry via where condition using multiple fields
     *
     * @param array $where
     * @return bool
     */
    public function deleteWhere(array $where = [])
    {
        if (empty($where)) {
            return false;
        } else {
            return $this->tableGateway->delete($where);
        }
    }
}
