<?php
namespace Intervolga\Migrato\Data\Module\Sale;

use Bitrix\Sale\Internals\OrderPropsTable;
use Bitrix\Main\Localization\Loc;
use Intervolga\Migrato\Data\BaseData;
use Intervolga\Migrato\Data\Link;
use Intervolga\Migrato\Data\Record;
use Intervolga\Migrato\Data\RecordId;
use Intervolga\Migrato\Data\Value;
use Intervolga\Migrato\Tool\ExceptionText;
use Intervolga\Migrato\Tool\XmlIdProvider\BaseXmlIdProvider;

Loc::loadMessages(__FILE__);

class Property extends BaseData
{
	protected function configure()
	{
		$this->setVirtualXmlId(true);
		$this->setEntityNameLoc(Loc::getMessage('INTERVOLGA_MIGRATO.SALE_PROPERTY'));
		$this->setFilesSubdir('/persontype/propertygroup/');
		$this->setDependencies(array(
			"PERSON_TYPE_ID" => new Link(PersonType::getInstance()),
		));
		$this->setReferences(array(
			'PROPS_GROUP_ID' => new Link(PropertyGroup::getInstance()),
		));
	}

	public function getList(array $filter = array())
	{
		$result = array();
		$getList = OrderPropsTable::getList();
		while ($property = $getList->fetch())
		{
			$record = new Record($this);
			$id = $this->createId($property["ID"]);
			$record->setId($id);
			$record->setXmlId(
				$this->getXmlId($id)
			);
			$record->addFieldsRaw(array(
				"NAME" => $property["NAME"],
				"TYPE" => $property["TYPE"],
				"REQUIRED" => $property["REQUIRED"],
				"DEFAULT_VALUE" => $property["DEFAULT_VALUE"],
				"SORT" => $property["SORT"],
				"USER_PROPS" => $property["USER_PROPS"],
				"IS_LOCATION" => $property["IS_LOCATION"],
				"DESCRIPTION" => $property["DESCRIPTION"],
				"IS_EMAIL" => $property["IS_EMAIL"],
				"IS_PROFILE_NAME" => $property["IS_PROFILE_NAME"],
				"IS_PAYER" => $property["IS_PAYER"],
				"IS_LOCATION4TAX" => $property["IS_LOCATION4TAX"],
				"IS_FILTERED" => $property["IS_FILTERED"],
				"CODE" => $property["CODE"],
				"IS_ZIP" => $property["IS_ZIP"],
				"IS_PHONE" => $property["IS_PHONE"],
				"IS_ADDRESS" => $property["IS_ADDRESS"],
				"ACTIVE" => $property["ACTIVE"],
				"UTIL" => $property["UTIL"],
				"INPUT_FIELD_LOCATION" => $property["INPUT_FIELD_LOCATION"],
				"MULTIPLE" => $property["MULTIPLE"],
			));
			$record->addFieldsRaw(Value::treeToList($property["SETTINGS"], "SETTINGS"));
			$this->addLinks($record, $property);
			$result[] = $record;
		}

		return $result;
	}

	public function getXmlId($id)
	{
		$record = OrderPropsTable::getById($id->getValue())->fetch();
		$personTypeId = PersonType::getInstance()->createId($record["PERSON_TYPE_ID"]);
		$personTypeXmlId = PersonType::getInstance()->getXmlId($personTypeId);
		$md5 = md5(serialize(array(
			$record['CODE'],
			$personTypeXmlId,
		)));

		return BaseXmlIdProvider::formatXmlId($md5);
	}

	/**
	 * @param Record $record
	 * @param array $property
	 */
	protected function addLinks(Record $record, array $property)
	{
		$link = clone $this->getDependency("PERSON_TYPE_ID");
		$personTypeId = PersonType::getInstance()->createId($property["PERSON_TYPE_ID"]);
		$personTypeXmlId = PersonType::getInstance()->getXmlId($personTypeId);
		$link->setValue($personTypeXmlId);
		$record->setDependency("PERSON_TYPE_ID", $link);

		$link = clone $this->getReference("PROPS_GROUP_ID");
		$propertyGroupId = PropertyGroup::getInstance()->createId($property["PROPS_GROUP_ID"]);
		$propertyGroupXmlId = PropertyGroup::getInstance()->getXmlId($propertyGroupId);
		$link->setValue($propertyGroupXmlId);
		$record->setReference("PROPS_GROUP_ID", $link);
	}

	public function update(Record $record)
	{
		$array = $this->recordToArray($record);
		$updateResult = OrderPropsTable::update($record->getId()->getValue(), $array);
		if ($updateResult->getErrorMessages())
		{
			throw new \Exception(ExceptionText::getFromResult($updateResult));
		}
	}

	protected function recordToArray(Record $record)
	{
		$array = $record->getFieldsRaw(array("SETTINGS"));
		if ($link = $record->getDependency("PERSON_TYPE_ID"))
		{
			if ($id = $link->findId())
			{
				$array["PERSON_TYPE_ID"] = $id->getValue();
			}
		}
		if ($link = $record->getReference("PROPS_GROUP_ID"))
		{
			if ($idObject = $link->findId())
			{
				$array["PROPS_GROUP_ID"] = $idObject->getValue();
			}
			else
			{
				$array["PROPS_GROUP_ID"] = false;
			}
		}

		return $array;
	}

	protected function createInner(Record $record)
	{
		$array = $this->recordToArray($record);
		$addResult = OrderPropsTable::add($array);
		if ($addResult->isSuccess())
		{
			return $this->createId($addResult->getId());
		}
		else
		{
			throw new \Exception(ExceptionText::getFromResult($addResult));
		}
	}

	protected function deleteInner(RecordId $id)
	{
		$result = OrderPropsTable::delete($id->getValue());
		if (!$result->isSuccess())
		{
			throw new \Exception(ExceptionText::getFromResult($result));
		}
	}
}