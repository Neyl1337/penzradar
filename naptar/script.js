window.onload = () => {
    const formatáltEgyenleg = new Intl.NumberFormat('en-US', { useGrouping: true }).format(egyenleg);

    document.getElementById('perselyegyenlegText').textContent = formatáltEgyenleg;

    if (userName) {
        document.getElementById('felhasznaloNev').textContent = userName;
        document.getElementById("bejelentkezesopcio").style.display = "none";
        document.getElementById("profilopcio").style.display = "block";
        document.getElementById("beallitasopcio").style.display = "block";
        document.getElementById("kijelentkezesopcio").style.display = "block";
        document.getElementById("perselyegyenleg").style.visibility = "visible";
        document.getElementById("szerepkor").style.visibility = "visible";
        document.getElementById("statisztika").style.visibility = "visible";
    } else {
        document.getElementById("profilopcio").style.display = "none";
        document.getElementById("beallitasopcio").style.display = "none";
        document.getElementById("kijelentkezesopcio").style.display = "none";
        document.getElementById("perselyegyenleg").style.visibility = "none";
        document.getElementById("szerepkor").style.visibility = "none";
        document.getElementById("statisztika").style.visibility = "none";
    }
};



document.addEventListener('DOMContentLoaded', function () {
    const monthYear = document.getElementById('month-year');
    const daysContainer = document.getElementById('days');
    const prevButton = document.getElementById('prev');
    const nextButton = document.getElementById('next');
    const months = ['Január', 'Február','Március', 'Április', 'Május',
        'Június', 'Július', 'Augusztus', 'Szept.',
        'Október','November','December'
    ];
    let currentDate = new Date();
    let today = new Date();
    function renderCalendar(date) {
    const year = date.getFullYear();
    const month = date.getMonth();
    const firstDay = new Date(year, month, 1).getDay();
    const lastDay = new Date(year, month + 1, 0).getDate();
    monthYear.textContent = `${months[month]} ${year}`;
    daysContainer.innerHTML = '';
    
    // Current month's dates
    for (let i = 1; i <= lastDay; i++) {
    const dayDiv = document.createElement('div');
    dayDiv.textContent = i;
    if (i === today.getDate() && month === today.getMonth() && year === today.getFullYear()) {
    dayDiv.classList.add('today');
    }
    daysContainer.appendChild(dayDiv);
    }
    // Next month's dates
    const nextMonthStartDay = 7 - new Date(year, month + 1, 0).getDay() - 1;
    for (let i = 1; i <= nextMonthStartDay; i++) {
    const dayDiv = document.createElement('div');
    dayDiv.textContent = i;
    dayDiv.classList.add('fade');
    daysContainer.appendChild(dayDiv);
    }
    }
    prevButton.addEventListener('click', function () {
    currentDate.setMonth(currentDate.getMonth() - 1);
    renderCalendar(currentDate);
    });
    nextButton.addEventListener('click', function () {
    currentDate.setMonth(currentDate.getMonth() + 1);
    renderCalendar(currentDate);
    });
    renderCalendar(currentDate);
    });

    document.addEventListener('DOMContentLoaded',function() {
        const monthYear = document.getElementById('month-year');
    
        const months = ['Január', 'Február','Március', 'Április', 'Május',
            'Június', 'Július', 'Augusztus', 'Szept.',
            'Október','November','December'
        ];
    
        let currentDate = new Date();
        let today = new Date();
        
        function renderCalendar(date){
            const year = date.getFullYear();
            const month = date.getMonth();
            const firstDay = new Date(year, month, 1).getDay();
            const lastDay = new Date(year,month + 1, 0).getDate();
    
            monthYear.textContent = `${months[month]} ${year}`;
        }
    
        renderCalendar(currentDate);
    });
    
    document.addEventListener('DOMContentLoaded', function () {
        const monthYear = document.getElementById('month-year');
        const daysContainer = document.getElementById('days');
        const prevButton = document.getElementById('prev');
        const nextButton = document.getElementById('next');
        const months = ['Január', 'Február','Március', 'Április', 'Május',
            'Június', 'Július', 'Augusztus', 'Szept.',
            'Október','November','December'
        ];
        let currentDate = new Date();
        let today = new Date();
        function renderCalendar(date) {
        const year = date.getFullYear();
        const month = date.getMonth();
        const firstDay = new Date(year, month, 1).getDay();
        const lastDay = new Date(year, month + 1, 0).getDate();
        monthYear.textContent = `${months[month]} ${year}`;
        daysContainer.innerHTML = '';
        // Previous month's dates
        const prevMonthLastDay = new Date(year, month, 0).getDate();
        for (let i = firstDay; i > 0; i--) {
        const dayDiv = document.createElement('div');
        dayDiv.textContent = prevMonthLastDay - i + 1;
        dayDiv.classList.add('fade');
        daysContainer.appendChild(dayDiv);
        }
        // Current month's dates
        for (let i = 1; i <= lastDay; i++) {
        const dayDiv = document.createElement('div');
        dayDiv.textContent = i;
        if (i === today.getDate() && month === today.getMonth() && year === today.getFullYear()) {
        dayDiv.classList.add('today');
        }
        daysContainer.appendChild(dayDiv);
        }
        // Next month's dates
        const nextMonthStartDay = 7 - new Date(year, month + 1, 0).getDay() - 1;
        for (let i = 1; i <= nextMonthStartDay; i++) {
        const dayDiv = document.createElement('div');
        dayDiv.textContent = i;
        dayDiv.classList.add('fade');
        daysContainer.appendChild(dayDiv);
        }
        }
        prevButton.addEventListener('click', function () {
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderCalendar(currentDate);
        });
        nextButton.addEventListener('click', function () {
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderCalendar(currentDate);
        });
        renderCalendar(currentDate);
        });

    // Árfolyamok lekérése és frissítése
    async function frissitArfolyamok() {
        try {
            const response = await fetch('https://api.exchangerate-api.com/v4/latest/HUF');
            const data = await response.json();
            const arfolyamLista = document.getElementById('arfolyam-lista');
            const frissitesIdo = document.getElementById('frissites-ido');
    
            // Töröld a meglévő listát
            arfolyamLista.innerHTML = '';
    
            // Csak az adott árfolyamok hozzáadása a listához, ikonokkal
            const currencies = ['EUR', 'USD', 'GBP', 'CHF', 'RUB', 'RON', 'AUD', 'PLN'];
    
            currencies.forEach(currency => {
                if (data.rates[currency]) {
                    const li = document.createElement('li');
                    const arfolyam = (1 / data.rates[currency]).toFixed(2); // Árfolyam forintban
    
                    // Ikon kép hozzáadása
                    const icon = document.createElement('img');
                    icon.src = `../kepek/${currency}.png`;
                    icon.alt = `${currency} flag`;
    
                    li.appendChild(icon);
                    li.innerHTML += ` ${currency}: ${arfolyam} HUF`;
                    arfolyamLista.appendChild(li);
                }
            });
    
            // Utolsó frissítés időpontjának megjelenítése
            const lastUpdate = new Date(data.time_last_updated * 1000);
            frissitesIdo.textContent = `Utolsó frissítés: ${lastUpdate.toLocaleString()}`;
        } catch (error) {
            console.error('Hiba az árfolyamok lekérése során:', error);
        }
    }
    
    // Az árfolyamok frissítése az oldal betöltésekor és minden órában
    frissitArfolyamok();
    // setInterval(frissitArfolyamok, 3600000); // 3600000 ms = 1 óra
    setInterval(frissitArfolyamok, 300000); // 300000 ms = 5 perc