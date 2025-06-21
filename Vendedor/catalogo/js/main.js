
// Mostrar piezas en piezas.html
function verPiezas(modelo_id) {
  fetch(`app.php?accion=get_piezas&modelo_id=${modelo_id}`)
    .then(res => res.json())
    .then(piezas => {
      const container = document.getElementById("piezasContainer");
      if (piezas.length === 0) {
        container.innerHTML = "<p>No hay piezas registradas para este modelo.</p>";
        return;
      }

      piezas.forEach(pieza => {
        const div = document.createElement("div");
        div.className = "pieza";
        div.innerHTML = `
          <strong>${pieza.nombre}</strong> (${pieza.categoria})
          <p>${pieza.descripcion}</p>
          <img src="${pieza.imagen}" width="100" />
        `;
        container.appendChild(div);
      });
    });
}

// Filtrar marcas al buscar
function filtrarMarcas() {
  const filtro = document.getElementById("searchMarca").value.toLowerCase();
  const botones = document.querySelectorAll("#marcasContainer button");
  botones.forEach(btn => {
    const texto = btn.textContent.toLowerCase();
    btn.style.display = texto.includes(filtro) ? "block" : "none";
  });
}

// Cargar funciones según página
document.addEventListener("DOMContentLoaded", () => {
  if (document.body.contains(document.getElementById("marcasContainer"))) {
    cargarMarcas();
  }

  if (document.body.contains(document.getElementById("selectMarca"))) {
    cargarMarcasRegistro();
    cargarCategorias();
    cargarMarcasEnFormulario();
    cargarModelosEnFormulario();
  }
});

// Registro: Cargar marcas
function cargarMarcasRegistro() {
  fetch("app.php?accion=get_marcas")
    .then(res => res.json())
    .then(marcas => {
      const select = document.getElementById("selectMarca");
      select.innerHTML = "<option value=''>-- Selecciona una marca --</option>";
      marcas.forEach(marca => {
        const option = document.createElement("option");
        option.value = marca.id;
        option.textContent = marca.nombre;
        select.appendChild(option);
      });
    });
}

// Registro: Cargar modelos por marca
function cargarModelos() {
  const marcaId = document.getElementById("selectMarca").value;
  const container = document.getElementById("modelosContainer");
  if (!marcaId) return;

  fetch(`app.php?accion=get_modelos&marca_id=${marcaId}`)
    .then(res => res.json())
    .then(modelos => {
      container.innerHTML = "<h3>Modelos:</h3>";
      modelos.forEach(modelo => {
        const div = document.createElement("div");
        div.className = "modelo";
        div.innerHTML = `
          <strong>${modelo.nombre}</strong> (${modelo.anio_inicio}-${modelo.anio_fin}) - ${modelo.motor}
          <button onclick="verPiezas(${modelo.id})">Ver Piezas</button>
        `;
        container.appendChild(div);
      });
    });
}

// Registro: Ver piezas del modelo
function verPiezas(modeloId) {
  const container = document.getElementById("piezasContainer");
  fetch(`app.php?accion=get_piezas&modelo_id=${modeloId}`)
    .then(res => res.json())
    .then(piezas => {
      container.innerHTML = "<h3>Piezas:</h3>";
      piezas.forEach(pieza => {
        const div = document.createElement("div");
        div.className = "pieza";
        div.innerHTML = `
          <strong>${pieza.nombre}</strong> (${pieza.categoria})
          <p>${pieza.descripcion}</p>
          <img src="${pieza.imagen}" width="100" />
          <button onclick="editarPieza(${pieza.id})">Editar</button>
          <button onclick="eliminarPieza(${pieza.id})">Eliminar</button>
        `;
        container.appendChild(div);
      });
    });
}

