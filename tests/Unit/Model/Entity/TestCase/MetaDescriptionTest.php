<?php declare(strict_types = 1);

namespace Ajda2\WebsiteChecker\Tests\Unit\Model\Entity\TestCase;

use Ajda2\WebsiteChecker\Model\Entity\TestCase\MetaDescription;
use Ajda2\WebsiteChecker\Model\Entity\TestResultInterface;
use Nette\Http\Url;
use Nette\Utils\Strings;
use PHPUnit\Framework\TestCase;

class MetaDescriptionTest extends TestCase {

	/** @var MetaDescription */
	private $item;

	/** @var Url */
	private $url;

	public function setUp() {
		parent::setUp();

		$code = 'Code';
		$name = 'Name';

		$this->item = new MetaDescription($code, $name);
		$this->url = new Url('https://www.surface.cz/');
	}

	public function testConstructor(): void {
		$code = 'Code';
		$name = 'Name';

		$this->item = new MetaDescription($code, $name);

		$this->assertInstanceOf(MetaDescription::class, $this->item);
		$this->assertSame($code, $this->item->getCode());
		$this->assertSame($name, $this->item->getName());
	}

	/**
	 * @throws \Exception
	 */
	public function testRunSuccess(): void {
		$contents = [
			'desc content',
			' desc content ',
			'ěščřžáýíéů',
		];
		$format = '<!DOCTYPE html><html lang="cs"><head><meta charset="utf-8"><meta name="description" content="%s"></head><body></body></html>';

		foreach ($contents as $content) {
			$source = \sprintf($format, $content);
			$document = new \DOMDocument();
			$document->loadHTML($source);

			$result = $this->item->run($this->url, $document);

			$this->assertInstanceOf(TestResultInterface::class, $result);
			$this->assertSame(Strings::trim($content), $result->getValue());
			$this->assertTrue($result->isSuccess());
			$this->assertFalse($result->isFail());
		}
	}

	/**
	 * @throws \Exception
	 */
	public function testRunFail(): void {
		$contents = [
			'',
			' ',
			'	',
		];
		$format = '<!DOCTYPE html><html lang="cs"><head><meta charset="utf-8"><meta name="description" content="%s"></head><body></body></html>';

		foreach ($contents as $content) {
			$source = \sprintf($format, $content);
			$document = new \DOMDocument();
			$document->loadHTML($source);

			$result = $this->item->run($this->url, $document);

			$this->assertInstanceOf(TestResultInterface::class, $result);
			$this->assertSame(Strings::trim($content), $result->getValue());
			$this->assertFalse($result->isSuccess());
			$this->assertTrue($result->isFail());
		}
	}

	/**
	 * @throws \Exception
	 */
	public function testRunNoContent(): void {
		$source = '<!DOCTYPE html><html lang="cs"><head><meta charset="utf-8"><meta name="description"></head><body></body></html>';

		$document = new \DOMDocument();
		$document->loadHTML($source);

		$result = $this->item->run($this->url, $document);

		$this->assertInstanceOf(TestResultInterface::class, $result);
		$this->assertFalse($result->isSuccess());
		$this->assertTrue($result->isFail());
	}

	/**
	 * @throws \Exception
	 */
	public function testRunNoTag(): void {
		$source = '<!DOCTYPE html><html lang="cs"><head><meta charset="utf-8"></head><body></body></html>';
		$document = new \DOMDocument();
		$document->loadHTML($source);

		$result = $this->item->run($this->url, $document);

		$this->assertInstanceOf(TestResultInterface::class, $result);
		$this->assertNull($result->getValue());
		$this->assertFalse($result->isSuccess());
		$this->assertTrue($result->isFail());
	}
}
