# silex-assetmerge



## Description
For boosting performance is recommended to reduce number of requests to web server.

I noticed that too many CSS and JS files are loaded at my site 
and decided to radically reduce their numbers by merging to one file.

Automatically, configurable, reusable.

> It is my first shared project on Github and english language not my mother tongue.
> I am Sorry!

## Installation

#### Via composer:
```php
composer require konstantin-s/silex-assetmerge
```
[See at Packagist ](https://packagist.org/packages/konstantin-s/silex-assetmerge)


## Usage

### Register Provider
```php
$app->register(new Performance\AssetMerge\AssetMergeProvider());
```

### Config parameters

name|default|description
-----|-----|-----
active|true|If set to false getScripts() returns HTML code with unmerged files  
alwaysReMerge|false|If set to true on every call getScripts() recreate merged files (useful when you modify scripts and styles)
fetchRemote|true|If set to true remote files will be downloaded and merged
mergedCssRootDir|"/assets/merged/"|Dir where to store merged CSS files. At this dir will be created subdir with name "hash_of_names_files_to_merge" with merged file. Relative to front controller dir.
mergedJsRootDir|"/assets/merged/"|Dir where to store merged JS files. At this dir will be created subdir with name "hash_of_names_files_to_merge" with merged file. Relative to front controller dir.
webRoot|result of: $app["request"]->server->get("CONTEXT_DOCUMENT_ROOT")|Used for build absolute filepaths
 
### Register Provider with parameters
```php
$app->register(new Performance\AssetMerge\AssetMergeProvider(), array(
    "assetmerge.config" => array(
        "active" => true,
        "alwaysReMerge" => true,
        "fetchRemote" => false,
        "mergedCssRootDir" => "/my_merged_css/",
        "mergedJsRootDir" => "/my_merged_js/"
    )
));
``` 


### Usage in TWIG templade

```twig
{% set cssFiles = ["/assets/vendor/twbs/css/bootstrap.css",
"/assets/vendor/twbs/css/bootstrap-theme.css",
"/assets/vendor/prism/prism.css",
"/assets/styles/style.css"] %}
{{ app.assetmerge_config.setCssFiles(cssFiles) }}

{% set jsFiles = ["https://ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js",
"/assets/vendor/twbs/js/bootstrap.js",
"/assets/vendor/prism/prism.js",
"/assets/scripts/common.js"] %}
{{ app.assetmerge_config.setJsFiles(jsFiles) }}

{# You can set parameters, set to inactive for example:
{{ app.assetmerge_config.setactive(false) }}
#}
{{app.assetmerge_merger.getScripts|raw}}

```

### Example output of {{app.assetmerge_merger.getScripts|raw}}

```html
<link href="/assets/merged/6e8109cbc09fbc2b4b1543b8d1f33c14/styles.css" rel="stylesheet" type="text/css"/>
<script src="/assets/merged/df764eb96f1c811270e575caaa87de82/js.js" type="text/javascript"></script>
```

> where `6e8109cbc09fbc2b4b1543b8d1f33c14` and `df764eb96f1c811270e575caaa87de82` is md5 hashes of files names,
> passed to setCssFiles() and setJsFiles()