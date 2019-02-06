<?php declare(strict_types = 1);

namespace Ajda2\WebsiteChecker\Model;


use Ajda2\WebsiteChecker\Model\Entity\TestInterface;
use Ajda2\WebsiteChecker\Model\Entity\TestResultInterface;
use Ajda2\WebsiteChecker\Model\Entity\WebsiteInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Masterminds\HTML5;
use Nette\SmartObject;
use Nette\Utils\ArrayHash;
use Psr\Http\Message\ResponseInterface;

/**
 * Class Tester
 * @package Ajda2\WebsiteChecker\Model
 * @method onTestSuccess(Tester $tester, WebsiteInterface $website, TestInterface $test, TestResultInterface $testResult)
 * @method onTestFail(Tester $tester, WebsiteInterface $website, TestInterface $test, TestResultInterface $testResult)
 * @method onWebResponse(Tester $tester, WebsiteInterface $website, ResponseInterface $response)
 * @method onWebResponseFail(Tester $tester, WebsiteInterface $website)
 */
class Tester {

	use SmartObject;

	/** @var array|callable[]|\Closure[] */
	public $onTestSuccess = [];

	/** @var array|callable[]|\Closure[] */
	public $onTestFail = [];

	/** @var array|callable[]|\Closure[] */
	public $onWebResponse = [];

	/** @var array|callable[]|\Closure[] */
	public $onWebResponseFail = [];

	/** @var ArrayHash|TestInterface[] */
	private $tests;

	public function __construct() {
		$this->tests = new ArrayHash();
	}

	public function addTest(TestInterface $test): Tester {
		$this->tests->offsetSet($test->getCode(), $test);

		return $this;
	}

	public function removeTest(string $code): Tester {
		if ($this->tests->offsetExists($code)) {
			$this->tests->offsetUnset($code);
		}

		return $this;
	}

	public function getTests(): ArrayHash {
		return clone $this->tests;
	}

	public function runTests(WebsiteInterface $website, float $requestTimeout): bool {
		$client = new Client();

		try {
			$response = $client->request('GET', (string)$website->getUrl(), ['timeout' => $requestTimeout,]);

			$this->onWebResponse($this, $website, $response);
		} catch (GuzzleException $e) {
			$this->onWebResponseFail($this, $website);

			return FALSE;
		}

		$doc = new HTML5();

		foreach ($this->tests as $test) {
			$result = $test->run($doc->loadHTML((string)$response->getBody()));

			if ($result->isSuccess()) {
				$this->onTestSuccess($this, $website, $test, $result);
			} else {
				$this->onTestFail($this, $website, $test, $result);
			}
		}

		return TRUE;
	}
}