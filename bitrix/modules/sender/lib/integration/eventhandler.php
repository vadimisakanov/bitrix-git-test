<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sender
 * @copyright 2001-2017 Bitrix
 */

namespace Bitrix\Sender\Integration;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Entity as MainEntity;

use Bitrix\Sender\Message;
use Bitrix\Sender\Entity;
use Bitrix\Sender\Dispatch;
use Bitrix\Sender\Templates;
use Bitrix\Sender\Internals\Model\LetterTable;

Loc::loadMessages(__FILE__);

/**
 * Class EventHandler
 * @package Bitrix\Sender\Integration\Crm
 */
class EventHandler
{
	/**
	 * Handler of event sender/onSenderPresetList.
	 *
	 * @return array
	 */
	public static function onSenderPresetList()
	{
		$list = array();

		if (ModuleManager::isModuleInstalled('crm'))
		{
			$list[] = 'Bitrix\Sender\Integration\Crm\Preset\Segment';
		}

		return $list;
	}

	/**
	 * Handler of event sender/onSenderPresetList.
	 *
	 * @return array
	 */
	public static function onSenderCompanyCard()
	{
		$list = array();

		if (Loader::includeModule('crm'))
		{
			$list[] = Crm\CompanyCard::getArray();
		}

		return $list;
	}

	/**
	 * Handler of event sender/OnAfterPostingSendRecipient.
	 *
	 * @param array $eventData Event.
	 * @param Entity\Letter $letter Letter.
	 */
	public static function onAfterPostingSendRecipient(array $eventData, Entity\Letter $letter)
	{
		if (ModuleManager::isModuleInstalled('crm'))
		{
			Crm\EventHandler::onAfterPostingSendRecipient($eventData, $letter);
		}

		if (Bitrix24\Service::isCloud() && $eventData['SEND_RESULT'])
		{
			Bitrix24\Limitation\DailyLimit::increment();
		}
	}

	/**
	 * Handler of event sender/onAfterPostingRecipientUnsubscribe.
	 *
	 * @param array $eventData Event.
	 * @return void
	 */
	public static function onAfterPostingRecipientUnsubscribe(array $eventData)
	{
		if (Bitrix24\Service::isCloud())
		{
			Bitrix24\Limitation\Rating::regulate();
		}
	}

	/**
	 * Handler of event sender/onConstantList.
	 *
	 * @param string $className Class name.
	 * @return array
	 */
	public static function onConstantList($className)
	{
		// sale
		if (Loader::includeModule('sale') && !Bitrix24\Service::isCloud())
		{
			if (Templates\Category::class === $className)
			{
				return Sale\Preset\TriggerCampaign::getTemplateCategories();
			}
		}

		return [];
	}

	/**
	 * Handler of event sender/onTemplateList.
	 *
	 * @return array
	 */
	public static function onTemplateList($messageType)
	{
		$list = [
			Message\iBase::CODE_MAIL => []
		];

		// sale
		if (Loader::includeModule('sale') && !Bitrix24\Service::isCloud())
		{
			foreach (Sale\Preset\TriggerCampaign::getAll() as $item)
			{
				foreach ($item['CHAIN'] as $letter)
				{
					$list[Message\iBase::CODE_MAIL][] = array(
						'ID' => $letter['TEMPLATE_ID'],
						'TYPE' => $letter['TEMPLATE_TYPE'],
						'CATEGORY' => strtoupper($item['CODE']),
						'MESSAGE_CODE' => Message\iBase::CODE_MAIL,
						'VERSION' => 2,
						'IS_TRIGGER' => true,
						'HOT' => false,
						'ICON' => false,

						'NAME' => $letter['SUBJECT'],
						'DESC' => '',
						'FIELDS' => array(
							'SUBJECT' => array(
								'CODE' => 'SUBJECT',
								'VALUE' => $letter['SUBJECT'],
							),
							'MESSAGE' => array(
								'CODE' => 'MESSAGE',
								'VALUE' => $letter['MESSAGE'],
								'ON_DEMAND' => true
							),
						),
					);
				}
			}
		}

		return isset($list[$messageType]) ? $list[$messageType] : [];
	}

	/**
	 * Handler of event sender/onConnectorList.
	 *
	 * @param array $data Data.
	 * @return array
	 */
	public static function onConnectorList(array $data = array())
	{
		// sender
		$data['CONNECTOR'][] = 'Bitrix\Sender\Integration\Sender\Connectors\Recipient';
		$data['CONNECTOR'][] = 'Bitrix\Sender\Integration\Sender\Connectors\Contact';
		$data['CONNECTOR'][] = 'Bitrix\Sender\Integration\Sender\Connectors\UnSubscribers';

		// crm
		if (Loader::includeModule('crm'))
		{
			$data['CONNECTOR'][] = 'Bitrix\Sender\Integration\Crm\Connectors\Lead';
			$data['CONNECTOR'][] = 'Bitrix\Sender\Integration\Crm\Connectors\Client';
		}

		return $data;
	}

