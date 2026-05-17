// Smooth scrolling for navigation links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Active nav link highlighting
window.addEventListener('scroll', function() {
    const sections = document.querySelectorAll('section[id]');
    let current = '';
    
    sections.forEach(section => {
        const sectionTop = section.offsetTop;
        if (scrollY >= sectionTop - 200) {
            current = section.getAttribute('id');
        }
    });
    
    document.querySelectorAll('.nav-link').forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href') === '#' + current) {
            link.classList.add('active');
        }
    });
});

// Add animation to elements on scroll
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver(function(entries) {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, observerOptions);

document.querySelectorAll('.project-card, .blog-card, .skill-card').forEach(el => {
    el.style.opacity = '0';
    el.style.transform = 'translateY(20px)';
    el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
    observer.observe(el);
});

            icon: '☀️'
        };
    }

    return {
        className: 'weather-panel--hot',
        title: 'Clima muito quente',
        subtitle: 'A ilustração destaca calor forte e radiação solar intensa.',
        icon: '🔥'
    };
}

function formatarHorarioAtualizado(hora) {
    if (!hora || typeof hora !== 'string') {
        return 'Atualizado em --';
    }

    const [data, tempo] = hora.split('T');
    if (!data || !tempo) {
        return `Atualizado em ${hora}`;
    }

    const [ano, mes, dia] = data.split('-');
    const [horaLocal, minuto = '00'] = tempo.split(':');
    return `Atualizado em ${dia}/${mes}/${ano} às ${horaLocal}:${minuto}`;
}

function carregarClima() {
    fetch('functions/api-clima.php')
        .then(response => response.json())
        .then(dados => {
            const current = dados.current_weather;
            let humidity = null;

            if (dados.hourly && dados.hourly.time && dados.hourly.relativehumidity_2m) {
                const idx = dados.hourly.time.indexOf(current.time);
                if (idx !== -1) {
                    humidity = dados.hourly.relativehumidity_2m[idx];
                }
            }

            const temperatura = current.temperature;
            const vento = current.windspeed || (dados.hourly && dados.hourly.windspeed_10m ? (dados.hourly.windspeed_10m[0] || '') : '');
            const hora = current.time;
            const climaVisual = getClimateVisual(Number(temperatura));
            const horarioFormatado = formatarHorarioAtualizado(hora);

            document.getElementById('clima').innerHTML = `
                <div class="weather-panel__title">Condição atual</div>
                <div class="weather-visual ${climaVisual.className}">
                    <div class="weather-visual__icon">${climaVisual.icon}</div>
                    <div class="weather-visual__text">
                        <div class="weather-visual__kicker">${climaVisual.title}</div>
                        <div class="weather-visual__caption">${climaVisual.subtitle}</div>
                    </div>
                </div>
                <div class="weather-grid">
                    <div class="weather-item">
                        <span class="weather-label">Temperatura</span>
                        <span class="weather-value weather-value--temperature">${temperatura} °C</span>
                    </div>
                    <div class="weather-item">
                        <span class="weather-label">Umidade</span>
                        <span class="weather-value">${humidity !== null ? humidity + '%' : 'N/D'}</span>
                    </div>
                    <div class="weather-item">
                        <span class="weather-label">Vento</span>
                        <span class="weather-value">${vento} km/h</span>
                    </div>
                </div>
                <div class="weather-meta">${horarioFormatado}</div>`;
        })
        .catch(err => console.error('Erro ao carregar clima:', err));
}

carregarClima();
setInterval(carregarClima, 5000);