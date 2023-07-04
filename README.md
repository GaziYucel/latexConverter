# Latex to PDF Converter Plugin

The plugin for OJS 3.3 that allows to convert articles in Latex to PDF format.

## Manual installation of the plugin
```shell
git clone https://github.com/GaziYucel/latexConverter
```
## Configuration of the plugin
- Login in your OJS instance as an Administrator or Manager
- Navigate to Website > Plugins > Installed Plugins > Generic Plugins > LaTex to PDF Converter Plugin > Settings
- Fill in the absolute path to pdflatex executable, e.g. /var/www/TexLive/texmf/bin/x86_64-linux/pdflatex
- Fill in the field "Allowed mime types" with mime types which should show dependent files. For Tex files fill in "text/x-tex" and "application/x-tex" on separate lines
- Click Save

## Installation of TexLive portable on Linux
```shell
# example of installation path: /var/www/TexLive
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

### Manual usage of pdflatex
- `cd /path-to-some-latex-project`
- `/var/www/TexLive/texmf/bin/x86_64_linux/pdflatex -no-shell-escape -interaction=nonstopmode main.tex`


