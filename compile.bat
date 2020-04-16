@ECHO OFF
IF %ComputerName% EQU CANTOR SET rq=1
IF %ComputerName% EQU EULERUS SET rq=1

CALL "vendor\bin\phpstan" analyse -c phpstan.neon -l 5 src\ --memory-limit 1024M > compile.txt

IF DEFINED rq (
	start compile.txt
) ELSE (
	start notepad compile.txt
)

