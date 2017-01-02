<?php
/**
 * This class will help convert models into arrays of data. This is only for single-level conversions. Other
 * classes are responsible for traversing the depth of relation trees. Those other processes should use
 * this class for each level, but then handle the depth themselves.
 */
namespace ThirdEngine\PropelSOABundle\Base;


use ThirdEngine\PropelSOABundle\Model\PropelSOAModel;

use BasePeer;
use DateTime;


class ModelConverter
{
  public function convertModelToData(PropelSOAModel $model)
  {
    $data = $model->toArray(BasePeer::TYPE_PHPNAME, false);

    foreach ($data as $key => $value)
    {
      if ($value instanceof DateTime)
      {
        $format = $this->getDateTimeFormat($model, $key);
        $data[$key] = $value->format($format);
      }
    }

    return $data;
  }

  /**
   * This method will figure out the correct DateTime format to use based
   * on the column type from the database. If this should be a date only, we
   * do not want bogus time information in the resulting data array.
   *
   * @param PropelSOAModel $model
   * @param string $columnPhpName
   *
   * @return string
   */
  protected function getDateTimeFormat(PropelSOAModel $model, $columnPhpName)
  {
    $tableMap = $model->getTableMap();
    return $tableMap->getColumnByPhpName($columnPhpName)->getType() == 'DATE' ? 'Y-m-d' : 'c';
  }
}