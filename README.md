ContentRepositoryImporter
=========================

This package contains generic utility to help importing data in the Neos Content Repository.

What's included ?
-----------------

* A command controller (CLI) to launch your import presets
* Based on simple conventions
* DataProvider: used to prepare and cleanup data from the external source
* Importer: get the data from the DataProvider and push everything in the CR
* DataType: Simple object used to cleanup value and share code between DataProvider
* Split your import in multiple sub commands to avoid high memory usage
* No big magic, you can always take control by overriding the default configuration and methods

A basic DataProvider
--------------------

Every data provider must extend the ``DataProvider`` abstract class or implement the
interface ```DataProviderInterface```. Check the source code of the abstract data provider, there are some useful things
to discover.

It's important to update the ```count``` property when you process data from the external source. During the processing,
you can decide to skip some data (invalid data, missing values, ...) so we can not use the SQL count feature.

Try to do most of the data cleaning up in the data provider, so the data would arrive to the importer ready for insertion. 
Basically the array build by the provider should contains the data with the property name that match your node type property name.
If you need to transport value that will not match the node properties, please prefix them with '_'. 

There is some magic value, those values MUST be on the first level of the array:

- **__identifier** (optional) This UUID will be used in the imported node, you should use ```AbstractImporter::applyProperties``` to have this feature, used by default
- **__externalIdentifier** (required) The external identifier of the data, this one is really important. The package keep track of imported data 
- **__label** (required) The label of this record used by the importer mainly for logging (this value is not imported, but useful to follow the process)
if you run twice the same import, the imported node will be updated and not created.

**Tips**: If the properties of your nodes are not at the first level of the array, you can override the method ```AbstractImporter::getPropertiesFromDataProviderPayload```

### Output of the provider 

Your provider should output something like this:

```
	[
		'__label' => 'The external content lable, for internal use'
		'__externalIdentifier' => 'The external external identifier, for internal use'
		'title' => 'My title'
		'year' => 1999
		'text' => '...'
	]
```

**Tips**: If your provider does not return an array, you MUST registrer a TypeConverter to convert it to an array. The property mapper is 
used automatically by the Importer. 

### Content Dimensions support

If your data provider follow this convention, the importer can automatically create variants of your nodes:

```
	[
		'__label' => 'The external content lable, for internal use'
		'__externalIdentifier' => 'The external external identifier, for internal use'
		'title' => 'My title'
		'year' => 1999
		'text' => '...',
                       
	    '@dimensions' => [
		   '@en' => [
			   '@strategy' => 'merge',
			   'title' => '...',
		   ],
		   '@fr' => [
			   '@strategy' => 'merge',
			   'title' => '...',
		   ],
	    ]
	]
```

The ```@en``` is a preset name, you must configuration the presets on your ```Settings.yaml```:

```
Ttree:
  ContentRepositoryImporter:
    dimensionsImporter:
      presets:
        fr:
	  language: ['fr', 'en', 'de']
        en:
	  language: ['en', 'de']
        de:
	  language: ['de']
```

### Share data between preset parts

You can split your import in multiple parts. Each parts is executed in a separate request. Sometimes it's useful to share data between parts (ex. in the first
part you import the taxonomy, and in the second parts you map documents with the taxonomy). Those solve this use case, we integrate a feature called **Vault**. The
Vault is simply a cache accessible in the importer and data provider by calling ```$this->vault->set($key, $name)``` and ```$this->vault->get($key)```. The
current preset is the namespace, so you can use simple keys like name, ids, ...

The cache is flushed if you call ```flow import:init --preset your-preset```. 

### Basic provider

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

Every data importer must extend the ``AbstractImporter`` abstract class or implement the interface ```ImporterInterface```.

In the `processRecord` method you handle the processing of every record, such as creating Content Repository node for each incoming data record.
 
