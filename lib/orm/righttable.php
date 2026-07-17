<?php

namespace Drdroid\Keyrights\Orm;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DateTimeField;
use Bitrix\Main\ORM\Fields\IntegerField;

/** Direct user/group permissions attached to an ItemTable row. */
class RightTable extends DataManager
{
    public static function getTableName()
    {
        return 'sib_kr_right';
    }

    public static function getMap()
    {
        return [
            (new IntegerField('ID'))
                ->configurePrimary(true)
                ->configureAutocomplete(true),
            new IntegerField('ITEM_ID'),
            new IntegerField('EDIT'),
            new IntegerField('BLOCKED'),
            (new DateTimeField('TIMED'))->configureNullable(true),
            (new IntegerField('USER'))->configureNullable(true),
            (new IntegerField('GROUP'))->configureNullable(true),
        ];
    }
}
