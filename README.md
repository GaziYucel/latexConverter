# Latex to PDF Converter Plugin

The plugin for OJS 3.3 that allows to convert articles in Latex to PDF format.

## Features

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

## Requirements

- PHP 8.0,8.1
- TexLive for your platform

## Manual installation of the plugin

```shell
git clone https://github.com/GaziYucel/latexConverter
```
Alternatively, you can download the latest release or download the code with the option 'Download ZIP'. Extract the downloaded file to `./plugins/generic/latexConverter`.

## Configuration of the plugin
- 
- Login in your OJS instance as an Administrator or Manager
- Navigate to Website > Plugins > Installed Plugins > Generic Plugins > LaTex to PDF Converter Plugin > Settings
- Fill in the absolute path to pdflatex executable, e.g. /var/www/TexLive/texmf/bin/x86_64-linux/pdflatex
- Fill in the field "Allowed mime types" with mime types which should show dependent files. For Tex files fill in "text/x-tex" and "application/x-tex" on separate lines
- Click Save

## Installation of TexLive portable on Linux

```shell
# example of installation path: /var/www/TexLive
# TexLive will be installed with all packages and options (around 8GB)
mkdir -p /var/www/TexLive/tmp
cd /var/www/TexLive/tmp
wget https://mirror.ctan.org/systems/texlive/tlnet/install-tl-unx.tar.gz
zcat < install-tl-unx.tar.gz | tar xf -
cd install-tl-*
perl install-tl --portable --no-interaction --TEXDIR /var/www/TexLive/texmf --TEXMFLOCAL /var/www/TexLive/texmf-local --TEXMFSYSCONFIG /var/www/TexLive/texmf-config --TEXMFSYSVAR /var/www/TexLive/texmf-var
export PATH=/var/www/TexLive/texmf/bin/x86_64_linux:$PATH
cd /var/www/TexLive
rm -rf tmp
```

## Manual usage of pdflatex

- `cd /path-to-some-latex-project`
- `/var/www/TexLive/texmf/bin/x86_64_linux/pdflatex -no-shell-escape -interaction=nonstopmode main.tex`

## Development

- Fork the repository
- Make your changes
- Open a PR with your changes

## Development notes

- Auto loading of the classes in the folder `classes` is done with composer [classmap](https://getcomposer.org/doc/04-schema.md#classmap). 
- If you add or remove classes in this folder, run the following command to update the autoload files: `composer dump-autoload -o`.
- Running `composer install -o` or `composer update -o` will also generate the autoload files
- The `-o` option generates the optimised files ready for production.

...
