<!--
SPDX-FileCopyrightText: Marcel Klehr <mklehr@gmx.net>
SPDX-License-Identifier: CC0-1.0
-->

# Llm
Place this app in **nextcloud/apps/**


## Command
Summarization is available via `occ llm:summarize "Your text here"`

You can use this to register a talk command as follows:

```
./occ talk:command:add summary Summarizer "/path/to/php /path/to/occ llm:summarize {ARGUMENTS}" 2 3
```

## Building the app

The app can be built by using the provided Makefile by running:

    make

This requires the following things to be present:
* make
* which
* tar: for building the archive
* curl: used if phpunit and composer are not installed to fetch them from the web
* npm: for building and testing everything JS, only required if a package.json is placed inside the **js/** folder
