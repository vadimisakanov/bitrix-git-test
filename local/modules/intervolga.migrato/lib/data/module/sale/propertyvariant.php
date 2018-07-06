<?php
namespace Intervolga\Migrato\Data\Module\Sale;

use Bitrix\Sale\Internals\OrderPropsVariantTable;
use Bitrix\Main\Localization\Loc;
use Intervolga\Migrato\Data\BaseData;
use Intervolga\Migrato\Data\Link;
use Intervolga\Migrato\Data\Record;
use Intervolga\Migrato\Data\RecordId;
use Intervolga\Migrato\Tool\ExceptionText;
use Intervolga\Migrato\Tool\XmlIdProvider\BaseXmlIdProvider;

Loc::loadMessages(__FILE__);

class PropertyVariant extends BaseData
{
	protected function configure()
	{
		$this->setVirtualXmlId(true);
		$this->setEntityNameLoc(Loc::getMessage('INTERVOLGA_MIGRATO.SALE_PROPERTY_VARIANT'));
		$this->setFilesSubdir('/persontype/propertygroup/property/');
		$this->setDependencies(array(
			'ORDER_PROPS_ID' => new Link(Property::getInstance()),
		));
	}

	public function getList(array $filter = array())
	{
		$result = array();
		$getList = OrderPropsVariantTable::getList();
		while ($variant = $getList->fetch())
		{
			$record = new Record($this);
			$id = $this->createId($variant["ID"]);
			$record->setId($id);
			$record->setXmlId(
				$this->getXmlId($id)
			);
			$record->addFieldsRaw(array(
				"NAME" => $variant["NAME"],
				"VALUE" => $variant["VALUE"],
				"SORT" => $variant["SORT"],
				"DESCRIPTION" => $variant["DESCRIPTION"],
			));

			$link = clone $this->getDependency("ORDER_PROPS_ID");
			$propId = Property::getInstance()->createId($variant["ORDER_PROPS_ID"]);
			$propXmlId = Property::getInstance()->getXmlId($propId);
			$link->setValue($propXmlId);
			$record->setDependency("ORDER_PROPS_ID", $link);

			$result[] = $record;
		}

		return $result;
	}

	public function getXmlId($id)
	{
		$record = OrderPropsVariantTable::getById($id->getValue())->fetch();
		$propId = Property::getInstance()->createId($record["ORDER_PROPS_ID"]);
		$propXmlId = Property::getInstance()->getXmlId($propId);
		$md5 = md5(serialize(array(
			$record['VALUE'],
			$propXmlId,
		)));

		return BaseXmlIdProvider::formatXmlId($md5);
	}

	protected function createInner(Record $record)
	{
		$create = $this->recordToArray($record);
		$addResult = OrderPropsVariantTable::add($create);
		if ($addResult->isSuccess())
		{
			return $this->createId($addResult->getId());
		}
		else
		{
			throw new \Exception(ExceptionText::getFromResult($addResult));
		}
	}

	/**
	 * @param \Intervolga\Migrato\Data\Record $record
	 *
	 * @return \string[]
	 * @throws \Exception
	 */
	protected function recordToArray(Record $record)
	{
		$array = $record->getFieldsRaw();
		if ($id = $record->getDependency("ORDER_PROPS_ID")->findId())
		{
			$array["ORDER_PROPS_ID"] = $id->getValue();
		}

		return $array;
	}

	public function update(Record $record)
	{
		$array = $this->recordToArray($record);
		$updateResult = OrderPropsVariantTable::update($record->getId()->getValue(), $array);
		if (!$updateResult->isSuccess())
		{
			throw new \Exception(ExceptionText::getFromResult($updateResult));
		}
	}

	protected function deleteInner(RecordId $id)
	{
		$result = OrderPropsVariantTable::delete($id->getValue());
		if (!$result->isSuccess())
		{
			throw new \Exception(ExceptionText::getFromResult($result));
		}
	}
}