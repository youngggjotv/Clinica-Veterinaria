const { createClient } = supabase;
// URL obtenida de tu proyecto
const supabaseUrl = 'https://bfvdlqayemifnsykjbjh.supabase.co'; 
const supabaseKey = 'sb_publishable__dcKhjydkm8s7Z9Jnpwqkw_3wJaehGz'; // Pega tu anon key aquí
const supabaseDb = createClient(supabaseUrl, supabaseKey);

// Reemplazar la carga inicial estática
let tutores = [];
let mascotas = [];
let citas = [];

// Diccionario estático de Veterinarios según UML
const VETERINARIOS = {
    "1": { id_veterinario: "1", nombre: "Jose Lagos" },
    "2": { id_veterinario: "2", nombre: "Maria Rodriguez" }
};

const DURACION_CITA = 30; // minutos
const TRAMOS_SEMANA = [['09:00', '13:00'], ['14:00', '18:00']];
const HORARIO_ATENCION = {
    0: [], 1: TRAMOS_SEMANA, 2: TRAMOS_SEMANA, 3: TRAMOS_SEMANA,
    4: TRAMOS_SEMANA, 5: TRAMOS_SEMANA, 6: [['10:00', '14:00']]
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

function diaSemana(fechaISO) {
    const [y, m, d] = fechaISO.split('-').map(Number);
    return new Date(y, m - 1, d).getDay();
}

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

// Verifica solapamiento usando fecha_hora_inicio
function buscarConflicto(id_veterinario, fecha, hora) {
    const fechaHoraBuscada = `${fecha}T${hora}`;
    return citas.find(c => 
        c.id_veterinario === id_veterinario && 
        c.fecha_hora_inicio === fechaHoraBuscada
    );
}

function actualizarHorasDisponibles() {
    const select = document.getElementById('hora');
    const fecha = document.getElementById('fecha').value;
    const id_veterinario = document.getElementById('veterinario').value;
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
        const ocupado = buscarConflicto(id_veterinario, fecha, hora);
        const opcion = new Option(ocupado ? `${hora} · ocupado` : hora, hora);
        opcion.disabled = Boolean(ocupado);
        select.add(opcion);
    });

    if (bloques.includes(anterior) && !buscarConflicto(id_veterinario, fecha, anterior)) {
        select.value = anterior;
    }
}

document.getElementById('fecha').addEventListener('change', actualizarHorasDisponibles);
document.getElementById('veterinario').addEventListener('change', actualizarHorasDisponibles);

function fechaLocalISO(fecha = new Date()) {
    const y = fecha.getFullYear();
    const m = String(fecha.getMonth() + 1).padStart(2, '0');
    const d = String(fecha.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

let toastTimer;
function mostrarToast(texto) {
    const toast = document.getElementById('toast');
    toast.textContent = texto;
    toast.classList.add('visible');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toast.classList.remove('visible'), 3200);
}

document.addEventListener('DOMContentLoaded', async () => {
    const hoy = fechaLocalISO();
    const textoFecha = new Date().toLocaleDateString('es-CL', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
    document.getElementById('fecha-hoy').textContent = textoFecha.charAt(0).toUpperCase() + textoFecha.slice(1);
    document.getElementById('filtro-fecha').value = hoy;
    document.getElementById('fecha').value = hoy;

    // Obtener datos desde Supabase
    const [{ data: tutoresData }, { data: mascotasData }, { data: citasData }] = await Promise.all([
        supabaseDb.from('tutores').select('*'),
        supabaseDb.from('mascotas').select('*'),
        supabaseDb.from('citas').select('*')
    ]);

    tutores = tutoresData || [];
    mascotas = mascotasData || [];
    citas = citasData || [];

    actualizarHorasDisponibles();
    actualizarSelectMascotas();
    renderizarAgenda();
    actualizarResumen();
});

// Registro de Entidades separadas (Tutor y Mascota) según UML
document.getElementById('registro-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const nuevoTutor = {
        id_tutor: "TUT-" + Date.now(),
        nombre: document.getElementById('tutor_nombre').value.trim(),
        correo: document.getElementById('tutor_correo').value.trim(),
        telefono: document.getElementById('tutor_telefono').value.trim(),
        consentimiento: document.getElementById('tutor_consentimiento').checked
    };
    
    const nuevaMascota = {
        id_mascota: "MAS-" + Date.now(),
        id_tutor: nuevoTutor.id_tutor,
        nombre: document.getElementById('mascota_nombre').value.trim(),
        especie: document.getElementById('mascota_especie').value
    };

    tutores.push(nuevoTutor);
    mascotas.push(nuevaMascota);
    
    localStorage.setItem('tutores', JSON.stringify(tutores));
    localStorage.setItem('mascotas', JSON.stringify(mascotas));

    mostrarToast(`Mascota ${nuevaMascota.nombre} y su tutor ${nuevoTutor.nombre} registrados.`);
    this.reset();
    actualizarSelectMascotas();
    actualizarResumen(); // Actualiza el contador de mascotas
});

function actualizarSelectMascotas() {
    const select = document.getElementById('select-mascota');
    select.innerHTML = '<option value="">Seleccione una mascota...</option>';
    mascotas.forEach(m => {
        const tutor = tutores.find(t => t.id_tutor === m.id_tutor);
        const tutorNombre = tutor ? tutor.nombre : "Sin tutor";
        
        const option = document.createElement('option');
        option.value = m.id_mascota;
        option.textContent = `${m.nombre} (${m.especie}) - Tutor: ${tutorNombre}`;
        select.appendChild(option);
    });
}

