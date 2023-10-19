<!--
SPDX-FileCopyrightText: Marcel Klehr <mklehr@gmx.net>
SPDX-License-Identifier: CC0-1.0
-->

![](https://raw.githubusercontent.com/nextcloud/translate/main/screenshots/Logo.png)

# Translate
A Machine translation provider using Opus models by University of Helsinki running locally on CPU.

The models run completely on your machine. No private data leaves your servers.

Currently supported languages:

* English
* German
* French
* Spanish
* Chinese

Model size:

 * ~1GB per language pair
 * ~10GB in total

Requirements:

* x86 CPU
* GNU lib C (musl is not supported)

#### Nextcloud All-in-One:
With Nextcloud AIO, this app is not going to work because AIO uses musl. However you can use [this community container](https://github.com/nextcloud/all-in-one/tree/main/community-containers/local-ai) as replacement for this app.

## Ethical AI Rating
### Rating: 🟢

Positive:
* the software for training and inference of this model is open source
* the trained model is freely available, and thus can be run on-premises
* the training data is freely available, making it possible to check or correct for bias or optimise the performance and CO2 usage.

Learn more about the Nextcloud Ethical AI Rating [in our blog](https://nextcloud.com/blog/nextcloud-ethical-ai-rating/).

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

will download both en->de and de->en.

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
