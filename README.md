ContentRepositoryImporter
=========================

This package contains generic utility to help importing data in the TYPO3 Content Repository (TYPO3CR) used by Neos.

**This package is currently in developement, change in the API can happen, as soon as we definea stable state 
we'll launch version 1.0. Any contributions and comments are welcome.**

What's included ?
-----------------

* A command controller (CLI) to launch your import presets
* Based on simple convention
* DataProvider: used to prepare and cleanup data from the external source
* Importer: get the data from the DataProvider and push everything in the CR
* DataType: Simple object used to cleanup value and share code between DataProvider
* No magic, the big part of the work is on your side

A basic DataProvider
--------------------

Every dataprovider must extends the ``DataProvider`` abstract class or implements the 
interface ```DtaProviderInterface```. Check the source code of the abstract data provider, there's some useful things.

It's important to update the ```cont``` property when you process data from the external source. During the processing,
you can decide to skip some data (invalid data, missing value, ...) so we can not use the SQL count feature.

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
				'name' => String::create($reportType['name'])->getValue()
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

You can configure an import preset in your ```Settings.yaml```. A preset is splitted in multiple parts. If you use the
```batchSize```, the current part will be executed by batch, by using a sub CLI request. This can solve memory or
performance issue for big import.

```yaml
Ttree:
  ContentRepositoryImporter:
    presets:
      'base':
        'news':
          label: 'News Import'
          dataProviderClassName: 'Your\Package\Importer\DataProvider\NewsDataProvider'
          importerClassName: 'Your\Package\Importer\Importer\NewsImporter'

        'page':
          label: 'Page Import'
          dataProviderClassName: 'Your\Package\Importer\DataProvider\PageDataProvider'
          dataProviderOptions:
            someOption: 'Some option that will be available in the options property of the data provider'
          importerClassName: 'Your\Package\Importer\Importer\PageImporter'
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

Acknowledgments
---------------

Development sponsored by [ttree ltd - neos solution provider](http://ttree.ch).

We try our best to craft this package with a lots of love, we are open to sponsoring, support request, ... just contact us.

License
-------

Licensed under GPLv3+, see [LICENSE](LICENSE)