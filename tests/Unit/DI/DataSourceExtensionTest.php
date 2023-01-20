<?php declare(strict_types = 1);

namespace Tests\OriNette\DataSources\Unit\DI;

use OriNette\DataSources\DI\LazyFormatEncoderManager;
use OriNette\DI\Boot\ManualConfigurator;
use Orisai\DataSources\Bridge\NetteNeon\NeonFormatEncoder;
use Orisai\DataSources\Bridge\SymfonyYaml\YamlFormatEncoder;
use Orisai\DataSources\DataSource;
use Orisai\DataSources\DefaultDataSource;
use Orisai\DataSources\JsonFormatEncoder;
use Orisai\Utils\Dependencies\DependenciesTester;
use PHPUnit\Framework\TestCase;
use Tests\OriNette\DataSources\Doubles\SerializeFormatEncoder;
use function dirname;
use function mkdir;
use function rtrim;
use function str_replace;
use const PHP_EOL;
use const PHP_VERSION_ID;

final class DataSourceExtensionTest extends TestCase
{

	private string $rootDir;

	protected function setUp(): void
	{
		parent::setUp();

		$this->rootDir = dirname(__DIR__, 4);
		if (PHP_VERSION_ID < 8_01_00) {
			@mkdir("$this->rootDir/var/build");
		}
	}

	public function testDefault(): void
	{
		$configurator = new ManualConfigurator($this->rootDir);
		$configurator->setForceReloadContainer();
		$configurator->addConfig(__DIR__ . '/DataSourceExtension.default.neon');

		$container = $configurator->createContainer();

		self::assertInstanceOf(LazyFormatEncoderManager::class, $container->getService('dataSource.encoders.manager'));
		self::assertInstanceOf(DefaultDataSource::class, $container->getService('dataSource.dataSource'));

		self::assertInstanceOf(JsonFormatEncoder::class, $container->getService('dataSource.encoder.json'));
		self::assertInstanceOf(NeonFormatEncoder::class, $container->getService('dataSource.encoder.neon'));
		self::assertInstanceOf(YamlFormatEncoder::class, $container->getService('dataSource.encoder.yaml'));

		$dataSource = $container->getByType(DataSource::class);

		self::assertSame(
			<<<'JSON'
{
    "foo": "bar"
}
JSON,
			str_replace("\n", PHP_EOL, $dataSource->encode(['foo' => 'bar'], 'json')),
		);

		self::assertSame(
			'foo: bar',
			rtrim($dataSource->encode(['foo' => 'bar'], 'neon'), "\n"),
		);

		self::assertSame(
			'foo: bar',
			rtrim($dataSource->encode(['foo' => 'bar'], 'yaml'), "\n"),
		);
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testCustomized(): void
	{
		$configurator = new ManualConfigurator($this->rootDir);
		$configurator->setForceReloadContainer();
		$configurator->addConfig(__DIR__ . '/DataSourceExtension.customized.neon');

		$container = $configurator->createContainer();

		self::assertInstanceOf(LazyFormatEncoderManager::class, $container->getService('dataSource.encoders.manager'));
		self::assertInstanceOf(DefaultDataSource::class, $container->getService('dataSource.dataSource'));

		self::assertInstanceOf(JsonFormatEncoder::class, $container->getService('dataSource.encoder.json'));
		self::assertInstanceOf(SerializeFormatEncoder::class, $container->getService('dataSource.encoder.neon'));
		self::assertInstanceOf(YamlFormatEncoder::class, $container->getService('dataSource.encoder.yaml'));
		self::assertInstanceOf(SerializeFormatEncoder::class, $container->getService('dataSource.encoder.serial'));

		$dataSource = $container->getByType(DataSource::class);

		self::assertSame(
			<<<'JSON'
{
    "foo": "bar"
}
JSON,
			str_replace("\n", PHP_EOL, $dataSource->encode(['foo' => 'bar'], 'json')),
		);

		SerializeFormatEncoder::addSupportedContentType('application/x-neon');
		self::assertSame(
			'a:1:{s:3:"foo";s:3:"bar";}',
			$dataSource->encode(['foo' => 'bar'], 'application/x-neon'),
		);

		self::assertSame(
			'foo: bar',
			rtrim($dataSource->encode(['foo' => 'bar'], 'yaml'), "\n"),
		);

		self::assertSame(
			'a:1:{s:3:"foo";s:3:"bar";}',
			$dataSource->encode(['foo' => 'bar'], 'serial'),
		);
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testOptionalEncoders(): void
	{
		$configurator = new ManualConfigurator($this->rootDir);
		$configurator->setForceReloadContainer();
		$configurator->addConfig(__DIR__ . '/DataSourceExtension.default.neon');
		$configurator->addStaticParameters([
			'__unique' => __METHOD__,
		]);

		DependenciesTester::addIgnoredPackages(['symfony/yaml']);

		$container = $configurator->createContainer();

		self::assertInstanceOf(LazyFormatEncoderManager::class, $container->getService('dataSource.encoders.manager'));
		self::assertInstanceOf(DefaultDataSource::class, $container->getService('dataSource.dataSource'));

		self::assertFalse($container->hasService('dataSource.encoder.yaml'));
	}

}
