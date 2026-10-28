// Inicialización de LocalStorage
let pacientes = JSON.parse(localStorage.getItem('pacientes')) || [];
let citas = JSON.parse(localStorage.getItem('citas')) || [];

// ---------- Horario de atención (Chile) ----------
// Lunes a viernes: 09:00-13:00 y 14:00-18:00 (colación de 13:00 a 14:00)
// Sábado: 10:00-14:00 | Domingo: cerrado
// Para cambiar el horario basta con editar estas constantes.
const DURACION_CITA = 30; // minutos que dura cada cita
const TRAMOS_SEMANA = [['09:00', '13:00'], ['14:00', '18:00']];
const HORARIO_ATENCION = {
    0: [],                      // domingo
    1: TRAMOS_SEMANA,           // lunes
    2: TRAMOS_SEMANA,
    3: TRAMOS_SEMANA,
    4: TRAMOS_SEMANA,
    5: TRAMOS_SEMANA,           // viernes
    6: [['10:00', '14:00']]     // sábado
};

function aMinutos(hora) {
    const [h, m] = hora.split(':').map(Number);
    return h * 60 + m;
}

function aHora(minutos) {
    const h = String(Math.floor(minutos / 60)).padStart(2, '0');
    const m = String(minutos % 60).padStart(2, '0');
    return `${h}:${m}`;
}

// Día de la semana de una fecha 'YYYY-MM-DD' en hora local (0 = domingo)
function diaSemana(fechaISO) {
    const [y, m, d] = fechaISO.split('-').map(Number);
    return new Date(y, m - 1, d).getDay();
}

// Bloques de 30 min en los que la clínica atiende ese día
function bloquesDelDia(fechaISO) {
    if (!fechaISO) return [];
    const tramos = HORARIO_ATENCION[diaSemana(fechaISO)] || [];
    const bloques = [];
    tramos.forEach(([inicio, fin]) => {
        for (let t = aMinutos(inicio); t + DURACION_CITA <= aMinutos(fin); t += DURACION_CITA) {
            bloques.push(aHora(t));
        }
    });
    return bloques;
}

// Busca una cita del mismo veterinario y día a menos de 30 min de la hora pedida
// (incluye la misma hora, así que también cubre el solapamiento exacto)
function buscarConflicto(veterinario, fecha, hora) {
    return citas.find(c =>
        c.veterinario === veterinario &&
        c.fecha === fecha &&
        Math.abs(aMinutos(c.hora) - aMinutos(hora)) < DURACION_CITA
    );
}

// Llena el selector de horas con los bloques del día y marca los ocupados
function actualizarHorasDisponibles() {
    const select = document.getElementById('hora');
    const fecha = document.getElementById('fecha').value;
    const veterinario = document.getElementById('veterinario').value;
    const anterior = select.value;
    select.innerHTML = '';

    if (!fecha) {
        select.add(new Option('Elige una fecha primero', ''));
        select.disabled = true;
        return;
    }

    const bloques = bloquesDelDia(fecha);
    if (bloques.length === 0) {
        select.add(new Option('Sin atención este día', ''));
        select.disabled = true;
        return;
    }

    select.disabled = false;
    select.add(new Option('Elige una hora...', ''));
    bloques.forEach(hora => {
        const ocupado = buscarConflicto(veterinario, fecha, hora);
        const opcion = new Option(ocupado ? `${hora} · ocupado` : hora, hora);
        opcion.disabled = Boolean(ocupado);
        select.add(opcion);
    });

    // Si la hora elegida antes sigue libre, se conserva
    if (bloques.includes(anterior) && !buscarConflicto(veterinario, fecha, anterior)) {
        select.value = anterior;
    }
}

document.getElementById('fecha').addEventListener('change', actualizarHorasDisponibles);
document.getElementById('veterinario').addEventListener('change', actualizarHorasDisponibles);

// Fecha de hoy en hora local (toISOString usa UTC y puede dar el día siguiente de noche)
function fechaLocalISO(fecha = new Date()) {
    const y = fecha.getFullYear();
    const m = String(fecha.getMonth() + 1).padStart(2, '0');
    const d = String(fecha.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

// Aviso flotante (reemplaza al alert)
let toastTimer;
function mostrarToast(texto) {
    const toast = document.getElementById('toast');
    toast.textContent = texto;
    toast.classList.add('visible');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toast.classList.remove('visible'), 3200);
}

document.addEventListener('DOMContentLoaded', () => {
    const hoy = fechaLocalISO();

    // Fecha de hoy en el encabezado
    document.getElementById('fecha-hoy').textContent = new Date().toLocaleDateString('es-CL', {
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
    });

    // Fecha actual por defecto en el filtro y en el formulario de cita
    document.getElementById('filtro-fecha').value = hoy;
    document.getElementById('fecha').value = hoy;

    actualizarHorasDisponibles();
    actualizarSelectPacientes();
    renderizarAgenda();
    actualizarResumen();
});

// Registrar Tutor y Mascota
document.getElementById('registro-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const tutor = document.getElementById('tutor').value.trim();
    const mascota = document.getElementById('mascota').value.trim();

    const nuevoPaciente = { id: Date.now(), tutor, mascota };
    pacientes.push(nuevoPaciente);
    localStorage.setItem('pacientes', JSON.stringify(pacientes));

    mostrarToast(`${mascota} (tutor: ${tutor}) quedó registrado.`);
    this.reset();
    actualizarSelectPacientes();
    actualizarResumen();
});

