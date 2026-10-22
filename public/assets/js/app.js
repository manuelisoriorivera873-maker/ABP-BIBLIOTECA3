document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-matriz-permisos]').forEach(function (formulario) {
        formulario.addEventListener('change', function (evento) {
            const casilla = evento.target;
            if (!(casilla instanceof HTMLInputElement) || casilla.type !== 'checkbox' || casilla.disabled || !casilla.dataset.modulo) {
                return;
            }
            const grupo = Array.from(formulario.querySelectorAll('input[type="checkbox"][data-modulo]'))
                .filter(input => input.dataset.modulo === casilla.dataset.modulo && !input.disabled);
            if (casilla.dataset.accion === 'ver' && !casilla.checked) {
                grupo.forEach(input => { input.checked = false; });
            } else if (casilla.checked) {
                const consulta = grupo.find(input => input.dataset.accion === 'ver');
                if (consulta) consulta.checked = true;
            }
        });
    });

    const sidebar = document.getElementById('sidebar');
    const sidebarButton = document.getElementById('sidebarButton');

    if (sidebar && sidebarButton) {
        sidebarButton.addEventListener('click', function () {
            sidebar.classList.toggle('open');
        });
    }

    document.querySelectorAll('[data-menu-toggle]').forEach(function (button) {
        button.addEventListener('click', function () {
            const target = document.getElementById(button.dataset.menuToggle);
            if (target) {
                target.classList.toggle('show');
            }
        });
    });

    document.querySelectorAll('[data-filter-select]').forEach(function (input) {
        const select = document.getElementById(input.dataset.filterSelect);
        if (!select) {
            return;
        }
        input.addEventListener('input', function () {
            const term = input.value.toLowerCase().trim();
            Array.from(select.options).forEach(function (option) {
                const matches = option.textContent.toLowerCase().includes(term);
                option.hidden = !matches && !option.selected;
            });
        });
    });

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    function parseError(data) {
        if (data && data.errors) {
            return Object.values(data.errors).flat().join(' ');
        }
        return data?.message || 'No fue posible guardar el registro.';
    }

    async function postForm(url, body) {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf,
                Accept: 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
            },
            body: new URLSearchParams(body).toString()
        });

        const data = await response.json();
        if (!response.ok) {
            throw new Error(parseError(data));
        }
        return data;
    }

    const autorButton = document.getElementById('guardarAutorRapido');
    if (autorButton) {
        autorButton.addEventListener('click', async function () {
            const error = document.getElementById('qaError');
            const persona = document.getElementById('qaPersona');
            error.textContent = '';
            autorButton.disabled = true;

            try {
                const data = await postForm(autorButton.dataset.url, {
                    id_persona: persona.value
                });

                const select = document.getElementById('autoresSelect');
                select.add(new Option(data.texto, data.id, true, true));
                persona.querySelector(`option[value="${persona.value}"]`)?.remove();

                bootstrap.Modal.getOrCreateInstance(document.getElementById('modalAutor')).hide();
                persona.value = '';
            } catch (errorRequest) {
                error.textContent = errorRequest.message;
            } finally {
                autorButton.disabled = false;
            }
        });
    }

    document.querySelectorAll('[data-quick-catalog]').forEach(function (button) {
        button.addEventListener('click', async function () {
            const input = document.getElementById(button.dataset.input);
            const select = document.getElementById(button.dataset.select);
            const error = document.getElementById(button.dataset.error);
            const modal = document.getElementById(button.dataset.modal);

            error.textContent = '';
            button.disabled = true;

            try {
                const data = await postForm(button.dataset.url, { nombre: input.value });
                select.add(new Option(data.texto, data.id, true, true));
                input.value = '';
                bootstrap.Modal.getOrCreateInstance(modal).hide();
            } catch (errorRequest) {
                error.textContent = errorRequest.message;
            } finally {
                button.disabled = false;
            }
        });
    });
});
