<!--
SPDX-FileCopyrightText: Marcel Klehr <mklehr@gmx.net>
SPDX-License-Identifier: CC0-1.0
-->

# Translate
Machine translation provider using Opus models by University of Helsinki

The models run completely on your machine. No private data leaves your servers.

Currently supported languages:

* English
* German
* French
* Spanish
* Chinese

## Install
 * Place this app in **nextcloud/apps/**

or 

 * Install from the Nextcloud appstore

After installing this app you will need to run:

```
$ php occ translate:download-models
```

### Downloading only specific languages
```
$ php occ translate:download-models <languages>
```

For example

```
$ php occ translate:download-models de en
``` 

will donwload both en->de and de->en.

```
$ php occ translate:download-models de en es
```

will download en->de, de->en, en->es, es->en, es->de, de->es

## Building the app

The app can be built by using the provided Makefile by running:

    make

This requires the following things to be present:
* make
* which
* tar: for building the archive
* curl: used if phpunit and composer are not installed to fetch them from the web
* npm: for building and testing everything JS, only required if a package.json is placed inside the **js/** folder
