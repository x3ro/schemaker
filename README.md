Fluid ViewHelper XSD Schema Generator
=====================================

> Schemaker is primarily a backport with some adaptations for TYPO3v4, from TYPO3.Fluid (i.e. not the TYPO3v4 version of Fluid).

[![Build Status](https://travis-ci.org/FluidTYPO3/schemaker.png?branch=master)](https://travis-ci.org/FluidTYPO3/schemaker)

## Why use it?

To name just a few reasons:

* Autocompletion in Fluid templates for any extension's ViewHelpers - not just your own.
* Basic validation of attributes, recognition and automatic adding of required attributes on Fluid ViewHelpers.
* Ability to generate XSD schemas for the precise version of installed extensions - so you can tailor them to your project.
* Increased speed and consistency in general when creating Fluid templates.

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

DESCRIPTION:
  Generates Schema documentation (XSD) for your ViewHelpers, preparing the
  file to be placed online and used by any XSD-aware editor.
  After creating the XSD file, reference it in your IDE and import the namespace
  in your Fluid template by adding the xmlns:* attribute(s):
  <html xmlns="http://www.w3.org/1999/xhtml" xmlns:f="http://typo3.org/ns/TYPO3/Fluid/ViewHelpers" ...>
```

Execution:

```bash
me@localhost:~/documentroot $ ./typo3/cli_dispatch.phpsh extbase schema:generate my_extkey "http://my.domain/namespace" > me.xsd
```

...which will generate an XSD schema for all ViewHelpers in extension key (not ExtensionName!) "my_extkey", with the XSD namespace
"http://my.domain/namespace". The file will be called "me.xsd" and will be output to your current directory.

## How to use XSD in IDE

Some IDEs require the XSD files to be manually loaded before they can be used; others are able to download them (which goes
without saying, would require you to publish your XSD files and their URLs). All IDEs seem to require that the namespace is
referenced in each file. The key is to always make sure the XSD is associated 100% correctly with the precise namespace (tailing
slashes also count!):

```html
{namespace v=FluidTYPO3\Vhs\ViewHelpers}
<?xml version="1.0" encoding="UTF-8" ?>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en"
	  xmlns:v="http://fedext.net/ns/vhs/ViewHelpers"
	  xmlns:f="http://typo3.org/ns/TYPO3/Fluid/ViewHelpers">
	<head>
		<f:layout name="Default" />
		<title>XSD Usage with extensions Fluid and VHS</title>
	</head>
	<body>
		<!-- Fluid goes here -->
	</body>
</html>
```

This is the standard format. Enter the proper URL for the namespace and choose the same alias as you used in the Fluid namespace
registration. Fluid is always "f:" naturally, but you are free to use any prefix you like for other ViewHelpers as long as it
matches the prefix used in the "xmlns:" definition.

Some IDEs then require you to load XSD schema files and enter a namespace URL associated with the XSD schema. Make sure you enter
the URL correctly or it won't work correctly. See your specific IDE's documentation about how to include XSD files (in PHPStorm
you open preferences, find the "Schemas and DTDs" configuration section and in the top frame, add the XSD files used in your
project.

## When to use - and when not to use

Use this when your template contains Sections. That's the base rule. The reason is the necessity for having a wrapping tag (in
the above example the HTML tag is used) and this can be difficult to accomodate correctly in all IDEs.

Using Fluid Sections to contain the "real" output of the template will allow you to place any amount of HTML outside of the
f:section tag. If your IDE only supports the standard implementation as described in the previous chapter you should always use
sections whenever you want to use the XSD capabilities.

However, some IDEs are capable of recognizing the xmlns: attributes and applying the XSD even if there is no XML header and the
HTML tag is not being used. Other IDEs may require that the standard way (XML header, HTML tag used for xmlns: definitions) is
used. If your particular IDE does not require this approach, you should be able to use as such:

```html
{namespace v=FluidTYPO3\Vhs\ViewHelpers}
<div xmlns="http://www.w3.org/1999/xhtml" lang="en"
	  xmlns:v="http://fedext.net/ns/vhs/ViewHelpers"
	  xmlns:f="http://typo3.org/ns/TYPO3/Fluid/ViewHelpers">
	<!-- Fluid goes here -->
</div>
```

Naturally the DIV element will be included in the output, but most importantly there will not be HTML, HEAD and BODY tags output.

### About Partials

Partial templates also allow using Sections - and you can use this to your advantage when you want the XSD capabilities inside
a Partial template (which means without sections, you normally would not be able to without some additional output as described
above). Simply construct your Partial template so that it contains only one section, then always render the section whenever you
render the Partial:

```xml
<f:render partial="MyPartial" section="Main" />
```

And construct the Partial template itself as such:

```html
{namespace v=FluidTYPO3\Vhs\ViewHelpers}
<?xml version="1.0" encoding="UTF-8" ?>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en"
	  xmlns:v="http://fedext.net/ns/vhs/ViewHelpers"
	  xmlns:f="http://typo3.org/ns/TYPO3/Fluid/ViewHelpers">
	<head>
		<title>Partials: MyPartial</title>
	</head>
	<body>
		<f:section name="Main">
			<!-- Fluid goes here -->
		</f:section>
	</body>
</html>
```

Although it does add to the complexity of each template, the benefit of auto completion - especially on attributes - may outweigh
the need for a lot of extra HTML in your templates. It does make your Partial templates nicely compliant when viewed directly as
HTML in a browser if you have a habit of previewing this way.

### About Layouts

This is the drawback: if your IDE does not support non-standard use of xmlns: definitions you will be forced to use the HTML tag
or live without XSD support in your Layout files. The reason is of course that neither Extbase nor TYPO3 itself expects HTML and
BODY tags to be output by any page or extension templates - and naturally you can't use Sections in a Layout.

Most likely you will want to just ignore the XSD capabilities in your Layouts to make them more compatible with TYPO3's core.

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

## Suggested namespaces and aliases
```xml
<?xml version="1.0" encoding="UTF-8" ?>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en"
	  xmlns:f="http://typo3.org/ns/TYPO3/Fluid/ViewHelpers"
	  xmlns:flux="http://fedext.net/ns/flux/ViewHelpers"
	  xmlns:fed="http://fedext.net/ns/fed/ViewHelpers"
	  xmlns:dialog="http://fedext.net/ns/dialog/ViewHelpers"
	  xmlns:notify="http://fedext.net/ns/notify/ViewHelpers"
	  xmlns:v="http://fedext.net/ns/vhs/ViewHelpers"
	  xmlns:w="http://fedext.net/ns/fluidwidget/ViewHelpers"
	/>
```

Note: The following schemas are available for download (use "save page as") at the URLs used in the namespaces:

* http://fedext.net/ns/flux/ViewHelpers
* http://fedext.net/ns/fed/ViewHelpers
* http://fedext.net/ns/vhs/ViewHelpers
* http://fedext.net/ns/fluidwidget/ViewHelpers
* http://fedext.net/ns/dialog/ViewHelpers
* http://fedext.net/ns/notify/ViewHelpers

These schemas all apply to the very latest master versions of each extension's ViewHelpers - if you require an XSD for an earlier
version which you currently have installed, simply generate an XSD from that TYPO3 installation and use the same namespace URL.
