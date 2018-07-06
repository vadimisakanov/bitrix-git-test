<?php
namespace Intervolga\Migrato\Data\Module\Highloadblock;

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Highloadblock\HighloadBlockRightsTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\TaskTable;
use Intervolga\Migrato\Data\BaseData;
use Intervolga\Migrato\Data\Module\Main\Group;
use Intervolga\Migrato\Data\Link;
use Intervolga\Migrato\Data\Record;
use Intervolga\Migrato\Data\RecordId;
use Intervolga\Migrato\Tool\Console\Application;
use Intervolga\Migrato\Tool\XmlIdProvider\BaseXmlIdProvider;
use Intervolga\Migrato\Tool\ExceptionText;

Loc::loadMessages(__FILE__);


/**
 * @class Permission
 * @package Intervolga\Migrato\Data\Module\Highloadblock
 *
 * Класс для миграции прав доступа к Highload-блокам.
 */
class Permission extends BaseData
{
	protected function configure()
	{
		Loader::includeModule('highloadblock');
		$this->setVirtualXmlId(true);
		$this->setEntityNameLoc(Loc::getMessage('INTERVOLGA_MIGRATO.HIGHLOADBLOCK_PERMISSION'));
		$this->setFilesSubdir('/highloadblock/');
		$this->setDependencies(array(
			'HL_ID' => new Link(HighloadBlock::getInstance()),
			'GROUP_ID' => new Link(Group::getInstance()),
		));
	}

	/**
	 * Получает список всех прав на доступ к Highload-блоку, параллельно проверяя их на возможность экспорта
	 * @param array $filter
	 * @return array
	 */
	public function getList(array $filter = array())
	{
		$hlBlocks = HighloadBlockTable::getList();
		$result = array();
		while ($hlBlock = $hlBlocks->fetch())
		{
			$hlBlockId = $hlBlock['ID'];
			$rightsCursor = HighloadBlockRightsTable::getList(array(
				'filter' => array(
					'HL_ID' => $hlBlockId,
				),
			));
			while ($arPermission = $rightsCursor->fetch())
			{
				if (mb_strlen($arPermission['ACCESS_CODE']))
				{
					$accessCode = $arPermission['ACCESS_CODE'];
					if ($accessCode[0] == 'G')
					{
						$groupId = intval(mb_substr($accessCode, 1));
						$id = $this->createId(array(
							"HL_ID" => $hlBlockId,
							"GROUP_ID" => $groupId,
						));
						$record = new Record($this);
						$record->setXmlId($this->getXmlId($id));
						$record->setId($id);

						// Получаем по TASK_ID
						$taskId = $arPermission['TASK_ID'];
						$taskCursor = TaskTable::getList(array(
							'filter' => array('ID' => $taskId),
						));
						if ($arTask = $taskCursor->fetch())
						{
							$record->addFieldsRaw(array(
								"PERMISSION" => $arTask['NAME'],
							));

							$dependency = clone $this->getDependency("GROUP_ID");
							$dependency->setValue(
								Group::getInstance()->getXmlId(RecordId::createNumericId($groupId))
							);
							$record->setDependency("GROUP_ID", $dependency);

							$dependency = clone $this->getDependency("HL_ID");
							$dependency->setValue(
								HighloadBlock::getInstance()->getXmlId(RecordId::createNumericId($hlBlockId))
							);
							$record->setDependency("HL_ID", $dependency);

							$result[] = $record;
						}
						else
						{
							$this->showWarning(
								'INTERVOLGA_MIGRATO.HIGHLOADBLOCK_PERMISSION_VALUE_NOT_FOUND',
								array(
									'#HL_BLOCK_ID#' => $hlBlockId,
									'#TASK_ID#' => $taskId,
								)
							);
						}
					}
					else
					{
						$this->showWarning(
							'INTERVOLGA_MIGRATO.HIGHLOADBLOCK_PERMISSION_WRONG_ACCESS_CODE',
							array(
								'#HL_BLOCK_ID#' => $hlBlockId,
								'#ACCESS_CODE#' => $accessCode,
							)
						);
					}
				}
				else
				{
					$this->showWarning(
						'INTERVOLGA_MIGRATO.HIGHLOADBLOCK_PERMISSION_EMPTY_ACCESS_CODE',
						array(
							'#HL_BLOCK_ID#' => $hlBlockId,
						)
					);
				}
			}
		}

		return $result;
	}


	/**
	 * Показывает предупреждение, используя текст из локализации
	 * @param string $locText текст
	 * @param array $arReplaces файлы из замены текста
	 */
	private function showWarning($locText, $arReplaces)
	{
		$warning = Loc::getMessage($locText, $arReplaces);
		Application::getInstance()->renderWarning($warning);
	}