// Registro: Agregar Marca
document.getElementById("formMarca").addEventListener("submit", e => {
  e.preventDefault();
  const nombre = document.getElementById("nombreMarca").value;

  fetch("app.php?accion=add_marca", {
    method: "POST",
    body: JSON.stringify({ nombre }),
    headers: { "Content-Type": "application/json" }
  }).then(() => {
    alert("Marca agregada");
    cargarMarcasRegistro();
    cargarMarcasEnFormulario();
  });
});

// Registro: Cargar marcas en formulario de modelos
function cargarMarcasEnFormulario() {
  fetch("app.php?accion=get_marcas")
    .then(res => res.json())
    .then(marcas => {
      const select = document.getElementById("marcaParaModelo");
      select.innerHTML = "";
      marcas.forEach(marca => {
        const option = document.createElement("option");
        option.value = marca.id;
        option.textContent = marca.nombre;
        select.appendChild(option);
      });
    });
}

// Registro: Agregar Modelo
document.getElementById("formModelo").addEventListener("submit", e => {
  e.preventDefault();
  const marca_id = document.getElementById("marcaParaModelo").value;
  const nombre = document.getElementById("nombreModelo").value;
  const anio_inicio = document.getElementById("anioInicio").value;
  const anio_fin = document.getElementById("anioFin").value;
  const motor = document.getElementById("motorModelo").value;

  fetch("app.php?accion=add_modelo", {
    method: "POST",
    body: JSON.stringify({ marca_id, nombre, anio_inicio, anio_fin, motor }),
    headers: { "Content-Type": "application/json" }
  }).then(() => {
    alert("Modelo agregado");
    cargarModelos();
    cargarModelosEnFormulario();
  });
});

// Registro: Cargar categorías
function cargarCategorias() {
  fetch("app.php?accion=get_categorias")
    .then(res => res.json())
    .then(categorias => {
      const select = document.getElementById("categoriaPieza");
      select.innerHTML = "";
      categorias.forEach(cat => {
        const option = document.createElement("option");
        option.value = cat.id;
        option.textContent = cat.nombre;
        select.appendChild(option);
      });
    });
}

// Registro: Cargar todos los modelos para el select de piezas
function cargarModelosEnFormulario() {
  fetch("app.php?accion=get_modelos_all")
    .then(res => res.json())
    .then(modelos => {
      const select = document.getElementById("modeloParaPieza");
      select.innerHTML = "";
      modelos.forEach(modelo => {
        const option = document.createElement("option");
        option.value = modelo.id;
        option.textContent = `${modelo.nombre} (${modelo.anio_inicio}-${modelo.anio_fin})`;
        select.appendChild(option);
      });
    });
}

// Registro: Agregar Pieza
document.getElementById("formPieza").addEventListener("submit", e => {
  e.preventDefault();
  const modelo_id = document.getElementById("modeloParaPieza").value;
  const categoria_id = document.getElementById("categoriaPieza").value;
  const nombre = document.getElementById("nombrePieza").value;
  const descripcion = document.getElementById("descripcionPieza").value;
  const imagen = document.getElementById("imagenPieza").value;

  fetch("app.php?accion=add_pieza", {
    method: "POST",
    body: JSON.stringify({ modelo_id, categoria_id, nombre, descripcion, imagen }),
    headers: { "Content-Type": "application/json" }
  }).then(() => {
    alert("Pieza agregada");
    verPiezas(modelo_id);
  });
});

// Registro: Eliminar Pieza
function eliminarPieza(id) {
  if (confirm("¿Eliminar esta pieza?")) {
    fetch(`app.php?accion=delete_pieza&id=${id}`, { method: "GET" })
      .then(() => location.reload());
  }
}

// Registro: Editar Pieza
function editarPieza(id) {
  const nuevaDescripcion = prompt("Editar descripción:");
  if (nuevaDescripcion !== null) {
    fetch("app.php?accion=edit_pieza", {
      method: "POST",
      body: JSON.stringify({ id, descripcion: nuevaDescripcion }),
      headers: { "Content-Type": "application/json" }
    }).then(() => location.reload());
  }
}