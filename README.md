
# Latex to PDF Converter Plugin

The plugin for OJS 3.3 and 3.4 that allows to convert articles in Latex to PDF format.

-   [Latex to PDF Converter Plugin](#latex-to-pdf-converter-plugin)
-   [Features](#features)
    -   [Extract Archive](#extract-archive)
    -   [Convert to PDF](#convert-to-pdf)
-   [Install and configure the plugin](#install-and-configure-the-plugin)
    -   [Requirements](#requirements)
    -   [Install with Git](#install-with-git)
    -   [Install via direct download](#install-via-direct-download)
    -   [Install TexLive portable  (Linux)](#install-texlive-portable-linux)
    -   [Configuration of the plugin](#configuration-of-the-plugin)
-   [Development](#development)
    -   [Structure](#structure)
    -   [Notes](#notes)
    -   [Classmap](#classmap)

[![Project Status: Active – The project has reached a stable, usable state and is being actively developed.](https://www.repostatus.org/badges/latest/active.svg)](https://www.repostatus.org/#active)
[![Contributor Covenant](https://img.shields.io/badge/Contributor%20Covenant-2.1-4baaaa.svg)](code_of_conduct.md)

# Features

### Extract Archive

Currently only ZIP files are supported.

This functionality is shown if there is an archive file file present in the Production phase.

Clicking this button will do the following: 
- get list of files in archive file
- present the list to the user in a modal
- the user selects which file will be the main file
- the archive file is extracted
  - the selected file will be added as the main file
  - all other files will be added as dependent files

### Convert to PDF

This functionality is shown for all files with the extension TEX in the Production phase.

Clicking this button will do the following:
- copy all files to temp file for processing
- execute pdflatex from shell
- check if there is a compiled pdf present:
  - if there is a pdf file
    - add this file the submission
    - add other output files as dependent files (aux, bcf, log, out, run.xml)
  - if there is no pdf file, than something went wrong
    - add the log file to the submission
    - add other output files as dependent files (aux, bcf, out, run.xml)

![latexConverter - extract and convert](.project/images/latexConverter-extract-convert.gif)

# Install and configure the plugin

### Requirements

- PHP 8.1+
- A LaTex converter for your platform, e.g. [TexLive](https://tug.org/texlive)

### Install with Git

Get the correct version for you OJS version: 
- branch main: development version, don't use for production
- branch stable-3_3_0: use this version for OJS version 3.3.0.x
- branch stable-3_4_0: use this version for OJS version 3.4.0.x

```shell
git clone -b stable-3_3_0 https://github.com/GaziYucel/latexConverter
```

### Install via direct download

- Download release for your OJS version from https://github.com/TIBHannover/latexConverter/releases. Note the correct version for you OJS version.
- Alternatively, download the code with the option 'Download ZIP'. Note the correct branch for your OJS version. 
- Extract the downloaded file to `./plugins/generic/latexConverter`.

### Install TexLive portable (Linux)

```shell
# example of installation path: /var/www/TexLive
# TexLive will be installed with all packages and options (around 8GB)
mkdir -p /var/www/TexLive/tmp
cd /var/www/TexLive/tmp
wget -O install-tl-unx.tar.gz https://mirror.ctan.org/systems/texlive/tlnet/install-tl-unx.tar.gz
mkdir install-tl-unx
tar xf install-tl-unx.tar.gz -C ./install-tl-unx --strip-components=1
cd install-tl-unx
perl install-tl --portable --no-interaction --TEXDIR /var/www/TexLive/texmf --TEXMFLOCAL /var/www/TexLive/texmf-local --TEXMFSYSCONFIG /var/www/TexLive/texmf-config --TEXMFSYSVAR /var/www/TexLive/texmf-var
export PATH=/var/www/TexLive/texmf/bin/x86_64_linux:$PATH
cd /var/www/TexLive
rm -rf tmp
```

#### Manual usage of pdflatex

```shell
cd /path-to-some-latex-project
/var/www/TexLive/texmf/bin/x86_64-linux/pdflatex -no-shell-escape -interaction=nonstopmode main.tex
```

### Configuration of the plugin

- Login in your OJS instance as an Administrator or Manager
- Navigate to Website > Plugins > Installed Plugins > Generic Plugins > LaTex to PDF Converter Plugin > Settings
- Fill in the absolute path to pdflatex executable, e.g. /var/www/TexLive/texmf/bin/x86_64-linux/pdflatex
- Fill in the field "Allowed mime types" with mime types which should show dependent files: 
  - for TEX files fill in "text/x-tex" and "application/x-tex" on separate lines
  - for TEXT files, fill in "text/plain"
  - for PDF files, fill in "application/pdf"
- Click Save

![latexConverter - settings](.project/images/latexConverter-settings.gif)

# Development

- Fork the repository
- Make your changes
- Open a PR with your changes

### Structure
    .
    ├── assets
    │   ├── images                        # Images used by the plugin
    ├── classes
    │   ├── Action                        # Features main classes
    │   │   ├── Convert.php               # Convert to PDF feature main class
    │   │   └── Extract.php               # Extract archive feature main class
    │   ├── Components
    │   │   └── Forms
    │   │       └── SettingsForm.php      # Settings form class
    │   ├── Handler
    │   │   └── PluginHandler.php         # Main plugin handler / controller
    │   └── Helpers                       # Helper classes
    │       ├── SubmissionFileHelper.php  # Add submission files to submission
    │       ├── FileSystemHelper.php      # FileSystem methods
    │       ├── LogHelper.php             # Logging methods
    │       └── ZipHelper.php             # ZipArchive methods
    ├── locale                            # Language files
    ├── templates                         # Templates folder
    │   ├── extract.tpl                   # Template for the extract modal
    │   └── settings.tpl                  # Settings template
    ├── vendor                            # Composer autoload and dependencies    
    ├── .gitignore                        # Git ignore file
    ├── composer.json                     # Composer file, e.g. dependencies, classmap
    ├── index.php                         # Main entry point of plugin
    ├── LatexConverterPlugin.php          # Main class of plugin
    ├── README.md                         # This file
    └── version.xml                       # Current version of the plugin

### Notes

- Auto loading of the classes in the folder `classes` is done with composer classmap ([see below](#classmap)).
- If you add or remove classes in this folder, run the following command to update the autoload files: `composer dump-autoload -o`.
- Running `composer install -o` or `composer update -o` will also generate the autoload files
- The `-o` option generates the optimised files ready for production.

### Classmap

You can use the classmap generation support to define autoloading for all libraries that do not follow PSR-0/4. To configure this you specify all directories or files to search for classes.

Example: 
```
{ 
  "autoload": {
    "classmap": ["src/", "lib/", "Something.php"]
  }
}
```

You can find more information about classmap [here](https://getcomposer.org/doc/04-schema.md#classmap). 

...