// Agendar Cita construyendo entidad Cita según UML
document.getElementById('agenda-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const id_mascota = document.getElementById('select-mascota').value;
    const id_veterinario = document.getElementById('veterinario').value;
    const tipo = document.getElementById('cita_tipo').value;
    const fecha = document.getElementById('fecha').value;
    const hora = document.getElementById('hora').value;
    const mensajeEl = document.getElementById('mensaje-validacion');

    if (!hora || !bloquesDelDia(fecha).includes(hora)) {
        mensajeEl.textContent = "Error: Seleccione una hora dentro del horario.";
        mensajeEl.className = "mensaje error";
        return; 
    }

    if (buscarConflicto(id_veterinario, fecha, hora)) {
        mensajeEl.textContent = "Error: El veterinario ya tiene una cita agendada en ese horario.";
        mensajeEl.className = "mensaje error";
        actualizarHorasDisponibles();
        return; 
    }

    const fecha_hora_inicio = `${fecha}T${hora}`;
    const horaFinCalculada = aHora(aMinutos(hora) + DURACION_CITA);
    const fecha_hora_fin = `${fecha}T${horaFinCalculada}`;

    const nuevaCita = {
        id_cita: "CIT-" + Date.now(),
        id_mascota: id_mascota,
        id_veterinario: id_veterinario,
        fecha_hora_inicio: fecha_hora_inicio,
        fecha_hora_fin: fecha_hora_fin,
        tipo: tipo,
        estado: 'Agendada'
    };

    citas.push(nuevaCita);
    localStorage.setItem('citas', JSON.stringify(citas));

    mensajeEl.textContent = "Cita agendada con éxito.";
    mensajeEl.className = "mensaje success";
    this.reset();
    document.getElementById('fecha').value = fecha; 
    actualizarHorasDisponibles(); 

    document.getElementById('filtro-fecha').value = fecha;
    renderizarAgenda();
    actualizarResumen(); // Actualiza los contadores de citas
});

document.getElementById('btn-filtrar').addEventListener('click', renderizarAgenda);
document.getElementById('filtro-fecha').addEventListener('change', renderizarAgenda);

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

    // CORRECCIÓN: Manejo robusto para evitar que citas con formato antiguo rompan la tabla
    let citasFiltradas = [];
    
    if (fechaFiltro) {
        citasFiltradas = citas.filter(c => {
            // Verifica que la propiedad exista antes de usar startsWith
            return c.fecha_hora_inicio && c.fecha_hora_inicio.startsWith(fechaFiltro);
        });
    } else {
        // Solo mostrar citas que tengan el nuevo formato UML
        citasFiltradas = citas.filter(c => c.fecha_hora_inicio);
    }

    citasFiltradas.sort((a, b) => a.fecha_hora_inicio.localeCompare(b.fecha_hora_inicio));

    if (citasFiltradas.length === 0) {
        tbody.innerHTML = `<tr class="fila-vacia"><td colspan="5" class="vacio"><strong>Día tranquilo</strong>No hay citas agendadas para esta fecha.</td></tr>`;
        return;
    }

    citasFiltradas.forEach(cita => {
        const tr = document.createElement('tr');
        
        const veterinarioObj = VETERINARIOS[cita.id_veterinario];
        const nombreVet = veterinarioObj ? veterinarioObj.nombre : "Desconocido";
        
        const mascotaObj = mascotas.find(m => m.id_mascota === cita.id_mascota) || {};
        
        // CORRECCIÓN: Validación por si alguna cita no tiene fecha_hora_fin definida
        const horaInicioStr = cita.fecha_hora_inicio.split('T')[1] || '00:00';
        const horaFinStr = cita.fecha_hora_fin ? cita.fecha_hora_fin.split('T')[1] : '00:00';
        const horarioMuestra = `${horaInicioStr} - ${horaFinStr}`;

        const esVet2 = cita.id_veterinario === '2';
        const claseVet = esVet2 ? 'badge-vet2' : 'badge-vet1';
        tr.className = esVet2 ? 'fila-vet2' : 'fila-vet1';

        tr.appendChild(crearCelda('Horario', horarioMuestra, 'hora'));
        tr.appendChild(crearCelda('Veterinario', crearBadge(nombreVet, claseVet), 'celda-vet'));
        tr.appendChild(crearCelda('Paciente', `${mascotaObj.nombre || '?'} (${mascotaObj.especie || '?'})`, 'mascota'));
        tr.appendChild(crearCelda('Tipo', cita.tipo || 'General', 'celda-tipo'));
        tr.appendChild(crearCelda('Estado', crearBadge(cita.estado, 'badge-estado'), 'celda-estado'));
        
        tbody.appendChild(tr);
    });
}

function actualizarResumen() {
    const hoy = fechaLocalISO();
    
    // CORRECCIÓN: Contar solo citas con el formato nuevo para evitar errores
    const citasHoy = citas.filter(c => c.fecha_hora_inicio && c.fecha_hora_inicio.startsWith(hoy)).length;
    
    document.getElementById('stat-hoy').textContent = citasHoy;
    document.getElementById('stat-total').textContent = citas.length;
    document.getElementById('stat-mascotas').textContent = mascotas.length;
}

const botonesNav = document.querySelectorAll('.bottom-nav button');
function mostrarVista(vista) {
    document.body.dataset.vista = vista;
    botonesNav.forEach(boton => {
        const activo = boton.dataset.vista === vista;
        boton.classList.toggle('activo', activo);
    });
    window.scrollTo({ top: 0 });
}

botonesNav.forEach(boton => boton.addEventListener('click', () => mostrarVista(boton.dataset.vista)));
mostrarVista('registro');
