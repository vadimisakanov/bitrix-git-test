<? namespace Intervolga\Migrato\Data\Module\Highloadblock;

use Bitrix\Main\Localization\Loc;
use Intervolga\Migrato\Data\BaseUserField;
use Intervolga\Migrato\Data\Link;
use Intervolga\Migrato\Data\Record;
use Intervolga\Migrato\Data\RecordId;
use Intervolga\Migrato\Tool\XmlIdProvider\BaseXmlIdProvider;

Loc::loadMessages(__FILE__);

class Field extends BaseUserField
{
	protected function configure()
	{
		parent::configure();
		$this->setFilesSubdir('/highloadblock/');
		$this->setDependencies(array(
			'HLBLOCK_ID' => new Link(HighloadBlock::getInstance()),
		));
	}

	/**
	 * @param string $userFieldEntityId
	 *
	 * @return int
	 */
	public function isCurrentUserField($userFieldEntityId)
	{
		return preg_match("/^HLBLOCK_[0-9]+$/", $userFieldEntityId);
	}

	/**
	 * @param array $userField
	 *
	 * @return Record
	 */
	protected function userFieldToRecord(array $userField)
	{
		$record = parent::userFieldToRecord($userField);
		$hlBlockId = str_replace("HLBLOCK_", "", $userField["ENTITY_ID"]);
		$hlBlockRecordId = RecordId::createNumericId($hlBlockId);
		$hlBlockXmlId = HighloadBlock::getInstance()->getXmlId($hlBlockRecordId);

		$dependency = clone $this->getDependency("HLBLOCK_ID");
		$dependency->setValue($hlBlockXmlId);
		$record->setDependency("HLBLOCK_ID", $dependency);

		return $record;
	}

	public function getList(array $filter = array())
	{
		if ($filter["HLBLOCK_ID"])
		{
			$filter["ENTITY_ID"] = "HLBLOCK_" . $filter["HLBLOCK_ID"];
			unset($filter["HLBLOCK_ID"]);
		}

		return parent::getList($filter);
	}

	public function getDependencyString()
	{
		return "HLBLOCK_ID";
	}

	public function getDependencyNameKey($id)
	{
		return "HLBLOCK_" . $id;
	}

	protected function createInner(Record $record)
	{
		$hlblockLink = $record->getDependency($this->getDependencyString());
		if ($hlblockLink && ($hlblockId = $hlblockLink->getId()))
		{
			$record->setFieldRaw("ENTITY_ID", $this->getDependencyNameKey($hlblockId->getValue()));

			return parent::createInner($record);
		}
		else
		{
			throw new \Exception(
				Loc::getMessage(
					'INTERVOLGA_MIGRATO.DEPENDENCY_NOT_RESOLVED',
					array
					(
						'#XML_ID#' => $record->getXmlId()
					)
				)
			);
		}
	}

	public function getXmlId($id)
	{
		$userField = \CUserTypeEntity::getById($id->getValue());
		$hlBlockId = str_replace("HLBLOCK_", "", $userField["ENTITY_ID"]);
		$hlBlockRecordId = RecordId::createNumericId($hlBlockId);
		$hlBlockXmlId = HighloadBlock::getInstance()->getXmlId($hlBlockRecordId);
		$md5 = md5(serialize(array(
			$hlBlockXmlId,
			$userField['FIELD_NAME'],
		)));

		return BaseXmlIdProvider::formatXmlId($md5);
	}
}