# VetAgenda — Sistema de Gestión de Citas Veterinarias

Aplicación web desarrollada para el curso **Ingeniería de Software I** orientada a digitalizar la agenda de una clínica veterinaria, evitar dobles reservas y reducir la tasa de no asistencia mediante el seguimiento de confirmaciones.


## Integrantes del grupo
* **Cristofer Alcaíno**
* **Javier Espinoza**
* **Diego Norambuena**
* **Docente:** Hernán Campos Rivero



## Enlaces del Proyecto
| Recurso | Enlace |
| :--- | :--- |
| **Sitio Web en Vivo** | [https://vetagenda.ct.ws](https://vetagenda.ct.ws) |
| **Tablero JIRA (Kanban)** | [Proyecto KAN - VetAgenda](https://vetagenda.atlassian.net/jira/software/projects/KAN/boards/2) |
| **Documentación Confluence** | [Procesos BPMN, UML y Requisitos](https://vetagenda.atlassian.net/wiki/spaces/Vetagenda/pages/5275651/Vetagenda+Taller+de+procesos+UML+y+requisitos) |
| **Repositorio GitHub** | [youngggjotv/Clinica-Veterinaria](https://github.com/youngggjotv/Clinica-Veterinaria) |



## Tecnologías y Despliegue
* **Lenguaje Backend:** PHP 8+ (PDO)
* **Frontend:** HTML5 y CSS3 (diseño responsivo para escritorio y móvil)
* **Base de Datos:** MySQL (producción en InfinityFree) / SQLite (pruebas locales rápidas)
* **Despliegue Continuo (CI/CD):** GitHub Actions conectado por FTP a InfinityFree (cada fusión en `main` actualiza automáticamente `vetagenda.ct.ws` excluyendo credenciales locales).
