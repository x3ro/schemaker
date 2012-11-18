Schemaker: Fluid ViewHelper XSD Schema Generator
================================================

## What is it?

Schemaker is primarily a backport with some adaptations for TYPO3v4, from TYPO3.Fluid (i.e. not the TYPO3v4 version of Fluid).

## What does it do?

Schemaker generates XSD files (XML Schema Definition) which is a standardised format that can be referenced from an XML file to
define which tags are allowed, which attributes they use and which values those attributes must have. Specific to Fluid templates,
it lets you autocomplete tag names and attributes and quickly add all required attributes when you add a tag.

## How does it work?

Schemaker analyses each ViewHelper and detects the possible arguments, their descriptions, wether or not they are required and
their default values. It then creates XML nodes with schemas for each ViewHelper and finally adds the nodes to the XSD file, which
is then output (and should be redirected into a file and published online; see examples).

Schemaker runs in CLI mode:

```bash
me@localhost:~/documentroot $ ./typo3/cli_dispatch.phpsh extbase help schema:generate

Generate Fluid ViewHelper XSD Schema

COMMAND:
  schemaker:schema:generate

USAGE:
  ./cli_dispatch.phpsh extbase schema:generate [<options>] <extension key>

ARGUMENTS:
  --extension-key      Namespace of the Fluid ViewHelpers without leading
                       backslash (for example 'TYPO3\Fluid\ViewHelpers'). NOTE:
                       Quote and/or escape this argument as needed to avoid
                       backslashes from being interpreted!

OPTIONS:
  --xsd-namespace      Unique target namespace used in the XSD schema (for
                       example "http://yourdomain.org/ns/viewhelpers").
                       Defaults to "http://typo3.org/ns/<php namespace>".
  --namespace-alias    Alias to use in the XSD file for the extension's
                       ViewHelpers, for example EXT:my_complex_name as "mcn"

DESCRIPTION:
  Generates Schema documentation (XSD) for your ViewHelpers, preparing the
  file to be placed online and used by any XSD-aware editor.
  After creating the XSD file, reference it in your IDE and import the namespace
  in your Fluid template by adding the xmlns:* attribute(s):
  <html xmlns="http://www.w3.org/1999/xhtml" xmlns:f="http://typo3.org/ns/TYPO3/Fluid/ViewHelpers" ...>
```

Execution:

```bash
me@localhost:~/documentroot $ ./typo3/cli_dispatch.phpsh extbase schema:generate my_extkey "http://my.domain/namespace" me > me.xsd
```

...which will generate an XSD schema for all ViewHelpers in extension key (not ExtensionName!) "my_extkey", with the XSD namespace
"http://my.domain/namespace" and the alias "me". The file will be called "me.xsd" and will be output to your current directory.

## What is a namespace alias?

Fluid templates do not require you do use the same namespace in every template, but usually you will be using one namespace per
extension that you use. For example, the namespace alias "v" is used for VHS. The result of using a namespace alias in the CLI
command is that instead of the extension key, this alias will be used as tag prefix - which means that if you use "v" as an alias
when generating an XSD file for extension "VHS", it matches this namespace in the Fluid template:

```xml
{namespace v=Tx_Vhs_ViewHelpers}
```

Without the namespace alias, the Fluid template namespace would have to be:

```xml
{namespace vhs=Tx_Vhs_ViewHelpers}
```

Which means that if you wish to generate an XSD for Fluid's own ViewHelpers, you must execute:

```bash
me@localhost:~/documentroot $ ./typo3/cli_dispatch.phpsh extbase schema:generate fluid "http://typo3.org/ns/fluid/ViewHelpers" f
```

Note the very last "f" - not a typo ;)

## How to use XSD in IDE

Some IDEs require the XSD files to be manually loaded before they can be used; others are able to download them (which goes
without saying, would require you to publish your XSD files and their URLs). All IDEs seem to require that the namespace is
referenced in each file. The key is to always make sure the XSD is associated 100% correctly with the precise namespace (tailing
slashes also count!):

```html
{namespace v=Tx_Vhs_ViewHelpers}
<?xml version="1.0" encoding="UTF-8" ?>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en"
	  xmlns:v="http://fedext.net/ns/vhs/ViewHelpers"
	  xmlns:f="http://typo3.org/ns/fluid/ViewHelpers">
	<head>
		<title>XSD Usage with extensions Fluid and VHS</title>
	</head>
	<body>
		<!-- Fluid goes here -->
	</body>
</html>
```

This is the basic form. Enter the proper URL for the namespace and choose the same alias as you used in the Fluid namespace
registration. Fluid is always "f:" naturally, but you are free to use any prefix you like for other ViewHelpers as long as it
matches the prefix used in the "xmlns:" definition.

Some IDEs then require you to load XSD schema files and enter a namespace URL associated with the XSD schema. Make sure you enter
the URL correctly or it won't work correctly. See your specific IDE's documentation about how to include XSD files (in PHPStorm
you open preferences, find the "Schemas and DTDs" configuration section and in the top frame, add the XSD files used in your
project.

## Note about TYPO3 4.5 LTS

Although it is possible to generate XSD files on TYPO3 4.5 it is not possible to do using the above commands. A CLI implementation
compatible with 4.5 was not included as it would greatly increase the complexity of this extremely simple extension. You can
create XSD files by injecting the SchemaService and calling it with a few arguments:

```php
/**
 * @var Tx_Schemaker_Service_SchemaService
 */
protected $schemaService;

/**
 * @param Tx_Schemaker_Service_Schema $schemaService
 * @return void
 */
public function injectSchemaService(Tx_Schemaker_Service_SchemaService $schemaService) {
	$this->schemaService = $schemaService;
}

/**
 * @return void
 */
public function generateXsdAction() {
		$extensionKey = 'my_extkey';
		$xsdNamespace = 'http://my.domain/namespace';
		$xsdSchema = $this->schemaService->generateXsd($extensionKey, $xsdNamespace);
			// optional: use the PHP Tidy extension to format the XML output a bit
		$xsdSchema = tidy_repair_string($xsdSchema, array(
			'output-xml' => TRUE,
			'input-xml' => TRUE
		));
		file_put_contents('/path/to/file.xsd', $xsdSchema);
}
```

You do not have to inject the Service in order to use it - but it does have to be created using Extbase's ObjectManager.