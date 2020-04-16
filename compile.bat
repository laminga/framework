@ECHO OFF
IF %ComputerName% EQU CANTOR SET rq=1
IF %ComputerName% EQU EULERUS SET rq=1

IF DEFINED rq (
	CALL "C:\Users\Admin\Dropbox\Utils\php\vendor\bin\phpstan.bat" analyse -c phpstan.neon -l 5 src\ --memory-limit 1024M > compile.txt
) ELSE (
	CALL "C:\Program Files\Php Compilers\php\vendor\bin\phpstan.bat" analyse -c phpstan.neon -l 5 src\ --memory-limit 1024M > compile.txt
	start notepad compile.txt
)

