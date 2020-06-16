# minga-framework
## Presentación
El minga-framework es un nanoframework que reúne una serie de clases desarrolladas para facilitar la creación de sitios públicos en PHP. Ofrece funcionalidad de dos tipos:

#### Helpers
- Compression. Unificación de las distintas vías de manejo de zip disponibles en php.
- Date. Formateo y conversiones generales de fechas. 
- Db. Acceso a la base de datos por medio de un objeto centralizado y coordinado con Profiling y Performance.
- IO. Métodos abreviados de acceso a disco, con métodos tales con GetFiles, GetDirectories o WriteAllText.
- Locking. Implementación apoyada en filesystem (multiplataforma) de SingleWriter-ManyReaders locks.
- String. Manejo de cadenas de texto.

#### Servicios
- Configuration. Clase Settings base para el manejo de configuración de cada instalación.
- Performance. Cuantificar la cantidad de controllers ejecutados por día y su tiempo de ejecución por día actual y meses previos, indicando la proporción de tiempo en la base de datos insumida por cada controller.
- Profiling. Ofrece clases para instrumentar código para evaluar tiempos utilizados. Implementa un esquema de profiling liviano que permite examinar tiempos en producción sin impactos significativos de performance.
- Traffic. Control en vivo de requerimientos por IP para controlar y alertar sobre volúmenes inusuales de pedidos desde usuarios únicos.

## Madurez
El minga/framework se encuentra en uso en producción en [Acta Académica] (https://www.aacademica.org) y en [Poblaciones] (https://www.poblaciones.org). No posee a la fecha documentación de ayuda para el uso en otros proyectos.

## Instalación
EN PROCESO: npm install minga/framework
