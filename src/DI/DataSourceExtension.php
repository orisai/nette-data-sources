<?php declare(strict_types = 1);

namespace OriNette\DataSources\DI;

use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use OriNette\DI\Definitions\DefinitionsLoader;
use Orisai\DataSources\Bridge\NetteNeon\NeonFormatEncoder;
use Orisai\DataSources\Bridge\SymfonyYaml\YamlFormatEncoder;
use Orisai\DataSources\DataSource;
use Orisai\DataSources\DefaultDataSource;
use Orisai\DataSources\FormatEncoder;
use Orisai\DataSources\FormatEncoderManager;
use Orisai\DataSources\JsonFormatEncoder;
use Orisai\Utils\Dependencies\Dependencies;
use stdClass;
use function array_keys;

/**
 * @property-read stdClass $config
 */
final class DataSourceExtension extends CompilerExtension
{

	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'encoders' => Expect::arrayOf(
				DefinitionsLoader::schema(),
			),
		]);
	}

	public function loadConfiguration(): void
	{
		parent::loadConfiguration();
		$loader = new DefinitionsLoader($this->compiler);
		$builder = $this->getContainerBuilder();
		$config = $this->config;

		$encoderDefinitions = [];

		$jsonDefinition = $this->addCoreEncoder('json', JsonFormatEncoder::class);
		$encoderDefinitions[$jsonDefinition->getName()] = $jsonDefinition;

		$neonDefinition = $this->addCoreEncoder('neon', NeonFormatEncoder::class);
		$encoderDefinitions[$neonDefinition->getName()] = $neonDefinition;

		if (Dependencies::isPackageLoaded('symfony/yaml')) {
			$yamlDefinition = $this->addCoreEncoder('yaml', YamlFormatEncoder::class);
			$encoderDefinitions[$yamlDefinition->getName()] = $yamlDefinition;
		}

		foreach ($config->encoders as $encoderName => $encoderConfig) {
			$encoderKey = $this->prefix("encoder.{$encoderName}");
			$encoderDefinitions[$encoderKey] = $loader->loadDefinitionFromConfig(
				$encoderConfig,
				$encoderKey,
			);
		}

		$encoderManagerDefinition = $builder->addDefinition($this->prefix('encoders.manager'))
			->setFactory(LazyFormatEncoderManager::class, [array_keys($encoderDefinitions)])
			->setType(FormatEncoderManager::class)
			->setAutowired(false);

		$builder->addDefinition($this->prefix('dataSource'))
			->setFactory(DefaultDataSource::class, [$encoderManagerDefinition])
			->setType(DataSource::class);
	}

	/**
	 * @param class-string<FormatEncoder> $className
	 */
	private function addCoreEncoder(string $encoderName, string $className): ServiceDefinition
	{
		$builder = $this->getContainerBuilder();
		$definitionName = $this->prefix("encoder.{$encoderName}");

		return $builder->addDefinition($definitionName)
			->setFactory($className)
			->setAutowired(false);
	}

}
