function initTour(currentPage, tourStep) {
    const tour = new Shepherd.Tour({
        useModalOverlay: true,
        defaultStepOptions: {
            cancelIcon: { enabled: false },
            classes: 'shepherd-theme-arrows',
            scrollTo: { behavior: 'smooth', block: 'center' },
            when: {
                show() {
                    document.body.classList.add('tour-active');
                },
                hide() {
                    document.body.classList.remove('tour-active');
                }
            }
        }
    });

    // PASO 0: Bienvenida (Dashboard)
    if (currentPage === 'dashboard' && tourStep === 0) {
        tour.addStep({
            id: 'welcome',
            title: 'Bienvenido a TopItUp! 🎉',
            text: 'Buenas! Esta app va de contar cosas. Lo que quieras: cubatas, días de gym, libros... lo que sea. Te cuento rápido cómo va. (6 pasitos y listo)',
            buttons: [{
                text: 'Dale, empezamos',
                action: function () {
                    window.location.href = '?page=dashboard&tour_step=1';
                }
            }]
        });
    }

    // PASO 1: Permiso de ubicación (Dashboard)
    if (currentPage === 'dashboard' && tourStep === 1) {
        tour.addStep({
            id: 'location-permission',
            title: '📍 Ubicación (opcional)',
            text: 'TopItUp puede guardar dónde haces cada +1 para futuras estadísticas y mapas molones.<br><br>El navegador te va a pedir permiso. Si quieres, acepta "Siempre permitir" y listo.',
            buttons: [{
                text: 'Entendido',
                action: function () {
                    if (navigator.geolocation) {
                        navigator.geolocation.getCurrentPosition(
                            (position) => {
                                console.log('Location permission granted:', position.coords);
                                window.location.href = '?page=dashboard&tour_step=2';
                            },
                            (error) => {
                                console.log('Location permission denied or error:', error);
                                window.location.href = '?page=dashboard&tour_step=2';
                            },
                            {
                                enableHighAccuracy: false,
                                timeout: 30000,
                                maximumAge: 0
                            }
                        );
                    } else {
                        window.location.href = '?page=dashboard&tour_step=2';
                    }
                }
            }]
        });
    }

    // PASO 2: Dashboard principal
    if (currentPage === 'dashboard' && tourStep === 2) {
        tour.addStep({
            id: 'groups-counters',
            title: '🏠 Tu Dashboard',
            text: 'Aquí verás tus <strong>grupos</strong> y <strong>contadores</strong>.<br><br>Un grupo agrupa contadores (ej: "Cubatas" con Roncola, Gintonic...). También puedes tener contadores sueltos.',
            attachTo: {
                element: '.groups-grid',
                on: 'top'
            },
            buttons: [{
                text: 'Siguiente',
                action: function () {
                    window.location.href = '?page=dashboard&tour_step=3';
                }
            }]
        });
    }

    // PASO 3: Cómo incrementar
    if (currentPage === 'dashboard' && tourStep === 3) {
        tour.addStep({
            id: 'how-to-increment',
            title: '+1 +1 +1 🔥',
            text: 'Toca cualquier tarjeta para entrar y darle caña al <strong>+1</strong>. Así de fácil.',
            attachTo: {
                element: '.group-card:first-child',
                on: 'bottom'
            },
            buttons: [{
                text: 'Siguiente',
                action: function () {
                    window.location.href = '?page=settings&tour_step=4';
                }
            }]
        });
    }

    // PASO 4: Settings - Crear contadores y grupos
    if (currentPage === 'settings' && tourStep === 4) {
        tour.addStep({
            id: 'settings-create',
            title: '⚙️ Settings',
            text: 'Aquí creas tus <strong>contadores</strong> y <strong>grupos</strong>.<br><br>Crea un grupo, y luego desde "My Groups" le añades contadores. Easy.',
            attachTo: {
                element: '.settings-section:first-child',
                on: 'bottom'
            },
            buttons: [{
                text: 'Siguiente',
                action: function () {
                    window.location.href = '?page=leaderboard&tour_step=5';
                }
            }]
        });
    }

    // PASO 5: Leaderboard
    if (currentPage === 'leaderboard' && tourStep === 5) {
        tour.addStep({
            id: 'leaderboard-intro',
            title: '🏆 Leaderboards',
            text: 'Aquí viene lo bueno. Crea <strong>leaderboards privados</strong> con código de invitación para competir con tus colegas.<br><br>Cada uno elige qué contador o grupo quiere trackear y se comparan los +1 de todos.',
            attachTo: {
                element: '.leaderboard-page',
                on: 'top'
            },
            buttons: [{
                text: 'Siguiente',
                action: function () {
                    window.location.href = '?page=dashboard&tour_step=6';
                }
            }]
        });
    }

    // PASO 6: Historial y despedida
    if (currentPage === 'dashboard' && tourStep === 6) {
        tour.addStep({
            id: 'dashboard-history',
            title: 'Y eso es todo! 🙌',
            text: 'Ahí abajo tienes el <strong>historial</strong> por si se te olvida algo entre cubata y cubata.<br><br>Ahora a darle caña. ¡Suerte compitiendo!',
            attachTo: {
                element: '.history-section',
                on: 'top'
            },
            buttons: [{
                text: 'A topar! 🚀',
                action: function () {
                    console.log('Completando tour...');

                    fetch('mark_tour_complete.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        }
                    })
                        .then(response => {
                            console.log('Respuesta recibida:', response);
                            return response.json();
                        })
                        .then(data => {
                            console.log('Tour completado:', data);

                            if (data.success) {
                                document.body.classList.remove('tour-active');
                                tour.complete();
                                setTimeout(() => {
                                    window.location.href = '?page=dashboard';
                                }, 500);
                            } else {
                                console.error('Error al completar el tour:', data);
                                window.location.href = '?page=dashboard';
                            }
                        })
                        .catch(error => {
                            console.error('Error en la petición:', error);
                            window.location.href = '?page=dashboard';
                        });
                }
            }]
        });
    }

    tour.start();
}
