// Tour translations
const tourText = {
    en: {
        step0: {
            title: 'Welcome to TopItUp! 🎉',
            text: 'Hey there! This app is all about counting stuff. Whatever you want: beers, gym days, books... you name it. Let me quickly show you how it works. (6 steps and done)',
            button: 'Let\'s go!'
        },
        step1: {
            title: '📍 Location (optional)',
            text: 'TopItUp can save where you make each +1 for future stats and cool maps.<br><br>Your browser will ask for permission. If you want, accept "Always allow" and you\'re set.',
            button: 'Got it'
        },
        step2: {
            title: '🏠 Your Dashboard',
            text: 'Here you\'ll see your <strong>groups</strong> and <strong>counters</strong>.<br><br>A group bundles counters (e.g. "Drinks" with Beer, Wine...). You can also have standalone counters.',
            button: 'Next'
        },
        step3: {
            title: '+1 +1 +1 🔥',
            text: 'Tap any card to go in and hit that <strong>+1</strong> button. That\'s it!',
            button: 'Next'
        },
        step4: {
            title: '⚙️ Settings',
            text: 'Here you create your <strong>counters</strong> and <strong>groups</strong>.<br><br>Create a group, then from "My Groups" add counters to it. Easy peasy.',
            button: 'Next'
        },
        step5: {
            title: '🏆 Leaderboards',
            text: 'Now for the fun part. Create <strong>private leaderboards</strong> with an invite code to compete with your friends.<br><br>Everyone picks which counter or group they want to track and compare everyone\'s +1s.',
            button: 'Next'
        },
        step6: {
            title: 'That\'s it! 🙌',
            text: 'Down there you\'ve got your <strong>history</strong> in case you forget stuff between beers.<br><br>Now go wild. Good luck competing!',
            button: 'Let\'s roll! 🚀'
        }
    },
    es: {
        step0: {
            title: 'Bienvenido a TopItUp! 🎉',
            text: 'Buenas! Esta app va de contar cosas. Lo que quieras: cubatas, días de gym, libros... lo que sea. Te cuento rápido cómo va. (6 pasitos y listo)',
            button: 'Dale, empezamos'
        },
        step1: {
            title: '📍 Ubicación (opcional)',
            text: 'TopItUp puede guardar dónde haces cada +1 para futuras estadísticas y mapas molones.<br><br>El navegador te va a pedir permiso. Si quieres, acepta "Siempre  permitir" y listo.',
            button: 'Entendido'
        },
        step2: {
            title: '🏠 Tu Dashboard',
            text: 'Aquí verás tus <strong>grupos</strong> y <strong>contadores</strong>.<br><br>Un grupo agrupa contadores (ej: "Cubatas" con Roncola, Gintonic...). También puedes tener contadores sueltos.',
            button: 'Siguiente'
        },
        step3: {
            title: '+1 +1 +1 🔥',
            text: 'Toca cualquier tarjeta para entrar y darle caña al <strong>+1</strong>. Así de fácil.',
            button: 'Siguiente'
        },
        step4: {
            title: '⚙️ Settings',
            text: 'Aquí creas tus <strong>contadores</strong> y <strong>grupos</strong>.<br><br>Crea un grupo, y luego desde "My Groups" le añades contadores. Easy.',
            button: 'Siguiente'
        },
        step5: {
            title: '🏆 Leaderboards',
            text: 'Aquí viene lo bueno. Crea <strong>leaderboards privados</strong> con código de invitación para competir con tus colegas.<br><br>Cada uno elige qué contador o grupo quiere trackear y se comparan los +1 de todos.',
            button: 'Siguiente'
        },
        step6: {
            title: 'Y eso es todo! 🙌',
            text: 'Ahí abajo tienes el <strong>historial</strong> por si se te olvida algo entre cubata y cubata.<br><br>Ahora a darle caña. ¡Suerte compitiendo!',
            button: 'A topar! 🚀'
        }
    }
};

function initTour(currentPage, tourStep) {
    // Get current language from cookie
    const cookieLang = document.cookie.split('; ').find(row => row.startsWith('user_lang='));
    const lang = cookieLang ? cookieLang.split('=')[1] : 'en';
    const text = tourText[lang] || tourText.en;

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
            title: text.step0.title,
            text: text.step0.text,
            buttons: [{
                text: text.step0.button,
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
            title: text.step1.title,
            text: text.step1.text,
            buttons: [{
                text: text.step1.button,
                action: function () {
                    // Trigger geolocation request immediately on button click
                    if (navigator.geolocation) {
                        console.log('Requesting location permission...');
                        navigator.geolocation.getCurrentPosition(
                            (position) => {
                                console.log('Location permission granted:', position.coords);
                                setTimeout(() => {
                                    window.location.href = '?page=dashboard&tour_step=2';
                                }, 300);
                            },
                            (error) => {
                                console.log('Location permission denied or error:', error);
                                setTimeout(() => {
                                    window.location.href = '?page=dashboard&tour_step=2';
                                }, 300);
                            },
                            {
                                enableHighAccuracy: true,
                                timeout: 20000,
                                maximumAge: 0
                            }
                        );
                    } else {
                        console.log('Geolocation not supported');
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
            title: text.step2.title,
            text: text.step2.text,
            attachTo: {
                element: '.groups-grid',
                on: 'top'
            },
            buttons: [{
                text: text.step2.button,
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
            title: text.step3.title,
            text: text.step3.text,
            attachTo: {
                element: '.group-card:first-child',
                on: 'bottom'
            },
            buttons: [{
                text: text.step3.button,
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
            title: text.step4.title,
            text: text.step4.text,
            attachTo: {
                element: '.settings-section:first-child',
                on: 'bottom'
            },
            buttons: [{
                text: text.step4.button,
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
            title: text.step5.title,
            text: text.step5.text,
            attachTo: {
                element: '.leaderboard-page',
                on: 'top'
            },
            buttons: [{
                text: text.step5.button,
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
            title: text.step6.title,
            text: text.step6.text,
            attachTo: {
                element: '.history-section',
                on: 'top'
            },
            buttons: [{
                text: text.step6.button,
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
