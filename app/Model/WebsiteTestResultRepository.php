<?php declare(strict_types = 1);

namespace Ajda2\WebsiteChecker\Model;


use Ajda2\WebsiteChecker\Model\Entity\TestResult;
use Ajda2\WebsiteChecker\Model\Entity\TestResultInterface;
use Nette\Database\Context;
use Nette\Database\DriverException;
use Nette\Database\Table\ActiveRow;
use Nette\SmartObject;
use Nette\Utils\ArrayHash;
use Tracy\ILogger;

class WebsiteTestResultRepository {

	use SmartObject;

	/** @var string */
	public const TABLE_TEST_RESULT = 'website_test_result';

	/** @var string */
	public const COLUMN_TEST_RESULT_ID = 'id';

	/** @var string */
	public const COLUMN_TEST_RESULT_WEBSITE_ID = 'website_id';

	/** @var string */
	public const COLUMN_TEST_RESULT_RUN_AT = 'run_at';

	/** @var string */
	public const COLUMN_TEST_RESULT_TEST_CODE = 'test_code';

	/** @var string */
	public const COLUMN_TEST_RESULT_IS_SUCCESS = 'is_success';

	/** @var string */
	public const COLUMN_TEST_RESULT_VALUE = 'value';

	/** @var string */
	public const COLUMN_TEST_RESULT_DESCRIPTION = 'description';

	/** @var Context */
	private $database;

	/** @var ILogger */
	private $logger;

	public function __construct(Context $database, ILogger $logger) {
		$this->database = $database;
		$this->logger = $logger;
	}

	public function removeWebsiteResults(int $websiteId): bool {
		$by = [
			self::COLUMN_TEST_RESULT_WEBSITE_ID => $websiteId
		];
		try {
			$this->database->table(self::TABLE_TEST_RESULT)->where($by)->delete();
		} catch (DriverException $e) {
			$this->logger->log($e, $this->logger::ERROR);

			return FALSE;
		}

		return TRUE;
	}

	/**
	 * @param array|int[] $websiteIds
	 * @return ArrayHash
	 */
	public function getResults(array $websiteIds): ArrayHash {
		$by = [
			self::COLUMN_TEST_RESULT_WEBSITE_ID => $websiteIds
		];
		$result = new ArrayHash();

		/** @var ActiveRow $row */
		foreach ($this->database->table(self::TABLE_TEST_RESULT)->where($by) as $row) {
			$websiteId = (int)$row->offsetGet(self::COLUMN_TEST_RESULT_WEBSITE_ID);

			if (!$result->offsetExists($websiteId)) {
				$result->offsetSet($websiteId, new ArrayHash());
			}

			$result[$websiteId][$row->offsetGet(self::COLUMN_TEST_RESULT_TEST_CODE)] = $this->fromRowFactory($row);
		}

		return $result;
	}

	/**
	 * @param TestResultInterface $testResult
	 * @param int                 $websiteId
	 * @return TestResultInterface
	 * @throws PersistException
	 */
	public function storeResult(TestResultInterface $testResult, int $websiteId): TestResultInterface {
		$data = [
			self::COLUMN_TEST_RESULT_WEBSITE_ID  => $websiteId,
			self::COLUMN_TEST_RESULT_TEST_CODE   => $testResult->getTestCode(),
			self::COLUMN_TEST_RESULT_RUN_AT      => $testResult->getRunAt(),
			self::COLUMN_TEST_RESULT_IS_SUCCESS  => $testResult->isSuccess(),
			self::COLUMN_TEST_RESULT_VALUE       => $testResult->getValue(),
			self::COLUMN_TEST_RESULT_DESCRIPTION => $testResult->getDescription()
		];

		try {
			/** @var ActiveRow $row */
			$row = $this->database->table(self::TABLE_TEST_RESULT)->insert($data);

			return $this->fromRowFactory($row);
		} catch (DriverException $e) {
			$this->logger->log($e, $this->logger::ERROR);

			throw new PersistException();
		}
	}

	private function fromRowFactory(ActiveRow $row): TestResultInterface {
		return new TestResult(
			$row->offsetGet(self::COLUMN_TEST_RESULT_TEST_CODE),
			$row->offsetGet(self::COLUMN_TEST_RESULT_RUN_AT),
			(bool)$row->offsetGet(self::COLUMN_TEST_RESULT_IS_SUCCESS),
			$row->offsetGet(self::COLUMN_TEST_RESULT_VALUE),
			$row->offsetGet(self::COLUMN_TEST_RESULT_DESCRIPTION)
		);
	}
}