	/**
	 * Handler of event sender/onSenderMessageList.
	 *
	 * @return array
	 */
	public static function onSenderMessageList()
	{
		$list = array();

		// mail
		$list[] = 'Bitrix\Sender\Integration\Sender\Mail\MessageMail';

		// sms
		if (MessageService\Sms\Service::canUse())
		{
			$list[] = 'Bitrix\Sender\Integration\MessageService\Sms\MessageSms';
		}

		// im
		if (Im\Service::canUse())
		{
			$list[] = 'Bitrix\Sender\Integration\Im\MessageIm';
		}

		// call
		if (VoxImplant\Service::canUse())
		{
			$list[] = 'Bitrix\Sender\Integration\VoxImplant\MessageCall';
		}

		// web_hook
		//$list[] = 'Bitrix\Sender\Integration\Sender\WebHook\MessageWebHook';

		// Ads
		if (Seo\Ads\Service::canUse())
		{
			$adsList = array(
				'Bitrix\Sender\Integration\Seo\Ads\MessageYa',
				'Bitrix\Sender\Integration\Seo\Ads\MessageGa',
				'Bitrix\Sender\Integration\Seo\Ads\MessageVk',
				'Bitrix\Sender\Integration\Seo\Ads\MessageFb',
			);
			foreach ($adsList as $adsClass)
			{
				if (!Bitrix24\Service::isAdVisibleInRegion($adsClass::CODE))
				{
					continue;
				}

				$list[] = $adsClass;
			}
		}

		// Return Customer
		if (Crm\ReturnCustomer\Service::canUse())
		{
			$list[] = 'Bitrix\Sender\Integration\Crm\ReturnCustomer\MessageLead';
			$list[] = 'Bitrix\Sender\Integration\Crm\ReturnCustomer\MessageDeal';
		}

		return $list;
	}

	/**
	 * Handler of event sender/OnPresetMailingList.
	 *
	 * @return array
	 */
	public static function onSenderTriggerCampaignPreset()
	{
		$list = [];

		// sale
		if (Loader::includeModule('sale') && !Bitrix24\Service::isCloud())
		{
			$list = array_merge($list, Sale\Preset\TriggerCampaign::getAll());
		}


		return $list;
	}

	/**
	 * Handler of event sender/onSenderTransportList.
	 *
	 * @return array
	 */
	public static function onSenderTransportList()
	{
		$list = array();

		// mail
		$list[] = 'Bitrix\Sender\Integration\Sender\Mail\TransportMail';

		// sms
		if (MessageService\Sms\Service::canUse())
		{
			$list[] = 'Bitrix\Sender\Integration\MessageService\Sms\TransportSms';
		}

		// im
		if (Im\Service::canUse())
		{
			$list[] = 'Bitrix\Sender\Integration\Im\TransportIm';
		}

		// call
		if (VoxImplant\Service::canUse())
		{
			$list[] = 'Bitrix\Sender\Integration\VoxImplant\TransportCall';
		}

		// web_hook
		//$list[] = 'Bitrix\Sender\Integration\Sender\WebHook\TransportWebHook';

		// Ads
		if (Seo\Ads\Service::canUse())
		{
			$list[] = 'Bitrix\Sender\Integration\Seo\Ads\TransportYa';
			$list[] = 'Bitrix\Sender\Integration\Seo\Ads\TransportGa';
			$list[] = 'Bitrix\Sender\Integration\Seo\Ads\TransportVk';
			$list[] = 'Bitrix\Sender\Integration\Seo\Ads\TransportFb';
		}

		// Return Customer
		if (Crm\ReturnCustomer\Service::canUse())
		{
			$list[] = 'Bitrix\Sender\Integration\Crm\ReturnCustomer\TransportLead';
			$list[] = 'Bitrix\Sender\Integration\Crm\ReturnCustomer\TransportDeal';
		}

		return $list;
	}

	/**
	 * On before update LetterTable.
	 *
	 * @param MainEntity\Event $event Event.
	 * @param MainEntity\EventResult $result Result.
	 * @return void
	 */
	public static function onBeforeUpdateLetterTable(MainEntity\Event $event, MainEntity\EventResult $result)
	{
		$data = $event->getParameters();

		if (Bitrix24\Service::isCloud() && isset($data['fields']['STATUS']))
		{
			$oldRow = LetterTable::getRowById($data['primary']['ID']);
			if ($oldRow['MESSAGE_CODE'] !== Message\iBase::CODE_MAIL)
			{
				return;
			}

			$isEmailBlocked = Bitrix24\Limitation\Rating::isBlocked();
			if($isEmailBlocked && in_array($data['fields']['STATUS'], Dispatch\Semantics::getWorkStates()))
			{
				$result->addError(
					new MainEntity\EntityError(
						Bitrix24\Limitation\Rating::getNotifyText('blocked')
					)
				);
			}
		}
	}
}