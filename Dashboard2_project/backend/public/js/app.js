// public/js/app.js

// Estado global
let currentSection = 'clientes';
let currentFiltroPedidos = 'todos';

// Elementos del DOM
const sections = document.querySelectorAll('.section');
const navButtons = document.querySelectorAll('.nav-btn');
const tablaClientes = document.querySelector('#tabla-clientes tbody');
const tablaPedidos = document.querySelector('#tabla-pedidos tbody');
const tablaProductos = document.querySelector('#tabla-productos tbody');
const filtrosPedidos = document.querySelectorAll('.filtro-btn');
const btnNuevoProducto = document.getElementById('btn-nuevo-producto');
const modal = document.getElementById('modal-producto');
const modalTitulo = document.getElementById('modal-titulo');
const formProducto = document.getElementById('form-producto');
const closeModal = document.querySelector('.close');
const inputId = document.getElementById('producto-id');
const inputNombre = document.getElementById('nombre');
const inputPrecio = document.getElementById('precio');
const inputStock = document.getElementById('stock');

// Inicialización
document.addEventListener('DOMContentLoaded', () => {
    cargarClientes();
    cargarPedidos();
    cargarProductos();
    setupEventListeners();
});

// Configurar event listeners
function setupEventListeners() {
    // Navegación por pestañas
    navButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const section = btn.dataset.section;
            cambiarSeccion(section);
        });
    });

    // Filtros de pedidos
    filtrosPedidos.forEach(btn => {
        btn.addEventListener('click', () => {
            filtrosPedidos.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentFiltroPedidos = btn.dataset.estado;
            cargarPedidos(currentFiltroPedidos);
        });
    });

    // Nuevo producto (abrir modal)
    btnNuevoProducto.addEventListener('click', () => {
        abrirModal(null);
    });

    // Cerrar modal
    closeModal.addEventListener('click', () => {
        modal.style.display = 'none';
    });

    window.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });

    // Envío del formulario de producto
    formProducto.addEventListener('submit', guardarProducto);
}

// Cambiar sección activa
function cambiarSeccion(sectionId) {
    currentSection = sectionId;
    sections.forEach(s => s.classList.remove('active'));
    document.getElementById(sectionId).classList.add('active');
    navButtons.forEach(btn => {
        btn.classList.toggle('active', btn.dataset.section === sectionId);
    });
    // Actualizar datos según sección (opcional)
    if (sectionId === 'clientes') cargarClientes();
    if (sectionId === 'pedidos') cargarPedidos(currentFiltroPedidos);
    if (sectionId === 'productos') cargarProductos();
}

// ---------- CLIENTES ----------
function cargarClientes() {
    fetch('http://localhost/backoffice/api/clientes.php')
        .then(response => response.json())
        .then(data => {
            tablaClientes.innerHTML = '';
            data.forEach(cliente => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${cliente.id}</td>
                    <td>${cliente.nombre}</td>
                    <td>${cliente.email}</td>
                `;
                tablaClientes.appendChild(tr);
            });
        })
        .catch(error => console.error('Error al cargar clientes:', error));
}

// ---------- PEDIDOS ----------
function cargarPedidos(estado = 'todos') {
    let url = 'http://localhost/backoffice/api/pedidos.php';
    if (estado !== 'todos') {
        url += `?estado=${estado}`;
    }
    fetch(url)
        .then(response => response.json())
        .then(data => {
            tablaPedidos.innerHTML = '';
            data.forEach(pedido => {
                const tr = document.createElement('tr');
                // Determinar clase de estado para el badge
                let estadoClass = '';
                let estadoTexto = pedido.estado;
                if (pedido.estado === 'pendiente') estadoClass = 'badge-pendiente';
                else if (pedido.estado === 'en_camino') estadoClass = 'badge-camino';
                else if (pedido.estado === 'entregado') estadoClass = 'badge-entregado';

                tr.innerHTML = `
                    <td>${pedido.id}</td>
                    <td>${pedido.cliente_nombre}</td>
                    <td>${new Date(pedido.fecha).toLocaleDateString()}</td>
                    <td><span class="badge ${estadoClass}">${pedido.estado}</span></td>
                    <td>${pedido.productos || 'Sin productos'}</td>
                `;
                tablaPedidos.appendChild(tr);
            });
            // Reemplazar iconos Feather si hay (no necesario aquí)
        })
        .catch(error => console.error('Error al cargar pedidos:', error));
}

// ---------- PRODUCTOS ----------
function cargarProductos() {
    fetch('http://localhost/backoffice/api/productos.php')
        .then(response => response.json())
        .then(data => {
            tablaProductos.innerHTML = '';
            data.forEach(prod => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${prod.id}</td>
                    <td>${prod.nombre}</td>
                    <td>${prod.precio} €</td>
                    <td>${prod.stock}</td>
                    <td>
                        <button class="btn-secondary editar-producto" data-id="${prod.id}"><i data-feather="edit-2"></i></button>
                        <button class="btn-secondary eliminar-producto" data-id="${prod.id}"><i data-feather="trash-2"></i></button>
                    </td>
                `;
                tablaProductos.appendChild(tr);
            });
            // Asignar eventos a botones de editar/eliminar
            document.querySelectorAll('.editar-producto').forEach(btn => {
                btn.addEventListener('click', () => editarProducto(btn.dataset.id));
            });
            document.querySelectorAll('.eliminar-producto').forEach(btn => {
                btn.addEventListener('click', () => eliminarProducto(btn.dataset.id));
            });
            feather.replace(); // actualizar iconos dentro de la tabla
        })
        .catch(error => console.error('Error al cargar productos:', error));
}

// Abrir modal para crear o editar producto
function abrirModal(producto = null) {
    if (producto) {
        modalTitulo.textContent = 'Editar producto';
        inputId.value = producto.id;
        inputNombre.value = producto.nombre;
        inputPrecio.value = producto.precio;
        inputStock.value = producto.stock;
    } else {
        modalTitulo.textContent = 'Nuevo producto';
        inputId.value = '';
        formProducto.reset();
    }
    modal.style.display = 'flex';
}

function editarProducto(id) {
    fetch(`http://localhost/backoffice/api/productos.php?id=${id}`)
        .then(response => response.json())
        .then(producto => {
            abrirModal(producto);
        })
        .catch(error => console.error('Error al obtener producto:', error));
}

function guardarProducto(e) {
    e.preventDefault();

    const id = inputId.value;
    const producto = {
        nombre: inputNombre.value,
        precio: parseFloat(inputPrecio.value),
        stock: parseInt(inputStock.value)
    };

    let url = 'http://localhost/backoffice/api/productos.php';
    let method = 'POST';
    if (id) {
        url += `?id=${id}`;
        method = 'PUT';
    }

    fetch(url, {
        method: method,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(producto)
    })
        .then(response => response.json())
        .then(data => {
            modal.style.display = 'none';
            cargarProductos(); // recargar lista
        })
        .catch(error => console.error('Error al guardar producto:', error));
}

function eliminarProducto(id) {
    if (confirm('¿Eliminar producto?')) {
        fetch(`http://localhost/backoffice/api/productos.php?id=${id}`, {
            method: 'DELETE'
        })
            .then(response => response.json())
            .then(data => {
                cargarProductos();
            })
            .catch(error => console.error('Error al eliminar producto:', error));
    }
}