	/**
	 * Возвращает XML_ID для записи
	 * @param RecordId $id
	 * @return string
	 */
	public function getXmlId($id)
	{
		$array = $id->getValue();
		$hlData = HighloadBlock::getInstance();
		$groupData = Group::getInstance();
		$hlBlockXmlId = $hlData->getXmlId($hlData->createId($array['HL_ID']));
		$groupXmlId = $groupData->getXmlId($groupData->createId($array['GROUP_ID']));
		$md5 = md5(serialize(array(
			$hlBlockXmlId,
			$groupXmlId,
		)));
		return BaseXmlIdProvider::formatXmlId($md5);
	}

	/**
	 * Создает ID для записи
	 * @param mixed $id
	 * @return RecordId
	 */
	public function createId($id)
	{
		return RecordId::createComplexId(array(
				"HL_ID" => intval($id['HL_ID']),
				"GROUP_ID" => intval($id['GROUP_ID']),
			)
		);
	}


	public function update(Record $record)
	{
		$arCurValue = $record->getId()->getValue();
		$hlBlockId = $arCurValue["HL_ID"];
		$groupId = $arCurValue["GROUP_ID"];

		$this->createOrUpdate($hlBlockId, $groupId, $record);
	}

	protected function createInner(Record $record)
	{
		$hlLinkId = HighloadBlock::getInstance()->findRecord($record->getDependency("HL_ID")->getValue());
		$groupLinkId = Group::getInstance()->findRecord($record->getDependency("GROUP_ID")->getValue());
		if ($hlLinkId)
		{
			$hlBlockId = $hlLinkId->getValue();
			if ($groupLinkId)
			{
				$groupId = $groupLinkId->getValue();
				$this->createOrUpdate($hlBlockId, $groupId, $record);

				return $this->createId(array(
					"HL_ID" => $hlBlockId,
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
			throw new \Exception(Loc::getMessage('INTERVOLGA_MIGRATO.HIGHLOADBLOCK_NOT_FOUND'));
		}
	}

	/**
	 * Создает или обновляет право доступа к ИБ
	 * @param int $hlBlockId ID ХЛБ
	 * @param int $groupId ID группы
	 * @param Record $record запись
	 * @throws \Exception исключение в случае проблемы
	 */
	public function createOrUpdate($hlBlockId, $groupId, Record $record)
	{
		$arCurFields = $record->getFieldsRaw();
		$permission = $arCurFields['PERMISSION'];

		$taskCursor = TaskTable::getList(array(
			'filter' => array('NAME' => $permission),
		));
		// Если такое значение уровня доступа есть в Task Table
		if ($arTask = $taskCursor->fetch())
		{
			// Если такое правило есть для группы - обновляем его иначе, добавляем новое
			$taskId = $arTask['ID'];
			$rightsCursor = HighloadBlockRightsTable::getList(array(
				'filter' => array(
					'HL_ID' => $hlBlockId,
					'ACCESS_CODE' => 'G' . $groupId,
				),
			));
			if ($arRight = $rightsCursor->fetch())
			{
				HighloadBlockRightsTable::update($arRight['ID'], array(
					'TASK_ID' => $taskId,
				));
			}
			else
			{
				$result = HighloadBlockRightsTable::add(array(
					'HL_ID' => $hlBlockId,
					'ACCESS_CODE' => 'G' . $groupId,
					'TASK_ID' => $taskId,
				));
				if (!$result->isSuccess())
				{
					throw new \Exception(ExceptionText::getFromResult($result));
				}
			}
		}
		else
		{
			$this->showWarning(
				"INTERVOLGA_MIGRATO.HIGHLOADBLOCK_PERMISSION_NOT_FOUND",
				array(
					'#PERMISSION#' => $permission,
				)
			);
		}
	}

	protected function deleteInner(RecordId $id)
	{
		$arComplexId = $id->getValue();
		$hlBlockId = $arComplexId["HL_ID"];
		$groupId = $arComplexId['GROUP_ID'];

		$rightsCursor = HighloadBlockRightsTable::getList(array(
			'filter' => array(
				'HL_ID' => $hlBlockId,
				'ACCESS_CODE' => 'G' . $groupId,
			),
		));
		if ($arRight = $rightsCursor->fetch())
		{
			$result = HighloadBlockRightsTable::delete($arRight['ID']);
			if (!$result->isSuccess())
			{
				throw new \Exception(ExceptionText::getFromResult($result));
			}
		}
	}
}