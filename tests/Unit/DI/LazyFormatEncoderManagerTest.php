<?php declare(strict_types = 1);

namespace Tests\OriNette\DataSources\Unit\DI;

use OriNette\DataSources\DI\LazyFormatEncoderManager;
use OriNette\DI\Boot\ManualConfigurator;
use Orisai\DataSources\Bridge\NetteNeon\NeonFormatEncoder;
use Orisai\DataSources\Bridge\SymfonyYaml\YamlFormatEncoder;
use Orisai\DataSources\JsonFormatEncoder;
use PHPUnit\Framework\TestCase;
use function dirname;
use function get_class;
use function mkdir;
use const PHP_VERSION_ID;

final class LazyFormatEncoderManagerTest extends TestCase
{

	private string $rootDir;

	protected function setUp(): void
	{
		parent::setUp();

		$this->rootDir = dirname(__DIR__, 3);
		if (PHP_VERSION_ID < 8_01_00) {
			@mkdir("$this->rootDir/var/build");
		}
	}

	public function test(): void
	{
		$configurator = new ManualConfigurator($this->rootDir);
		$configurator->setForceReloadContainer();
		$configurator->addConfig(__DIR__ . '/LazyFormatEncoderManager.neon');

		$container = $configurator->createContainer();

		$manager = $container->getService('orisai.dataSources.encoders.manager');
		self::assertInstanceOf(LazyFormatEncoderManager::class, $manager);

		$expected = [
			JsonFormatEncoder::class => true,
			NeonFormatEncoder::class => true,
			YamlFormatEncoder::class => true,
		];

		// Test that instantiation of only few encoders does not break getting all
		foreach ($manager->getAll() as $encoder) {
			$class = get_class($encoder);
			self::assertTrue(isset($expected[$class]));

			break;
		}

		foreach ($manager->getAll() as $encoder) {
			$class = get_class($encoder);
			self::assertTrue(isset($expected[$class]));
			unset($expected[$class]);
		}

		self::assertSame([], $expected);
	}

}
