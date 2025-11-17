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
            title: 'Bienvenido a TopItUp v2! 🎉',
            text: 'Esta es tu página principal. Te voy a dar la chapa un poco rapido para que no te me pierdas y todo tuyo. (son 7 pasos chill)',
            buttons: [{
                text: 'Empezar Tour',
                action: function() {
                    window.location.href = '?page=dashboard&tour_step=1';
                }
            }]
        });
    }

    // PASO 1: Grupos y contadores (Dashboard)
    if (currentPage === 'dashboard' && tourStep === 1) {
        tour.addStep({
            id: 'groups-counters',
            title: 'Tus Contadores 🎯',
            text: 'Nombre - valor. Sueltos o en <strong>grupo</strong> (ej: Cubatas -> Roncola, Gintonic), simple.',
            attachTo: {
                element: '.groups-grid',
                on: 'top'
            },
            buttons: [{
                text: 'Siguiente',
                action: function() {
                    window.location.href = '?page=dashboard&tour_step=2';
                }
            }]
        });
    }

    // PASO 2: C�mo incrementar (Dashboard)
    if (currentPage === 'dashboard' && tourStep === 2) {
        tour.addStep({
            id: 'how-to-increment',
            title: 'Incrementar Contadores',
            text: 'Haciendo <strong>clic en cualquier tarjeta</strong> se abre la página de incremento. Busca el contador y revienta el +1!',
            attachTo: {
                element: '.group-card:first-child',
                on: 'bottom'
            },
            buttons: [{
                text: 'Siguiente',
                action: function() {
                    window.location.href = '?page=settings&tour_step=3';
                }
            }]
        });
    }

    // PASO 3.5: Crear contadores (Settings)
    if (currentPage === 'settings' && tourStep === 3) {
        tour.addStep({
            id: 'settings-create',
            title: '⚙️ Crear Contadores',
            text: 'Aquí se crean los contadores. No confundamos grupos por contadores 🙏. Fíjate en la opción de público, pa\' despues :)',
            attachTo: {
                element: '.settings-section:first-child',
                on: 'bottom'
            },
            buttons: [{
                text: 'Siguiente',
                action: function() {
                    window.location.href = '?page=settings&tour_step=4';
                }
            }]
        });
    }

    // PASO 3: Crear grupos (Settings)
    if (currentPage === 'settings' && tourStep === 4) {
        tour.addStep({
            id: 'settings-create',
            title: '⚙️ Crear Grupos',
            text: 'Aquí puedes crear nuevos <strong>grupos</strong>, que contendrán contadores.',
            attachTo: {
                element: '.settings-section:nth-child(2)',
                on: 'bottom'
            },
            buttons: [{
                text: 'Siguiente',
                action: function() {
                    window.location.href = '?page=leaderboard&tour_step=6';
                }
            }]
        });
    }

    // PASO 4: Gestionar grupos (Settings)
    if (currentPage === 'settings' && tourStep === 5) {
        tour.addStep({
            id: 'settings-manage',
            title: '?? Gestionar Grupos',
            text: 'Aquí iras viendo los grupos qie tengas con.',
            attachTo: {
                element: '.settings-section:nth-child(3)',
                on: 'top'
            },
            buttons: [{
                text: 'Anterior',
                action: function() {
                    window.location.href = '?page=settings&tour_step=3';
                }
            }, {
                text: 'Siguiente',
                action: function() {
                    window.location.href = '?page=leaderboard&tour_step=6';
                }
            }]
        });
    }

    // PASO 5: Leaderboard
    if (currentPage === 'leaderboard' && tourStep === 6) {
        const hasLeaderboard = document.querySelector('.leaderboard-table');
        tour.addStep({
            id: 'leaderboard-compete',
            title: '📈 Compite con Otros',
            text: 'Solo los grupos <strong>públicos</strong> aparecen aquí. Compara tus números con otros usuarios y sube en el ranking.',
            attachTo: {
                element: '.leaderboard-table',
                on: 'top'
            },
            buttons: [{
                text: 'Finalizar Tour',
                action: function() {
                    window.location.href = '?page=dashboard&tour_step=7';
                }
            }]
        });
    }

    // PASO 6: Historial final (Dashboard)
    if (currentPage === 'dashboard' && tourStep === 7) {
        tour.addStep({
            id: 'dashboard-history',
            title: 'Historial de Actividad',
            text: 'A veces, entre cubata y cubata, se te olvida lo que has hecho. Aquí tienes un resumen de tus últimas acciones. De nada.',
            attachTo: {
                element: '.history-section',
                on: 'top'
            },
            buttons: [{
                text: 'Empezar a contar!',
                action: function() {
                    console.log('Intentando completar tour...');
                    
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
                        console.log('Datos del tour:', data);
                        
                        if (data.success) {
                            document.body.classList.remove('tour-active');
                            tour.complete();
                            setTimeout(() => {
                                window.location.href = '?page=dashboard';
                            }, 500);
                        } else {
                            console.error('Error al completar el tour:', data);
                            alert('Error al completar el tour. Recargando p�gina...');
                            window.location.href = '?page=dashboard';
                        }
                    })
                    .catch(error => {
                        console.error('Error en la petición:', error);
                        alert('Error de conexión. Recargando página...');
                        window.location.href = '?page=dashboard';
                    });
                }
            }]
        });
    }

    tour.start();
}
