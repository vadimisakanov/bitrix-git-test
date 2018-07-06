<?namespace Intervolga\Migrato\Tool\Console\Command;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Intervolga\Migrato\Tool\Console\Logger;

Loc::loadMessages(__FILE__);

class ReIndexCommand extends BaseCommand
{
	const MAX_EXEC_TIME = 40;

	protected function configure()
	{
		$this->setName('reindex');
		$this->setHidden(true);
		$this->setDescription(Loc::getMessage('INTERVOLGA_MIGRATO.REINDEX_DESCRIPTION'));
	}

	public function executeInner()
	{
		if (Loader::includeModule('search'))
		{
			if (!defined('BX_UTF'))
			{
				ini_set("mbstring.internal_encoding", "cp1251");
			}

			$result = array();
			$maxExecTime = static::MAX_EXEC_TIME;

			do
			{
				$result = \CSearch::ReIndexAll(true, $maxExecTime, $result);
				$maxExecTime += static::MAX_EXEC_TIME;

				if (is_array($result))
				{
					$this->logger->add(
						Loc::getMessage(
							'INTERVOLGA_MIGRATO.REINDEX_RESULT',
							array(
								'#COUNT#' => $result['CNT'],
							)
						),
						Logger::LEVEL_DETAIL,
						Logger::TYPE_INFO
					);
				}
			}
			while (is_array($result));
			$count = $result;

			$this->logger->registerFinal(
				Loc::getMessage(
					'INTERVOLGA_MIGRATO.REINDEX_RESULT',
					array(
						'#COUNT#' => $count,
					)
				),
				Logger::TYPE_OK
			);
		}
		else
		{
			$this->logger->registerFinal(
				Loc::getMessage('INTERVOLGA_MIGRATO.NO_SEARCH_MODULE'),
				Logger::TYPE_INFO
			);
		}
	}
}