<?php
namespace Intervolga\Migrato\Data\Module\Iblock;

use Intervolga\Migrato\Data\BaseData;
use Intervolga\Migrato\Data\Module\Main\Group;
use Intervolga\Migrato\Data\Record;
use Intervolga\Migrato\Data\RecordId;
use Intervolga\Migrato\Data\Link;
use Intervolga\Migrato\Tool\XmlIdProvider\BaseXmlIdProvider;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Permission extends BaseData
{
	protected function configure()
	{
		$this->setVirtualXmlId(true);
		$this->setEntityNameLoc(Loc::getMessage('INTERVOLGA_MIGRATO.IBLOCK_PERMISSION'));
		$this->setFilesSubdir('/type/iblock/');
		$this->setDependencies(array(
			'GROUP_ID' => new Link(Group::getInstance()),
			'IBLOCK_ID' => new Link(Iblock::getInstance()),
		));
	}

	public function getList(array $filter = array())
	{
		$result = array();
		foreach ($this->getIblocks() as $iblockId)
		{
			$permissions = \CIBlock::GetGroupPermissions($iblockId);
			foreach ($permissions as $groupId => $permission)
			{
				$id = $this->createId(array(
					"IBLOCK_ID" => $iblockId,
					"GROUP_ID" => $groupId,
				));
				$record = new Record($this);
				$record->setXmlId($this->getXmlId($id));
				$record->setId($id);

				$record->addFieldsRaw(array(
					"PERMISSION" => $permission,
				));

				$dependency = clone $this->getDependency("GROUP_ID");
				$dependency->setValue(
					Group::getInstance()->getXmlId(RecordId::createNumericId($groupId))
				);
				$record->setDependency("GROUP_ID", $dependency);

				$dependency = clone $this->getDependency("IBLOCK_ID");
				$dependency->setValue(
					Iblock::getInstance()->getXmlId(RecordId::createNumericId($iblockId))
				);
				$record->setDependency("IBLOCK_ID", $dependency);

				$result[] = $record;
			}
		}

		return $result;
	}

	/**
	 * @return array|int[]
	 */
	protected function getIblocks()
	{
		$result = array();
		$getList = \CIBlock::GetList();
		while ($iblock = $getList->fetch())
		{
			$result[] = $iblock["ID"];
		}

		return $result;
	}

	public function update(Record $record)
	{
		$curValue = $record->getId()->getValue();
		$arGroups = \CIBlock::GetGroupPermissions($curValue["IBLOCK_ID"]);

		$curFields = $record->getFieldsRaw();
		$arGroups[$curValue["GROUP_ID"]] = $curFields["PERMISSION"];
		$iblock = new \CIBlock();
		$iblock->SetPermission($curValue["IBLOCK_ID"], $arGroups);
	}

	protected function createInner(Record $record)
	{
		$iblockLinkId = Iblock::getInstance()->findRecord($record->getDependency("IBLOCK_ID")->getValue());
		$groupLinkId = Group::getInstance()->findRecord($record->getDependency("GROUP_ID")->getValue());
		if ($iblockLinkId)
		{
			$iblockId = $iblockLinkId->getValue();
			$arGroups = \CIBlock::GetGroupPermissions($iblockId);
			if ($groupLinkId)
			{
				$groupId = $groupLinkId->getValue();
				$arGroups[$groupId] = $record->getField("PERMISSION")->getValue();
				$iblock = new \CIBlock();
				$iblock->setPermission($iblockId, $arGroups);

				return $this->createId(array(
					"IBLOCK_ID" => $iblockId,
					"GROUP_ID" => $groupId,
				));
			}
			else
			{
				throw new \Exception(Loc::getMessage('INTERVOLGA_MIGRATO.GROUP_NOT_FOUND'));
			}
		}
		else
		{
			throw new \Exception(Loc::getMessage('INTERVOLGA_MIGRATO.IBLOCK_NOT_FOUND'));
		}
	}

	protected function deleteInner(RecordId $id)
	{
		$complexId = $id->getValue();
		$arGroups = \CIBlock::GetGroupPermissions($complexId["IBLOCK_ID"]);
		if (in_array($complexId['GROUP_ID'], array_keys($arGroups)))
		{
			unset($arGroups[$complexId['GROUP_ID']]);
			$iblock = new \CIBlock();
			$iblock->setPermission($complexId["IBLOCK_ID"], $arGroups);
		}
	}

	public function createId($id)
	{
		return RecordId::createComplexId(array(
				"IBLOCK_ID" => intval($id['IBLOCK_ID']),
				"GROUP_ID" => intval($id['GROUP_ID']),
			)
		);
	}

	public function getXmlId($id)
	{
		$array = $id->getValue();
		$iblockData = Iblock::getInstance();
		$groupData = Group::getInstance();
		$iblockXmlId = $iblockData->getXmlId($iblockData->createId($array['IBLOCK_ID']));
		$groupXmlId = $groupData->getXmlId($groupData->createId($array['GROUP_ID']));
		$md5 = md5(serialize(array(
			$iblockXmlId,
			$groupXmlId,
		)));
		return BaseXmlIdProvider::formatXmlId($md5);
	}
}