Do not forget to register the processed nodes with `registerNodeProcessing`. The method will handle feature like logging and tracking of imported node to decide if the local node need to be created or updated.
 
```php
class ProductImporter extends AbstractImporter
{

    /**
     * @var string
     */
    protected $externalIdentifierDataKey = 'productNumber';

    /**
     * @var string
     */
    protected $labelDataKey = 'properties.name';

    /**
     * @var string
     */
    protected $nodeNamePrefix = 'product-';

    /**
     * @var string
     */
    protected $nodeTypeName = 'Acme.Demo:Product';

    /**
     * Starts batch processing all commands
     *
     * @return void
     * @api
     */
    public function process()
    {
        $this->initializeStorageNode('shop/products', 'products', 'Products', 'products');
        $this->initializeNodeTemplates();

        $nodeTemplate = new NodeTemplate();
        $this->processBatch($nodeTemplate);
    }

}

```

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

**Tips**: Do not forget to require this package from the package in which you do the importing, to ensure the correct loading order, so the settings would get overriden correctly.

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

Passing exceeding arguments to the DataProvider
-----------------------------------------------

The import process supports passing unnamed exceeding arguments to the `DataProvider`. This can be useful if you e.g. want to
allow importing only a single record

```
flow import:batch --preset base recordIdentifier:1234
```

Exceeding arguments will be available in the `DataProvider` through `$this->getExceedingArguments()`. You need to process
this data yourself and apply it to your fetching logic.

Command based importers
-----------------------

Some data sources may consist of commands rather than data records. For example, a JSON file may contain `create`,
`update` and `delete` instructions which reduce the guess-work on the importer's side, which records may be new,
which should be updated and if the absence of a record means that the corresponding node should be deleted from
the content repository.

For these cases you can extend the `AbstractCommandBasedImporter`. If your data records contain a `mode` field, the
importer will try to call a corresponding command method within the same class.

Consider the following data source file as an example:

```json
[
    {
        "mode": "create",
        "mpn": "1081251137",
        "languageIdentifier": "de",
        "properties": {
            "label": "Coffee Machine",
            "price": "220000",
            "externalKey": "1081251137"
        }
    },
    {
        "mode": "delete",
        "mpn": "591500202"
    }
]
```

A corresponding `ProductImporter` might look like this:

```php
/**
 * Class ProductImporter
 */
class ProductImporter extends AbstractCommandBasedImporter
{

    /**
     * @var string
     */
    protected $storageNodeNodePath = 'products';

    /**
     * @var string
     */
    protected $storageNodeTitle = 'Products';

    /**
     * @var string
     */
    protected $externalIdentifierDataKey = 'mpn';

    /**
     * @var string
     */
    protected $labelDataKey = 'properties.Label';

    /**
     * @var string
     */
    protected $nodeNamePrefix = 'product-';

    /**
     * @var string
    */
    protected $nodeTypeName = 'Acme.MyShop:Product';

    /**
     * Creates a new product
     *
     * @param string $externalIdentifier
     * @param array $data
     * @return void
     */
    protected function createCommand($externalIdentifier, array $data)
    {
        $this->applyProperties($data['properties'], $this->nodeTemplate);

        $node = $this->storageNode->createNodeFromTemplate($this->nodeTemplate);
        $this->registerNodeProcessing($node, $externalIdentifier);
    }

    /**
     * Updates a product
     *
     * @param string $externalIdentifier
     * @param array $data
     * @return void
     */
    protected function updateCommand($externalIdentifier, array $data)
    {
        $this->applyProperties($data['properties'], $this->nodeTemplate);

        $node = $this->storageNode->createNodeFromTemplate($this->nodeTemplate);
        $this->registerNodeProcessing($node, $externalIdentifier);
    }

    /**
     * Deletes a product
     *
     * @param string $externalIdentifier
     * @param array $data
     */
    protected function deleteCommand($externalIdentifier, array $data)
    {
        // delete the product node
    }
}
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
