# Nette Data Sources

Orisai Data Sources integration for Nette

## Content

- [Setup](#setup)
- [Usage](#usage)
- [Registering encoders](#registering-encoders)

## Setup

Install with [Composer](https://getcomposer.org)

```sh
composer require orisai/nette-data-sources
```

Register extension

```neon
extensions:
	orisai.dataSources: OriNette\DataSources\DI\DataSourceExtension
```

## Usage

Check [orisai/data-sources](https://github.com/orisai/data-sources) docs first for data source usage, available formats
and implementing own formats.

This package is just lightweight nette/di integration.

## Registering encoders

Json and neon encoders are auto-registered. Yaml is auto-registered if `symfony/yaml` is installed.

Register custom encoders via extension

```neon
orisai.dataSource:
	encoders:
		example: Example\ExampleFormatEncoder()
```
