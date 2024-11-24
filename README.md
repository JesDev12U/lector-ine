# Lector de credenciales del INE México
Para poder utilizar esto, se necesita descargar la extensión Tesseract para PHP

En Fedora Linux se instala de la siguiente forma

1. Se descargan los paquetes necesarios
```bash
sudo dnf install tesseract tesseract-langpack-spa -y
```
2. Verificamos que esté instalado
```bash
tesseract --version
php -m | grep fileinfo
```
3. Si queremos ver el OCR generado, se puede hacer así
```bash
tesseract ~/INE.jpeg resultado -l spa
```
Ese comando genera un archivo .txt en el que se ve el OCR generado