// Actualizar lista desplegable de pacientes
function actualizarSelectPacientes() {
    const select = document.getElementById('select-paciente');
    select.innerHTML = '<option value="">Seleccione un paciente...</option>';
    pacientes.forEach(p => {
        const option = document.createElement('option');
        option.value = p.id;
        option.textContent = `${p.mascota} - ${p.tutor}`;
        select.appendChild(option);
    });
}

// Agendar Cita y aplicar Regla de Negocio (Prevención de Solapamiento)
document.getElementById('agenda-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const pacienteId = document.getElementById('select-paciente').value;
    const veterinario = document.getElementById('veterinario').value;
    const fecha = document.getElementById('fecha').value;
    const hora = document.getElementById('hora').value;
    const mensajeEl = document.getElementById('mensaje-validacion');

    // Regla de negocio 1: la hora debe estar dentro del horario de atención
    if (!hora || !bloquesDelDia(fecha).includes(hora)) {
        mensajeEl.textContent = "Error: Seleccione una hora dentro del horario de atención de la clínica.";
        mensajeEl.className = "mensaje error";
        return; // Cita rechazada: fuera de horario
    }

    // Regla de negocio 2: Verificar solapamiento (Caso de rechazo o dato inválido para EV3)
    // Cada cita dura 30 min, así que el veterinario no puede tener otra a menos de 30 min
    const solapamiento = buscarConflicto(veterinario, fecha, hora);

    if (solapamiento) {
        mensajeEl.textContent = "Error: El veterinario ya tiene una cita agendada en ese horario (cada cita dura 30 minutos). Por favor, seleccione otro.";
        mensajeEl.className = "mensaje error";
        actualizarHorasDisponibles();
        return; // Detiene la ejecución, cita rechazada
    }

    // Si no hay solapamiento, crear cita (Caso exitoso para EV3)
    const paciente = pacientes.find(p => p.id == pacienteId);
    const nuevaCita = {
        id: Date.now(),
        pacienteId,
        mascota: paciente.mascota,
        tutor: paciente.tutor,
        veterinario,
        fecha,
        hora,
        estado: 'Agendada'
    };

    citas.push(nuevaCita);
    localStorage.setItem('citas', JSON.stringify(citas));

    mensajeEl.textContent = "Cita agendada con éxito.";
    mensajeEl.className = "mensaje success";
    this.reset();
    document.getElementById('fecha').value = fecha; // se mantiene el día para agendar varias seguidas
    actualizarHorasDisponibles(); // la hora recién tomada queda marcada como ocupada

    // Si la cita es de otro día, mostrar la agenda de ese día
    document.getElementById('filtro-fecha').value = fecha;
    renderizarAgenda();
    actualizarResumen();
});

// Filtrar y ver la agenda diaria
document.getElementById('btn-filtrar').addEventListener('click', renderizarAgenda);
document.getElementById('filtro-fecha').addEventListener('change', renderizarAgenda);

// Crea una celda con su etiqueta (se usa en la vista móvil)
function crearCelda(etiqueta, contenido, clase) {
    const td = document.createElement('td');
    td.dataset.label = etiqueta;
    if (clase) td.className = clase;
    if (contenido instanceof Node) {
        td.appendChild(contenido);
    } else {
        td.textContent = contenido;
    }
    return td;
}

function crearBadge(texto, clase) {
    const span = document.createElement('span');
    span.className = `badge ${clase}`;
    span.textContent = texto;
    return span;
}

function renderizarAgenda() {
    const tbody = document.querySelector('#tabla-agenda tbody');
    const fechaFiltro = document.getElementById('filtro-fecha').value;
    tbody.innerHTML = '';

    // Consultar datos filtrando por fecha (copia para no alterar el arreglo original)
    let citasFiltradas = fechaFiltro ? citas.filter(c => c.fecha === fechaFiltro) : [...citas];

    // Ordenar por hora
    citasFiltradas.sort((a, b) => a.hora.localeCompare(b.hora));

    if (citasFiltradas.length === 0) {
        const tr = document.createElement('tr');
        const td = document.createElement('td');
        td.colSpan = 5;
        td.className = 'vacio';
        td.innerHTML = '<strong>Día tranquilo por ahora</strong>No hay citas agendadas para esta fecha.';
        tr.appendChild(td);
        tbody.appendChild(tr);
        return;
    }

    citasFiltradas.forEach(cita => {
        const tr = document.createElement('tr');
        const claseVet = cita.veterinario === 'Vet 2' ? 'badge-vet2' : 'badge-vet1';

        tr.appendChild(crearCelda('Hora', cita.hora, 'hora'));
        tr.appendChild(crearCelda('Veterinario', crearBadge(cita.veterinario, claseVet)));
        tr.appendChild(crearCelda('Paciente', cita.mascota, 'mascota'));
        tr.appendChild(crearCelda('Tutor', cita.tutor));
        tr.appendChild(crearCelda('Estado', crearBadge(cita.estado, 'badge-estado')));
        tbody.appendChild(tr);
    });
}

// Contadores del encabezado
function actualizarResumen() {
    const hoy = fechaLocalISO();
    document.getElementById('stat-hoy').textContent = citas.filter(c => c.fecha === hoy).length;
    document.getElementById('stat-total').textContent = citas.length;
    document.getElementById('stat-pacientes').textContent = pacientes.length;
}
