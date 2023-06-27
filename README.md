# Latex to PDF Converter Plugin

The plugin for OJS 3.3 that allows to convert articles in Latex to PDF format.

## Manual installation
```shell
git clone https://github.com/GaziYucel/latexConverter
cd latexConverter
git submodule init
git submodule update
```

## Configuration
- Login your OJS instance as an Administrator or Manager
- Navigate to Website > Plugins > Installed Plugins > Generic Plugins > LaTex to PDF Converter Plugin > Settings
- Fill in the field "Allowed Mime Types" with mimetypes which should show dependent files. For Tex files fill in "text/x-tex" and "application/x-tex" on separate lines 
- Click Save
