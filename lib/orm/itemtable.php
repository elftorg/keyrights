<?php

namespace Drdroid\Keyrights\Orm;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;

/**
 * Access roots for a KeyRights item or section.
 *
 * The actual password/section data lives in Bitrix iblock tables. This table
 * contains only the ownership and access anchor used by the module.
 */
class ItemTable extends DataManager
{
    public static function getTableName()
    {
        return 'sib_kr_item';
    }

    public static function getMap()
    {
        return [
            (new IntegerField('ID'))
                ->configurePrimary(true)
                ->configureAutocomplete(true),
            (new IntegerField('ENTITY_ID'))->configureNullable(true),
            (new IntegerField('SECTION_ID'))->configureNullable(true),
            new IntegerField('OWNER'),
        ];
    }
}
