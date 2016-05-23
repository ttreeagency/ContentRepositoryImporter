ContentRepositoryImporter
=========================

This package contains generic utility to help importing data in the TYPO3 Content Repository (TYPO3CR) used by Neos.

What's included ?
-----------------

* A command controller (CLI) to launch your import presets
* Based on simple conventions
* DataProvider: used to prepare and cleanup data from the external source
* Importer: get the data from the DataProvider and push everything in the CR
* DataType: Simple object used to cleanup value and share code between DataProvider
* No big magic, you can always take control by overriding the default configuration and methods

A basic DataProvider
--------------------

Every data provider must extend the ``DataProvider`` abstract class or implement the
interface ```DataProviderInterface```. Check the source code of the abstract data provider, there are some useful things
to discover.

It's important to update the ```count``` property when you process data from the external source. During the processing,
you can decide to skip some data (invalid data, missing values, ...) so we can not use the SQL count feature.

```php
class BasicDataProvider extends DataProvider {

	/**
	 * @return array
	 */
	public function fetch() {
		$result = [];
		$query = $this->createQuery()
			->select('*')
			->from('demo_table', 'd')
			->orderBy('d.name');

		$statement = $query->execute();
		while ($demoRecord = $statement->fetch()) {
			$result[] = [
				'__externalIdentifier' => (integer)$demoRecord['id'],
				'name' => String::create($demoRecord['name'])->getValue()
			];
		}

		$this->count = count($result);

		return $result;
	}

}
```

A basic Importer
----------------

TODO

A basic preset
--------------

You can configure an import preset in your ```Settings.yaml```. A preset is split in multiple parts. If you use the
```batchSize```, the current part will be executed by batch, by using a sub CLI request. This can solve memory or
performance issue for big imports.

```yaml
Ttree:
  ContentRepositoryImporter:
    sources:
      default:
        host: localhost
        driver: pdo_mysql
        dbname: database
        user: user
        password: password
      extraSourceDatabase:
        host: localhost
        driver: pdo_mysql
        dbname: database
        user: user
        password: password

    presets:
      'base':
        parts:
          'news':
            label: 'News Import'
            dataProviderClassName: 'Your\Package\Importer\DataProvider\NewsDataProvider'
            importerClassName: 'Your\Package\Importer\Importer\NewsImporter'
          'page':
            label: 'Page Import'
            dataProviderClassName: 'Your\Package\Importer\DataProvider\PageDataProvider'
            dataProviderOptions:
              source: 'extraSourceDatabase'
              someOption: 'Some option that will be available in the options property of the data provider'
            importerClassName: 'Your\Package\Importer\Importer\PageImporter'
            importerOptions:
              siteNodePath: '/sites/my-site'
              someOption: 'Some option that will be available in the options property of the importer'
            batchSize': 120

          'pageContent':
            label: 'Page Content Import'
            dataProviderClassName: 'Your\Package\Importer\DataProvider\PageContentDataProvider'
            importerClassName: 'Your\Package\Importer\Importer\PageContentImporter'
            batchSize: 120
```

Start your import process
-------------------------

From the CLI:

```
flow import:batch --preset base
```

You can also filter the preset steps:

```
flow import:batch --preset base --parts page,pageContent
```

For testing purposes, or if you would like to override the value defined in your preset, you can also specify the number
of records which should be imported at a time in an isolated sub-process:

```
flow import:batch --preset base --batch-size 50
```

CSV Data Provider
-----------------

This package comes with a basic data provider for CSV files which will suffice for many scenarios. The class name for
this data provider is `Ttree\ContentRepositoryImporter\DataProvider\CsvDataProvider`.

The following options can be passed to the data provider:

- `csvFilePath`: the full path and filename leading to the file to import
- `csvDelimiter`: the delimiter used in the CSV file (default: `,`)
- `csvEnclosure`: the character which is used for enclosing the values (default: `"`)
- `skipHeader`: if the first line in the CSV file should be ignored (default: false)

Here is an example for a preset using the CSV Data Provider:

```yaml
Ttree:
  ContentRepositoryImporter:
    presets:
      'products':
        parts:
          'products':
            label: 'Product Import'
            batchSize: 100
            dataProviderClassName: 'Ttree\ContentRepositoryImporter\DataProvider\CsvDataProvider'
            dataProviderOptions:
              csvFilePath: '/tmp/Products.csv'
              csvDelimiter: ';'
              csvEnclosure: '"'
              skipHeader: true
            importerClassName: 'Acme\MyProductImporter\Service\Import\ProductImporter'
            importerOptions:
              siteNodePath: '/sites/wwwacmecom'
```


Acknowledgments
---------------

Development sponsored by [ttree ltd - neos solution provider](http://ttree.ch).

We try our best to craft this package with a lots of love, we are open to sponsoring, support request, ... just contact us.

License
-------

Licensed under GPLv3+, see [LICENSE](LICENSE)
