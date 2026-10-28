// Inicialización de LocalStorage
let pacientes = JSON.parse(localStorage.getItem('pacientes')) || [];
let citas = JSON.parse(localStorage.getItem('citas')) || [];

document.addEventListener('DOMContentLoaded', () => {
    actualizarSelectPacientes();
    renderizarAgenda();
    
    // Establecer fecha actual por defecto en el filtro
    const hoy = new Date().toISOString().split('T')[0];
    document.getElementById('filtro-fecha').value = hoy;
});

// Registrar Tutor y Mascota
document.getElementById('registro-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const tutor = document.getElementById('tutor').value.trim();
    const mascota = document.getElementById('mascota').value.trim();

    const nuevoPaciente = { id: Date.now(), tutor, mascota };
    pacientes.push(nuevoPaciente);
    localStorage.setItem('pacientes', JSON.stringify(pacientes));

    alert(`Paciente ${mascota} (Tutor: ${tutor}) registrado exitosamente.`);
    this.reset();
    actualizarSelectPacientes();
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

    // Regla de negocio: Verificar solapamiento (Caso de rechazo o dato inválido para EV3)
    const solapamiento = citas.find(c => c.veterinario === veterinario && c.fecha === fecha && c.hora === hora);

    if (solapamiento) {
        mensajeEl.textContent = "Error: El veterinario ya tiene una cita agendada en ese horario. Por favor, seleccione otro.";
        mensajeEl.className = "mensaje error";
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
    renderizarAgenda();
});

// Filtrar y ver la agenda diaria
document.getElementById('btn-filtrar').addEventListener('click', renderizarAgenda);

function renderizarAgenda() {
    const tbody = document.querySelector('#tabla-agenda tbody');
    const fechaFiltro = document.getElementById('filtro-fecha').value;
    tbody.innerHTML = '';

    // Consultar datos filtrando por fecha
    let citasFiltradas = citas;
    if (fechaFiltro) {
        citasFiltradas = citas.filter(c => c.fecha === fechaFiltro);
    }

    // Ordenar por hora
    citasFiltradas.sort((a, b) => a.hora.localeCompare(b.hora));

    if (citasFiltradas.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">No hay citas agendadas para esta fecha.</td></tr>';
        return;
    }

    citasFiltradas.forEach(cita => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${cita.hora}</td>
            <td>${cita.veterinario}</td>
            <td>${cita.mascota}</td>
            <td>${cita.tutor}</td>
            <td>${cita.estado}</td>
        `;
        tbody.appendChild(tr);
    });
}
