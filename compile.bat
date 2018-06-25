call "C:\Program Files\Php Compilers\php\vendor\bin\phpstan.bat" analyse -c phpstan.neon -l 7 src\ --memory-limit 1024M>compile.txt

start notepad compile.txt
